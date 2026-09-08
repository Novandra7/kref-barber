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
    public function index(Request $request): View
    {
        $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        if ($request->filled('date_from') && $request->filled('date_to')
            && $request->input('date_from') > $request->input('date_to')) {
            $request->merge([
                'date_from' => $request->input('date_to'),
                'date_to' => $request->input('date_from'),
            ]);
        }

        $paymentsQuery = Payment::query()
            ->with(['booking:id,name,phone,barber_id', 'booking.barber:id,name,role'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim((string) $request->input('search'));
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('id', $search)
                        ->orWhere('partner_reference_no', 'like', "%{$search}%")
                        ->orWhere('doku_reference_no', 'like', "%{$search}%")
                        ->orWhereHas('booking', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('id', $search);
                        });
                });
            })
            ->when($request->filled('method'), fn (Builder $query) => $query->where('method', $request->input('method')))
            ->when($request->filled('provider'), fn (Builder $query) => $query->where('provider', $request->input('provider')))
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

        dd($payments->toArray());
        return view('admin.payments.index', [
            'payments' => $payments,
            'paymentCount' => $payments->total(),
            'paidTotal' => $paidTotal,
            'currentFilters' => [
                'search' => (string) $request->input('search', ''),
                'method' => (string) $request->input('method', ''),
                'provider' => (string) $request->input('provider', ''),
                'status' => (string) $request->input('status', ''),
                'date_from' => (string) $request->input('date_from', ''),
                'date_to' => (string) $request->input('date_to', ''),
            ],
            'methodOptions' => [
                'qris_doku' => 'QRIS (DOKU)',
                'qris_static' => 'QRIS (Static)',
                'cash' => 'Cash',
            ],
            'providerOptions' => [
                'doku' => 'DOKU',
                'manual' => 'Manual',
            ],
            'statusOptions' => [
                'pending' => 'Pending',
                'paid' => 'Paid',
                'failed' => 'Failed',
                'expired' => 'Expired',
                'cancelled' => 'Cancelled',
                'refunded' => 'Refunded',
            ],
            'purposeOptions' => [
                'dp' => 'Down Payment',
                'full_payment' => 'Full Payment',
                'pelunasan' => 'Pelunasan',
                'walk_in' => 'Walk-in',
            ],
            'filterUrl' => route('admin.payments.index'),
            'resetUrl' => route('admin.payments.index'),
        ]);
    }
}
