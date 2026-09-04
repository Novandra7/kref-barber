<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BarberController extends Controller
{
    public function index(): View
    {
        $barbers = Barber::latest()->paginate(10);
        return view('admin.barbers.index', compact('barbers'));
    }

    public function create(): View
    {
        return view('admin.barbers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $barber = Barber::create($this->validatedData($request));

        if ($request->hasFile('photo')) {
            $barber->update(['photo' => $request->file('photo')->store('uploads', 'public')]);
        }

        return redirect()
            ->route('admin.barbers.index')
            ->with('success', 'Barber created successfully.');
    }

    public function edit(Barber $barber): View
    {
        return view('admin.barbers.edit', compact('barber'));
    }

    public function update(Request $request, Barber $barber): RedirectResponse
    {
        $barber->update($this->validatedData($request));

        if ($request->hasFile('photo')) {
            if ($barber->photo) {
                Storage::disk('public')->delete($barber->photo);
            }

            $barber->update(['photo' => $request->file('photo')->store('uploads', 'public')]);
        }

        return redirect()
            ->route('admin.barbers.index')
            ->with('success', 'Barber updated successfully.');
    }

    public function destroy(Barber $barber): RedirectResponse
    {
        if ($barber->photo) {
            Storage::disk('public')->delete($barber->photo);
        }

        $barber->delete();

        return redirect()
            ->route('admin.barbers.index')
            ->with('success', 'Barber deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
