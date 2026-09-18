<?php

namespace App\Console\Commands;

use App\Services\BookingNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDailyOpsRecapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ops:send-rekap {--date= : Tanggal rekap dalam format Y-m-d (default: hari ini)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim rekap agenda operasional harian ke grup WhatsApp Barber';

    /**
     * Execute the console command.
     */
    public function handle(BookingNotificationService $notificationService): int
    {
        $dateInput = $this->option('date');
        $targetDate = $dateInput ? Carbon::parse($dateInput) : now();

        $this->info("Menyusun dan mengirim rekap agenda operasional untuk tanggal: {$targetDate->format('Y-m-d')}...");

        $notificationService->sendDailyRecapToOpsGroup($targetDate);

        $this->info("Rekap harian berhasil diproses dan dikirim ke grup operasional.");

        return Command::SUCCESS;
    }
}
