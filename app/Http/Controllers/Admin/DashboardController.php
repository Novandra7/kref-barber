<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Barber;
use App\Models\Payment;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(): View
    {
        $recentBookings = Booking::with(['barber', 'payment'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (Booking $booking): array {
                return [
                    'customer' => $booking->name ?: '-',
                    'barber'   => $booking->barber?->name ?: '-',
                    'schedule' => $booking->scheduled_at?->format('d M Y H:i') ?: '-',
                    'amount'   => $booking->total_amount === null
                        ? '-'
                        : 'Rp '.number_format($booking->total_amount, 0, ',', '.'),
                    'status'   => str_replace('_', ' ', ucfirst($booking->status ?: '-')),
                    'details'  => [
                        ['label' => 'Customer', 'value' => $booking->name ?: '-'],
                        ['label' => 'Barber', 'value' => $booking->barber?->name ?: '-'],
                        ['label' => 'Schedule', 'value' => $booking->scheduled_at?->format('d M Y H:i') ?: '-'],
                        [
                            'label' => 'Amount',
                            'value' => $booking->total_amount === null
                                ? '-'
                                : 'Rp '.number_format($booking->total_amount, 0, ',', '.'),
                        ],
                        ['label' => 'Status', 'value' => str_replace('_', ' ', ucfirst($booking->status ?: '-'))],
                    ],
                ];
            });

        // Ambil booking yang membutuhkan tindakan admin (Cancel / Reschedule)
        $pendingRequests = Booking::with(['payment', 'barber', 'requestedSchedule'])
            ->whereIn('status', ['cancel_requested', 'reschedule_requested'])
            ->latest('scheduled_at')
            ->limit(10)
            ->get()
            ->map(function (Booking $booking): array {
                $requestedScheduleStr = null;
                if ($booking->requestedSchedule) {
                    $requestedScheduleStr = $booking->requestedSchedule->date->format('d M Y') . ', ' . $booking->requestedSchedule->slot_time->format('H:i') . ' WITA';
                }

                return [
                    'id'                 => $booking->id,
                    'customer'           => $booking->name ?: '-',
                    'phone'              => $booking->formattedPhone ?: '-',
                    'barber'             => $booking->barber?->name ?: '-',
                    'schedule'           => $booking->scheduled_at?->format('d M Y, H:i') . ' WITA' ?: '-',
                    'requested_schedule' => $requestedScheduleStr,
                    'amount'             => $booking->total_amount === null ? '-' : 'Rp ' . number_format($booking->total_amount, 0, ',', '.'),
                    'type'               => $booking->status, // 'cancel_requested' atau 'reschedule_requested'
                    'payment'            => $booking->payment,
                    'edit_url'           => route('admin.bookings.edit', $booking),
                    'action_url'         => route('admin.bookings.update-status', $booking),
                ];
            });

        return view('admin.dashboard.index', [
            'stats' => [
                ['label' => 'Total Bookings', 'value' => Booking::count()],
                ['label' => 'Pending Bookings', 'value' => Booking::whereIn('status', ['pending', 'waiting_payment'])->count()],
                ['label' => 'Pending Payments', 'value' => Payment::where('status', 'pending')->count()],
                ['label' => 'Active Barbers', 'value' => Barber::where('is_active', true)->count()],
                ['label' => 'Active Services', 'value' => Service::where('is_active', true)->count()],
            ],
            'recentBookings'  => $recentBookings,
            'pendingRequests' => $pendingRequests,
        ]);
    }
}
