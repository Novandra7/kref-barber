<?php

namespace App\Http\Controllers;

use App\Models\Barber;
use App\Models\Service;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        $services = Service::all()->groupBy('category');
        $barbers = Barber::where('is_active', true)->get();

        return view('landing', compact('services', 'barbers'));
    }
}
