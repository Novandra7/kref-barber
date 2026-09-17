<?php

namespace App\Observers;

use App\Jobs\SendBookingReminderJob;
use App\Models\Booking;

class BookingObserver
{
    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking): void
    {
        $this->scheduleReminderIfNeeded($booking);
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        // 1. Jika jadwal booking diubah (reschedule) saat status confirmed
        if ($booking->wasChanged('scheduled_at') && $booking->status === 'confirmed') {
            // Reset status reminder karena jadwal berubah
            $booking->reminder_sent_at = null;
            $booking->saveQuietly();

            $this->scheduleReminderIfNeeded($booking);
            return;
        }

        // 2. Jika status berubah menjadi confirmed
        if ($booking->wasChanged('status') && $booking->status === 'confirmed') {
            $this->scheduleReminderIfNeeded($booking);
        }
    }

    /**
     * Jadwalkan delayed job 30 menit sebelum jadwal reservasi jika relevan.
     */
    protected function scheduleReminderIfNeeded(Booking $booking): void
    {
        if ($booking->status !== 'confirmed' || ! $booking->scheduled_at) {
            return;
        }

        if ($booking->reminder_sent_at !== null) {
            return;
        }

        // Hitung waktu reminder: 30 menit sebelum jadwal
        $reminderAt = $booking->scheduled_at->copy()->subMinutes(30);

        // Hanya jadwalkan jika waktu H-30 menit masih berada di masa depan
        if ($reminderAt->isFuture()) {
            SendBookingReminderJob::dispatch($booking->id)->delay($reminderAt);
        }
    }
}
