<?php

namespace App\Http\Controllers\Api;

use App\Events\PaymentStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DokuWebhookController extends Controller
{
    public function webhook(Request $request, BookingNotificationService $notifications): Response
    {
        $headers = $request->headers;
        $payload = $request->all();
        $rawBody = $request->getContent();

        try {
            // 1. Ambil Header dari DOKU (Mendukung standard DOKU Checkout & SNAP)
            $clientId = $request->header('Client-Id') ?? $request->header('X-PARTNER-ID');
            $requestId = $request->header('Request-Id') ?? $request->header('X-EXTERNAL-ID');
            $requestTimestamp = $request->header('Request-Timestamp') ?? $request->header('X-TIMESTAMP');
            $incomingSignature = $request->header('Signature') ?? $request->header('X-SIGNATURE');

            $secretKey = config('services.doku.secret_key');

            $endpointPath = $request->getPathInfo(); 

            $digest = base64_encode(hash('sha256', $rawBody, true));

            $stringToSign = "Client-Id:" . $clientId . "\n" .
                            "Request-Id:" . $requestId . "\n" .
                            "Request-Timestamp:" . $requestTimestamp . "\n" .
                            "Request-Target:" . $endpointPath . "\n" .
                            "Digest:" . $digest;

            $calculatedSignature = 'HMACSHA256=' . base64_encode(
                hash_hmac('sha256', $stringToSign, (string) $secretKey, true)
            );

            if (! $secretKey || ! $incomingSignature || ! hash_equals($calculatedSignature, (string) $incomingSignature)) {
                Log::error('DOKU Webhook Signature Invalid', [
                    'expected'      => $calculatedSignature,
                    'received'      => $incomingSignature,
                    'endpoint_path' => $endpointPath,
                    'headers'       => $request->headers->all(),
                    'payload'       => $payload,
                ]);

                return response()->json(['message' => 'Invalid Signature'], 401);
            }

            // 3. Extract Data Pembayaran dari Payload (Mendukung DOKU Checkout & SNAP QRIS)
            $reference = data_get($payload, 'order.invoice_number')
                ?? data_get($payload, 'originalPartnerReferenceNo')
                ?? data_get($payload, 'partnerReferenceNo')
                ?? data_get($payload, 'order.partner_reference_no')
                ?? data_get($payload, 'order.partnerReferenceNo')
                ?? data_get($payload, 'transaction.originalPartnerReferenceNo');

            $transactionStatus = data_get($payload, 'transaction.status')
                ?? data_get($payload, 'latestTransactionStatus')
                ?? data_get($payload, 'transactionStatusDesc')
                ?? data_get($payload, 'transaction.statusDescription')
                ?? data_get($payload, 'status');

            $paymentSource = data_get($payload, 'issuer.name')
                ?? data_get($payload, 'additionalInfo.channel')
                ?? data_get($payload, 'paymentMethod');

            if (! $reference) {
                Log::error('DOKU webhook reference missing.', [
                    'payload_keys' => array_keys($payload),
                    'payload'      => $payload,
                ]);

                return response()->json(['message' => 'Reference is required.'], 422);
            }

            // 4. Update Database Aplikasi
            $payments = Payment::where('provider', 'doku')
                ->where(function ($query) use ($reference): void {
                    $query->where('partner_reference_no', $reference)
                        ->orWhere('doku_reference_no', $reference)
                        ->orWhereJsonContains('provider_payload->partnerReferenceNo', $reference);
                })
                ->get();

            if ($payments->isEmpty()) {
                Log::error('DOKU webhook payment not found.', [
                    'reference' => $reference,
                    'payload'   => $payload,
                ]);

                return response()->json(['message' => 'Order Not Found'], 404);
            }

            $normalizedStatus = strtoupper((string) $transactionStatus);
            $isPaid = in_array($normalizedStatus, ['SUCCESS', 'PAID', 'SETTLED', '00'], true);
            $isFailed = in_array($normalizedStatus, ['FAILED', 'EXPIRED', 'CANCELLED', 'CANCELED', '05', '06'], true);

            $bookingId = null;
            $shouldNotify = false;

            if ($isPaid || $isFailed) {
                $bookingId = DB::transaction(function () use ($payments, $paymentSource, $payload, $isPaid, &$shouldNotify): ?int {
                    $firstBookingId = null;

                    foreach ($payments as $payment) {
                        $lockedPayment = Payment::query()->lockForUpdate()->find($payment->id);
                        if (! $lockedPayment) {
                            continue;
                        }

                        $wasPaid = $lockedPayment->status === 'paid';

                        $dokuReference = data_get($payload, 'referenceNo')
                            ?? $lockedPayment->doku_reference_no;

                        $lockedPayment->update([
                            'status'            => $isPaid ? 'paid' : 'failed',
                            'doku_reference_no' => $dokuReference,
                            'provider_payload'  => $payload,
                            'payment_source'    => $paymentSource,
                        ]);

                        $bookings = $lockedPayment->bookings()->lockForUpdate()->get();

                        foreach ($bookings as $booking) {
                            if (! $firstBookingId) {
                                $firstBookingId = $booking->id;
                            }

                            if ($isPaid) {
                                $bookingCount = max(1, $bookings->count());
                                $isDp = $lockedPayment->purpose === 'dp';
                                $dpPerBooking = $isDp ? (int) round($lockedPayment->amount / $bookingCount) : 0;
                                $outstanding = $isDp ? max(0, $booking->total_amount - $dpPerBooking) : 0;

                                $booking->update([
                                    'outstanding_amount' => $outstanding,
                                    'status'             => 'confirmed',
                                ]);
                            } else {
                                $booking->update([
                                    'status' => 'cancelled',
                                ]);

                                // Kalau pembayaran gagal/kedaluwarsa, lepas kembali slot jadwal
                                if ($booking->schedule) {
                                    $booking->schedule->update(['is_available' => true]);
                                }
                            }
                        }

                        if ($isPaid && ! $wasPaid) {
                            $shouldNotify = true;
                        }
                    }

                    return $firstBookingId;
                });
            }

            Log::info('DOKU Webhook Processed', [
                'reference' => $reference,
                'transaction_status' => $transactionStatus,
                'normalized_status' => $normalizedStatus,
                'is_paid' => $isPaid,
                'is_failed' => $isFailed,
                'booking_id' => $bookingId,
                'should_notify' => $shouldNotify,
            ]);

            if ($isPaid || $isFailed) {
                PaymentStatusUpdated::dispatch(
                    (string) $reference,
                    $isPaid ? 'paid' : 'failed',
                    $rawBody
                );
            }

            if ($shouldNotify && $bookingId) {
                $booking = Booking::find($bookingId);
                if ($booking) {
                    $notifications->paymentSucceeded(
                        $booking,
                        (string) $reference,
                        route('booking.payment.detail', ['reference' => $reference])
                    );

                    if ($booking->scheduled_at) {
                        $notifications->sendDailyRecapToOpsGroup($booking->scheduled_at);
                    }
                }
            }

            // 5. Response HTTP 200 OK ke DOKU
            // DOKU SNAP mengharapkan response JSON SNAP, sedangkan DOKU Direct API mengharapkan string 'CONTINUE'
            $isSnap = $request->hasHeader('X-SIGNATURE') || data_get($payload, 'originalPartnerReferenceNo');
            if ($isSnap) {
                return response()->json([
                    'responseCode'    => '2005200',
                    'responseMessage' => 'Successful',
                ], 200);
            }

            return response('CONTINUE', 200)->header('Content-Type', 'text/plain');
        } catch (\Throwable $e) {
            Log::error('DOKU Webhook Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'payload'   => $payload,
                'headers'   => $headers->all(),
            ]);
            report($e);

            throw $e;
        }
    }
}