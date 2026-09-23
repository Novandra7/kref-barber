<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\WalkInService;

class WahaWebhookController extends Controller
{
    public function handle(Request $request, WalkInService $walkInService)
    {
        try {
            $event = $request->input('event');
            
            // Hanya proses event message
            if ($event !== 'message') {
                return response()->json(['status' => 'ignored', 'reason' => 'not message event']);
            }

            $payload = $request->input('payload');
            
            if (!$payload) {
                return response()->json(['status' => 'ignored', 'reason' => 'no payload']);
            }

            $fromMe = $payload['fromMe'] ?? false;
            // Abaikan pesan fromMe: true
            if ($fromMe) {
                return response()->json(['status' => 'ignored', 'reason' => 'fromMe is true']);
            }

            $from = $payload['from'] ?? null;
            $opsGroupId = env('KREF_OPS_GROUP_ID', config('services.waha.ops_group_id'));

            // Hanya proses jika from === ops group ID
            if ($from !== $opsGroupId) {
                return response()->json(['status' => 'ignored', 'reason' => 'not from ops group']);
            }

            $participant = $payload['participant'] ?? null;
            $body = $payload['body'] ?? '';

            if (empty($body)) {
                return response()->json(['status' => 'ignored', 'reason' => 'empty body']);
            }

            // Delegate ke WalkInService
            $walkInService->processWebhook($body, $participant);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('WahaWebhookController error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }
}
