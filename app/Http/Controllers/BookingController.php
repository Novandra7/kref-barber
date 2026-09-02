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
        $selectedDate = $request->input('date');
        $schedules = Schedule::orderBy('date')
            ->orderBy('slot_time')
            ->get();
        $scheduleData = $schedules->map(fn (Schedule $schedule) => [
            'id' => $schedule->id,
            'barber_id' => $schedule->barber_id,
            'date' => $schedule->date->format('Y-m-d'),
            'slot_time' => $schedule->slot_time->format('H:i'),
            'is_available' => $schedule->is_available,
        ])->values();
        $availableDates = $scheduleData->pluck('date')
            ->unique()
            ->values();
        if (!$selectedDate || !$availableDates->contains($selectedDate)) {
            $selectedDate = $availableDates->first() ?? now()->toDateString();
        }

        return view('booking', compact('services', 'barbers', 'scheduleData', 'availableDates', 'selectedDate'));
    }
}
