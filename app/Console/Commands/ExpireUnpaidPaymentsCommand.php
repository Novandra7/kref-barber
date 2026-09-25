<?php

namespace App\Console\Commands;

use App\Services\PaymentExpirationService;
use Illuminate\Console\Command;

class ExpireUnpaidPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:expire-unpaid-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memeriksa pembayaran pending yang sudah melewati batas waktu pembayaran, menandai sebagai expired, membatalkan booking, serta melepas slot jadwal barber';

    /**
     * Execute the console command.
     */
    public function handle(PaymentExpirationService $expirationService): int
    {
        $this->info('Memeriksa pembayaran pending yang telah kedaluwarsa...');

        $expiredCount = $expirationService->expireAllPendingOverdue();

        $this->info("Selesai. Sebanyak {$expiredCount} transaksi pembayaran telah ditandai sebagai expired.");

        return Command::SUCCESS;
    }
}
