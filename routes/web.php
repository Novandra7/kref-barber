<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BarberController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\BookingAdminController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Api\DokuWebhookController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\DokuTestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. Subdomain Admin Area (admin.kref.test)
|--------------------------------------------------------------------------
*/
Route::domain('admin.' . config('app.domain', 'kref.test'))->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:admin-login');
        Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])
            ->name('password.request');
        Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
            ->name('password.email')
            ->middleware('throttle:6,1');
        Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])
            ->name('password.reset');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->name('password.update');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/booking/{reference}', [DashboardController::class, 'show'])->name('admin.booking.show');

        // Barbers
        Route::resource('barbers', BarberController::class)
            ->names('admin.barbers')
            ->except(['show']);

        // Services
        Route::resource('services', ServiceController::class)
            ->names('admin.services')
            ->except(['show']);

        // Schedules
        Route::get('/schedules', [ScheduleController::class, 'index'])->name('admin.schedules.index');
        Route::post('/schedules', [ScheduleController::class, 'store'])->name('admin.schedules.store');
        Route::patch('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('admin.schedules.update');
        Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('admin.schedules.destroy');
        Route::post('/schedules/bulk', [ScheduleController::class, 'bulkStore'])->name('admin.schedules.bulk');
        Route::post('/schedules/copy-previous-week', [ScheduleController::class, 'copyPreviousWeek'])->name('admin.schedules.copy-previous-week');

        // Bookings
        Route::get('/bookings/export', [BookingAdminController::class, 'export'])->name('admin.bookings.export');
        Route::resource('bookings', BookingAdminController::class)
            ->names('admin.bookings');
        Route::patch('bookings/{booking}/status', [BookingAdminController::class, 'updateStatus'])
            ->name('admin.bookings.update-status');

        // Payments
        Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index');

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});

/*
|--------------------------------------------------------------------------
| 2. Customer Landing Page & Booking Area (kref.test)
|--------------------------------------------------------------------------
*/
Route::domain(config('app.domain', 'kref.test'))->group(function () {
    // Landing Page
    Route::get('/', [LandingController::class, 'index'])->name('landing');

    // Customer Booking Flow
    Route::get('/booking', [BookingController::class, 'index'])->name('booking.index');
    Route::post('/booking/checkout', [BookingController::class, 'checkout'])->name('booking.checkout');
    Route::get('/booking/payment/{reference}', [BookingController::class, 'paymentReturn'])->name('booking.payment.return');
    Route::get('/booking/payment/{reference}/status', [BookingController::class, 'paymentStatus'])->name('booking.payment.status');

    // DOKU Test Page (untuk pengujian QRIS)
    Route::get('/testing-payment', [DokuTestController::class, 'index'])->name('doku-test.index');
    Route::post('/testing-payment/generate', [DokuTestController::class, 'generate'])->name('doku-test.generate');
    Route::post('/testing-payment/query', [DokuTestController::class, 'query'])->name('doku-test.query');

});