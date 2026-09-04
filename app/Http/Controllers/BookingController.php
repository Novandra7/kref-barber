<?php

namespace App\Http\Controllers;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Services\DokuService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function checkout(Request $request, DokuService $doku): JsonResponse
    {
        $data = $request->validate([
            'payment_type' => ['required', 'in:DP,Full'],
            'guests' => ['required', 'array', 'min:1'],
            'guests.*.name' => ['required', 'string', 'max:255'],
            'guests.*.phone' => ['required', 'string', 'max:30'],
            'guests.*.barber' => ['required'],
            'guests.*.date' => ['required', 'date_format:Y-m-d'],
            'guests.*.time' => ['required', 'date_format:H:i'],
            'guests.*.selectedHaircut' => ['nullable', 'string'],
            'guests.*.selectedChemical' => ['nullable', 'string'],
            'guests.*.selectedTreatments' => ['array'],
            'guests.*.selectedTreatments.*' => ['string'],
        ]);

        $payments = DB::transaction(function () use ($data, $doku) {
            $bookingPayments = [];
            $totalAmount = 0;

            foreach ($data['guests'] as $guest) {
                $barber = Barber::where('is_active', true)
                    ->where(fn ($query) => $query
                        ->whereKey($guest['barber'])
                        ->orWhere('name', $guest['barber']))
                    ->firstOrFail();

                $schedule = Schedule::where('barber_id', $barber->id)
                    ->whereDate('date', $guest['date'])
                    ->whereTime('slot_time', $guest['time'])
                    ->where('is_available', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                $services = collect([
                    $guest['selectedHaircut'] ?? null,
                    $guest['selectedChemical'] ?? null,
                    ...($guest['selectedTreatments'] ?? []),
                ])->filter()->map(function (string $name) use ($barber) {
                    $service = Service::where('is_active', true)->where('name', $name)->firstOrFail();
                    $isOwnerRegular = strtolower((string) $barber->role) === 'owner'
                        && str_contains(strtolower($service->name), 'regular');

                    return [
                        'service' => $service,
                        'price' => $service->price + ($isOwnerRegular ? 10000 : 0),
                        'name' => $isOwnerRegular ? $service->name . ' - By Owner' : $service->name,
                    ];
                });

                if ($services->isEmpty()) {
                    abort(422, 'At least one service is required for every guest.');
                }

                $guestTotal = $services->sum('price');
                $booking = Booking::create([
                    'source' => 'online',
                    'payment_type' => strtolower($data['payment_type']) === 'dp' ? 'dp' : 'full',
                    'status' => 'waiting_payment',
                    'payment_status' => 'unpaid',
                    'walk_in_customer_name' => $guest['name'],
                    'walk_in_customer_phone' => $guest['phone'],
                    'barber_id' => $barber->id,
                    'total_amount' => $guestTotal,
                    'outstanding_amount' => $guestTotal,
                    'scheduled_at' => $guest['date'] . ' ' . $guest['time'],
                ]);

                foreach ($services as $item) {
                    BookingItem::create([
                        'booking_id' => $booking->id,
                        'item_type' => 'service',
                        'service_id' => $item['service']->id,
                        'qty' => 1,
                        'service_name_snapshot' => $item['name'],
                        'price_snapshot' => $item['price'],
                    ]);
                }

                $totalAmount += $guestTotal;
                $bookingPayments[] = [$booking, $guestTotal];
                $schedule->update(['is_available' => false]);
            }

            $amount = strtolower($data['payment_type']) === 'dp' ? 40000 : $totalAmount;
            if ($amount > $totalAmount) {
                abort(422, 'DP amount cannot exceed the booking total.');
            }

            $reference = 'KREF-' . Str::upper(Str::random(14));
            $response = $doku->createQrisPayment(
                $reference,
                $amount,
                route('booking.payment.return', ['reference' => $reference])
            );
            $qrContent = data_get($response, 'qrContent')
                ?? data_get($response, 'qrContentString')
                ?? data_get($response, 'qrCode');
            $paymentUrl = data_get($response, 'paymentUrl') ?? data_get($response, 'redirectUrl');
            $providerId = data_get($response, 'referenceNo') ?? data_get($response, 'partnerReferenceNo');

            $remainingAmount = $amount;
            foreach ($bookingPayments as [$booking, $guestTotal]) {
                $paymentAmount = min($remainingAmount, $guestTotal);
                Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $paymentAmount,
                    'method' => 'qris_static',
                    'provider' => 'doku',
                    'purpose' => $data['payment_type'] === 'DP' ? 'dp' : 'full_payment',
                    'status' => 'pending',
                    'provider_transaction_id' => $providerId,
                    'external_reference' => $reference,
                    'payment_url' => $paymentUrl,
                    'qr_content' => $qrContent,
                    'provider_payload' => $response,
                ]);
                $remainingAmount -= $paymentAmount;
            }

            return compact('reference', 'amount', 'qrContent', 'paymentUrl', 'response');
        });

        return response()->json($payments, 201);
    }

    public function paymentReturn(string $reference): JsonResponse
    {
        return $this->paymentStatus($reference);
    }

    public function paymentStatus(string $reference): JsonResponse
    {
        $payments = Payment::where('provider', 'doku')
            ->where('external_reference', $reference)
            ->get();

        abort_if($payments->isEmpty(), 404, 'Payment not found.');

        return response()->json([
            'reference' => $reference,
            'status' => $payments->contains(fn (Payment $payment) => $payment->status === 'paid')
                ? 'paid'
                : $payments->first()->status,
            'payments' => $payments->map(fn (Payment $payment) => [
                'booking_id' => $payment->booking_id,
                'status' => $payment->status,
            ]),
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $secret = config('services.doku.webhook_secret');
        $provided = (string) $request->header('X-SIGNATURE');
        $expected = $secret
            ? hash_hmac('sha256', $request->getContent(), $secret)
            : '';

        abort_unless($secret && $provided && hash_equals($expected, $provided), 401, 'Invalid DOKU signature.');

        $reference = $request->input('partnerReferenceNo')
            ?? $request->input('originalPartnerReferenceNo')
            ?? $request->input('externalReference');
        $status = strtolower((string) ($request->input('transactionStatusDesc') ?? $request->input('status')));
        $paymentStatus = match (true) {
            in_array($status, ['success', 'paid', 'settled'], true) => 'paid',
            in_array($status, ['failed', 'expired', 'cancelled'], true) => $status,
            default => 'pending',
        };

        DB::transaction(function () use ($reference, $paymentStatus) {
            Payment::where('provider', 'doku')
                ->where('external_reference', $reference)
                ->lockForUpdate()
                ->get()
                ->each(function (Payment $payment) use ($paymentStatus) {
                    $payment->update(['status' => $paymentStatus]);
                    $booking = $payment->booking()->lockForUpdate()->first();
                    $paid = $booking->payments()->where('status', 'paid')->sum('amount');
                    $booking->update([
                        'payment_status' => $paid >= $booking->total_amount ? 'paid_full' : ($paid > 0 ? 'partial' : 'unpaid'),
                        'outstanding_amount' => max(0, $booking->total_amount - $paid),
                        'status' => $paid >= $booking->total_amount ? 'paid' : 'waiting_payment',
                    ]);
                });
        });

        return response()->json(['received' => true]);
    }
}
