<?php

namespace App\Console\Commands;

use App\Jobs\SendBookingReminderJob;
use App\Models\Booking;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:send-reminders 
                            {--minutes=45 : Jendela waktu ke depan dalam menit untuk booking yang akan diingatkan} 
                            {--force : Kirimkan ulang meskipun sudah pernah memiliki catatan reminder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa dan kirimkan pengingat jadwal booking yang mendekati waktu layanan via Queue/WAHA';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $force = (bool) $this->option('force');

        $this->info("Mencari booking terkonfirmasi dalam {$minutes} menit ke depan...");

        $query = Booking::query()
            ->where('status', 'confirmed')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', now()->addMinutes($minutes));

        if (! $force) {
            $query->whereNull('reminder_sent_at');
        }

        $bookings = $query->get();

        if ($bookings->isEmpty()) {
            $this->line("Tidak ada booking terkonfirmasi yang memerlukan pengingat saat ini.");
            return Command::SUCCESS;
        }

        $this->info("Ditemukan {$bookings->count()} booking untuk diingatkan.");

        $bar = $this->output->createProgressBar($bookings->count());
        $bar->start();

        foreach ($bookings as $booking) {
            // Masukkan ke antrean job untuk dieksekusi oleh queue worker
            SendBookingReminderJob::dispatch($booking->id);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Semua job pengingat berhasil dimasukkan ke antrean (Queue).");

        return Command::SUCCESS;
    }
}
