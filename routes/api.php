<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\DokuService;
use App\Http\Controllers\Api\DokuWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/testing-payment', function (Request $request) {
    return response()->json([
        'message' => 'API endpoint for testing DOKU QRIS payment',
    ]);
});

// Route::get('/doku/access-token', function () {
//     $dokuService = app(DokuService::class);
//     return $dokuService->getB2BToken();
// });

// Webhook Payment DOKU (Public API endpoint)
Route::post('/payments/webhook', [DokuWebhookController::class, 'webhook'])->name('doku.webhook');


Route::post('/doku/create-qris', function (Request $request) {
    \Log::info('API /doku/create-qris called with payload: ' . json_encode($request->all()));
    // Validasi parameter input
    $validated = $request->validate([
        'amount' => ['required', 'numeric', 'min:1000'],
        'reference' => ['nullable', 'string', 'max:64'],
    ]);

    // Generate reference number otomatis jika tidak diisi
    $referenceNo = $validated['reference'] ?? 'INV-' . date('YmdHis') . '-' . rand(100, 999);
    $amount = (int) $validated['amount'];

    $dokuService = app(DokuService::class);
    
    return response()->json(
        $dokuService->createQrisPayment($referenceNo, $amount)
    );
});