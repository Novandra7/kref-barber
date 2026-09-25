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
use App\Services\WahaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;

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

        $dpAmount = (int) config('booking.dp_amount', 40000);

        return view('booking', compact('services', 'barbers', 'scheduleData', 'availableDates', 'selectedDate', 'dpAmount'));
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
            'guests.0.phone'                    => ['required', 'string', 'max:30'],
            'guests.*.name'                     => ['required', 'string', 'max:255'],
            'guests.*.phone'                    => ['nullable', 'string', 'max:30'],
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
            $primaryPhone = $data['guests'][0]['phone'] ?? null;

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
                    'name'               => $guest['name'],
                    'phone'              => !empty($guest['phone']) ? $guest['phone'] : $primaryPhone,
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

            // --- Hitung jumlah yang harus dibayar (DP per tamu atau Full) ---
            $isDp = strtolower($data['payment_type']) === 'dp';
            $guestCount = count($bookingPayments);
            $dpAmount = (int) config('booking.dp_amount', 40000);
            $amount = $isDp ? ($dpAmount * $guestCount) : $totalAmount;
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
            $paymentUrl = $dokuPaymentUrl ?: route('booking.payment.detail', ['reference' => $reference]);

            $validityPeriod  = data_get($response, 'additionalInfo.validityPeriod');
            $expiresAt = $validityPeriod
                ? Carbon::parse($validityPeriod)->setTimezone(config('app.timezone'))
                : now()->addMinutes(30);

            // Buat 1 Payment tunggal yang menaungi seluruh booking tamu
            $payment = Payment::create([
                'amount'              => $amount,
                'method'              => 'qris_doku',
                'provider'            => 'doku',
                'purpose'             => $isDp ? 'dp' : 'full_payment',
                'status'              => 'pending',
                'partner_reference_no'=> $reference,
                'doku_reference_no'   => $providerId,
                'payment_url'         => $paymentUrl,
                'expires_at'          => $response['validityPeriod'] ?? $expiresAt,
                'qr_content'          => $qrContent,
                'provider_payload'    => array_merge($response, [
                    'partnerReferenceNo' => $reference,
                ]),
            ]);

            // Hubungkan semua booking ke payment yang baru dibuat
            foreach ($bookingPayments as [$booking, $guestTotal]) {
                $booking->update(['payment_id' => $payment->id]);
            }

            return [
                'reference'  => $reference,
                'amount'     => $amount,
                'qrContent'  => $qrContent,
                'expiresAt'  => $expiresAt->toDateTimeString(),
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
    public function paymentDetail(string $reference): View
    {
        $payments = Payment::where('provider', 'doku')
            ->where(function ($query) use ($reference): void {
                $query->where('partner_reference_no', $reference)
                    ->orWhere('doku_reference_no', $reference)
                    ->orWhereJsonContains('provider_payload->partnerReferenceNo', $reference);
            })
            ->get();

        abort_if($payments->isEmpty(), 404, 'Payment not found.');

        $bookings = Booking::with([
            'barber',
            'schedule',
            'requestedSchedule',
            'items' => fn ($query) => $query
                ->where('item_type', 'service')
                ->orderBy('id'),
        ])
            ->whereIn('payment_id', $payments->pluck('id'))
            ->get();

        // 1. Payment Status (dari transaksi DOKU)
        $paymentStatus = $payments->contains(fn (Payment $payment) => $payment->status === 'paid')
            ? 'paid'
            : ($payments->first()->status ?? 'pending');

        // 2. Booking Status (prioritaskan status pembatalan jika salah satu booking cancel)
        $bookingStatus = $bookings->first()?->status ?? 'pending';

        $qrContent = $payments->pluck('qr_content')->filter()->first();

        // 3. Ambil slot jadwal yang tersedia di database untuk barber terkait (untuk modal reschedule)
        $barberIds = $bookings->pluck('barber_id')->unique()->filter();
        $availableSchedules = Schedule::with('barber')
            ->whereIn('barber_id', $barberIds)
            ->where('is_available', true)
            ->orderBy('date')
            ->orderBy('slot_time')
            ->get();

        return view('booking.payment-status', [
            'reference'          => $reference,
            'paymentStatus'      => $paymentStatus,
            'bookingStatus'      => $bookingStatus,
            'qrContent'          => $qrContent,
            'paymentAmount'      => $payments->sum('amount'),
            'bookings'           => $bookings,
            'expiresAt'          => $payments->pluck('expires_at')->filter()->first(),
            'availableSchedules' => $availableSchedules,
        ]);
    }

    /**
     * Mengajukan pembatalan booking (Menunggu Approval Admin).
     */
    public function cancel(Request $request, string $reference): RedirectResponse
    {
        $booking = Booking::with('payment')->find($request->input('booking_id'));
        $payment = $booking?->payment;

        $validReference = $payment && (
            $payment->partner_reference_no === $reference ||
            data_get($payment->provider_payload, 'partnerReferenceNo') === $reference
        );

        if (! $booking || ! $validReference) {
            return $this->cancelFailed($reference, $booking ? 'Booking tidak terkait dengan transaksi ini.': 'Booking tidak ditemukan.');
        }

        if (in_array($booking->status, ['cancelled', 'cancel_requested'], true)) {
            return $this->cancelFailed($reference, 'Booking ini sudah dibatalkan atau sedang dalam proses pembatalan.');
        }

        if ($booking->scheduled_at && ($booking->scheduled_at->isPast() || now()->diffInMinutes($booking->scheduled_at, false) < 180)) {
            return $this->cancelFailed($reference, 'Pembatalan ditolak. Sudah memasuki batas H-3 jam sebelum jadwal layanan!');
        }

        DB::transaction(function () use ($booking): void {
            $booking->update([
                'status' => 'cancel_requested',
            ]);
        });

        $adminPhone = config('services.kref.admin_phone', '6283862681541');
        $adminPhoneFormatted = preg_replace('/[^0-9]/', '', (string) $adminPhone);
        if (str_starts_with($adminPhoneFormatted, '0')) {
            $adminPhoneFormatted = '62' . substr($adminPhoneFormatted, 1);
        }

        $isManualRefund = ! ($payment?->canBeRefundedViaDoku() ?? false);

        if ($isManualRefund && $adminPhone) {
            $paymentTypeLabel = strtolower((string) $booking->payment_type) === 'dp' ? 'Down Payment (DP)' : 'Full Payment';
            $paidAmount = (int) ($payment?->amount ?? $booking->total_amount);

            $waText = implode("\n", [
                'Halo Admin KREF Barber, saya mengajukan pembatalan booking (Refund Manual):',
                '',
                '• Kode Ref: ' . $reference,
                '• Nama: ' . $booking->name,
                '• Jadwal: ' . ($booking->scheduled_at?->format('d M Y, H:i') ?? '-') . ' WITA',
                '• Barber: ' . ($booking->barber?->name ?? '-'),
                '• Sumber Pembayaran: ' . ($payment?->payment_source ?: 'QRIS'),
                '• Jenis Pembayaran: ' . $paymentTypeLabel,
                '• Nominal Dibayar: Rp ' . number_format($paidAmount, 0, ',', '.'),
                '',
                'Berikut rekening/e-wallet saya untuk pengembalian dana:',
                '• Bank/E-Wallet: ',
                '• No. Rekening: ',
                '• Atas Nama: ',
                '',
                'Terima kasih!',
            ]);

            $waUrl = 'https://wa.me/' . $adminPhoneFormatted . '?text=' . urlencode($waText);

            return redirect()
                ->route('booking.payment.detail', ['reference' => $reference])
                ->with('success', 'Permintaan pembatalan diajukan. Silakan kirim rincian rekening pengembalian dana ke WhatsApp Admin.')
                ->with('wa_refund_url', $waUrl);
        }

        if ($adminPhone) {
            $waText = 'Halo kak, saya mau refund';
            $waUrl = 'https://wa.me/' . $adminPhoneFormatted . '?text=' . urlencode($waText);

            return redirect()
                ->route('booking.payment.detail', ['reference' => $reference])
                ->with('success', 'Permintaan pembatalan berhasil diajukan. Silakan konfirmasi ke WhatsApp Admin.')
                ->with('wa_refund_url', $waUrl);
        }

        return redirect()
            ->route('booking.payment.detail', ['reference' => $reference])
            ->with('success', 'Permintaan pembatalan berhasil dikirim. Menunggu persetujuan admin.');
    }

    private function cancelFailed(string $reference, string $message): RedirectResponse
    {
        return redirect()
            ->route('booking.payment.detail', ['reference' => $reference])
            ->with('error', $message);
    }

    /**
     * Mengajukan reschedule booking (Menunggu Approval Admin).
     */
    public function requestReschedule(Request $request, string $reference): RedirectResponse
    {
        $data = $request->validate([
            'booking_id'  => ['required', 'integer', 'exists:bookings,id'],
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
        ]);

        $booking = Booking::with('payment')->find($data['booking_id']);
        $payment = $booking?->payment;

        $validReference = $payment && (
            $payment->partner_reference_no === $reference ||
            data_get($payment->provider_payload, 'partnerReferenceNo') === $reference
        );

        if (! $booking || ! $validReference) {
            return $this->rescheduleFailed($reference, $booking ? 'Booking tidak terkait dengan transaksi ini.' : 'Booking tidak ditemukan.');
        }

        if (in_array($booking->status, ['cancelled', 'cancel_requested', 'completed'], true)) {
            return $this->rescheduleFailed($reference, 'Booking ini sudah tidak dapat dijadwalkan ulang.');
        }

        if ($booking->scheduled_at && ($booking->scheduled_at->isPast() || now()->diffInMinutes($booking->scheduled_at, false) < 180)) {
            return $this->rescheduleFailed($reference, 'Jadwal ulang ditolak. Sudah memasuki batas H-3 jam sebelum jadwal layanan!');
        }

        $newSchedule = Schedule::whereKey($data['schedule_id'])
            ->where('barber_id', $booking->barber_id)
            ->where('is_available', true)
            ->first();

        if (! $newSchedule) {
            return $this->rescheduleFailed($reference, 'Slot jadwal yang dipilih sudah tidak tersedia. Silakan pilih slot waktu lain.');
        }

        DB::transaction(function () use ($booking, $newSchedule): void {
            // Jika ada slot yang sebelumnya sempat di-hold, bebaskan kembali
            if ($booking->requested_schedule_id && $booking->requested_schedule_id !== $newSchedule->id) {
                Schedule::whereKey($booking->requested_schedule_id)->update(['is_available' => true]);
            }

            // Kunci slot baru sementara agar tidak dipesan pelanggan lain
            $newSchedule->update(['is_available' => false]);

            $booking->update([
                'status'                => 'reschedule_requested',
                'requested_schedule_id' => $newSchedule->id,
            ]);
        });

        $adminPhone = config('services.kref.admin_phone', '6283862681541');
        if ($adminPhone) {
            $adminPhoneFormatted = preg_replace('/[^0-9]/', '', (string) $adminPhone);
            if (str_starts_with($adminPhoneFormatted, '0')) {
                $adminPhoneFormatted = '62' . substr($adminPhoneFormatted, 1);
            }

            $waText = 'Halo kak, saya mau reschedule';
            $waUrl = 'https://wa.me/' . $adminPhoneFormatted . '?text=' . urlencode($waText);

            return redirect()
                ->route('booking.payment.detail', ['reference' => $reference])
                ->with('success', 'Permintaan jadwal ulang berhasil diajukan untuk tanggal ' . $newSchedule->date->format('d M Y') . ' pukul ' . $newSchedule->slot_time->format('H:i') . ' WITA. Silakan konfirmasi ke WhatsApp Admin.')
                ->with('wa_reschedule_url', $waUrl);
        }

        return redirect()
            ->route('booking.payment.detail', ['reference' => $reference])
            ->with('success', 'Permintaan jadwal ulang berhasil diajukan untuk tanggal ' . $newSchedule->date->format('d M Y') . ' pukul ' . $newSchedule->slot_time->format('H:i') . ' WITA. Menunggu persetujuan admin.');
    }

    private function rescheduleFailed(string $reference, string $message): RedirectResponse
    {
        return redirect()
            ->route('booking.payment.detail', ['reference' => $reference])
            ->with('error', $message);
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
                'payment_id' => $payment->id,
                'status'     => $payment->status,
            ]),
        ]);
    }

}