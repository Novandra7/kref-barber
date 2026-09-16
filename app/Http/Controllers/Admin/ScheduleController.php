<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Schedule;
use App\Services\BookingNotificationService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
                ->with(['bookings' => fn ($query) => $query->select([
                    'id',
                    'schedule_id',
                    'name',
                    'phone',
                    'payment_type',
                    'status',
                ])->whereNotIn('status', ['cancelled'])->with(['items' => fn ($query) => $query->select([
                    'id',
                    'booking_id',
                    'item_type',
                    'service_name_snapshot',
                    'qty',
                ])])])
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
        ]);

        Schedule::updateOrCreate(
            [
                'barber_id' => $data['barber_id'],
                'date' => $data['date'],
                'slot_time' => $data['slot_time'],
            ],
            ['is_available' => true],
        );

        return back()->with('success', 'Schedule saved successfully.');
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        if (!$schedule->is_available) {
            return back()->with('error', 'Booked schedule slots cannot be deleted.');
        }

        $schedule->delete();

        return back()->with('success', 'Schedule deleted successfully.');
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        if (!$schedule->is_available) {
            return back()->with('error', 'Booked schedule slots cannot be edited.');
        }

        $data = $request->validate([
            'barber_id' => ['required', 'exists:barbers,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'slot_time' => [
                'required',
                'date_format:H:i',
                Rule::unique('schedules', 'slot_time')
                    ->where('barber_id', $request->input('barber_id'))
                    ->where('date', $request->input('date'))
                    ->ignore($schedule->id),
            ],
        ]);

        $schedule->update([
            'barber_id' => $data['barber_id'],
            'date' => $data['date'],
            'slot_time' => $data['slot_time'],
        ]);

        return back()->with('success', 'Schedule updated successfully.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'barber_id' => ['required', 'exists:barbers,id'],
            'week' => ['required', 'date_format:Y-m-d'],
            'slot_times' => ['required', 'array', 'min:1'],
            'slot_times.*' => ['required', 'date_format:H:i'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['integer', 'between:0,6'],
            'is_available' => ['required', 'boolean'],
        ]);

        $weekStart = $this->weekStart($data['week']);

        DB::transaction(function () use ($data, $weekStart): void {
            foreach ($data['days'] as $day) {
                $scheduleDate = $weekStart->copy()->addDays((int) $day)->toDateString();

                foreach ($data['slot_times'] as $slotTime) {
                    Schedule::updateOrCreate(
                        [
                            'barber_id' => $data['barber_id'],
                            'date' => $scheduleDate,
                            'slot_time' => $slotTime,
                        ],
                        ['is_available' => $data['is_available']]
                    );
                }
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
                        ['is_available' => true],
                    );
                });
        });

        return back()->with('success', 'Previous week schedule copied successfully.');
    }

    /**
     * Reschedule booking melalui aksi drag-and-drop jadwal oleh admin.
     */
    public function rescheduleBooking(Request $request, BookingNotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'booking_id'         => ['required', 'integer', 'exists:bookings,id'],
            'target_schedule_id' => ['nullable', 'integer', 'exists:schedules,id'],
            'target_barber_id'   => ['required_without:target_schedule_id', 'nullable', 'integer', 'exists:barbers,id'],
            'target_date'        => ['required_without:target_schedule_id', 'nullable', 'date_format:Y-m-d'],
            'target_time'        => ['required_without:target_schedule_id', 'nullable', 'date_format:H:i'],
            'release_old_slot'   => ['nullable', 'boolean'],
            'notify_customer'    => ['nullable', 'boolean'],
        ]);

        $booking = Booking::with(['schedule', 'barber'])->findOrFail($data['booking_id']);

        // Cari atau tentukan target schedule
        $targetSchedule = null;
        if (!empty($data['target_schedule_id'])) {
            $targetSchedule = Schedule::with('barber')->findOrFail($data['target_schedule_id']);
        } elseif (!empty($data['target_barber_id']) && !empty($data['target_date']) && !empty($data['target_time'])) {
            $targetSchedule = Schedule::with('barber')
                ->where('barber_id', $data['target_barber_id'])
                ->where('date', $data['target_date'])
                ->where('slot_time', $data['target_time'])
                ->first();
        }

        // Jika slot di target day sudah ada dan tidak available (sudah dibooking orang lain)
        if ($targetSchedule && ! $targetSchedule->is_available && $targetSchedule->id !== $booking->schedule_id) {
            return back()->with('error', 'Gagal memindahkan jadwal: Slot jam ' . $targetSchedule->slot_time->format('H:i') . ' pada tanggal tersebut sudah terisi oleh pesanan pelanggan lain.');
        }

        if ($targetSchedule && $booking->schedule_id === $targetSchedule->id) {
            return back()->with('info', 'Booking sudah berada di slot jadwal yang dipilih.');
        }

        $oldScheduledAt = $booking->scheduled_at;
        $oldSchedule = $booking->schedule;

        DB::transaction(function () use ($booking, &$targetSchedule, $oldSchedule, $data): void {
            // 1. Tangani slot jadwal lama
            if ($oldSchedule) {
                if ($data['release_old_slot'] ?? true) {
                    $oldSchedule->update(['is_available' => true]);
                } else {
                    $oldSchedule->delete();
                }
            }

            // 2. Kunci atau buat slot jadwal baru
            if ($targetSchedule) {
                $targetSchedule->update(['is_available' => false]);
            } else {
                $targetSchedule = Schedule::create([
                    'barber_id'    => $data['target_barber_id'],
                    'date'         => $data['target_date'],
                    'slot_time'    => $data['target_time'],
                    'is_available' => false,
                ]);
                $targetSchedule->load('barber');
            }

            // 3. Pindahkan booking ke jadwal dan barber baru
            $dateStr = $targetSchedule->date instanceof \DateTimeInterface
                ? $targetSchedule->date->format('Y-m-d')
                : Carbon::parse($targetSchedule->date)->format('Y-m-d');
            $timeStr = $targetSchedule->slot_time instanceof \DateTimeInterface
                ? $targetSchedule->slot_time->format('H:i:s')
                : Carbon::parse($targetSchedule->slot_time)->format('H:i:s');
            $newDateTime = Carbon::parse("{$dateStr} {$timeStr}");

            $booking->update([
                'schedule_id'           => $targetSchedule->id,
                'barber_id'             => $targetSchedule->barber_id,
                'scheduled_at'          => $newDateTime,
                'requested_schedule_id' => null,
                'status'                => in_array($booking->status, ['reschedule_requested', 'pending'], true) ? 'confirmed' : $booking->status,
            ]);
        });

        // 4. Kirim notifikasi WhatsApp ke pelanggan jika dicentang
        if ($request->boolean('notify_customer', true)) {
            $notifications->bookingRescheduled($booking, $oldScheduledAt);
        }

        return back()->with('success', "Booking #BK-{$booking->id} ({$booking->name}) berhasil dipindahkan ke {$targetSchedule->barber->name} pada {$targetSchedule->date->format('d M Y')}, {$targetSchedule->slot_time->format('H:i')} WITA.");
    }

    private function weekStart(?string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date ?: now()->toDateString())->startOfWeek(CarbonImmutable::MONDAY);
    }
}
