<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Schedule;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $weekStart = $this->weekStart($request->input('week'));
        $weekEnd = $weekStart->addDays(6);
        $role = $request->input('role');
        $barberId = $request->input('barber');

        $barbers = Barber::query()
            ->where('is_active', true)
            ->when($role, fn ($query) => $query->where('role', $role))
            ->when($barberId, fn ($query) => $query->whereKey($barberId))
            ->with(['schedules' => fn ($query) => $query
                ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->orderBy('slot_time')])
            ->orderBy('name')
            ->get();

        return view('admin.schedules.index', [
            'barbers' => $barbers,
            'allBarbers' => Barber::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => Barber::query()->where('is_active', true)->distinct()->orderBy('role')->pluck('role'),
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'days' => collect(range(0, 6))->map(fn (int $offset) => $weekStart->addDays($offset)),
            'selectedRole' => $role,
            'selectedBarber' => $barberId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'barber_id' => ['required', 'exists:barbers,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'slot_time' => ['required', 'date_format:H:i'],
            'is_available' => ['required', 'boolean'],
        ]);

        Schedule::updateOrCreate(
            [
                'barber_id' => $data['barber_id'],
                'date' => $data['date'],
                'slot_time' => $data['slot_time'],
            ],
            ['is_available' => $data['is_available']],
        );

        return back()->with('success', 'Schedule saved successfully.');
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return back()->with('success', 'Schedule deleted successfully.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'barber_id' => ['required', 'exists:barbers,id'],
            'week' => ['required', 'date_format:Y-m-d'],
            'slot_time' => ['required', 'date_format:H:i'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['integer', 'between:0,6'],
            'is_available' => ['required', 'boolean'],
        ]);

        $weekStart = $this->weekStart($data['week']);

        DB::transaction(function () use ($data, $weekStart): void {
            foreach ($data['days'] as $day) {
                Schedule::updateOrCreate(
                    [
                        'barber_id' => $data['barber_id'],
                        'date' => $weekStart->addDays((int) $day)->toDateString(),
                        'slot_time' => $data['slot_time'],
                    ],
                    ['is_available' => $data['is_available']],
                );
            }
        });

        return back()->with('success', 'Bulk schedule saved successfully.');
    }

    public function copyPreviousWeek(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'week' => ['required', 'date_format:Y-m-d'],
        ]);
        $targetStart = $this->weekStart($data['week']);
        $sourceStart = $targetStart->subWeek();
        $sourceEnd = $sourceStart->addDays(6);

        DB::transaction(function () use ($sourceStart, $sourceEnd, $targetStart): void {
            Schedule::whereBetween('date', [$sourceStart->toDateString(), $sourceEnd->toDateString()])
                ->get()
                ->each(function (Schedule $schedule) use ($targetStart): void {
                    Schedule::updateOrCreate(
                        [
                            'barber_id' => $schedule->barber_id,
                            'date' => CarbonImmutable::parse($schedule->date)->addWeek()->toDateString(),
                            'slot_time' => $schedule->slot_time->format('H:i'),
                        ],
                        ['is_available' => $schedule->is_available],
                    );
                });
        });

        return back()->with('success', 'Previous week schedule copied successfully.');
    }

    private function weekStart(?string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date ?: now()->toDateString())->startOfWeek(CarbonImmutable::MONDAY);
    }
}
