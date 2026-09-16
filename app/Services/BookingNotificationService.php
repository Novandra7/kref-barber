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
        $booking->loadMissing(['barber']);

        $scheduled = $booking->scheduled_at
            ? $booking->scheduled_at->format('d M Y, H:i') . ' WIB'
            : '-';

        $message = implode("\n", [
            '💈 *KREF BARBERSHOP*',
            '_Reservasi Baru Diterima_',
            '─────────────────────────',
            '',
            'Halo, *' . ($booking->name ?: 'Pelanggan') . '*! 👋',
            'Terima kasih telah memesan layanan di *KREF Barbershop*. Reservasi Anda telah tercatat dan saat ini *menunggu pembayaran*.',
            '',
            '📋 *Detail Reservasi:*',
            '• *Kode Referensi:* `' . $reference . '`',
            '• *Barber:* ' . ($booking->barber?->name ?? '-'),
            '• *Jadwal:* ' . $scheduled,
            '',
            '💳 *Instruksi Pembayaran:*',
            'Silakan selesaikan pembayaran Anda melalui tautan resmi berikut:',
            '👉 ' . $paymentUrl,
            '',
            '─────────────────────────',
            '_Harap selesaikan pembayaran sebelum batas waktu berakhir._',
            '_Sampai jumpa di kursi barber! ✂️_',
        ]);

        $this->send($booking->phone, $message);
    }

    public function paymentSucceeded(Booking $booking, string $reference, string $detailUrl): void
    {
        $booking->loadMissing(['barber']);

        $scheduled = $booking->scheduled_at
            ? $booking->scheduled_at->format('d M Y, H:i') . ' WIB'
            : '-';

        $totalFormatted = 'Rp ' . number_format((int) $booking->total_amount, 0, ',', '.');

        $message = implode("\n", [
            '💈 *KREF BARBERSHOP*',
            '_Pembayaran Berhasil Dikonfirmasi_',
            '─────────────────────────',
            '',
            'Halo, *' . ($booking->name ?: 'Pelanggan') . '*! 🎉',
            'Pembayaran untuk reservasi Anda telah *berhasil diverifikasi*. Slot jadwal Anda resmi terkunci!',
            '',
            '✅ *Detail Reservasi:*',
            '• *Kode Referensi:* `' . $reference . '`',
            '• *Barber:* ' . ($booking->barber?->name ?? '-'),
            '• *Jadwal:* ' . $scheduled,
            '• *Total Pembayaran:* *' . $totalFormatted . '*',
            '• *Status:* *TERKONFIRMASI*',
            '',
            '📄 *E-Receipt & Tiket Reservasi:*',
            'Lihat rincian bukti pembayaran & status reservasi Anda di sini:',
            '👉 ' . $detailUrl,
            '',
            '─────────────────────────',
            '_Mohon hadir tepat waktu (disarankan 5-10 menit sebelum jadwal)._',
            '_Terima kasih atas kepercayaan Anda di KREF Barbershop! ✂️_',
        ]);

        $this->send($booking->phone, $message);
    }

    public function bookingRefunded(
        Booking $booking,
        int $refundAmount,
        ?string $refundNo = null,
        bool $isPartial = false,
        bool $isManual = false,
    ): void {
        $booking->loadMissing(['barber']);

        $scheduled = $booking->scheduled_at
            ? $booking->scheduled_at->format('d M Y, H:i') . ' WIB'
            : '-';

        $refundFormatted = 'Rp ' . number_format($refundAmount, 0, ',', '.');
        $statusText = $isPartial
            ? ($isManual ? 'Pengembalian Dana Sebagian (Manual Refund)' : 'Pengembalian Dana Sebagian (Partial Refund)')
            : ($isManual ? 'Pengembalian Dana (Manual Refund)' : 'Pengembalian Dana (Refund Penuh)');

        $details = [
            '• *Nama Pelanggan:* ' . ($booking->name ?: '-'),
            '• *Nominal Refund:* *' . $refundFormatted . '*',
        ];

        if ($refundNo) {
            $details[] = '• *No. Refund:* `' . $refundNo . '`';
        }

        $details[] = '• *Barber:* ' . ($booking->barber?->name ?? '-');
        $details[] = '• *Jadwal Batal:* ' . $scheduled;

        $infoText = $isManual
            ? 'Dana pengembalian telah/akan ditransfer secara manual oleh Admin KREF Barber ke rekening/e-wallet Anda. Silakan hubungi admin via WhatsApp jika ada kendala.'
            : 'Dana telah dikembalikan ke metode pembayaran awal Anda sesuai ketentuan penyedia pembayaran.';

        $message = implode("\n", [
            '💈 *KREF BARBERSHOP*',
            '_Pemberitahuan Pengembalian Dana_',
            '─────────────────────────',
            '',
            'Halo, *' . ($booking->name ?: 'Pelanggan') . '*! 👋',
            'Permintaan pembatalan booking telah diproses. Berikut rincian *' . $statusText . '* Anda:',
            '',
            '💸 *Rincian Refund:*',
            ...$details,
            '',
            'ℹ️ *Informasi Pengembalian:*',
            $infoText,
            '',
            '─────────────────────────',
            '_Terima kasih atas pengertian Anda._',
            '_Kami siap melayani Anda kembali di kesempatan berikutnya! ✂️_',
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
