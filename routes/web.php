<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index']);

Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
Route::post('/booking/checkout', [BookingController::class, 'checkout'])->name('booking.checkout');
Route::post('/booking/doku/webhook', [BookingController::class, 'webhook'])->name('booking.payment.webhook');
Route::get('/booking/payment/{reference}', [BookingController::class, 'paymentReturn'])->name('booking.payment.return');
Route::get('/booking/payment/{reference}/status', [BookingController::class, 'paymentStatus'])->name('booking.payment.status');
