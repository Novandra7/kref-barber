<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\DokuService;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/doku/access-token', function () {
    $dokuService = app(DokuService::class);
    return $dokuService->getB2BToken();
});

Route::get('/doku/create-qris', function () {
    $dokuService = app(DokuService::class);
    return $dokuService->createQrisPayment('ref-123', 10000);
});