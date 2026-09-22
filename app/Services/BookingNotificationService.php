<?php

namespace App\Services;

use App\Models\Barber;
use App\Models\Booking;
use Carbon\Carbon;
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
            ? $booking->scheduled_at->format('d M Y, H:i') . ' WITA'
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
        $booking->loadMissing(['barber', 'payment']);

        $scheduled = $booking->scheduled_at
            ? $booking->scheduled_at->format('d M Y, H:i') . ' WITA'
            : '-';

        $total = (int) $booking->total_amount;
        $totalFormatted = 'Rp ' . number_format($total, 0, ',', '.');
        $isDp = strtolower((string) $booking->payment_type) === 'dp';

        if ($isDp) {
            $latePolicy = 'Jika terlambat lebih dari *15 menit* dari jadwal, maka uang muka (DP) dinyatakan *hangus*.';
        } else {
            $dpForfeit = 40000;
            $refundEstimate = max(0, $total - $dpForfeit);
            $dpFormatted = 'Rp ' . number_format($dpForfeit, 0, ',', '.');
            $refundFormatted = 'Rp ' . number_format($refundEstimate, 0, ',', '.');

            if ($refundEstimate > 0) {
                $latePolicy = "Jika terlambat lebih dari *15 menit* dari jadwal, maka biaya senilai DP (*{$dpFormatted}*) akan hangus, dan sisa pembayaran sebesar *{$refundFormatted}* akan dikembalikan (refund).";
            } else {
                $latePolicy = "Jika terlambat lebih dari *15 menit* dari jadwal, maka biaya senilai DP (*{$dpFormatted}*) akan hangus.";
            }
        }

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
            '• *Jenis Pembayaran:* ' . ($isDp ? 'Down Payment (DP)' : 'Full Payment'),
            '• *Total Biaya:* *' . $totalFormatted . '*',
            '• *Status:* *TERKONFIRMASI*',
            '',
            '📄 *E-Receipt & Tiket Reservasi:*',
            'Lihat rincian bukti pembayaran & status reservasi Anda di sini:',
            '👉 ' . $detailUrl,
            '',
            '⚠️ *Ketentuan Keterlambatan:*',
            $latePolicy,
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
            ? $booking->scheduled_at->format('d M Y, H:i') . ' WITA'
            : '-';

        $refundFormatted = 'Rp ' . number_format($refundAmount, 0, ',', '.');
        $statusText = $isPartial
            ? ($isManual ? 'Pengembalian Dana Sebagian (Manual Refund)' : 'Pengembalian Dana Sebagian (Partial Refund)')
            : ($isManual ? 'Pengembalian Dana (Manual Refund)' : 'Pengembalian Dana (Refund Penuh)');

        $details = [
            '• *Nama Pelanggan:* ' . ($booking->name ?: '-'),
            '• *Nominal Refund:* *' . $refundFormatted . '*',
            '• *Jenis Pembayaran:* ' . (strtolower((string) $booking->payment_type) === 'dp' ? 'Down Payment (DP)' : 'Full Payment'),
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

    public function bookingRescheduled(
        Booking $booking,
        ?\DateTimeInterface $oldScheduledAt = null,
    ): void {
        $booking->loadMissing(['barber']);

        $newScheduled = $booking->scheduled_at
            ? $booking->scheduled_at->format('d M Y, H:i') . ' WITA'
            : '-';

        $oldScheduled = $oldScheduledAt
            ? $oldScheduledAt->format('d M Y, H:i') . ' WITA'
            : '-';

        $message = implode("\n", [
            '💈 *KREF BARBERSHOP*',
            '_Perubahan Jadwal Berhasil Dikonfirmasi_',
            '─────────────────────────',
            '',
            'Halo, *' . ($booking->name ?: 'Pelanggan') . '*! 👋',
            'Permintaan perubahan jadwal (reschedule) Anda telah *disetujui oleh admin*.',
            '',
            '🗓️ *Rincian Jadwal Baru:*',
            '• *Nama Pelanggan:* ' . ($booking->name ?: '-'),
            '• *Barber:* ' . ($booking->barber?->name ?? '-'),
            '• *Jadwal Baru:* *' . $newScheduled . '*',
            '• *Jadwal Sebelumnya:* ~' . $oldScheduled . '~',
            '• *Status:* *TERKONFIRMASI*',
            '',
            '─────────────────────────',
            '_Mohon hadir tepat waktu (disarankan 5-10 menit sebelum jadwal baru)._',
            '_Sampai jumpa di kursi barber! ✂️_',
        ]);

        $this->send($booking->phone, $message);
    }

    public function bookingReminder(Booking $booking): void
    {
        $booking->loadMissing(['barber', 'payment']);

        $timeFormatted = $booking->scheduled_at
            ? $booking->scheduled_at->format('H:i') . ' WITA'
            : '-';

        $reference = $booking->payment?->reference ?? ('BK-' . $booking->id);

        $message = implode("\n", [
            '💈 *KREF BARBERSHOP*',
            '_Pengingat Jadwal Kunjungan_',
            '─────────────────────────',
            '',
            'Halo, *' . ($booking->name ?: 'Pelanggan') . '*! 👋',
            'Jadwal reservasi Anda di *KREF Barbershop* akan dimulai dalam *30 menit*:',
            '',
            '• *Barber:* ' . ($booking->barber?->name ?? '-'),
            '• *Waktu:* ' . $timeFormatted,
            '• *Kode Booking:* `' . $reference . '`',
            '',
            'Mohon hadir tepat waktu di lokasi.',
            'Sampai jumpa di kursi barber! ✂️',
        ]);

        $this->send($booking->phone, $message);
    }

    /**
     * Kirim pesan Rekap Agenda Harian ke Grup Operasional WhatsApp
     */
    public function sendDailyRecapToOpsGroup(string|\DateTimeInterface $date): void
    {
        $opsGroupId = trim((string) config('services.kref.ops_group_id', env('KREF_OPS_GROUP_ID', '120363423614283565@g.us')));
        $notifyOps = config('services.kref.notify_ops_group', true);

        if (! $notifyOps || $opsGroupId === '') {
            return;
        }

        if (! str_contains($opsGroupId, '@')) {
            $opsGroupId .= '@g.us';
        }

        try {
            $targetDate = $date instanceof \DateTimeInterface
                ? Carbon::instance($date)->locale('id')
                : Carbon::parse($date)->locale('id');

            $dateString = $targetDate->toDateString();
            $dateFormatted = $targetDate->translatedFormat('l, d F Y');

            $barbers = Barber::query()
                ->where('is_active', true)
                ->with(['schedules' => function ($query) use ($dateString) {
                    $query->whereDate('date', $dateString)
                        ->orderBy('slot_time');
                }])
                ->orderBy('name')
                ->get();

            $totalBookings = Booking::query()
                ->whereDate('scheduled_at', $dateString)
                ->whereNotIn('status', ['cancelled'])
                ->count();

            $lines = [
                $dateFormatted,
                '',
            ];

            foreach ($barbers as $barber) {
                $lines[] = $barber->name . ':';

                // Ambil booking aktif untuk barber ini pada tanggal tersebut
                $barberBookings = Booking::query()
                    ->where('barber_id', $barber->id)
                    ->whereDate('scheduled_at', $dateString)
                    ->whereNotIn('status', ['cancelled'])
                    ->with(['payment'])
                    ->orderBy('scheduled_at')
                    ->get();

                // Kumpulkan seluruh slot waktu dari jadwal dan booking agar kronologis
                $scheduleSlots = $barber->schedules->map(function ($s) {
                    return $s->slot_time instanceof \DateTimeInterface
                        ? $s->slot_time->format('H:i')
                        : Carbon::parse($s->slot_time)->format('H:i');
                });

                $bookingSlots = $barberBookings->map(function ($b) {
                    return $b->scheduled_at ? $b->scheduled_at->format('H:i') : null;
                })->filter();

                $allTimes = $scheduleSlots->merge($bookingSlots)->unique()->sort()->values();

                if ($allTimes->isEmpty()) {
                    $lines[] = 'Belum ada jadwal';
                } else {
                    foreach ($allTimes as $time) {
                        // Cari booking pada jam slot ini
                        $booking = $barberBookings->first(function ($b) use ($time) {
                            return $b->scheduled_at && $b->scheduled_at->format('H:i') === $time;
                        });

                        if ($booking) {
                            $name = strtolower(trim((string) $booking->name)) ?: 'pelanggan';

                            // Tentukan apakah DP atau FP
                            $isPaidFull = (int) $booking->outstanding_amount === 0;
                            $paymentType = $isPaidFull ? 'FP' : 'DP';

                            // Tentukan nominal yang dibayarkan
                            if ($isPaidFull) {
                                $paidAmount = (int) $booking->total_amount;
                            } else {
                                $paidAmount = $booking->payment?->amount
                                    ?? max(0, (int) $booking->total_amount - (int) $booking->outstanding_amount);
                            }

                            // Format nominal dalam 'k' (misal: 40000 -> 40k)
                            $amountFormatted = ($paidAmount >= 1000)
                                ? ($paidAmount % 1000 === 0 ? ($paidAmount / 1000) : number_format($paidAmount / 1000, 1, ',', '')) . 'k'
                                : $paidAmount;

                            $lines[] = "{$time}: {$name} {$paymentType} {$amountFormatted}";
                        } else {
                            // Slot kosong diakhiri tanda titik dua ':'
                            $lines[] = "{$time}:";
                        }
                    }
                }

                $lines[] = '';
            }

            $message = implode("\n", $lines);

            $this->send($opsGroupId, $message);
        } catch (\Throwable $e) {
            Log::error('Send daily recap to ops group failed.', [
                'date' => (string) $date,
                'error' => $e->getMessage(),
            ]);
        }
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
