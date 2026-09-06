<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DokuWebhookController extends Controller
{
    public function webhook(Request $request, BookingNotificationService $notifications): JsonResponse
    {
        $headers = $request->headers;
        $payload = $request->all();
        $rawBody = $request->getContent();

        // 1. Ambil Header dari DOKU
        $clientId = $request->header('Client-Id');
        $requestId = $request->header('Request-Id');
        $requestTimestamp = $request->header('Request-Timestamp');
        $incomingSignature = $request->header('Signature');

        Log::info('DOKU Webhook Received', [
            'headers' => $headers->all(),
            'body' => $payload,
        ]);

        // 2. Verifikasi Signature (HMAC-SHA256)
        $secretKey = config('services.doku.webhook_secret');

        // PENTING: path ini HARUS sama dengan path Notification URL yang
        // benar-benar terdaftar di DOKU Back Office (bukan path bawaan DOKU).
        // Sesuai route:list project ini, path-nya adalah /api/payments/webhook.
        $endpointPath = '/api/payments/webhook';

        // Digest = Base64(SHA256(RawBody))
        $digest = base64_encode(hash('sha256', $rawBody, true));

        // Susun Component String to Sign
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
        $reference = data_get($payload, 'order.invoice_number')
            ?? data_get($payload, 'order.partner_reference_no')
            ?? data_get($payload, 'order.partnerReferenceNo')
            ?? data_get($payload, 'partnerReferenceNo')
            ?? data_get($payload, 'originalPartnerReferenceNo')
            ?? data_get($payload, 'transaction.originalPartnerReferenceNo');
        $transactionStatus = data_get($payload, 'transaction.status')
            ?? data_get($payload, 'transactionStatusDesc')
            ?? data_get($payload, 'transaction.statusDescription')
            ?? data_get($payload, 'status');

        if (! $reference) {
            Log::warning('DOKU webhook reference missing.', [
                'payload_keys' => array_keys($payload),
            ]);

            return response()->json(['message' => 'Reference is required.'], 422);
        }

        // 4. Update Database Aplikasi
        $payment = Payment::where('provider', 'doku')
            ->where(function ($query) use ($reference): void {
                $query->where('partner_reference_no', $reference)
                    ->orWhere('doku_reference_no', $reference)
                    ->orWhereJsonContains('provider_payload->partnerReferenceNo', $reference);
            })
            ->first();

        if (! $payment) {
            Log::error('DOKU webhook payment not found.', ['reference' => $reference]);

            return response()->json(['message' => 'Order Not Found'], 404);
        }

        $normalizedStatus = strtoupper((string) $transactionStatus);
        $isPaid = in_array($normalizedStatus, ['SUCCESS', 'PAID', 'SETTLED'], true);
        $isFailed = in_array($normalizedStatus, ['FAILED', 'EXPIRED', 'CANCELLED', 'CANCELED'], true);

        $bookingId = null;
        $shouldNotify = false;

        if ($isPaid || $isFailed) {
            $bookingId = DB::transaction(function () use ($payment, $payload, $isPaid, &$shouldNotify): ?int {
                $lockedPayment = Payment::query()->lockForUpdate()->find($payment->id);
                if (! $lockedPayment) {
                    return null;
                }

                $wasPaid = $lockedPayment->status === 'paid';

                $lockedPayment->update([
                    'status' => $isPaid ? 'paid' : 'failed',
                    'provider_payload' => $payload,
                ]);

                $booking = $lockedPayment->booking()->lockForUpdate()->first();
                if (! $booking) {
                    return null;
                }

                $paid = $booking->payments()->where('status', 'paid')->sum('amount');

                $booking->update([
                    'payment_status' => $paid >= $booking->total_amount
                        ? 'paid_full'
                        : ($paid > 0 ? 'partial' : 'unpaid'),
                    'outstanding_amount' => max(0, $booking->total_amount - $paid),
                    'status' => $paid >= $booking->total_amount
                        ? 'confirmed'
                        : (! $isPaid ? 'cancelled' : 'pending'),
                ]);

                // Kalau pembayaran gagal/kedaluwarsa, lepas kembali slot jadwal
                // supaya bisa dipesan orang lain.
                if (! $isPaid) {
                    $booking->schedule()->update(['is_available' => true]);
                }

                $shouldNotify = $isPaid && ! $wasPaid;

                return $booking->id;
            });
        }

        if ($shouldNotify && $bookingId) {
            $booking = Booking::find($bookingId);
            if ($booking) {
                $notifications->paymentSucceeded(
                    $booking,
                    (string) $reference,
                    route('booking.payment.return', ['reference' => $reference])
                );
            }
        }

        // 5. Response HTTP 200 OK ke DOKU
        return response()->json([
            'message' => 'SUCCESS',
        ], 200);
    }
}