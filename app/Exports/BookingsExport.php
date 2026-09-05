<?php

namespace App\Exports;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BookingsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private readonly array $filters,
    ) {
    }

    public function query(): Builder
    {
        return Booking::query()
            ->with([
                'barber:id,name,role',
                'items:id,booking_id,item_type,service_name_snapshot,product_name_snapshot,qty,price_snapshot',
                'payments:id,booking_id,amount,method,status',
            ])
            ->when($this->filters['search'] ?? null, function (Builder $query, string $search): void {
                $search = trim($search);
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('id', $search);
                });
            })
            ->when($this->filters['barber_id'] ?? null, fn (Builder $query, string $barberId) => $query->where('barber_id', $barberId))
            ->when($this->filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($this->filters['payment_status'] ?? null, fn (Builder $query, string $paymentStatus) => $query->where('payment_status', $paymentStatus))
            ->when($this->filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->where('scheduled_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($this->filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->where('scheduled_at', '<=', Carbon::parse($date)->endOfDay()))
            ->latest('scheduled_at');
    }

    public function headings(): array
    {
        return [
            'Booking ID',
            'Scheduled At',
            'Source',
            'Customer Name',
            'Phone',
            'Barber',
            'Barber Role',
            'Services',
            'Total Amount',
            'Paid Amount',
            'Outstanding Amount',
            'Payment Status',
            'Operational Status',
            'Payment Methods',
            'Notes',
        ];
    }

    public function map($booking): array
    {
        $items = $booking->items
            ->map(function ($item): string {
                $name = $item->item_type === 'product'
                    ? ($item->product_name_snapshot ?? 'Product')
                    : ($item->service_name_snapshot ?? 'Service');

                return $name . ' x' . $item->qty;
            })
            ->implode(', ');

        return [
            'BK-' . $booking->id,
            $booking->scheduled_at?->format('Y-m-d H:i'),
            $booking->source === 'walk_in' ? 'Walk-in' : 'Online',
            $booking->name,
            $booking->phone,
            $booking->barber?->name,
            $booking->barber?->role,
            $items,
            $booking->total_amount,
            $booking->payments->where('status', 'paid')->sum('amount'),
            $booking->outstanding_amount,
            $booking->payment_status,
            $booking->status,
            $booking->payments->pluck('method')->filter()->unique()->implode(', '),
            $booking->description,
        ];
    }
}
