<?php

namespace App\Console\Commands;

use App\Services\WahaService;
use Illuminate\Console\Command;

class TestWahaSend extends Command
{
    protected $signature = 'waha:send {phone} {message}';
    protected $description = 'Kirim pesan tes via WAHA';

    public function handle(WahaService $wahaService)
    {
        $phone = $this->argument('phone');
        $message = $this->argument('message');

        $this->info("Mengirim pesan ke {$phone}...");

        $response = $wahaService->sendMessage($phone, $message);

        $this->line("Response dari WAHA:");
        $this->info(json_encode($response, JSON_PRETTY_PRINT));
    }
}