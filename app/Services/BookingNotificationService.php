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

        $barberName = $booking->barber?->name ?? '-';
        $customerName = $booking->name ?: 'Pelanggan';

        $message = implode("\n", [
            '💈 KREF BARBER',
            'Reservasi Baru',
            '',
            "Halo, {$customerName}! 👋",
            '',
            "{$barberName} • {$scheduled}",
            $reference,
            '',
            '💳 Bayar:',
            '👉 ' . $paymentUrl,
            '',
            'Mohon selesaikan pembayaran sebelum batas waktu. ✂️',
        ]);

        $this->send($booking->phone, $message);
    }

    public function paymentSucceeded(Booking $booking, string $reference, string $detailUrl): void
    {
        $booking->loadMissing(['barber', 'payment']);

        $scheduled = $booking->scheduled_at
            ? $booking->scheduled_at->format('d M Y, H:i') . ' WITA'
            : '-';

        $barberName = $booking->barber?->name ?? '-';
        $customerName = $booking->name ?: 'Pelanggan';

        $message = implode("\n", [
            '💈 KREF BARBER',
            'Pembayaran Terkonfirmasi ✅',
            '',
            "Halo, {$customerName}! 🎉",
            'Reservasi kamu sudah TERKONFIRMASI.',
            '',
            "📋 {$barberName} • {$scheduled}",
            $reference,
            '',
            '🎫 E-Receipt:',
            '👉 ' . $detailUrl,
            '',
            '⚠️ Terlambat >15 menit dapat di-handle tanpa keramas, menyesuaikan situasi. Jika tidak dapat di-handle, DP hangus.',
            '🔄 Reschedule maksimal H-3 jam sebelum jadwal.',
            '',
            'Sampai jumpa di KREF! ✂️',
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
            '💈 *KREF BARBER*',
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
            '💈 *KREF BARBER*',
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

    public function bookingRescheduleRejected(Booking $booking): void
    {
        $booking->loadMissing(['barber']);

        $scheduled = $booking->scheduled_at
            ? $booking->scheduled_at->format('d M Y, H:i') . ' WITA'
            : '-';

        $message = implode("\n", [
            '💈 *KREF BARBER*',
            '_Pemberitahuan Permintaan Perubahan Jadwal_',
            '─────────────────────────',
            '',
            'Halo, *' . ($booking->name ?: 'Pelanggan') . '*! 👋',
            'Mohon maaf, permintaan perubahan jadwal (reschedule) Anda *tidak dapat disetujui oleh admin*.',
            '',
            '🗓️ *Jadwal Reservasi Anda Tetap Sesuai Semula:*',
            '• *Nama Pelanggan:* ' . ($booking->name ?: '-'),
            '• *Barber:* ' . ($booking->barber?->name ?? '-'),
            '• *Jadwal:* *' . $scheduled . '*',
            '• *Status:* *TERKONFIRMASI*',
            '',
            '─────────────────────────',
            '_Jadwal kunjungan Anda tetap berlaku sesuai rincian di atas. Mohon hadir tepat waktu._',
            '_Jika ada pertanyaan atau kendala, silakan hubungi admin via WhatsApp. Terima kasih! ✂️_',
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
            '💈 *KREF BARBER*',
            '_Pengingat Jadwal Kunjungan_',
            '─────────────────────────',
            '',
            'Halo, *' . ($booking->name ?: 'Pelanggan') . '*! 👋',
            'Jadwal reservasi Anda di *KREF Barber* akan dimulai dalam *30 menit*:',
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

    public function bookingExpired(Booking $booking, string $reference): void
    {
        $message = "Mohon maaf, batas waktu pembayaran untuk reservasi dengan kode {$reference} telah berakhir. Jadwal reservasi Anda telah dibatalkan secara otomatis. Silakan lakukan pemesanan ulang jika Anda ingin melakukan reservasi kembali di KREF Barber. Terima kasih.";

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
                    ->with(['payment', 'items.service'])
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
                            $sourcePrefix = $booking->source === 'walk_in' ? '[WI] ' : '';

                            // Tentukan apakah DP atau FP
                            $isCompleted = $booking->status === 'completed';
                            $isDp = ($booking->payment_type === 'dp' || $booking->payment?->purpose === 'dp') && ! $isCompleted;

                            if ($isDp) {
                                $paymentType = 'DP';
                                $paidAmount = $booking->payment?->amount
                                    ?? max(0, (int) $booking->total_amount - (int) $booking->outstanding_amount);
                            } elseif ($isCompleted || (int) $booking->total_amount > 0) {
                                $paymentType = 'FP';
                                $paidAmount = (int) $booking->total_amount;
                            } else {
                                $paymentType = strtolower((string) $booking->payment_type) === 'full' ? 'FULL' : '';
                                $paidAmount = 0;
                            }

                            // Format nominal dalam 'k' (misal: 40000 -> 40k)
                            $amountFormatted = '';
                            if ($paidAmount > 0) {
                                $amountFormatted = ($paidAmount >= 1000)
                                    ? ($paidAmount % 1000 === 0 ? ($paidAmount / 1000) : number_format($paidAmount / 1000, 1, ',', '')) . 'k'
                                    : $paidAmount;
                            }

                            $paymentLabel = trim("{$paymentType} {$amountFormatted}");
                            $paymentPart = $paymentLabel !== '' ? " {$paymentLabel}" : '';

                            // Ambil kode services yang dipilih
                            $serviceCodes = $booking->items
                                ->filter(fn ($item) => $item->item_type === 'service' || !empty($item->service_id))
                                ->map(function ($item) {
                                    if (!empty($item->service?->code)) {
                                        return strtoupper(trim($item->service->code));
                                    }

                                    if (!empty($item->service_name_snapshot)) {
                                        $words = preg_split('/\s+/', trim($item->service_name_snapshot));
                                        $initials = '';
                                        foreach ($words as $w) {
                                            $initials .= strtoupper(substr($w, 0, 1));
                                        }
                                        return $initials ?: strtoupper(substr($item->service_name_snapshot, 0, 3));
                                    }

                                    return null;
                                })
                                ->filter()
                                ->values()
                                ->implode(' ');

                            $serviceSuffix = $serviceCodes !== '' ? " | {$serviceCodes}" : '';

                            $lines[] = "{$time}: {$sourcePrefix}{$name}{$paymentPart}{$serviceSuffix}";
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

    private function send(?string $phone, string $message): void
    {
        if (empty($phone)) {
            Log::warning('Booking WhatsApp notification skipped: recipient phone number is empty.');
            return;
        }

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
