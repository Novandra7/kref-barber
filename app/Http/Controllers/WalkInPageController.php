<?php

namespace App\Http\Controllers;

use App\Models\Barber;
use App\Models\BlockedSlot;
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
    public function index(Request $request)
    {
        $barbers = Barber::where('is_active', true)
            ->orderBy('name')->get(['id', 'name', 'role']);

        $today = Carbon::today()->toDateString();

        $query = Booking::with(['barber:id,name,role', 'items', 'items.service:id,name,price', 'payment'])
            ->whereDate('scheduled_at', $today)
            ->where(function ($q) {
                $q->where('source', 'walk_in')
                  ->orWhere(function ($onlineQuery) {
                      $onlineQuery->where('source', 'online')
                                  ->whereIn('status', ['confirmed', 'completed', 'cancelled']);
                  });
            });

        if ($request->filled('barber')) {
            $query->where('barber_id', $request->input('barber'));
        }

        if ($request->filled('source') && in_array($request->input('source'), ['walk_in', 'online'])) {
            $query->where('source', $request->input('source'));
        }

        $walkIns = $query->orderByDesc('scheduled_at')->get();

        $services = Service::where('is_active', true)
            ->orderBy('category')->orderBy('name')
            ->get(['id', 'name', 'code', 'price', 'category']);

        $serviceCategories = $services
            ->groupBy(fn ($s) => $s->category ?: 'Other')
            ->map(fn ($items, $cat) => [
                'name'     => $cat,
                'services' => $items,
            ])->values();

        $availableSchedules = Schedule::where('date', $today)
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
            'barbers'            => $barbers,
            'walkIns'            => $walkIns,
            'serviceCategories'  => $serviceCategories,
            'today'              => $today,
            'availableSchedules' => $availableSchedules,
            'selectedBarber'     => $request->input('barber'),
            'selectedSource'     => $request->input('source'),
        ]);
    }

    public function store(Request $request, BookingNotificationService $notifications)
    {
        $data = $request->validate([
            'barber_id'      => ['required', 'exists:barbers,id'],
            'name'           => ['required', 'string', 'max:255'],
            'booking_type'   => ['required', 'in:now,scheduled'],
            'is_chemical'    => ['nullable', 'boolean'],
            'scheduled_time' => ['nullable', 'required_if:booking_type,scheduled'],
            'phone'          => ['nullable', 'required_if:booking_type,scheduled', 'string', 'max:20'],
            'payment_type'   => ['nullable', 'in:full,dp'],
            'dp_amount'          => ['nullable', 'required_if:payment_type,dp', 'numeric', 'min:1000'],
            'dp_payment_method'  => ['nullable', 'required_if:payment_type,dp', 'in:cash,qris_static'],
        ]);

        if (!empty($data['phone'])) {
            $normalizedPhone = preg_replace('/[^0-9]/', '', (string) $data['phone']);
            if (str_starts_with($normalizedPhone, '62')) {
                $normalizedPhone = substr($normalizedPhone, 2);
            }
            $data['phone'] = ltrim($normalizedPhone, '0') ?: null;
        }

        $barber = Barber::where('id', $data['barber_id'])
            ->where('is_active', true)->firstOrFail();

        $today = Carbon::today()->toDateString();
        $now   = now();
        $currentTime = $now->format('H:i:00');
        $isChemical = $request->boolean('is_chemical');

        DB::transaction(function () use ($data, $barber, $today, $now, $currentTime, $isChemical) {
            $isScheduled = $data['booking_type'] === 'scheduled';
            
            if ($isScheduled) {
                // Booking dijadwalkan nanti
                $slotTime = $data['scheduled_time'] . ':00';
                
                $schedule = Schedule::where('barber_id', $barber->id)
                    ->whereDate('date', $today)
                    ->whereTime('slot_time', $slotTime)
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
                    ->whereDate('date', $today)
                    ->where('is_available', true)
                    ->whereBetween('slot_time', [$currentTime, $maxUpcomingTime])
                    ->orderBy('slot_time')
                    ->first();

                // 2. Jika tidak ada slot available <= 30 menit, assign ke jam saat itu juga
                if (!$schedule) {
                    $schedule = Schedule::where('barber_id', $barber->id)
                        ->whereDate('date', $today)
                        ->whereTime('slot_time', $currentTime)
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

            // Jika is_chemical, cari slot jadwal berikutnya untuk barber ini pada hari ini
            $nextSchedule = null;
            if ($isChemical) {
                $existingNext = Schedule::where('barber_id', $barber->id)
                    ->whereDate('date', $today)
                    ->where('slot_time', '>', $slotTimeStr)
                    ->orderBy('slot_time')
                    ->lockForUpdate()
                    ->first();

                if ($existingNext) {
                    if (!$existingNext->is_available) {
                        $nextTimeDisplay = $existingNext->slot_time instanceof \DateTimeInterface
                            ? $existingNext->slot_time->format('H:i')
                            : substr((string) $existingNext->slot_time, 0, 5);
                        throw ValidationException::withMessages([
                            'scheduled_time' => "Slot jadwal berikutnya ({$nextTimeDisplay}) untuk layanan chemical sudah terisi. Silakan pilih jam lain."
                        ]);
                    }
                    $nextSchedule = $existingNext;
                }
            }

            $paymentType = $data['payment_type'] ?? 'full';
            $paymentId   = null;
            $totalAmount = 0;
            $outstandingAmount = 0;

            // Jika dijadwalkan dengan DP, buat Payment langsung
            if ($isScheduled && $paymentType === 'dp') {
                $dpAmount = (int) $data['dp_amount'];
                $dpPayment = Payment::create([
                    'amount'      => $dpAmount,
                    'method'      => $data['dp_payment_method'],
                    'provider'    => 'manual',
                    'purpose'     => 'dp',
                    'status'      => 'paid',
                    'recorded_by' => null,
                ]);
                $paymentId       = $dpPayment->id;
                $totalAmount     = $dpAmount;
                $outstandingAmount = 1; // Tandai belum lunas (akan dihitung ulang saat complete)
            }

            $booking = Booking::create([
                'schedule_id'        => $schedule->id,
                'barber_id'          => $barber->id,
                'name'               => $data['name'],
                'phone'              => $data['phone'] ?? null,
                'source'             => 'walk_in',
                'status'             => 'confirmed',
                'payment_type'       => $paymentType,
                'payment_id'         => $paymentId,
                'total_amount'       => $totalAmount,
                'outstanding_amount' => $outstandingAmount,
                'scheduled_at'       => $today . ' ' . $slotTimeStr,
            ]);

            $schedule->update(['is_available' => false]);

            if ($isChemical && $nextSchedule) {
                $nextSchedule->update(['is_available' => false]);
                BlockedSlot::create([
                    'schedule_id' => $nextSchedule->id,
                    'booking_id'  => $booking->id,
                    'reason'      => BlockedSlot::REASON_CHEMICAL,
                    'notes'       => 'Lanjutan pengerjaan chemical untuk walk-in ' . $data['name'],
                ]);
            }
        });

        // Kirim rekap ke grup WA ops saat walk-in didaftarkan (baik Sekarang maupun Dijadwalkan)
        $notifications->sendDailyRecapToOpsGroup($today);

        return redirect()->route('walkin.index')
            ->with('success', "Walk-in {$data['name']} berhasil didaftarkan.");
    }

    public function complete(
        Request $request,
        Booking $booking
    ) {
        abort_if(!in_array($booking->source, ['walk_in', 'online']), 404);
        abort_if($booking->status === 'completed', 422, 'Sudah dilengkapi.');
        abort_if($booking->status === 'cancelled', 422, 'Booking yang dibatalkan tidak dapat dilengkapi.');

        $booking->load(['payment', 'barber', 'items']);
        $hasDp = ($booking->payment_type === 'dp' || ($booking->payment && $booking->payment->purpose === 'dp'));
        $isPrepaidFull = (!$hasDp && $booking->payment && $booking->payment->status === 'paid' && in_array($booking->payment->purpose, ['full_payment', 'walk_in']));
        $prepaidAmount = $hasDp
            ? ($booking->payment ? (int) $booking->payment->amount : 0)
            : ($isPrepaidFull ? ($booking->payment ? (int) $booking->payment->amount : (int) $booking->total_amount) : 0);

        // Validasi dinamis berdasarkan apakah sudah ada pembayaran (DP atau Online Full)
        $rules = [
            'service_ids'     => ['required', 'array', 'min:1'],
            'service_ids.*'   => ['integer', 'distinct', 'exists:services,id'],
            'custom_prices'   => ['nullable', 'array'],
            'custom_prices.*' => ['nullable', 'integer', 'min:0'],
            'barber_notes'    => ['nullable', 'string', 'max:1000'],
            'description'     => ['nullable', 'string', 'max:1000'],
        ];

        if ($hasDp || $isPrepaidFull) {
            $rules['settlement_payment_method'] = ['nullable', 'in:cash,qris_static'];
        } else {
            $rules['payment_type']   = ['required', 'in:full,dp'];
            $rules['payment_method'] = ['required', 'in:cash,qris_static'];
        }

        $data = $request->validate($rules, [
            'service_ids.required' => 'Wajib memilih minimal 1 layanan.',
        ]);

        DB::transaction(function () use ($data, $booking, $hasDp, $isPrepaidFull, $prepaidAmount) {
            $barber = $booking->barber;
            $customPrices = $data['custom_prices'] ?? [];

            $services = Service::whereIn('id', $data['service_ids'])
                ->where('is_active', true)->get();
            abort_if($services->count() !== count($data['service_ids']), 422);

            // Owner price markup (konsisten dengan BookingAdminController) & custom price adjustments (e.g. Long Trim)
            $serviceItems = $services->map(function ($svc) use ($barber, $customPrices) {
                $isOwnerRegular = strtolower($barber->role) === 'owner'
                    && str_contains(strtolower($svc->name), 'regular haircut');
                $basePrice = $svc->price + ($isOwnerRegular ? 10000 : 0);
                $finalPrice = $basePrice;

                if (isset($customPrices[$svc->id]) && is_numeric($customPrices[$svc->id])) {
                    $custom = (int) $customPrices[$svc->id];
                    // Custom price hanya berlaku jika >= harga dasar
                    if ($custom >= $basePrice) {
                        $finalPrice = $custom;
                    }
                }

                return [
                    'service' => $svc,
                    'name'    => $isOwnerRegular
                        ? preg_replace('/^regular\s+/i', '', $svc->name)
                          . ' - By ' . $barber->name
                        : $svc->name,
                    'price'   => $finalPrice,
                ];
            });

            $total = (int) $serviceItems->sum('price');

            // Sync booking items (delete existing and recreate to match final selection)
            $booking->items()->delete();
            $booking->items()->createMany(
                $serviceItems->map(fn ($item) => [
                    'item_type'             => 'service',
                    'service_id'            => $item['service']->id,
                    'qty'                   => 1,
                    'service_name_snapshot' => $item['name'],
                    'price_snapshot'        => $item['price'],
                ])->all()
            );

            if ($hasDp || $isPrepaidFull) {
                // Booking sudah memiliki pembayaran di awal (DP atau Online Full) - hitung sisa (memperhitungkan tip barber jika ada)
                $tipAmount  = (int) ($booking->tip_amount ?? 0);
                $grandTotal = $total + $tipAmount;
                $sisa       = max(0, $grandTotal - $prepaidAmount);

                if ($sisa > 0) {
                    Payment::create([
                        'amount'               => $sisa,
                        'method'               => $data['settlement_payment_method'] ?? 'cash',
                        'provider'             => 'manual',
                        'purpose'              => 'pelunasan',
                        'status'               => 'paid',
                        'recorded_by'          => null,
                        'partner_reference_no' => 'PELUNASAN-BK-' . $booking->id,
                        'provider_payload'     => [
                            'booking_id'         => $booking->id,
                            'initial_payment_id' => $booking->payment_id,
                        ],
                    ]);
                }

                $updateData = [
                    'total_amount'       => $total,
                    'outstanding_amount' => 0,
                    'status'             => 'completed',
                ];
                if (array_key_exists('barber_notes', $data)) {
                    $updateData['barber_notes'] = $data['barber_notes'];
                }
                if (array_key_exists('description', $data) && empty($booking->description)) {
                    $updateData['description'] = $data['description'];
                }
                $booking->update($updateData);
            } else {
                // Booking tanpa pembayaran awal (Sekarang / Jadwalkan Full)
                $dpAmount    = (int) config('booking.dp_amount', 40000);
                $paid        = $data['payment_type'] === 'dp' ? $dpAmount : $total;
                $outstanding = max(0, $total - $paid);

                $payment = Payment::create([
                    'amount'      => $paid,
                    'method'      => $data['payment_method'],
                    'provider'    => 'manual',
                    'purpose'     => $data['payment_type'] === 'dp' ? 'dp' : 'walk_in',
                    'status'      => 'paid',
                    'recorded_by' => null,
                ]);

                $updateData = [
                    'payment_id'         => $payment->id,
                    'payment_type'       => $data['payment_type'],
                    'total_amount'       => $total,
                    'outstanding_amount' => $outstanding,
                    'status'             => 'completed',
                ];
                if (array_key_exists('barber_notes', $data)) {
                    $updateData['barber_notes'] = $data['barber_notes'];
                }
                if (array_key_exists('description', $data) && empty($booking->description)) {
                    $updateData['description'] = $data['description'];
                }
                $booking->update($updateData);
            }
        });

        $label = $booking->source === 'online' ? 'Booking online' : 'Walk-in';
        return redirect()->back()
            ->with('success', "{$label} {$booking->name} berhasil dilengkapi.");
    }

    public function sendRecap(BookingNotificationService $notifications)
    {
        $today = Carbon::today()->toDateString();
        $notifications->sendDailyRecapToOpsGroup($today);

        return redirect()->back()
            ->with('success', 'Rekapan hari ini berhasil dikirim ke grup WhatsApp.');
    }

    public function cancel(Booking $booking, BookingNotificationService $notifications)
    {
        abort_if(!in_array($booking->source, ['walk_in', 'online']), 404);
        abort_if(
            in_array($booking->status, ['completed', 'cancelled']),
            422,
            'Booking tidak dapat dibatalkan.'
        );

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);

            $booking->releaseSchedules();
        });

        // Kirim update rekap ke grup WA ops (slot sudah kosong)
        $notifications->sendDailyRecapToOpsGroup(
            Carbon::parse($booking->scheduled_at)->toDateString()
        );

        $label = $booking->source === 'online' ? 'Booking online' : 'Walk-in';
        return redirect()->back()
            ->with('success', "{$label} {$booking->name} dibatalkan.");
    }
}
