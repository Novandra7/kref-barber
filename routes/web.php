<?php

use Illuminate\Support\Facades\Route;
use App\Models\Service;
use App\Models\Barber;

Route::get('/', function () {
    $services = Service::all()->groupBy('category');
    $barbers = Barber::where('is_active', true)->get();
    return view('landing', compact('services', 'barbers'));
});

Route::get('/booking', function () {
    $services = Service::where('is_active', true)->get();
    $barbers = Barber::where('is_active', true)->get();

    return view('booking', compact('services', 'barbers'));
})->name('booking.index');
