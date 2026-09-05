<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;

class BookingNotificationService
{
    public function __construct(
        private readonly WahaService $waha,
    ) {
    }

    public function bookingCreated(Booking $booking, string $reference, string $paymentUrl): void
    {
        $message = implode("\n", [
            'KREF Barber',
            '',
            'Booking Anda berhasil dibuat.',
            'Kode referensi: ' . $reference,
            'Jadwal: ' . ($booking->scheduled_at?->format('d M Y, H:i') ?? '-'),
            'Barber: ' . ($booking->barber?->name ?? '-'),
            '',
            'Lanjutkan pembayaran melalui link berikut:',
            $paymentUrl,
        ]);

        $this->send($booking->phone, $message);
    }

    public function paymentSucceeded(Booking $booking, string $reference, string $detailUrl): void
    {
        $message = implode("\n", [
            'KREF Barber',
            '',
            'Pembayaran booking Anda berhasil.',
            'Kode referensi: ' . $reference,
            'Total: Rp ' . number_format((int) $booking->total_amount, 0, ',', '.'),
            '',
            'Lihat detail booking:',
            $detailUrl,
        ]);

        $this->send($booking->phone, $message);
    }

    private function send(string $phone, string $message): void
    {
        try {
            $this->waha->sendMessage($phone, $message);
        } catch (\Throwable $exception) {
            Log::error('Booking WhatsApp notification failed.', [
                'phone' => $phone,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
