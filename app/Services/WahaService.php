<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    protected string $baseUrl;
    protected string $session;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.waha.base_url', env('WAHA_BASE_URL', 'http://localhost:3000'));
        $this->session = config('services.waha.session', env('WAHA_SESSION', 'kref'));
        $this->apiKey = config('services.waha.api_key', env('WAHA_API_KEY'));
    }

    /**
     * Kirim pesan teks WA
     */
    public function sendMessage(string $phone, string $text)
    {
        // Format nomor HP agar sesuai standar WAHA (contoh: 08123456789 -> 628123456789@c.us)
        $chatId = $this->formatPhoneNumber($phone);

        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}/api/sendText", [
                'session' => $this->session,
                'chatId' => $chatId,
                'text' => $text,
            ]);

        if ($response->failed()) {
            Log::error('WAHA Send Message Failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Format nomor HP Indonesia ke format chatId WhatsApp
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Hapus karakter selain angka
        $number = preg_replace('/[^0-9]/', '', $phone);

        // Ubah awalan 08 menjadi 628
        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        }

        // Jika belum ada suffix @c.us, tambahkan
        if (!str_contains($number, '@c.us')) {
            $number .= '@c.us';
        }

        return $number;
    }

    protected function getHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($this->apiKey) {
            $headers['X-Api-Key'] = $this->apiKey;
        }

        return $headers;
    }
}