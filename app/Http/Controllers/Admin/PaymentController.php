<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public const STATUS_OPTIONS = [
        'pending'          => 'Pending',
        'paid'             => 'Paid',
        'cancel_requested' => 'Cancel Requested',
        'refunded'         => 'Refunded',
        'failed'           => 'Failed',
        'expired'          => 'Expired',
        'cancelled'        => 'Cancelled',
    ];

    public const METHOD_OPTIONS = [
        'qris_doku'   => 'QRIS (DOKU)',
        'qris_static' => 'QRIS (Static)',
        'cash'        => 'Cash',
    ];

    public const PURPOSE_OPTIONS = [
        'dp'           => 'Down Payment',
        'full_payment' => 'Full Payment',
        'pelunasan'    => 'Pelunasan',
        'walk_in'      => 'Walk-in',
    ];

    public function index(Request $request): View
    {
        $this->normalizeDateRange($request);

        $paymentsQuery = Payment::query()
            ->with([
                'bookings:id,payment_id,name,phone,barber_id',
                'bookings.barber:id,name,role',
            ])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim((string) $request->input('search'));
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('id', $search)
                        ->orWhere('partner_reference_no', 'like', "%{$search}%")
                        ->orWhere('doku_reference_no', 'like', "%{$search}%")
                        ->orWhere('payment_source', 'like', "%{$search}%")
                        ->orWhereHas('bookings', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('id', $search);
                        });
                });
            })
            ->when($request->filled('method'), fn (Builder $query) => $query->where('method', $request->input('method')))
            ->when($request->filled('payment_source'), fn (Builder $query) => $query->where('payment_source', $request->input('payment_source')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->input('status')))
            ->when($request->filled('date_from'), function (Builder $query) use ($request): void {
                $query->where('created_at', '>=', Carbon::createFromFormat('Y-m-d', $request->input('date_from'))->startOfDay());
            })
            ->when($request->filled('date_to'), function (Builder $query) use ($request): void {
                $query->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', $request->input('date_to'))->endOfDay());
            });

        $paidTotal = (int) (clone $paymentsQuery)->where('status', 'paid')->sum('amount');

        $payments = $paymentsQuery
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        // Ambil daftar payment_source unik yang tersedia di database
        $sourceOptions = Payment::query()
            ->whereNotNull('payment_source')
            ->where('payment_source', '!=', '')
            ->distinct()
            ->pluck('payment_source', 'payment_source')
            ->toArray();

        return view('admin.payments.index', [
            'payments'       => $payments,
            'paymentCount'   => $payments->total(),
            'paidTotal'      => $paidTotal,
            'currentFilters' => $request->only(['search', 'method', 'payment_source', 'status', 'date_from', 'date_to']),
            'methodOptions'  => self::METHOD_OPTIONS,
            'sourceOptions'  => $sourceOptions,
            'statusOptions'  => self::STATUS_OPTIONS,
            'purposeOptions' => self::PURPOSE_OPTIONS,
            'filterUrl'      => route('admin.payments.index'),
            'resetUrl'       => route('admin.payments.index'),
        ]);
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
}