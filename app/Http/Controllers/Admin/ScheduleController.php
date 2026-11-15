<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Models\Schedule;
use App\Services\BookingNotificationService;
use App\Services\SlotRuleService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
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
                ])->whereNotIn('status', ['cancelled'])])
                ->orderBy('slot_time')])
            ->orderBy('name')
            ->get();

        $days = collect(range(0, 6))->map(fn (int $offset) => $weekStart->addDays($offset));

        $allAvailableScheduleIds = [];
        $cellAvailableMap = [];

        foreach ($barbers as $b) {
            foreach ($days as $day) {
                $cellKey = $b->id . '_' . $day->toDateString();
                $cellSlots = $b->schedules
                    ->filter(fn ($s) => $s->date->isSameDay($day) && $s->is_available)
                    ->sortBy('slot_time')
                    ->values()
                    ->map(fn ($s) => [
                        'id' => $s->id,
                        'time' => $s->slot_time->format('H:i'),
                    ])
                    ->all();

                $cellAvailableMap[$cellKey] = $cellSlots;
                foreach ($cellSlots as $cs) {
                    $allAvailableScheduleIds[] = $cs['id'];
                }
            }
        }

        $templateBarbers = Barber::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $templateData = [];
        foreach ($templateBarbers as $tb) {
            $templateData[$tb->id] = [
                'id'     => $tb->id,
                'name'   => $tb->name,
                'role'   => ucfirst($tb->role),
                'active' => (bool) $tb->is_active,
                'days'   => $tb->getWeeklyTemplate(),
            ];
        }

        return view('admin.schedules.index', [
            'barbers' => $barbers,
            'allBarbers' => Barber::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => Barber::query()->where('is_active', true)->distinct()->orderBy('role')->pluck('role'),
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'days' => $days,
            'selectedRole' => $role,
            'selectedBarber' => $barberId,
            'allAvailableScheduleIds' => $allAvailableScheduleIds,
            'cellAvailableMap' => $cellAvailableMap,
            'templateBarbers' => $templateBarbers,
            'templateData' => $templateData,
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

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'schedule_ids'   => ['required', 'array', 'min:1'],
            'schedule_ids.*' => ['integer', 'exists:schedules,id'],
        ]);

        $deletedCount = Schedule::whereIn('id', $data['schedule_ids'])
            ->where('is_available', true)
            ->whereDoesntHave('bookings', function ($q) {
                $q->whereNotIn('status', ['cancelled']);
            })
            ->delete();

        if ($deletedCount === 0) {
            return back()->with('error', 'Tidak ada slot jadwal yang dapat dihapus (slot mungkin sudah terisi pesanan).');
        }

        return back()->with('success', "{$deletedCount} slot jadwal berhasil dihapus.");
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

    /**
     * Terapkan template jadwal mingguan statis (Rizal & Sogi) dengan opsi bersihkan slot kosong lama.
     */
    public function applyTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'week'           => ['required', 'date_format:Y-m-d'],
            'template_data'  => ['required'],
            'clean_unbooked' => ['nullable'],
        ]);

        $weekStart = $this->weekStart($data['week']);
        $cleanUnbooked = $request->boolean('clean_unbooked', true);

        $raw = $data['template_data'];
        $templates = is_array($raw) ? $raw : json_decode($raw, true);

        if (empty($templates) || !is_array($templates)) {
            return back()->with('error', 'Format data template tidak valid.');
        }

        $totalApplied = 0;

        DB::transaction(function () use ($templates, $weekStart, $cleanUnbooked, &$totalApplied): void {
            foreach ($templates as $barberId => $config) {
                if (empty($config['active'])) {
                    continue;
                }

                $barber = Barber::find($barberId);
                if (! $barber) {
                    continue;
                }

                // Ambil daftar slot per hari (offset 0: Senin s/d 6: Minggu)
                $days = $config['days'] ?? [];
                $cleanedDays = [];

                for ($offset = 0; $offset <= 6; $offset++) {
                    $rawTimes = $days[$offset] ?? [];
                    // Normalisasi jam ke format H:i
                    $times = array_values(array_filter(array_unique(array_map(function ($t) {
                        return substr(trim($t), 0, 5);
                    }, $rawTimes))));
                    sort($times);
                    $cleanedDays[$offset] = $times;

                    $dateStr = $weekStart->addDays($offset)->toDateString();

                    // 1. Jika opsi cleanUnbooked aktif: hapus slot lama yang tidak ada di template baru
                    // HANYA JIKA slot tersebut belum ada pesanan aktif (aman 100%)
                    if ($cleanUnbooked) {
                        $allTimeFormats = [];
                        foreach ($times as $t) {
                            $allTimeFormats[] = $t;
                            $allTimeFormats[] = "{$t}:00";
                        }

                        Schedule::where('barber_id', $barber->id)
                            ->where('date', $dateStr)
                            ->whereNotIn('slot_time', $allTimeFormats)
                            ->whereDoesntHave('bookings', function ($q) {
                                $q->whereNotIn('status', ['cancelled']);
                            })
                            ->where('is_available', true)
                            ->delete();
                    }

                    // 2. Buat atau perbarui ketersediaan slot sesuai template
                    foreach ($times as $timeStr) {
                        $existing = Schedule::where('barber_id', $barber->id)
                            ->where('date', $dateStr)
                            ->where(function ($q) use ($timeStr) {
                                $q->where('slot_time', $timeStr)
                                  ->orWhere('slot_time', "{$timeStr}:00");
                            })
                            ->first();

                        if ($existing) {
                            // Cek jika slot sudah terisi pesanan aktif, jangan ubah is_available
                            $hasActiveBookings = $existing->bookings()
                                ->whereNotIn('status', ['cancelled'])
                                ->exists();

                            if (! $hasActiveBookings) {
                                $existing->update(['is_available' => true]);
                            }
                        } else {
                            Schedule::create([
                                'barber_id'    => $barber->id,
                                'date'         => $dateStr,
                                'slot_time'    => $timeStr,
                                'is_available' => true,
                            ]);
                        }

                        $totalApplied++;
                    }
                }

                // Sinkronkan juga konfigurasi template barber ke database
                $barber->update([
                    'schedule_template' => $cleanedDays,
                ]);
            }
        });

        return back()->with('success', "Template jadwal berhasil diterapkan ({$totalApplied} slot jadwal aktif).");
    }

    /**
     * Simpan template jadwal mingguan barber ke database secara dinamis via AJAX / Form.
     */
    public function saveTemplate(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'templates' => ['required'],
        ]);

        $raw = $data['templates'];
        $templates = is_array($raw) ? $raw : json_decode($raw, true);

        if (empty($templates) || ! is_array($templates)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format data template tidak valid.',
                ], 422);
            }
            return back()->with('error', 'Format data template tidak valid.');
        }

        DB::transaction(function () use ($templates): void {
            foreach ($templates as $barberId => $config) {
                $barber = Barber::find($barberId);
                if (! $barber) {
                    continue;
                }

                $days = $config['days'] ?? [];
                $cleanedDays = [];

                for ($offset = 0; $offset <= 6; $offset++) {
                    $rawTimes = $days[$offset] ?? [];
                    $times = array_values(array_filter(array_unique(array_map(function ($t) {
                        return substr(trim($t), 0, 5);
                    }, $rawTimes))));
                    sort($times);
                    $cleanedDays[$offset] = $times;
                }

                $barber->update([
                    'schedule_template' => $cleanedDays,
                ]);
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Template jadwal mingguan berhasil disimpan!',
            ]);
        }

        return back()->with('success', 'Template jadwal mingguan berhasil disimpan!');
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

        $booking = Booking::with(['schedule', 'barber', 'items.service', 'blockedSlots.schedule'])->findOrFail($data['booking_id']);

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

        $selfScheduleIds = array_values(array_filter([
            $booking->schedule_id,
            $booking->requested_schedule_id,
            ...$booking->blockedSlots->pluck('schedule_id')->all(),
        ]));

        // Jika slot di target day sudah ada dan tidak available (sudah dibooking orang lain)
        if ($targetSchedule && ! $targetSchedule->is_available && ! in_array($targetSchedule->id, $selfScheduleIds, true)) {
            return back()->with('error', 'Gagal memindahkan jadwal: Slot jam ' . $targetSchedule->slot_time->format('H:i') . ' pada tanggal tersebut sudah terisi oleh pesanan pelanggan lain.');
        }

        if ($targetSchedule && $booking->schedule_id === $targetSchedule->id) {
            return back()->with('info', 'Booking sudah berada di slot jadwal yang dipilih.');
        }

        // Hitung kebutuhan multi-slot di jadwal target
        $targetBarber = $targetSchedule?->barber ?? Barber::find($data['target_barber_id'] ?? $booking->barber_id);
        $bookingServices = $booking->items->pluck('service')->filter()->values();
        $extraSchedules = collect();
        $extraReason = BlockedSlot::REASON_CUSTOM_RULE;
        $extraNotes = 'Lanjutan multi-slot untuk ' . $booking->name;

        if ($bookingServices->isNotEmpty()) {
            $slotEval = app(SlotRuleService::class)->evaluate($targetBarber, $bookingServices, $booking->name);
            $extraNeeded = $slotEval['extra_slots'];
            if ($extraNeeded > 0) {
                $extraReason = $slotEval['reason'] ?? $slotEval['display_label'];
                $extraNotes = $slotEval['notes'];
            }
        } else {
            // Untuk walk-in sebelum complete (belum ada booking items), pertahankan jumlah blockedSlots sebelumnya
            $extraNeeded = $booking->blockedSlots->count();
            $firstBlocked = $booking->blockedSlots->first();
            if ($firstBlocked) {
                $extraReason = $firstBlocked->reason;
                $extraNotes = $firstBlocked->notes;
            }
        }

        $targetDateStr = $targetSchedule
            ? ($targetSchedule->date instanceof \DateTimeInterface ? $targetSchedule->date->format('Y-m-d') : Carbon::parse($targetSchedule->date)->format('Y-m-d'))
            : $data['target_date'];
        $targetTimeStr = $targetSchedule
            ? ($targetSchedule->slot_time instanceof \DateTimeInterface ? $targetSchedule->slot_time->format('H:i:s') : Carbon::parse($targetSchedule->slot_time)->format('H:i:s'))
            : Carbon::parse($data['target_time'])->format('H:i:s');
        $targetBarberId = $targetSchedule?->barber_id ?? $data['target_barber_id'];

        if ($extraNeeded > 0) {
            $candidates = Schedule::query()
                ->where('barber_id', $targetBarberId)
                ->whereDate('date', $targetDateStr)
                ->where('slot_time', '>', $targetTimeStr)
                ->orderBy('slot_time')
                ->limit($extraNeeded)
                ->get();

            if ($candidates->count() < $extraNeeded) {
                $totalNeeded = $extraNeeded + 1;
                return back()->with('error', "Gagal memindahkan jadwal: Booking ini membutuhkan {$totalNeeded} slot berurutan, namun slot setelah jam target tidak mencukupi.");
            }

            foreach ($candidates as $candidate) {
                if (! $candidate->is_available && ! in_array($candidate->id, $selfScheduleIds, true)) {
                    $cTime = $candidate->slot_time instanceof \DateTimeInterface
                        ? $candidate->slot_time->format('H:i')
                        : substr((string) $candidate->slot_time, 0, 5);
                    return back()->with('error', "Gagal memindahkan jadwal: Slot lanjutan ({$cTime}) sudah terisi oleh pesanan pelanggan lain.");
                }
            }

            $extraSchedules = $candidates;
        }

        $oldScheduledAt = $booking->scheduled_at;
        $oldSchedule = $booking->schedule;

        DB::transaction(function () use ($booking, &$targetSchedule, $oldSchedule, $data, $extraSchedules, $extraReason, $extraNotes): void {
            $keepIds = $extraSchedules->pluck('id')->all();

            // 0. Bebaskan blockedSlots lama milik booking ini yang tidak lagi terpakai
            foreach ($booking->blockedSlots()->with('schedule')->get() as $oldBlocked) {
                if (! in_array($oldBlocked->schedule_id, $keepIds, true)) {
                    if ($oldBlocked->schedule && $oldBlocked->schedule_id !== ($targetSchedule?->id)) {
                        $oldBlocked->schedule->update(['is_available' => true]);
                    }
                    $oldBlocked->delete();
                }
            }

            // 1. Tangani slot jadwal lama
            if ($oldSchedule && ! in_array($oldSchedule->id, $keepIds, true)) {
                if ($data['release_old_slot'] ?? true) {
                    $oldSchedule->update(['is_available' => true]);
                } else {
                    $oldSchedule->delete();
                }
            }

            // 1b. Jika ada slot yang sedang di-hold untuk reschedule namun tidak jadi dipakai, bebaskan kembali
            if ($booking->requested_schedule_id && (! $targetSchedule || $booking->requested_schedule_id !== $targetSchedule->id) && ! in_array($booking->requested_schedule_id, $keepIds, true)) {
                Schedule::whereKey($booking->requested_schedule_id)->update(['is_available' => true]);
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

            // 2b. Kunci slot lanjutan yang dibutuhkan
            foreach ($extraSchedules as $extraSchedule) {
                $extraSchedule->update(['is_available' => false]);
                BlockedSlot::updateOrCreate([
                    'schedule_id' => $extraSchedule->id,
                    'booking_id'  => $booking->id,
                ], [
                    'reason'     => $extraReason,
                    'notes'      => $extraNotes,
                    'created_by' => auth()->id(),
                ]);
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

        // 5. Kirim pesan Rekap Agenda Harian ke Grup Operasional WhatsApp
        if ($booking->scheduled_at) {
            $notifications->sendDailyRecapToOpsGroup($booking->scheduled_at);

            if ($oldScheduledAt) {
                $oldDate = Carbon::parse($oldScheduledAt)->toDateString();
                $newDate = Carbon::parse($booking->scheduled_at)->toDateString();
                if ($oldDate !== $newDate) {
                    $notifications->sendDailyRecapToOpsGroup($oldDate);
                }
            }
        }

        return back()->with('success', "Booking #BK-{$booking->id} ({$booking->name}) berhasil dipindahkan ke {$targetSchedule->barber->name} pada {$targetSchedule->date->format('d M Y')}, {$targetSchedule->slot_time->format('H:i')} WITA.");
    }

    private function weekStart(?string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date ?: now()->toDateString())->startOfWeek(CarbonImmutable::MONDAY);
    }
}
