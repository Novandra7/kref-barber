<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\BookingNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBookingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan maksimal jika terjadi error.
     */
    public int $tries = 3;

    /**
     * Waktu jeda (dalam detik) sebelum mencoba kembali jika gagal (1 menit, lalu 5 menit).
     *
     * @var array<int, int>
     */
    public array $backoff = [60, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $bookingId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(BookingNotificationService $notifications): void
    {
        // Ambil data segar terkini dari database
        $booking = Booking::with(['barber', 'payment'])->find($this->bookingId);

        if (! $booking) {
            Log::info("SendBookingReminderJob: Booking #{$this->bookingId} tidak ditemukan.");
            return;
        }

        // 1. Pastikan status masih confirmed
        if ($booking->status !== 'confirmed') {
            Log::info("SendBookingReminderJob: Diabaikan untuk booking #{$booking->id} karena status saat ini adalah '{$booking->status}'.");
            return;
        }

        // 2. Cegah pengiriman ganda jika sudah pernah diingatkan
        if ($booking->reminder_sent_at !== null) {
            Log::info("SendBookingReminderJob: Diabaikan untuk booking #{$booking->id} karena pengingat sudah dikirim pada {$booking->reminder_sent_at}.");
            return;
        }

        // 3. Pastikan jadwal booking belum lewat
        if ($booking->scheduled_at && $booking->scheduled_at->isPast()) {
            Log::info("SendBookingReminderJob: Diabaikan untuk booking #{$booking->id} karena jadwal sudah terlewat ({$booking->scheduled_at}).");
            return;
        }

        // 4. Kirim notifikasi reminder via WAHA
        $notifications->bookingReminder($booking);

        // 5. Tandai bahwa reminder telah sukses terkirim
        $booking->update([
            'reminder_sent_at' => now(),
        ]);

        Log::info("SendBookingReminderJob: Pengingat berhasil dikirim untuk booking #{$booking->id} ke {$booking->phone}.");
    }
}
