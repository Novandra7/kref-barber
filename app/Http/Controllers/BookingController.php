<?php

namespace App\Http\Controllers;

use App\Models\Barber;
use App\Models\Schedule;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $services = Service::where('is_active', true)->get();
        $barbers = Barber::where('is_active', true)->get();
        $selectedDate = $request->input('date', now()->toDateString());
        $schedules = Schedule::where('date', $selectedDate)
            ->orderBy('slot_time')
            ->get()
            ->groupBy('barber_id');

        return view('booking', compact('services', 'barbers', 'schedules'));
    }
}
