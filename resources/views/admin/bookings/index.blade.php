@extends('admin.layouts.app')

@section('title', 'Bookings')
@section('header', 'Bookings')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-brand">Management</p>
                <div class="mt-1 flex items-center gap-3">
                    <h2 class="font-league text-4xl uppercase text-gray-900">Bookings</h2>
                    <span class="rounded-full bg-brand/10 px-3 py-1 text-sm font-semibold text-brand">
                        {{ number_format($bookingCount) }} transactions
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500">Manage customer appointments and payments.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.bookings.create') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90 focus:outline-none focus:ring-4 focus:ring-brand/20">
                    <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/>
                    </svg>
                    Create Walk-in Booking
                </a>
                <a href="{{ $exportUrl ?? '#' }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-brand bg-white px-4 py-2.5 text-sm font-semibold text-brand shadow-sm hover:bg-brand/5 focus:outline-none focus:ring-4 focus:ring-brand/10">
                    <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2"/>
                    </svg>
                    Export Data
                </a>
            </div>
        </div>

        <form method="GET" action="{{ $filterUrl ?? url()->current() }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="relative lg:col-span-2">
                    <label for="booking-search" class="sr-only">Search bookings</label>
                    <div class="pointer-events-none absolute inset-y-0 inset-s-0 flex items-center ps-3 text-gray-400">
                        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/>
                        </svg>
                    </div>
                    <input type="search" name="search" id="booking-search" value="{{ $currentFilters['search'] }}"
                           placeholder="Search name, phone, or booking ID"
                           class="block w-full rounded-lg border-gray-300 py-2.5 ps-10 text-sm text-gray-900 focus:border-brand focus:ring-brand">
                </div>

                <select name="barber_id" class="rounded-lg border-gray-300 text-sm text-gray-700 focus:border-brand focus:ring-brand">
                    <option value="">All Barbers</option>
                    @foreach ($barberOptions as $barber)
                        <option value="{{ $barber->id }}" @selected((string) $currentFilters['barber_id'] === (string) $barber->id)>
                            {{ $barber->name }}
                        </option>
                    @endforeach
                </select>

                <select name="status" class="rounded-lg border-gray-300 text-sm text-gray-700 focus:border-brand focus:ring-brand">
                    <option value="">All Status</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($currentFilters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="payment_status" class="rounded-lg border-gray-300 text-sm text-gray-700 focus:border-brand focus:ring-brand">
                    <option value="">All Payment Status</option>
                    @foreach ($paymentStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($currentFilters['payment_status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <div id="booking-date-range" date-rangepicker datepicker-format="yyyy-mm-dd" datepicker-autohide data-range-picker class="flex items-center gap-2 lg:col-span-2">
                    <div class="relative w-full">
                        <div class="pointer-events-none absolute inset-y-0 inset-s-0 flex items-center ps-3">
                            <svg class="h-4 w-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1H8V1a1 1 0 0 0-2 0v1H4a2 2 0 0 0-2 2v2h18V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1Z"/>
                            </svg>
                        </div>
                        <input name="date_from" type="text" data-date-format="yyyy-mm-dd" value="{{ $currentFilters['date_from'] }}" placeholder="Start date" autocomplete="off"
                               class="block w-full rounded-lg border-gray-300 bg-white p-2.5 ps-10 text-sm text-gray-700 focus:border-brand focus:ring-brand">
                    </div>
                    <span class="text-sm text-gray-400">to</span>
                    <div class="relative w-full">
                        <div class="pointer-events-none absolute inset-y-0 inset-s-0 flex items-center ps-3">
                            <svg class="h-4 w-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1H8V1a1 1 0 0 0-2 0v1H4a2 2 0 0 0-2 2v2h18V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1Z"/>
                            </svg>
                        </div>
                        <input name="date_to" type="text" data-date-format="yyyy-mm-dd" value="{{ $currentFilters['date_to'] }}" placeholder="End date" autocomplete="off"
                               class="block w-full rounded-lg border-gray-300 bg-white p-2.5 ps-10 text-sm text-gray-700 focus:border-brand focus:ring-brand">
                    </div>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-gray-500">Search by customer name, phone number, or booking ID.</p>
                <div class="flex gap-2">
                    <a href="{{ $resetUrl ?? url()->current() }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                        Reset
                    </a>
                    <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                        Apply Filter
                    </button>
                </div>
            </div>
        </form>

        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="relative overflow-x-auto">
                <table class="w-full min-w-275 text-left text-sm text-gray-500">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <th scope="col" class="px-5 py-4">Booking</th>
                            <th scope="col" class="px-5 py-4">Customer</th>
                            <th scope="col" class="px-5 py-4">Barber & Service</th>
                            <th scope="col" class="px-5 py-4">Financial</th>
                            <th scope="col" class="px-5 py-4">Payment</th>
                            <th scope="col" class="px-5 py-4">Status</th>
                            <th scope="col" class="px-5 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($bookingRows as $booking)
                            @php
                                $bookingId = $booking->id ?? $booking->reference ?? '-';
                                $customerName = $booking->name ?? $booking->customer_name ?? '-';
                                $customerPhone = $booking->phone ?? $booking->customer_phone ?? null;
                                $serviceItems = collect($booking->items ?? [])->filter(fn ($item) => ($item->item_type ?? 'service') === 'service');
                                $paymentStatus = $booking->payment_status ?? 'unpaid';
                                $operationStatus = $booking->status ?? 'pending';
                                $statusLabel = $statusOptions[$operationStatus] ?? str_replace('_', ' ', ucfirst($operationStatus));
                            @endphp
                            <tr class="bg-white hover:bg-gray-50">
                                <td class="px-5 py-4 align-top">
                                    <div class="font-mono font-bold text-gray-900">#BK-{{ $bookingId }}</div>
                                    <div class="mt-1 whitespace-nowrap text-xs text-gray-500">
                                        {{ $booking->scheduled_at?->format('d M Y, H:i') ?? '-' }}
                                    </div>
                                    <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ ($booking->source ?? '') === 'walk_in' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ ($booking->source ?? '') === 'walk_in' ? 'Walk-in' : 'Online' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="font-semibold text-gray-900">{{ $customerName }}</div>
                                    @if ($customerPhone)
                                        <a href="https://wa.me/{{ preg_replace('/\D+/', '', $customerPhone) }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1 text-xs text-gray-500 hover:text-brand">
                                            <svg class="h-4 w-4 text-green-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.149-.669-1.611-.916-2.206-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.273.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.876 1.213 3.074.149.198 2.095 3.2 5.077 4.487.709.306 1.262.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.124-.272-.198-.57-.347Z"/>
                                                <path d="M20.52 3.449A11.82 11.82 0 0 0 12.08 0C5.495 0 .135 5.36.135 11.945c0 2.105.55 4.16 1.594 5.974L.025 24l6.225-1.634a11.89 11.89 0 0 0 5.83 1.525h.005c6.584 0 11.944-5.36 11.944-11.946a11.86 11.86 0 0 0-3.509-8.496ZM12.085 21.85h-.004a9.87 9.87 0 0 1-5.032-1.378l-.361-.214-3.694.97.986-3.603-.235-.37a9.86 9.86 0 0 1-1.51-5.31c0-5.47 4.452-9.921 9.926-9.921a9.86 9.86 0 0 1 7.044 2.923 9.86 9.86 0 0 1 2.913 7.047c-.001 5.47-4.453 9.916-9.933 9.916Z"/>
                                            </svg>
                                            {{ $customerPhone }}
                                        </a>
                                    @endif
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="font-semibold text-gray-900">{{ $booking->barber?->name ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $booking->barber?->role ?? '-' }}</div>
                                    <div class="mt-2 max-w-xs space-y-1 text-xs text-gray-600">
                                        @forelse ($serviceItems as $item)
                                            <div>{{ $item->service_name_snapshot ?? $item->service?->name ?? 'Service' }} </div>
                                        @empty
                                            <span class="text-gray-400">No service items</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="font-semibold text-gray-900">Rp {{ number_format($booking->total_amount ?? 0, 0, ',', '.') }}</div>
                                    @if ($paymentStatus === 'partial')
                                        <div class="mt-1 text-xs text-amber-600">Sisa: Rp {{ number_format($booking->outstanding_amount ?? 0, 0, ',', '.') }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $paymentStatus === 'paid_full' ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $paymentStatus === 'partial' ? 'bg-amber-100 text-amber-700' : '' }}
                                        {{ $paymentStatus === 'unpaid' ? 'bg-red-100 text-red-700' : '' }}
                                        {{ in_array($paymentStatus, ['failed', 'expired'], true) ? 'bg-gray-100 text-gray-600' : '' }}">
                                        {{ match ($paymentStatus) {
                                            'paid_full' => 'Paid Full / Lunas',
                                            'partial' => 'DP Received',
                                            'unpaid' => 'Unpaid',
                                            'failed', 'expired' => 'Failed / Expired',
                                            default => str_replace('_', ' ', ucfirst($paymentStatus)),
                                        } }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $operationStatus === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                                        {{ $operationStatus === 'confirmed' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $operationStatus === 'in_progress' ? 'bg-purple-100 text-purple-700' : '' }}
                                        {{ $operationStatus === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $operationStatus === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right align-top">
                                    <button type="button" data-dropdown-toggle="booking-actions-{{ $bookingId }}" class="inline-flex rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-brand" aria-label="Booking actions">
                                        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M12 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm0 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm0 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>
                                    </button>
                                    <div id="booking-actions-{{ $bookingId }}" class="z-10 hidden w-48 divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white shadow-sm">
                                        <ul class="p-2 text-sm text-gray-700">
                                            <li><a href="{{ route('admin.bookings.show', $booking) }}" class="block w-full rounded px-3 py-2 text-left hover:bg-gray-100">View Detail</a></li>
                                            <li><a href="{{ route('admin.bookings.edit', $booking) }}" class="block w-full rounded px-3 py-2 text-left hover:bg-gray-100">Edit Booking</a></li>
                                            @if (($booking->outstanding_amount ?? 0) > 0)
                                                <li><button type="button" data-modal-target="paymentModal-{{ $bookingId }}" data-modal-toggle="paymentModal-{{ $bookingId }}" class="w-full rounded px-3 py-2 text-left hover:bg-gray-100">Pelunasan / Mark as Paid</button></li>
                                            @endif
                                                <li><button type="button" data-modal-target="statusModal-{{ $bookingId }}" data-modal-toggle="statusModal-{{ $bookingId }}" class="w-full rounded px-3 py-2 text-left hover:bg-gray-100">Update Status</button></li>
                                            @if ($operationStatus !== 'cancelled')
                                                <li><button type="button" data-modal-target="cancelModal-{{ $bookingId }}" data-modal-toggle="cancelModal-{{ $bookingId }}" class="w-full rounded px-3 py-2 text-left text-red-600 hover:bg-red-50">Cancel Booking</button></li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">No bookings found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-col justify-between gap-3 text-sm text-gray-500 sm:flex-row sm:items-center">
            <p>
                Showing {{ $bookingRows->firstItem() ?? 0 }} to {{ $bookingRows->lastItem() ?? 0 }} of {{ $bookingCount }} results
            </p>
            @if (method_exists($bookingRows, 'links'))
                {{ $bookingRows->withQueryString()->links() }}
            @endif
        </div>
    </div>

    @foreach ($bookingRows as $booking)
        @php
            $bookingId = $booking->id ?? $booking->reference ?? '-';
            $customerName = $booking->name ?? $booking->customer_name ?? '-';
            $customerPhone = $booking->phone ?? $booking->customer_phone ?? '-';
            $serviceItems = collect($booking->items ?? [])->filter(fn ($item) => ($item->item_type ?? 'service') === 'service');
            $paymentStatus = $booking->payment_status ?? 'unpaid';
            $operationStatus = $booking->status ?? 'pending';
        @endphp

        <div id="detailBookingModal-{{ $bookingId }}" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden p-4">
            <div class="relative max-h-full w-full max-w-2xl"><div class="relative rounded-xl bg-white shadow">
                <div class="flex items-center justify-between border-b border-gray-200 p-4 md:p-5"><h3 class="font-montserrat text-xl font-semibold text-gray-900">Booking #BK-{{ $bookingId }}</h3><button type="button" data-modal-hide="detailBookingModal-{{ $bookingId }}" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100">&times;</button></div>
                <div class="grid gap-5 p-4 text-sm md:grid-cols-2 md:p-5">
                    <div><h4 class="mb-2 font-semibold text-gray-900">Customer</h4><p>{{ $customerName }}</p><p class="text-gray-500">{{ $customerPhone }}</p></div>
                    <div><h4 class="mb-2 font-semibold text-gray-900">Appointment</h4><p>{{ $booking->scheduled_at?->format('d M Y, H:i') ?? '-' }}</p><p class="text-gray-500">{{ $booking->barber?->name ?? '-' }} - {{ $booking->barber?->role ?? '-' }}</p></div>
                    <div class="md:col-span-2"><h4 class="mb-2 font-semibold text-gray-900">Services / Products</h4><div class="divide-y divide-gray-100 rounded-lg border border-gray-200">@forelse ($booking->items ?? [] as $item)<div class="flex justify-between gap-3 p-3"><span>{{ $item->service_name_snapshot ?? $item->product_name_snapshot ?? $item->service?->name ?? $item->product?->name ?? 'Item' }} x{{ $item->qty ?? 1 }}</span><span class="font-semibold">Rp {{ number_format(($item->price_snapshot ?? 0) * ($item->qty ?? 1), 0, ',', '.') }}</span></div>@empty<div class="p-3 text-gray-500">No items recorded.</div>@endforelse</div></div>
                    <div><h4 class="mb-2 font-semibold text-gray-900">Note</h4><p class="text-gray-600">{{ $booking->description ?? 'No special notes.' }}</p></div>
                    <div><h4 class="mb-2 font-semibold text-gray-900">Payment Summary</h4><p>Total: <strong>Rp {{ number_format($booking->total_amount ?? 0, 0, ',', '.') }}</strong></p><p>Outstanding: <strong>Rp {{ number_format($booking->outstanding_amount ?? 0, 0, ',', '.') }}</strong></p><p class="capitalize text-gray-500">{{ str_replace('_', ' ', $paymentStatus) }}</p></div>
                    <div class="md:col-span-2"><h4 class="mb-2 font-semibold text-gray-900">Payment History</h4>@forelse ($booking->payments ?? [] as $payment)<div class="flex justify-between border-b border-gray-100 py-2"><span class="capitalize">{{ str_replace('_', ' ', $payment->purpose ?? 'payment') }} · {{ $payment->method ?? '-' }}</span><span>Rp {{ number_format($payment->amount ?? 0, 0, ',', '.') }}</span></div>@empty<p class="text-gray-500">No payment history.</p>@endforelse</div>
                </div>
            </div></div>
        </div>
        {{-- Payment Modal --}}
        @if (($booking->outstanding_amount ?? 0) > 0)
            <div id="paymentModal-{{ $bookingId }}" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 p-4 backdrop-blur-xs">
                <div class="relative w-full max-w-md">
                    <div class="relative rounded-xl bg-white shadow-lg">
                        {{-- Header --}}
                        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                            <h3 class="font-bold text-gray-900">Process Payment</h3>
                            <button type="button" data-modal-hide="paymentModal-{{ $bookingId }}" class="text-gray-400 hover:text-gray-600 rounded-lg p-1 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Form --}}
                        <form method="POST" action="{{ $paymentUrl ?? '#' }}" class="space-y-4 p-5">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $bookingId }}">

                            <div>
                                <p class="text-sm text-gray-500">Outstanding Amount:</p>
                                <p class="text-lg font-bold text-gray-900">Rp {{ number_format($booking->outstanding_amount, 0, ',', '.') }}</p>
                            </div>

                            <div>
                                <label for="amount-{{ $bookingId }}" class="mb-1.5 block text-sm font-medium text-gray-700">
                                    Payment Amount <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    id="amount-{{ $bookingId }}"
                                    name="amount" 
                                    value="{{ $booking->outstanding_amount }}" 
                                    min="1" 
                                    max="{{ $booking->outstanding_amount }}" 
                                    required
                                    disabled
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand disabled:bg-gray-200 disabled:text-gray-500 disabled:cursor-not-allowed disabled:border-gray-200"
                                >
                            </div>

                            <div>
                                <label for="method-{{ $bookingId }}" class="mb-1.5 block text-sm font-medium text-gray-700">Payment Method <span class="text-red-500">*</span></label>
                                <select 
                                    id="method-{{ $bookingId }}"
                                    name="method" 
                                    required 
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand"
                                >
                                    <option value="cash">Cash</option>
                                    <option value="qris_static">QRIS</option>
                                </select>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center justify-end gap-2 pt-2">
                                <button type="button" data-modal-hide="paymentModal-{{ $bookingId }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                                    Cancel
                                </button>
                                <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-90 transition shadow-xs">
                                    Mark as Paid
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Status Update Modal --}}
        <div id="statusModal-{{ $bookingId }}" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 p-4 backdrop-blur-xs">
            <div class="relative w-full max-w-md">
                <div class="relative rounded-xl bg-white shadow-lg">
                    {{-- Header --}}
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h3 class="font-bold text-gray-900">Update Booking Status</h3>
                        <button type="button" data-modal-hide="statusModal-{{ $bookingId }}" class="text-gray-400 hover:text-gray-600 rounded-lg p-1 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Form --}}
                    <form method="POST" action="{{ route('admin.bookings.update-status', $bookingId) }}" class="space-y-4 p-5">
                        @csrf
                        @method('PATCH')
                        {{-- <input type="hidden" name="booking_id" value="{{ $bookingId }}"> --}}

                        <div>
                            <label for="status-{{ $bookingId }}" class="mb-1.5 block text-sm font-medium text-gray-700">Select Status <span class="text-red-500">*</span></label>
                            <select 
                                id="status-{{ $bookingId }}"
                                name="status" 
                                required 
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand"
                            >
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($operationStatus === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" data-modal-hide="statusModal-{{ $bookingId }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-90 transition shadow-xs">
                                Save Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Cancel Modal --}}
        <div id="cancelModal-{{ $bookingId }}" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 p-4 backdrop-blur-xs">
            <div class="relative w-full max-w-md">
                <div class="relative rounded-xl bg-white p-6 shadow-lg">
                    <h3 class="text-lg font-bold text-gray-900">Cancel this booking?</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        This action will mark the booking as cancelled.
                    </p>

                    <form method="POST" action="{{ route('admin.bookings.update', $booking) }}" class="mt-6 flex items-center justify-end gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="booking_id" value="{{ $bookingId }}">
                        <input type="hidden" name="status" value="cancelled">

                        <button type="button" data-modal-hide="cancelModal-{{ $bookingId }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                            Keep Booking
                        </button>
                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition shadow-xs">
                            Cancel Booking
                        </button>
                    </form>
                </div>
            </div>
        </div>

    @endforeach
@endsection
