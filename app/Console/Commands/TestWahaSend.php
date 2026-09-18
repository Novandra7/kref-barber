<?php

namespace App\Console\Commands;

use App\Services\WahaService;
use Illuminate\Console\Command;

class TestWahaSend extends Command
{
    protected $signature = 'waha:send {recipient} {message}';
    protected $description = 'Kirim pesan tes via WAHA (bisa nomor HP atau Group ID WhatsApp)';

    public function handle(WahaService $wahaService)
    {
        $recipient = $this->argument('recipient');
        $message = $this->argument('message');

        $this->info("Mengirim pesan ke {$recipient}...");

        $response = $wahaService->sendMessage($recipient, $message);

        $this->line("Response dari WAHA:");
        $this->info(json_encode($response, JSON_PRETTY_PRINT));
    }
}