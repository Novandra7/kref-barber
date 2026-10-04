<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookingNotificationService;
use App\Services\WahaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WahaWebhookController extends Controller
{
    public function handle(Request $request, BookingNotificationService $notificationService)
    {
        try {
            $event = $request->input('event');

            if ($event !== 'message') {
                return response()->json(['status' => 'ignored', 'reason' => 'not message event']);
            }

            $payload = $request->input('payload');

            if (!$payload) {
                return response()->json(['status' => 'ignored', 'reason' => 'no payload']);
            }

            if ($payload['fromMe'] ?? false) {
                return response()->json(['status' => 'ignored', 'reason' => 'fromMe is true']);
            }

            $from = $payload['from'] ?? null;
            $opsGroupId = config('services.kref.ops_group_id', env('KREF_OPS_GROUP_ID'));

            if ($from !== $opsGroupId) {
                return response()->json(['status' => 'ignored', 'reason' => 'not from ops group']);
            }

            $body = trim($payload['body'] ?? '');

            if (empty($body)) {
                return response()->json(['status' => 'ignored', 'reason' => 'empty body']);
            }

            if ($this->handleJadwalCommand($body, $notificationService)) {
                return response()->json(['status' => 'success', 'action' => 'jadwal_command']);
            }

            return response()->json(['status' => 'ignored', 'reason' => 'not a recognized command']);
        } catch (\Exception $e) {
            Log::error('WahaWebhookController error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Handle command /jadwal dari grup ops.
     *
     * Format:
     *   /jadwal             → Jadwal hari ini
     *   /jadwal 2026-10-10  → YYYY-MM-DD
     *   /jadwal 10-10-2026  → DD-MM-YYYY
     *   /jadwal 10/10/2026  → DD/MM/YYYY
     */
    private function handleJadwalCommand(string $body, BookingNotificationService $notificationService): bool
    {
        if (!preg_match('/^\/jadwal(?:\s+(.+))?$/i', $body, $matches)) {
            return false;
        }

        $dateParam = isset($matches[1]) ? trim($matches[1]) : null;

        try {
            $targetDate = $dateParam
                ? $this->parseFlexibleDate($dateParam)
                : now();

            $notificationService->sendDailyRecapToOpsGroup($targetDate);

            Log::info('WahaWebhookController: /jadwal command processed', [
                'date_param' => $dateParam,
                'target_date' => $targetDate->toDateString(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('WahaWebhookController: Failed to process /jadwal command', [
                'date_param' => $dateParam,
                'error' => $e->getMessage(),
            ]);

            $this->replyFormatError($dateParam);
            return true;
        }
    }

    /**
     * Parse tanggal dari berbagai format.
     * Didukung: YYYY-MM-DD, DD-MM-YYYY, DD/MM/YYYY
     */
    private function parseFlexibleDate(string $dateString): Carbon
    {
        $dateString = trim($dateString);

        // YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $dateString)) {
            return Carbon::createFromFormat('Y-m-d', $dateString)->startOfDay();
        }

        // DD-MM-YYYY atau DD/MM/YYYY
        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $dateString, $m)) {
            return Carbon::createFromFormat('d-m-Y', "{$m[1]}-{$m[2]}-{$m[3]}")->startOfDay();
        }

        // Fallback: Carbon::parse natural language
        return Carbon::parse($dateString)->startOfDay();
    }

    /**
     * Kirim pesan panduan format tanggal ke grup ops jika parsing gagal.
     */
    private function replyFormatError(?string $dateParam): void
    {
        try {
            $opsGroupId = config('services.kref.ops_group_id', env('KREF_OPS_GROUP_ID'));
            if (!$opsGroupId) {
                return;
            }

            $message = "⚠️ Format tanggal tidak valid: *{$dateParam}*\n\n"
                . "Format yang diterima:\n"
                . "• /jadwal → jadwal hari ini\n"
                . "• /jadwal 2026-10-10 → YYYY-MM-DD\n"
                . "• /jadwal 10-10-2026 → DD-MM-YYYY\n"
                . "• /jadwal 10/10/2026 → DD/MM/YYYY";

            app(WahaService::class)->sendMessage($opsGroupId, $message);
        } catch (\Throwable $e) {
            Log::error('WahaWebhookController: Failed to send error reply', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
