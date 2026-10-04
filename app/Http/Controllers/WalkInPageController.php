<?php

namespace App\Http\Controllers;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Services\BookingNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalkInPageController extends Controller
{
    public function index()
    {
        $barbers = Barber::where('is_active', true)
            ->orderBy('name')->get(['id', 'name', 'role']);

        $today = Carbon::today()->toDateString();

        $walkIns = Booking::with(['barber:id,name,role', 'items.service:id,name', 'payment'])
            ->where('source', 'walk_in')
            ->whereDate('scheduled_at', $today)
            ->orderByDesc('scheduled_at')
            ->get();

        $services = Service::where('is_active', true)
            ->orderBy('category')->orderBy('name')
            ->get(['id', 'name', 'code', 'price', 'category']);

        $serviceCategories = $services
            ->groupBy(fn ($s) => $s->category ?: 'Other')
            ->map(fn ($items, $cat) => [
                'name'     => $cat,
                'services' => $items,
            ])->values();

        $availableSchedules = \App\Models\Schedule::where('date', $today)
            ->where('is_available', true)
            ->where('slot_time', '>=', now()->format('H:i:00'))
            ->orderBy('slot_time')
            ->get(['id', 'barber_id', 'slot_time'])
            ->map(function ($s) {
                return [
                    'barber_id' => $s->barber_id,
                    'time'      => \Carbon\Carbon::parse($s->slot_time)->format('H:i')
                ];
            });

        return view('walkin.page', [
            'barbers'           => $barbers,
            'walkIns'           => $walkIns,
            'serviceCategories' => $serviceCategories,
            'today'             => $today,
            'availableSchedules'=> $availableSchedules,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'barber_id'      => ['required', 'exists:barbers,id'],
            'name'           => ['required', 'string', 'max:255'],
            'booking_type'   => ['required', 'in:now,scheduled'],
            'scheduled_time' => ['nullable', 'required_if:booking_type,scheduled'],
            'phone'          => ['nullable', 'required_if:booking_type,scheduled', 'string', 'max:20'],
            'payment_type'   => ['nullable', 'in:full,dp'],
        ]);

        $barber = Barber::where('id', $data['barber_id'])
            ->where('is_active', true)->firstOrFail();

        $today = Carbon::today()->toDateString();
        $now   = now();
        $currentTime = $now->format('H:i:00');

        DB::transaction(function () use ($data, $barber, $today, $now, $currentTime) {
            $isScheduled = $data['booking_type'] === 'scheduled';
            
            if ($isScheduled) {
                // Booking dijadwalkan nanti
                $slotTime = $data['scheduled_time'] . ':00';
                
                $schedule = Schedule::where('barber_id', $barber->id)
                    ->where('date', $today)
                    ->where('slot_time', $slotTime)
                    ->first();
                    
                if (!$schedule) {
                    $schedule = Schedule::create([
                        'barber_id'    => $barber->id,
                        'date'         => $today,
                        'slot_time'    => $slotTime,
                        'is_available' => true,
                    ]);
                }
                
                if (!$schedule->is_available) {
                    throw ValidationException::withMessages([
                        'scheduled_time' => 'Jadwal pada jam tersebut sudah terisi. Silakan pilih jam lain.'
                    ]);
                }
                
            } else {
                // Booking sekarang
                $maxUpcomingTime = $now->copy()->addMinutes(30)->format('H:i:59');

                // 1. Cari slot available yang akan datang dalam rentang <= 30 menit
                $schedule = Schedule::where('barber_id', $barber->id)
                    ->where('date', $today)
                    ->where('is_available', true)
                    ->whereBetween('slot_time', [$currentTime, $maxUpcomingTime])
                    ->orderBy('slot_time')
                    ->first();

                // 2. Jika tidak ada slot available <= 30 menit, assign ke jam saat itu juga
                if (!$schedule) {
                    $schedule = Schedule::where('barber_id', $barber->id)
                        ->where('date', $today)
                        ->where('slot_time', $currentTime)
                        ->first();

                    if (!$schedule) {
                        $schedule = Schedule::create([
                            'barber_id'    => $barber->id,
                            'date'         => $today,
                            'slot_time'    => $currentTime,
                            'is_available' => true,
                        ]);
                    } elseif (!$schedule->is_available) {
                        // Fallback jika jam saat ini persis sedang terisi
                        for ($m = 1; $m <= 10; $m++) {
                            $altTime = $now->copy()->addMinutes($m)->format('H:i:00');
                            $alt = Schedule::firstOrCreate(
                                ['barber_id' => $barber->id, 'date' => $today, 'slot_time' => $altTime],
                                ['is_available' => true]
                            );
                            if ($alt->is_available) {
                                $schedule = $alt;
                                break;
                            }
                        }
                    }
                }
            }

            $slotTimeStr = $schedule->slot_time instanceof \DateTimeInterface
                ? $schedule->slot_time->format('H:i:s')
                : $schedule->slot_time;

            Booking::create([
                'schedule_id'        => $schedule->id,
                'barber_id'          => $barber->id,
                'name'               => $data['name'],
                'phone'              => $data['phone'] ?? null,
                'source'             => 'walk_in',
                'status'             => 'confirmed',
                'payment_type'       => $data['payment_type'] ?? 'full',
                'total_amount'       => 0,
                'outstanding_amount' => 0,
                'scheduled_at'       => $today . ' ' . $slotTimeStr,
            ]);

            $schedule->update(['is_available' => false]);
        });

        return redirect()->route('walkin.index')
            ->with('success', "Walk-in {$data['name']} berhasil didaftarkan.");
    }

