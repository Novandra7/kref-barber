<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Barber;
use App\Models\Payment;
use App\Models\Service;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard.index', [
            'bookingCount' => Booking::count(),
            'pendingBookingCount' => Booking::whereIn('status', ['pending', 'waiting_payment'])->count(),
            'barberCount' => Barber::where('is_active', true)->count(),
            'serviceCount' => Service::where('is_active', true)->count(),
            'recentBookings' => Booking::with(['barber', 'payments'])
                ->latest()
                ->limit(10)
                ->get(),
            'pendingPayments' => Payment::where('status', 'pending')->count(),
        ]);
    }

    public function show(string $reference): View
    {
        $booking = Booking::with(['barber', 'items.service', 'payments'])
            ->findOrFail($reference);

        return view('admin.dashboard.booking-show', compact('booking'));
    }
}
