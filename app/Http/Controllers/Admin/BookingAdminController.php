<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\BookingsExport;
use App\Jobs\SendBookingReminderJob;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Schedule;
use App\Models\Service;
use App\Services\BookingNotificationService;
use App\Services\DokuService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class BookingAdminController extends Controller
{
    public const STATUS_OPTIONS = [
        'pending'          => 'Pending',
        'confirmed'        => 'Confirmed',
        'in_progress'      => 'In Progress',
        'completed'            => 'Completed',
        'cancel_requested'     => 'Cancel Requested',
        'reschedule_requested' => 'Reschedule Requested',
        'cancelled'            => 'Cancelled',
    ];

    public const PAYMENT_STATUS_OPTIONS = [
        'pending'            => 'Pending',
        'paid'               => 'Paid',
        'partially_refunded' => 'Partially Refunded',
        'refunded'           => 'Refunded',
        'failed'             => 'Failed',
        'expired'            => 'Expired',
    ];

    public function index(Request $request): View
    {
        $this->normalizeDateRange($request);

        $bookings = $this->buildBookingQuery($request)
            ->latest('scheduled_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings'             => $bookings,
            'bookingRows'          => $bookings, // backward-compatibility view
            'bookingCount'         => $bookings->total(),
            'barberOptions'        => Barber::where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']),
            'currentFilters'       => $request->only(['search', 'barber_id', 'status', 'payment_status', 'date_from', 'date_to']),
            'statusOptions'        => self::STATUS_OPTIONS,
            'paymentStatusOptions' => self::PAYMENT_STATUS_OPTIONS,
            'exportUrl'            => route('admin.bookings.export', array_filter($request->query())),
            'filterUrl'            => route('admin.bookings.index'),
            'resetUrl'             => route('admin.bookings.index'),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $request->validate([
            'search'         => ['nullable', 'string', 'max:255'],
            'barber_id'      => ['nullable', 'integer', 'exists:barbers,id'],
            'status'         => ['nullable', 'in:pending,confirmed,in_progress,completed,cancel_requested,cancelled'],
            'payment_status' => ['nullable', 'in:pending,paid,partially_refunded,refunded,failed,expired'],
            'date_from'      => ['nullable', 'date_format:Y-m-d'],
            'date_to'        => ['nullable', 'date_format:Y-m-d'],
        ]);

        if (($filters['date_from'] ?? null) && ($filters['date_to'] ?? null)
            && $filters['date_from'] > $filters['date_to']) {
            [$filters['date_from'], $filters['date_to']] = [$filters['date_to'], $filters['date_from']];
        }

        return Excel::download(
            new BookingsExport($filters),
            'bookings-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function create(): View
    {
        return view('admin.bookings.create', $this->bookingFormData());
    }

    public function store(Request $request, BookingNotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'phone'          => ['required', 'string', 'max:30'],
            'barber_id'      => ['required', 'exists:barbers,id'],
            'date'           => ['required', 'date_format:Y-m-d'],
            'time'           => ['required', 'date_format:H:i'],
            'service_ids'    => ['required', 'array', 'min:1'],
            'service_ids.*'  => ['integer', 'distinct', 'exists:services,id'],
            'payment_type'   => ['required', 'in:dp,full'],
            'payment_method' => ['required', 'in:cash,qris_static'],
            'description'    => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data): void {
            $barber = Barber::query()
                ->whereKey($data['barber_id'])
                ->where('is_active', true)
                ->first();

            if (! $barber) {
                throw ValidationException::withMessages([
                    'barber_id' => 'The selected barber is not active or could not be found.',
                ]);
            }

            $scheduleExists = Schedule::query()
                ->where('barber_id', $data['barber_id'])
                ->where('date', $data['date'])
                ->whereTime('slot_time', $data['time'])
                ->exists();

            if ($scheduleExists) {
                throw ValidationException::withMessages([
                    'time' => 'A booking already exists for this barber at the selected date and time.',
                ]);
            }

            $schedule = Schedule::create([
                'barber_id'    => $data['barber_id'],
                'date'         => $data['date'],
                'slot_time'    => $data['time'],
                'is_available' => false,
            ]);

            $services = Service::query()
                ->whereIn('id', $data['service_ids'])
                ->where('is_active', true)
                ->get();

            abort_if($services->count() !== count($data['service_ids']), 422, 'One or more selected services are inactive.');

            $serviceItems = $services->map(function (Service $service) use ($barber): array {
                $isOwnerRegularHaircut = strtolower((string) $barber->role) === 'owner'
                    && str_contains(strtolower($service->name), 'regular haircut');

                return [
                    'service' => $service,
                    'name'    => $isOwnerRegularHaircut
                        ? preg_replace('/^regular\s+/i', '', $service->name) . ' - By ' . $barber->name
                        : $service->name,
                    'price'   => $service->price + ($isOwnerRegularHaircut ? 10000 : 0),
                ];
            });

            $total = (int) $serviceItems->sum('price');
            $dpAmount = (int) config('booking.dp_amount', 40000);
            $amount = $data['payment_type'] === 'dp' ? $dpAmount : $total;
            abort_if($amount > $total, 422, 'DP amount cannot exceed the booking total.');

            // --- PERBAIKAN: Menghapus 'payment_status' dari array create ---
            $booking = Booking::create([
                'schedule_id'        => $schedule->id,
                'name'               => $data['name'],
                'phone'              => $data['phone'],
                'description'        => $data['description'] ?? null,
                'barber_id'          => $data['barber_id'],
                'created_by'         => auth()->id(),
                'source'             => 'walk_in',
                'payment_type'       => $data['payment_type'],
                'status'             => 'confirmed',
                'total_amount'       => $total,
                'outstanding_amount' => $total - $amount,
                'scheduled_at'       => $data['date'] . ' ' . $data['time'],
            ]);

            $booking->items()->createMany($serviceItems->map(fn (array $item): array => [
                'item_type'             => 'service',
                'service_id'            => $item['service']->id,
                'qty'                   => 1,
                'service_name_snapshot' => $item['name'],
                'price_snapshot'        => $item['price'],
            ])->all());

            $payment = Payment::create([
                'amount'      => $amount,
                'method'      => $data['payment_method'],
                'provider'    => 'manual',
                'purpose'     => $data['payment_type'] === 'dp' ? 'dp' : 'walk_in',
                'status'      => 'paid',
                'recorded_by' => auth()->id(),
            ]);

            $booking->update(['payment_id' => $payment->id]);

            $schedule->update(['is_available' => false]);
        });

        $notifications->sendDailyRecapToOpsGroup($data['date']);

        return redirect()->route('admin.bookings.index')->with('success', 'Walk-in booking created successfully.');
    }

    public function show(Booking $booking): RedirectResponse
    {
        return redirect()->route('admin.bookings.index', ['search' => $booking->id]);
    }

    public function edit(Booking $booking): View
    {
        $booking->load(['barber', 'schedule', 'items']);

        return view('admin.bookings.edit', $this->bookingFormData($booking));
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['required', 'string', 'max:30'],
            'barber_id'     => ['required', 'exists:barbers,id'],
            'date'          => ['required', 'date_format:Y-m-d'],
            'time'          => ['required', 'date_format:H:i'],
            'service_ids'   => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'distinct', 'exists:services,id'],
            'payment_type'  => ['required', 'in:dp,full'],
            'status'        => ['required', 'in:pending,confirmed,in_progress,completed,cancel_requested,reschedule_requested,cancelled'],
            'description'   => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $booking): void {
            $booking->load(['items', 'payment']);
            $schedule = Schedule::query()
                ->where('barber_id', $data['barber_id'])
                ->whereDate('date', $data['date'])
                ->whereTime('slot_time', $data['time'])
                ->where(function (Builder $query) use ($booking): void {
                    $query->where('is_available', true)->orWhere('id', $booking->schedule_id);
                })
                ->lockForUpdate()
                ->firstOrFail();

            $barber = Barber::query()->whereKey($data['barber_id'])->where('is_active', true)->firstOrFail();
            $services = Service::query()->whereIn('id', $data['service_ids'])->where('is_active', true)->get();
            abort_if($services->count() !== count($data['service_ids']), 422, 'One or more selected services are inactive.');

            $serviceItems = $this->snapshotServices($services, $barber);
            $total = (int) $serviceItems->sum('price');
            $paid  = $booking->payment && $booking->payment->status === 'paid'
                ? (int) $booking->payment->amount
                : 0;
            $outstanding = max(0, $total - $paid);

            if ($booking->schedule_id !== $schedule->id) {
                Schedule::whereKey($booking->schedule_id)->update(['is_available' => true]);
            }
            $schedule->update(['is_available' => false]);

            $booking->update([
                'schedule_id'        => $schedule->id,
                'name'               => $data['name'],
                'phone'              => $data['phone'],
                'description'        => $data['description'] ?? null,
                'barber_id'          => $barber->id,
                'payment_type'       => $data['payment_type'],
                'status'             => $data['status'],
                'total_amount'       => $total,
                'outstanding_amount' => $outstanding,
                'scheduled_at'       => $data['date'] . ' ' . $data['time'],
            ]);

            $booking->items()->delete();
            $booking->items()->createMany($serviceItems->map(fn (array $item): array => [
                'item_type'             => 'service',
                'service_id'            => $item['service']->id,
                'qty'                   => 1,
                'service_name_snapshot' => $item['name'],
                'price_snapshot'        => $item['price'],
            ])->all());
        });

        return redirect()->route('admin.bookings.index')->with('success', 'Booking updated successfully.');
    }

    /**
     * Memperbarui status booking & menangani approval pembatalan oleh Admin.
     */
    public function updateStatus(
        Request $request,
        Booking $booking,
        DokuService $doku,
        BookingNotificationService $notifications,
    ): RedirectResponse {
        $data = $request->validate([
            'status' => ['required', 'in:pending,confirmed,in_progress,completed,cancel_requested,reschedule_requested,cancelled'],
        ]);

        if ($booking->status === $data['status']) {
            return back()->with('info', 'Booking status is already set to the selected value.');
        }

        $isPartialRefund = false;
        $isManualRefund = false;
        $isRescheduleApproved = false;
        $refundNotificationData = null;
        $rescheduleNotificationData = null;

        try {
            DB::transaction(function () use ($booking, $data, $doku, &$isPartialRefund, &$refundNotificationData, &$isManualRefund, &$isRescheduleApproved, &$rescheduleNotificationData): void {
                // Selalu load relasi yang diperlukan di awal agar data segar dari DB
                $booking->load(['payment', 'schedule', 'barber', 'requestedSchedule']);

                if ($data['status'] === 'cancelled') {
                    // Jika ada slot baru yang sempat di-hold saat permintaan reschedule, bebaskan kembali
                    if ($booking->requested_schedule_id) {
                        Schedule::whereKey($booking->requested_schedule_id)->update(['is_available' => true]);
                        $booking->requested_schedule_id = null;
                    }

                    $payment = $booking->payment;

                    if ($payment) {
                        // Booking yang masih "aktif" = selain cancelled dan cancel_requested
                        $otherActiveBookings = $payment->bookings()
                            ->where('id', '!=', $booking->id)
                            ->whereNotIn('status', ['cancelled', 'cancel_requested'])
                            ->exists();

                        $newPaymentStatus = $otherActiveBookings ? 'partially_refunded' : 'refunded';
                        $isPartialRefund = $otherActiveBookings;

                        // 1. Cek riwayat dana yang sudah di-refund sebelumnya (dari tabel refunds)
                        $alreadyRefunded = $payment->totalRefunded();
                        $remainingPaymentAmount = max(0, (int) $payment->amount - $alreadyRefunded);

                        // 2. Tentukan nominal refund sesuai tipe pembayaran (DP dibagi rata vs Full Payment sesuai items)
                        $isDp = $payment->purpose === 'dp' || strtolower((string) $booking->payment_type) === 'dp';

                        if ($isDp) {
                            // Down Payment: Dibagi rata sejumlah total booking awal pada transaksi ini
                            $initialBookingCount = max(1, $payment->bookings()->count());
                            $dpPerBooking = (int) round($payment->amount / $initialBookingCount);

                            // Jika ini booking terakhir yang dibatalkan, ambil seluruh sisa dana transaksi
                            $refundAmount = $otherActiveBookings
                                ? min($remainingPaymentAmount, $dpPerBooking)
                                : $remainingPaymentAmount;
                        } else {
                            // Full Payment: Refund sesuai dengan total biaya booking items dari tamu yang dibatalkan
                            $bookingCost = (int) $booking->total_amount;

                            $refundAmount = $otherActiveBookings
                                ? min($remainingPaymentAmount, $bookingCost)
                                : $remainingPaymentAmount;
                        }
                        $refundAmount = max(1, $refundAmount);

                        // Jika pembayaran sudah LUNAS (paid atau partially_refunded) dan butuh refund
                        if (in_array($payment->status, ['paid', 'partially_refunded'], true)) {
                            $refundNo = 'RFD-' . Str::upper(Str::random(12));
                            $canAutoRefund = $payment->canBeRefundedViaDoku();
                            $isManualRefund = ! $canAutoRefund;
                            $refundReason = $otherActiveBookings ? 'Admin Approved Partial Refund' : 'Admin Approved Refund';

                            if ($canAutoRefund) {
                                $approvalCode = data_get($payment->provider_payload, 'emoney_payment.approval_code');

                                // Panggil DOKU QRIS Refund API
                                $refundResponse = $doku->refundQrisPayment(
                                    originalPartnerReferenceNo: $payment->partner_reference_no,
                                    originalReferenceNo: $payment->doku_reference_no,
                                    refundPartnerReferenceNo: $refundNo,
                                    refundAmount: $refundAmount,
                                    reason: $refundReason,
                                    approvalCode: $approvalCode ? (string) $approvalCode : null,
                                );

                                // Simpan ke tabel refunds (auto-refund langsung completed)
                                Refund::create([
                                    'payment_id'        => $payment->id,
                                    'booking_id'        => $booking->id,
                                    'refund_no'         => $refundNo,
                                    'amount'            => $refundAmount,
                                    'type'              => 'auto_doku',
                                    'status'            => 'completed',
                                    'reason'            => $refundReason,
                                    'provider_response' => $refundResponse,
                                    'completed_at'      => now(),
                                    'recorded_by'       => auth()->id(),
                                ]);

                                $payment->update(['status' => $newPaymentStatus]);
                            } else {
                                // Manual Refund: Sumber pembayaran belum didukung oleh DOKU auto-refund API
                                // Status 'pending' sampai admin konfirmasi transfer manual
                                Refund::create([
                                    'payment_id'        => $payment->id,
                                    'booking_id'        => $booking->id,
                                    'refund_no'         => $refundNo,
                                    'amount'            => $refundAmount,
                                    'type'              => 'manual',
                                    'status'            => 'pending',
                                    'reason'            => 'Issuer not supported by DOKU API (' . ($payment->payment_source ?? 'unknown') . ')',
                                    'recorded_by'       => auth()->id(),
                                ]);

                                $payment->update(['status' => $newPaymentStatus]);
                            }

                            // Siapkan data notifikasi WhatsApp setelah transaksi DB berhasil
                            $refundNotificationData = [
                                'booking'      => $booking,
                                'refundAmount' => $refundAmount,
                                'refundNo'     => $refundNo,
                                'isPartial'    => $otherActiveBookings,
                                'isManual'     => ! $canAutoRefund,
                            ];
                        } elseif ($payment->status === 'pending') {
                            if (! $otherActiveBookings) {
                                $payment->update(['status' => 'expired']);
                            }
                        }
                    }

                    // Bebaskan slot jadwal barber milik booking yang dibatalkan
                    if ($booking->schedule) {
                        $booking->schedule->update(['is_available' => true]);
                    }
                } elseif ($booking->status === 'cancelled') {
                    // Booking sebelumnya cancelled, di-restore → kunci kembali slot jadwalnya
                    if ($booking->schedule) {
                        $booking->schedule->update(['is_available' => false]);
                    }
                } elseif ($booking->status === 'reschedule_requested' && $data['status'] === 'confirmed') {
                    // Admin menyetujui permintaan reschedule
                    if ($booking->requested_schedule_id) {
                        $newSchedule = Schedule::find($booking->requested_schedule_id);
                        if ($newSchedule) {
                            // 1. Bebaskan slot jadwal lama
                            if ($booking->schedule) {
                                $booking->schedule->update(['is_available' => true]);
                            }
                            // 2. Kunci slot jadwal baru
                            $newSchedule->update(['is_available' => false]);

                            $oldScheduledAt = $booking->scheduled_at;

                            // 3. Pindahkan booking ke slot jadwal baru
                            $booking->schedule_id = $newSchedule->id;
                            $booking->scheduled_at = Carbon::parse($newSchedule->date->format('Y-m-d') . ' ' . $newSchedule->slot_time->format('H:i:s'));
                            $booking->requested_schedule_id = null;

                            $rescheduleNotificationData = [
                                'booking'        => $booking,
                                'oldScheduledAt' => $oldScheduledAt,
                            ];
                            $isRescheduleApproved = true;
                        }
                    }
                }

                $booking->update(['status' => $data['status']]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses perubahan status: ' . $e->getMessage());
        }

        // Kirim pesan WhatsApp ke pelanggan jika refund berhasil diproses
        if ($refundNotificationData) {
            $notifications->bookingRefunded(
                booking: $refundNotificationData['booking'],
                refundAmount: $refundNotificationData['refundAmount'],
                refundNo: $refundNotificationData['refundNo'],
                isPartial: $refundNotificationData['isPartial'],
                isManual: $refundNotificationData['isManual'] ?? false,
            );
        }

        // Kirim pesan WhatsApp ke pelanggan jika reschedule berhasil disetujui
        if ($rescheduleNotificationData) {
            $notifications->bookingRescheduled(
                booking: $rescheduleNotificationData['booking'],
                oldScheduledAt: $rescheduleNotificationData['oldScheduledAt'],
            );
        }

        // Kirim pesan Rekap Agenda Harian ke Grup Operasional WhatsApp
        if ($data['status'] === 'cancelled' && $booking->scheduled_at) {
            $notifications->sendDailyRecapToOpsGroup($booking->scheduled_at);
        }

        if ($isRescheduleApproved && $booking->scheduled_at) {
            $notifications->sendDailyRecapToOpsGroup($booking->scheduled_at);

            if ($rescheduleNotificationData && !empty($rescheduleNotificationData['oldScheduledAt'])) {
                $oldDate = Carbon::parse($rescheduleNotificationData['oldScheduledAt'])->toDateString();
                $newDate = Carbon::parse($booking->scheduled_at)->toDateString();
                if ($oldDate !== $newDate) {
                    $notifications->sendDailyRecapToOpsGroup($oldDate);
                }
            }
        }

        if ($isRescheduleApproved) {
            $successMessage = 'Perubahan jadwal booking berhasil disetujui dan jadwal baru telah dikonfirmasi.';
        } elseif ($isManualRefund) {
            $successMessage = 'Status booking berhasil dibatalkan. Sumber pembayaran ini tidak didukung auto-refund DOKU, pastikan pengembalian dana manual telah/akan ditransfer ke rekening pelanggan.';
        } else {
            $successMessage = $isPartialRefund
                ? 'Status booking berhasil dibatalkan dan pengembalian dana parsial (partial refund) berhasil diproses.'
                : 'Status booking dan refund berhasil diproses.';
        }

        return back()->with('success', $successMessage);
    }

    /**
     * Admin konfirmasi bahwa transfer manual refund sudah dilakukan.
     */
    public function confirmManualRefund(Refund $refund): RedirectResponse
    {
        if ($refund->type !== 'manual' || $refund->status !== 'pending') {
            return back()->with('error', 'Refund ini tidak memerlukan konfirmasi manual.');
        }

        $refund->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'recorded_by'  => auth()->id(),
        ]);

        return back()->with('success', 'Refund manual berhasil dikonfirmasi. Pengembalian dana telah ditandai selesai.');
    }

    private function bookingFormData(?Booking $booking = null): array
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'category']);

        $selectedServiceIds = session()->getOldInput(
            'service_ids',
            $booking?->items->pluck('service_id')->filter()->values()->all() ?? []
        );

        $selectedServiceIds = collect($selectedServiceIds)
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->all();

        $serviceCategories = $services
            ->groupBy(fn (Service $service) => $service->category ?: 'Other')
            ->map(fn ($categoryServices, string $category): array => [
                'name'      => $category,
                'isHaircut' => strtolower(trim($category)) === 'haircut',
                'services'  => $categoryServices->map(function (Service $service) use ($selectedServiceIds): Service {
                    $service->setAttribute('is_selected', in_array((int) $service->id, $selectedServiceIds, true));

                    return $service;
                }),
            ])
            ->values();

        return [
            'booking'           => $booking,
            'isEdit'            => $booking !== null,
            'barbers'           => Barber::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']),
            'services'          => $services,
            'serviceCategories' => $serviceCategories,
            'serviceCount'      => $services->count(),
            'categoryCount'     => min($serviceCategories->count(), 4),
            'statusOptions'     => self::STATUS_OPTIONS,
            'formUrl'    => $booking ? route('admin.bookings.update', $booking) : route('admin.bookings.store'),
            'formMethod' => $booking ? 'PUT' : 'POST',
        ];
    }
    
    public function destroy(Booking $booking): RedirectResponse
    {
        if ($booking->status !== 'cancelled') {
            return back()->with('error', 'Only cancelled bookings can be deleted.');
        }

        $booking->delete();

        return redirect()->route('admin.bookings.index')->with('success', 'Booking deleted successfully.');
    }
    
    private function snapshotServices($services, Barber $barber)
    {
        return $services->map(function (Service $service) use ($barber): array {
            $isOwnerRegularHaircut = strtolower((string) $barber->role) === 'owner'
                && str_contains(strtolower($service->name), 'regular haircut');

            return [
                'service' => $service,
                'name'    => $isOwnerRegularHaircut
                    ? preg_replace('/^regular\s+/i', '', $service->name) . ' - By ' . $barber->name
                    : $service->name,
                'price'   => $service->price + ($isOwnerRegularHaircut ? 10000 : 0),
            ];
        });
    }

    /**
     * Pastikan urutan rentang tanggal valid (date_from <= date_to).
     */
    private function normalizeDateRange(Request $request): void
    {
        $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to'   => ['nullable', 'date_format:Y-m-d'],
        ]);

        if ($request->filled('date_from') && $request->filled('date_to')
            && $request->input('date_from') > $request->input('date_to')) {
            $request->merge([
                'date_from' => $request->input('date_to'),
                'date_to'   => $request->input('date_from'),
            ]);
        }
    }

    /**
     * Kirim pengingat jadwal booking secara manual via Queue / WAHA.
     */
    public function sendReminder(Booking $booking): RedirectResponse
    {
        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Pengingat hanya dapat dikirimkan untuk booking dengan status Terkonfirmasi (Confirmed).');
        }

        if (! $booking->phone) {
            return back()->with('error', 'Nomor telepon pelanggan tidak ditemukan.');
        }

        // Dispatch ke Queue untuk diproses oleh queue worker
        SendBookingReminderJob::dispatch($booking->id);

        return back()->with('success', "Pengingat jadwal untuk booking #BK-{$booking->id} ({$booking->name}) berhasil dimasukkan ke antrean pengiriman.");
    }

    /**
     * Query builder dengan eager loading dan filter untuk booking.
     */
    private function buildBookingQuery(Request $request): Builder
    {
        return Booking::query()
            ->with([
                'barber:id,name,role',
                'schedule:id,barber_id,date,slot_time,is_available',
                'items:id,booking_id,item_type,service_id,product_id,qty,service_name_snapshot,product_name_snapshot,price_snapshot',
                'items.service:id,name',
                'items.product:id,name',
                'payment:id,amount,method,purpose,status,created_at',
            ])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim((string) $request->input('search'));
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('id', $search);
                });
            })
            ->when($request->filled('barber_id'), fn (Builder $query) => $query->where('barber_id', $request->input('barber_id')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->input('status')))
            ->when($request->filled('payment_status'), function (Builder $query) use ($request): void {
                $query->whereHas('payment', fn (Builder $q) => $q->where('status', $request->input('payment_status')));
            })
            ->when($request->filled('date_from'), function (Builder $query) use ($request): void {
                $query->where('scheduled_at', '>=', Carbon::createFromFormat('Y-m-d', $request->input('date_from'))->startOfDay());
            })
            ->when($request->filled('date_to'), function (Builder $query) use ($request): void {
                $query->where('scheduled_at', '<=', Carbon::createFromFormat('Y-m-d', $request->input('date_to'))->endOfDay());
            });
    }
}