    public function complete(
        Request $request,
        Booking $booking,
        BookingNotificationService $notifications
    ) {
        abort_if($booking->source !== 'walk_in', 404);
        abort_if($booking->status === 'completed', 422, 'Sudah dilengkapi.');

        $data = $request->validate([
            'service_ids'    => ['required', 'array', 'min:1'],
            'service_ids.*'  => ['integer', 'distinct', 'exists:services,id'],
            'payment_type'   => ['required', 'in:full,dp'],
            'payment_method' => ['required', 'in:cash,qris_static'],
        ], [
            'service_ids.required' => 'Wajib memilih minimal 1 layanan.',
        ]);

        DB::transaction(function () use ($data, $booking) {
            $booking->load('barber');
            $barber = $booking->barber;

            $services = Service::whereIn('id', $data['service_ids'])
                ->where('is_active', true)->get();
            abort_if($services->count() !== count($data['service_ids']), 422);

            // Owner price markup (konsisten dengan BookingAdminController)
            $serviceItems = $services->map(function ($svc) use ($barber) {
                $isOwnerRegular = strtolower($barber->role) === 'owner'
                    && str_contains(strtolower($svc->name), 'regular haircut');
                return [
                    'service' => $svc,
                    'name'    => $isOwnerRegular
                        ? preg_replace('/^regular\s+/i', '', $svc->name)
                          . ' - By ' . $barber->name
                        : $svc->name,
                    'price'   => $svc->price + ($isOwnerRegular ? 10000 : 0),
                ];
            });

            $total       = (int) $serviceItems->sum('price');
            $dpAmount    = (int) config('booking.dp_amount', 40000);
            $paid        = $data['payment_type'] === 'dp' ? $dpAmount : $total;
            $outstanding = max(0, $total - $paid);

            $booking->items()->createMany(
                $serviceItems->map(fn ($item) => [
                    'item_type'             => 'service',
                    'service_id'            => $item['service']->id,
                    'qty'                   => 1,
                    'service_name_snapshot' => $item['name'],
                    'price_snapshot'        => $item['price'],
                ])->all()
            );

            $payment = Payment::create([
                'amount'      => $paid,
                'method'      => $data['payment_method'],
                'provider'    => 'manual',
                'purpose'     => $data['payment_type'] === 'dp' ? 'dp' : 'walk_in',
                'status'      => 'paid',
                'recorded_by' => null,
            ]);

            $booking->update([
                'payment_id'         => $payment->id,
                'payment_type'       => $data['payment_type'],
                'total_amount'       => $total,
                'outstanding_amount' => $outstanding,
                'status'             => 'completed',
            ]);
        });

        // Kirim rekap harian ke grup WA ops (dengan label walk-in)
        $notifications->sendDailyRecapToOpsGroup(
            Carbon::parse($booking->scheduled_at)->toDateString()
        );

        return redirect()->route('walkin.index')
            ->with('success', "Walk-in {$booking->name} berhasil dilengkapi.");
    }

    public function cancel(Booking $booking)
    {
        abort_if($booking->source !== 'walk_in', 404);
        abort_if(
            in_array($booking->status, ['completed', 'cancelled']),
            422,
            'Booking tidak dapat dibatalkan.'
        );

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);

            if ($booking->schedule) {
                $booking->schedule->update(['is_available' => true]);
            }
        });

        return redirect()->route('walkin.index')
            ->with('success', "Walk-in {$booking->name} dibatalkan.");
    }
}
