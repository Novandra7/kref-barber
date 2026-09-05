<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\DokuService;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route::get('/doku/access-token', function () {
//     $dokuService = app(DokuService::class);
//     return $dokuService->getB2BToken();
// });

Route::post('/doku/create-qris', function (Request $request) {
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