<?php

namespace App\Services;

use App\Events\PaymentStatusUpdated;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentExpirationService
{
    public function __construct(
        private readonly BookingNotificationService $notifications
    ) {
    }

    /**
     * Memeriksa dan mengeksekusi kadaluwarsa pada satu pembayaran jika sudah melewati batas waktu.
     */
    public function expireIfOverdue(Payment $payment): bool
    {
        if ($payment->status !== 'pending') {
            return false;
        }

        $now = now();
        $isOverdue = false;

        if ($payment->expires_at) {
            $isOverdue = $payment->expires_at->isPast();
        } elseif ($payment->created_at) {
            $isOverdue = $payment->created_at->addHour()->isPast();
        }

        if (! $isOverdue) {
            return false;
        }

        return $this->expirePayment($payment);
    }

    /**
     * Mengeksekusi perubahan status pembayaran menjadi expired,
     * membatalkan booking terkait, melepas slot jadwal, dan mengirim notifikasi.
     */
    public function expirePayment(Payment $payment): bool
    {
        $bookingsToNotify = [];
        $reference = $payment->partner_reference_no
            ?? data_get($payment->provider_payload, 'partnerReferenceNo')
            ?? ('KREF-' . $payment->id);

        $executed = DB::transaction(function () use ($payment, &$bookingsToNotify): bool {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();

            if (! $lockedPayment || $lockedPayment->status !== 'pending') {
                return false;
            }

            $lockedPayment->update([
                'status' => 'expired',
            ]);

            $bookings = $lockedPayment->bookings()->with(['schedule'])->lockForUpdate()->get();

            foreach ($bookings as $booking) {
                $booking->update([
                    'status' => 'cancelled',
                ]);

                // Lepas kembali slot jadwal barber agar dapat dipesan pelanggan lain
                if ($booking->schedule) {
                    $booking->schedule->update(['is_available' => true]);
                }

                if ($booking->phone) {
                    $bookingsToNotify[] = $booking;
                }
            }

            return true;
        });

        if (! $executed) {
            return false;
        }

        Log::info('Payment successfully marked as expired', [
            'payment_id' => $payment->id,
            'reference'  => $reference,
        ]);

        // Broadcast event ke websocket / Echo agar halaman frontend langsung ter-update
        try {
            PaymentStatusUpdated::dispatch(
                (string) $reference,
                'expired',
                (string) json_encode([
                    'status'    => 'EXPIRED',
                    'reference' => $reference,
                ])
            );
        } catch (\Throwable $e) {
            Log::warning('Broadcast PaymentStatusUpdated failed during expiration', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
        }

        // Kirim notifikasi WhatsApp resmi ke pelanggan
        foreach ($bookingsToNotify as $booking) {
            try {
                $this->notifications->bookingExpired($booking, (string) $reference);
            } catch (\Throwable $e) {
                Log::error('Failed sending expired notification to customer', [
                    'booking_id' => $booking->id,
                    'phone'      => $booking->phone,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return true;
    }

    /**
     * Memeriksa dan mengeksekusi semua pembayaran pending yang telah kedaluwarsa.
     * Mengembalikan jumlah pembayaran yang berhasil ditandai expired.
     */
    public function expireAllPendingOverdue(): int
    {
        $now = now();

        $overduePayments = Payment::query()
            ->where('status', 'pending')
            ->where(function ($query) use ($now): void {
                $query->where(function ($sub) use ($now): void {
                    $sub->whereNotNull('expires_at')
                        ->where('expires_at', '<=', $now);
                })->orWhere(function ($sub) use ($now): void {
                    $sub->whereNull('expires_at')
                        ->where('created_at', '<=', $now->copy()->subHour());
                });
            })
            ->get();

        $count = 0;
        foreach ($overduePayments as $payment) {
            if ($this->expirePayment($payment)) {
                $count++;
            }
        }

        return $count;
    }
}
