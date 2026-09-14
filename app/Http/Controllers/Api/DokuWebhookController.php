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
use Illuminate\Http\Response;

class DokuWebhookController extends Controller
{
    public function webhook(Request $request, BookingNotificationService $notifications): Response
    {
        $headers = $request->headers;
        $payload = $request->all();
        $rawBody = $request->getContent();

        // 1. Ambil Header dari DOKU
        $clientId = $request->header('Client-Id');
        $requestId = $request->header('Request-Id');
        $requestTimestamp = $request->header('Request-Timestamp');
        $incomingSignature = $request->header('Signature');

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
            Log::warning('DOKU Webhook Signature Invalid', [
                'expected'      => $calculatedSignature,
                'received'      => $incomingSignature,
                'endpoint_path' => $endpointPath, // sementara, untuk cek apakah path ini penyebab mismatch
            ]);

            return response()->json(['message' => 'Invalid Signature'], 401);
        }

        // 3. Extract Data Pembayaran dari Payload
        $reference = data_get($payload, 'order.invoice_number');
        $transactionStatus = data_get($payload, 'transaction.status');
        $paymentSource = data_get($payload, 'issuer.name');

        if (! $reference) {
            Log::warning('DOKU webhook reference missing.', [
                'payload_keys' => array_keys($payload),
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
            Log::error('DOKU webhook payment not found.', ['reference' => $reference]);

            return response()->json(['message' => 'Order Not Found'], 404);
        }

        $normalizedStatus = strtoupper((string) $transactionStatus);
        $isPaid = in_array($normalizedStatus, ['SUCCESS', 'PAID', 'SETTLED'], true);
        $isFailed = in_array($normalizedStatus, ['FAILED', 'EXPIRED', 'CANCELLED', 'CANCELED'], true);

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

                    $lockedPayment->update([
                        'status'           => $isPaid ? 'paid' : 'failed',
                        'provider_payload' => $payload,
                        'payment_source'   => $paymentSource,
                    ]);

                    $bookings = $lockedPayment->bookings()->lockForUpdate()->get();

                    foreach ($bookings as $booking) {
                        if (! $firstBookingId) {
                            $firstBookingId = $booking->id;
                        }

                        if ($isPaid) {
                            $booking->update([
                                'outstanding_amount' => max(0, $booking->total_amount - (int) $lockedPayment->amount),
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
            }
        }

        // 5. Response HTTP 200 OK ke DOKU
        return response('CONTINUE', 200)->header('Content-Type', 'text/plain');
    }
}