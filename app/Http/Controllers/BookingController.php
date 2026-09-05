<?php

namespace App\Http\Controllers;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Services\DokuService;
use App\Services\BookingNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    /**
     * Menampilkan halaman booking beserta data service, barber,
     * dan jadwal yang tersedia.
     */
    public function index(Request $request): View
    {
        $services = Service::where('is_active', true)->get();
        $barbers  = Barber::where('is_active', true)->get();

        $selectedDate = $request->input('date');

        $schedules = Schedule::orderBy('date')
            ->orderBy('slot_time')
            ->get();

        $scheduleData = $schedules->map(fn (Schedule $schedule) => [
            'id'           => $schedule->id,
            'barber_id'    => $schedule->barber_id,
            'date'         => $schedule->date->format('Y-m-d'),
            'slot_time'    => $schedule->slot_time->format('H:i'),
            'is_available' => $schedule->is_available,
        ])->values();

        $availableDates = $scheduleData->pluck('date')
            ->unique()
            ->values();

        // Fallback ke tanggal pertama yang tersedia jika tanggal
        // yang dipilih tidak valid / tidak ada di daftar.
        if (!$selectedDate || !$availableDates->contains($selectedDate)) {
            $selectedDate = $availableDates->first() ?? now()->toDateString();
        }

        return view('booking', compact('services', 'barbers', 'scheduleData', 'availableDates', 'selectedDate'));
    }

    /**
     * Memproses checkout booking: validasi input, membuat booking + item
     * per tamu, membuat pembayaran QRIS via DOKU, lalu mengirim notifikasi.
     */
    public function checkout(
        Request $request,
        DokuService $doku,
        BookingNotificationService $notifications,
    ): JsonResponse {

        // -----------------------------------------------------------
        // 1. Validasi input
        // -----------------------------------------------------------
        $data = $request->validate([
            'payment_type'                      => ['required', 'in:DP,Full'],
            'guests'                            => ['required', 'array', 'min:1'],
            'guests.*.name'                     => ['required', 'string', 'max:255'],
            'guests.*.phone'                    => ['required', 'string', 'max:30'],
            'guests.*.barber'                   => ['required'],
            'guests.*.date'                     => ['required', 'date_format:Y-m-d'],
            'guests.*.time'                     => ['required', 'date_format:H:i'],
            'guests.*.selectedHaircut'          => ['nullable', 'string'],
            'guests.*.selectedChemical'         => ['nullable', 'string'],
            'guests.*.selectedTreatments'       => ['array'],
            'guests.*.selectedTreatments.*'     => ['string'],
        ]);

        // -----------------------------------------------------------
        // 2. Buat booking + booking item untuk setiap tamu, di dalam
        //    satu transaksi DB agar konsisten.
        // -----------------------------------------------------------
        $checkout = DB::transaction(function () use ($data, $doku) {
            $bookingPayments = [];
            $totalAmount = 0;

            foreach ($data['guests'] as $guest) {

                // --- Cari barber (berdasarkan id atau nama) ---
                $barber = Barber::where('is_active', true)
                    ->where(fn ($query) => $query
                        ->whereKey($guest['barber'])
                        ->orWhere('name', $guest['barber']))
                    ->firstOrFail();

                // --- Kunci jadwal yang dipilih agar tidak double booking ---
                $schedule = Schedule::where('barber_id', $barber->id)
                    ->whereDate('date', $guest['date'])
                    ->whereTime('slot_time', $guest['time'])
                    ->where('is_available', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                // --- Kumpulkan semua service yang dipilih tamu ini ---
                // (haircut, chemical, dan treatment tambahan)
                $services = collect([
                    $guest['selectedHaircut'] ?? null,
                    $guest['selectedChemical'] ?? null,
                    ...($guest['selectedTreatments'] ?? []),
                ])->filter()->map(function (string $name) use ($barber) {
                    $service = Service::where('is_active', true)->where('name', $name)->firstOrFail();

                    // Owner mengenakan biaya tambahan khusus untuk "regular haircut"
                    $isOwnerRegular = strtolower((string) $barber->role) === 'owner'
                        && str_contains(strtolower($service->name), 'regular haircut');

                    return [
                        'service' => $service,
                        'price'   => $service->price + ($isOwnerRegular ? 10000 : 0),
                        'name'    => $isOwnerRegular
                            ? preg_replace('/^regular\s+/i', '', $service->name) . ' - By ' . $barber->name
                            : $service->name,
                    ];
                });

                if ($services->isEmpty()) {
                    abort(422, 'At least one service is required for every guest.');
                }

                // --- Buat record booking untuk tamu ini ---
                $guestTotal = $services->sum('price');
                $booking = Booking::create([
                    'schedule_id'        => $schedule->id,
                    'source'             => 'online',
                    'payment_type'       => strtolower($data['payment_type']) === 'dp' ? 'dp' : 'full',
                    'status'             => 'pending',
                    'payment_status'     => 'unpaid',
                    'name'               => $guest['name'],
                    'phone'              => $guest['phone'],
                    'barber_id'          => $barber->id,
                    'total_amount'       => $guestTotal,
                    'outstanding_amount' => $guestTotal,
                    'scheduled_at'       => $guest['date'] . ' ' . $guest['time'],
                ]);

                // --- Simpan snapshot tiap service sebagai booking item ---
                foreach ($services as $item) {
                    BookingItem::create([
                        'booking_id'             => $booking->id,
                        'item_type'              => 'service',
                        'service_id'             => $item['service']->id,
                        'qty'                    => 1,
                        'service_name_snapshot'  => $item['name'],
                        'price_snapshot'         => $item['price'],
                    ]);
                }

                $totalAmount += $guestTotal;
                $bookingPayments[] = [$booking, $guestTotal];

                // Tandai jadwal sebagai sudah tidak tersedia
                $schedule->update(['is_available' => false]);
            }

            // --- Hitung jumlah yang harus dibayar (DP atau Full) ---
            $amount = strtolower($data['payment_type']) === 'dp' ? 40000 : $totalAmount;
            if ($amount > $totalAmount) {
                abort(422, 'DP amount cannot exceed the booking total.');
            }

            // -------------------------------------------------------
            // 3. Buat pembayaran QRIS DOKU
            // -------------------------------------------------------
            $reference = 'KREF-' . Str::upper(Str::random(14));
            $response = $doku->createQrisPayment($reference, $amount);

            $qrContent       = data_get($response, 'qrContent');
            $providerId      = data_get($response, 'referenceNo') ?? data_get($response, 'partnerReferenceNo');
            $dokuPaymentUrl  = data_get($response, 'paymentUrl') ?? data_get($response, 'redirectUrl');

            abort_if(! $qrContent, 502, 'DOKU did not return QRIS content.');

            // Buat URL default jika DOKU tidak mengembalikan paymentUrl
            $paymentUrl = $dokuPaymentUrl ?: route('booking.payment.return', ['reference' => $reference]);

            $remainingAmount = $amount;
            $validityPeriod  = data_get($response, 'additionalInfo.validityPeriod');

            $expiresAt = $validityPeriod
                ? Carbon::parse($validityPeriod)->setTimezone(config('app.timezone'))
                : now()->addMinutes(30);

            // --- Distribusikan jumlah pembayaran ke tiap booking tamu ---
            foreach ($bookingPayments as $index => [$booking, $guestTotal]) {
                $paymentAmount = min($remainingAmount, $guestTotal);

                Payment::create([
                    'booking_id'          => $booking->id,
                    'amount'              => $paymentAmount,
                    'method'              => 'qris_doku',
                    'provider'            => 'doku',
                    'purpose'             => $data['payment_type'] === 'DP' ? 'dp' : 'full_payment',
                    'status'              => 'pending',
                    'partner_reference_no'=> $index === 0 ? $reference : null,
                    'doku_reference_no'   => $providerId,
                    'payment_url'         => $paymentUrl,
                    'expires_at'          => $response['validityPeriod'] ?? $expiresAt,
                    'qr_content'          => $qrContent,
                    'provider_payload'    => array_merge($response, [
                        'partnerReferenceNo' => $reference,
                    ]),
                ]);

                $remainingAmount -= $paymentAmount;
            }

            return [
                'reference'  => $reference,
                'amount'     => $amount,
                'qrContent'  => $qrContent,
                'paymentUrl' => $paymentUrl,
                'bookingIds' => collect($bookingPayments)->map(fn (array $item) => $item[0]->id)->values()->all(),
                'response'   => $response,
            ];
        });

        // -----------------------------------------------------------
        // 4. Kirim notifikasi booking dibuat (di luar transaksi DB)
        // -----------------------------------------------------------
        $booking = Booking::with('barber')->find($checkout['bookingIds'][0] ?? null);
        if ($booking) {
            $notifications->bookingCreated($booking, $checkout['reference'], $checkout['paymentUrl']);
        }

        return response()->json($checkout, 201);
    }

    /**
     * Halaman redirect setelah user selesai membayar (dipanggil oleh DOKU
     * atau saat user kembali secara manual). Menampilkan status pembayaran.
     */
    public function paymentReturn(string $reference): View
    {
        $payments = Payment::where('provider', 'doku')
            ->where(function ($query) use ($reference): void {
                $query->where('partner_reference_no', $reference)
                    ->orWhereJsonContains('provider_payload->partnerReferenceNo', $reference);
            })
            ->get();

        abort_if($payments->isEmpty(), 404, 'Payment not found.');

        $bookings = Booking::with('barber')
            ->whereIn('id', $payments->pluck('booking_id'))
            ->get();

        // Jika ada minimal satu payment yang sudah "paid", anggap status keseluruhan "paid"
        $status = $payments->contains(fn (Payment $payment) => $payment->status === 'paid')
            ? 'paid'
            : $payments->first()->status;

        $qrContent = $payments->pluck('qr_content')->filter()->first();

        return view('booking.payment-status', [
            'reference'     => $reference,
            'status'        => $status,
            'qrContent'     => $qrContent,
            'paymentAmount' => $payments->sum('amount'),
            'bookings'      => $bookings,
        ]);
    }

    /**
     * Endpoint API untuk polling status pembayaran dari frontend.
     */
    public function paymentStatus(string $reference): JsonResponse
    {
        $payments = Payment::where('provider', 'doku')
            ->where(function ($query) use ($reference): void {
                $query->where('partner_reference_no', $reference)
                    ->orWhereJsonContains('provider_payload->partnerReferenceNo', $reference);
            })
            ->get();

        abort_if($payments->isEmpty(), 404, 'Payment not found.');

        return response()->json([
            'reference' => $reference,
            'status'    => $payments->contains(fn (Payment $payment) => $payment->status === 'paid')
                ? 'paid'
                : $payments->first()->status,
            'payments'  => $payments->map(fn (Payment $payment) => [
                'booking_id' => $payment->booking_id,
                'status'     => $payment->status,
            ]),
        ]);
    }

    /**
     * Webhook yang dipanggil DOKU untuk memberi tahu perubahan status
     * pembayaran. Memverifikasi signature, lalu update payment & booking
     * terkait, dan mengirim notifikasi jika pembayaran berhasil.
     */
    public function webhook(Request $request): JsonResponse
    {
        // -----------------------------------------------------------
        // 1. Verifikasi signature webhook dari DOKU
        // -----------------------------------------------------------
        $secret   = config('services.doku.webhook_secret');
        $provided = (string) $request->header('X-SIGNATURE');
        $expected = $secret
            ? hash_hmac('sha256', $request->getContent(), $secret)
            : '';

        abort_unless($secret && $provided && hash_equals($expected, $provided), 401, 'Invalid DOKU signature.');

        // -----------------------------------------------------------
        // 2. Ambil reference & tentukan status pembayaran
        // -----------------------------------------------------------
        $reference = $request->input('partnerReferenceNo')
            ?? $request->input('originalPartnerReferenceNo')
            ?? $request->input('externalReference');

        $status = strtolower((string) ($request->input('transactionStatusDesc') ?? $request->input('status')));

        $paymentStatus = match (true) {
            in_array($status, ['success', 'paid', 'settled'], true) => 'paid',
            in_array($status, ['failed', 'expired', 'cancelled'], true) => $status,
            default => 'pending',
        };

        // -----------------------------------------------------------
        // 3. Update payment & booking terkait dalam satu transaksi
        // -----------------------------------------------------------
        $paidBookings = DB::transaction(function () use ($request, $reference, $paymentStatus): array {
            $paidBookings = [];

            Payment::where('provider', 'doku')
                ->where(function ($query) use ($reference): void {
                    $query->where('partner_reference_no', $reference)
                        ->orWhereJsonContains('provider_payload->partnerReferenceNo', $reference);
                })
                ->lockForUpdate()
                ->get()
                ->each(function (Payment $payment) use ($paymentStatus, $reference, &$paidBookings, $request): void {
                    $wasPaid = $payment->status === 'paid';

                    $payment->update([
                        'status'           => $paymentStatus,
                        'provider_payload' => $request->all(),
                    ]);

                    $booking = $payment->booking()->lockForUpdate()->first();
                    $paid = $booking->payments()->where('status', 'paid')->sum('amount');

                    $booking->update([
                        'payment_status'     => $paid >= $booking->total_amount ? 'paid_full' : ($paid > 0 ? 'partial' : 'unpaid'),
                        'outstanding_amount' => max(0, $booking->total_amount - $paid),
                        'status'             => $paid >= $booking->total_amount ? 'confirmed' : ($paymentStatus === 'failed' ? 'cancelled' : 'pending'),
                    ]);

                    if ($paymentStatus === 'paid' && !$wasPaid) {
                        $paidBookings[] = $booking->id;
                    }
                });

            return $paidBookings;
        });

        // -----------------------------------------------------------
        // 4. Kirim notifikasi untuk setiap booking yang baru saja lunas
        // -----------------------------------------------------------
        if ($paymentStatus === 'paid') {
            $notifications = app(\App\Services\BookingNotificationService::class);

            foreach ($paidBookings as $bookingId) {
                $booking = Booking::find($bookingId);
                if ($booking) {
                    $notifications->paymentSucceeded(
                        $booking,
                        $reference,
                        route('booking.payment.return', ['reference' => $reference])
                    );
                }
            }
        }

        return response()->json(['received' => true]);
    }
}