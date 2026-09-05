<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class DokuService
{
    private string $clientId;
    private string $secretKey;
    private string $privateKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = config('services.doku.client_id');
        $this->secretKey = config('services.doku.secret_key');
        $privateKeyPath = config('services.doku.private_key');

        if (!file_exists($privateKeyPath)) {
            throw new \RuntimeException(
                'Private key tidak ditemukan: ' . $privateKeyPath
            );
        }

        $this->privateKey = file_get_contents($privateKeyPath);

        $this->baseUrl = rtrim(
            config('services.doku.base_url', 'https://api-sandbox.doku.com'),
            '/'
        );
    }

    public function getB2BToken(): array
{
    // Format timestamp ISO-8601 (Contoh: 2026-09-04T08:00:00Z)
    $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');
    
    // String to sign sesuai dokumentasi DOKU SNAP: ClientId|Timestamp
    $stringToSign = $this->clientId . '|' . $timestamp;
    $signature = $this->generateTokenSignature($stringToSign);

    $response = Http::timeout(15)
        ->withHeaders([
            'Content-Type' => 'application/json',
            'X-TIMESTAMP'  => $timestamp,
            'X-CLIENT-KEY' => $this->clientId,
            'X-SIGNATURE'  => $signature,
        ])
        ->post(
            $this->baseUrl . '/authorization/v1/access-token/b2b',
            [
                'grantType' => 'client_credentials',
            ]
        );

    return $this->responseData($response);
}

    private function generateTokenSignature(string $stringToSign): string
    {
        if (empty($this->privateKey)) {         
            throw new \RuntimeException('DOKU private key belum dikonfigurasi.');
        }

        // Memastikan Private Key memiliki format PEM yang valid
        $formattedPrivateKey = $this->formatPrivateKey($this->privateKey);

        $privateKey = openssl_pkey_get_private($formattedPrivateKey);

        if (!$privateKey) {
            throw new \RuntimeException('DOKU private key tidak valid: ' . openssl_error_string());
        }

        // Lakukan enkripsi SHA256withRSA
        if (!openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Gagal membuat DOKU RSA signature.');
        }

        // Encode hasil signature ke Base64
        return base64_encode($signature);
    }

    /**
     * Helper untuk merapikan format Private Key dari .env atau string
     */
    private function formatPrivateKey(string $key): string
    {
        if (str_contains($key, '-----BEGIN PRIVATE KEY-----')) {
            return $key;
        }

        // Jika disimpan tanpa header/footer di .env
        $key = trim(preg_replace('/\s+/', '', $key));
        $key = chunk_split($key, 64, "\n");

        return "-----BEGIN PRIVATE KEY-----\n" . $key . "-----END PRIVATE KEY-----";
    }

    public function createQrisPayment(string $reference, int $amount): array
    {
        $tokenResponse = $this->getB2BToken();

        // DOKU SNAP mengembalikan format camelCase: accessToken
        $accessToken = $tokenResponse['accessToken']
            ?? $tokenResponse['access_token']
            ?? null;

        if (!$accessToken) {
            throw new \RuntimeException('DOKU access token was not returned.');
        }

        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        // 1. Susun Array Body
        $body = [
            'partnerReferenceNo' => $reference,
            'amount' => [
                'value'    => number_format($amount, 2, '.', ''),
                'currency' => 'IDR',
            ],
            'merchantId' => config('services.doku.merchant_id'),
            'terminalId' => config('services.doku.terminal_id'),
            'validityPeriod' => now()->addHours(1)->toIso8601String(),
            'additionalInfo' => [
                'postalCode' => config('services.doku.postal_code'),
                'feeType'    => '1',
            ],
        ];

        $endpoint = '/snap-adapter/b2b/v1.0/qr/qr-mpm-generate';

        // 2. Encode JSON Minified & Unescaped
        $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($bodyJson === false) {
            throw new \RuntimeException('Failed to encode DOKU request body.');
        }

        // 3. Hash Body (SHA-256 Hex Lowercase)
        $hashedBody = strtolower(hash('sha256', $bodyJson));

        // 4. Susun String to Sign (Format SNAP: HTTPMethod:Endpoint:AccessToken:HashedBody:Timestamp)
        $stringToSign = 'POST:' . $endpoint . ':' . $accessToken . ':' . $hashedBody . ':' . $timestamp;

        // 5. Generate HMAC-SHA512 Signature menggunakan Secret Key / Secret Client
        $signature = base64_encode(
            hash_hmac(
                'sha512',
                $stringToSign,
                $this->secretKey,
                true
            )
        );

        $externalId = (string) ((int) (microtime(true) * 1000));

        // 6. Send Request menggunakan raw Body JSON agar tidak di-reformat oleh Laravel
        $response = Http::timeout(15)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'   => 'application/json',
                'Accept'         => '*/*',
                'X-PARTNER-ID'   => $this->clientId,
                'X-EXTERNAL-ID'  => $externalId,
                'X-TIMESTAMP'    => $timestamp,
                'X-SIGNATURE'    => $signature,
                'CHANNEL-ID'     => 'H2H',
            ])
            ->withBody($bodyJson, 'application/json') // Kirim string JSON mentah yang sudah di-hash
            ->post($this->baseUrl . $endpoint);

        return $this->responseData($response);
    }

    private function responseData(Response $response): array
    {
        if ($response->failed()) {
            throw new \RuntimeException(
                'DOKU request failed: ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }
}