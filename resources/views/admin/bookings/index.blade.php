@extends('admin.layouts.app')

@section('title', 'Bookings')
@section('header', 'Bookings')

@section('content')
    <div class="space-y-6">
        <!-- Header Banner Container -->
        <div class="rounded-2xl border-2 border-gray-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <span class="inline-block rounded-full border border-gray-900 bg-brand/10 px-3 py-0.5 text-xs font-bold uppercase tracking-wider text-brand">
                        Management
                    </span>
                    <div class="mt-1 flex items-center gap-3">
                        <h2 class="font-league text-4xl font-black uppercase text-gray-900">Bookings</h2>
                        <span class="rounded-xl border-2 border-gray-900 bg-amber-300 px-3 py-1 text-xs font-black text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            {{ number_format($bookingCount) }} transactions
                        </span>
                    </div>
                    <p class="mt-1 text-xs font-semibold text-gray-500">Manage customer appointments and payments.</p>
                </div>

                <!-- Action Buttons Header -->
                <div class="flex flex-wrap gap-2.5">
                    <a href="{{ route('admin.bookings.create') }}"
                       class="inline-flex items-center gap-2 rounded-xl border-2 border-gray-900 bg-brand px-4 py-2.5 text-xs font-black uppercase tracking-wider text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[5px_5px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none">
                        <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14m-7-7h14"/>
                        </svg>
                        Create Walk-in Booking
                    </a>
                    <a href="{{ $exportUrl ?? '#' }}"
                       class="inline-flex items-center gap-2 rounded-xl border-2 border-gray-900 bg-white px-4 py-2.5 text-xs font-black uppercase tracking-wider text-gray-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-amber-50 hover:shadow-[5px_5px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none">
                        <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2"/>
                        </svg>
                        Export Data
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Form Container -->
        <form method="GET" action="{{ $filterUrl ?? url()->current() }}" class="rounded-2xl border-2 border-gray-900 bg-white p-5 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="relative lg:col-span-2">
                    <label for="booking-search" class="sr-only">Search bookings</label>
                    <div class="pointer-events-none absolute inset-y-0 inset-s-0 flex items-center ps-3 text-gray-400">
                        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2.5" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/>
                        </svg>
                    </div>
                    <input type="search" name="search" id="booking-search" value="{{ $currentFilters['search'] }}"
                           placeholder="Search name, phone, or booking ID"
                           class="block w-full rounded-xl border-2 border-gray-900 py-2.5 ps-10 text-xs font-bold text-gray-900 placeholder:text-gray-400 focus:border-brand focus:ring-0">
                </div>

                <select name="barber_id" class="rounded-xl border-2 border-gray-900 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                    <option value="">All Barbers</option>
                    @foreach ($barberOptions as $barber)
                        <option value="{{ $barber->id }}" @selected((string) $currentFilters['barber_id'] === (string) $barber->id)>
                            {{ $barber->name }}
                        </option>
                    @endforeach
                </select>

                <select name="status" class="rounded-xl border-2 border-gray-900 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                    <option value="">All Status</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($currentFilters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="payment_status" class="rounded-xl border-2 border-gray-900 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                    <option value="">All Payment Status</option>
                    @foreach ($paymentStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($currentFilters['payment_status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <div id="booking-date-range" date-rangepicker datepicker-format="yyyy-mm-dd" datepicker-autohide data-range-picker class="flex items-center gap-2 lg:col-span-2">
                    <div class="relative w-full">
                        <input name="date_from" type="text" data-date-format="yyyy-mm-dd" value="{{ $currentFilters['date_from'] }}" placeholder="Start date" autocomplete="off"
                               class="block w-full rounded-xl border-2 border-gray-900 bg-white p-2.5 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                    </div>
                    <span class="text-xs font-black text-gray-500">TO</span>
                    <div class="relative w-full">
                        <input name="date_to" type="text" data-date-format="yyyy-mm-dd" value="{{ $currentFilters['date_to'] }}" placeholder="End date" autocomplete="off"
                               class="block w-full rounded-xl border-2 border-gray-900 bg-white p-2.5 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t-2 border-dashed border-gray-200 pt-3">
                <p class="text-[11px] font-semibold text-gray-500">Search by customer name, phone number, or booking ID.</p>
                <div class="flex gap-2">
                    <!-- Button Reset dengan Animasi Hover Neobrutalism -->
                    <a href="{{ $resetUrl ?? url()->current() }}" 
                    class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-white px-4 py-2 text-xs font-black uppercase text-gray-700 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-amber-50 hover:shadow-[5px_5px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none">
                        Reset
                    </a>

                    <!-- Button Apply Filter dengan Animasi Hover Neobrutalism -->
                    <button type="submit" 
                            class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-brand px-4 py-2 text-xs font-black uppercase text-amber-50 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[5px_5px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none">
                        Apply Filter
                    </button>
                </div>
            </div>
        </form>

        <!-- Flash Alerts -->
        @if (session('success'))
            <div class="rounded-xl border-2 border-gray-900 bg-green-100 p-4 text-xs font-bold text-green-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border-2 border-gray-900 bg-red-100 p-4 text-xs font-bold text-red-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        <!-- Table Container (Struktur Persis Seperti yang Anda Minta) -->
        <div class="overflow-hidden rounded-2xl border-2 border-gray-900 bg-white py-3 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
            <div class="overflow-hidden rounded-xl bg-white">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="font-montserrat font-bold text-gray-900">Bookings List</h3>
                </div>

                <div class="relative overflow-x-auto">
                    <table id="{{ $tableId ?? 'bookings-table' }}" class="w-full text-left text-sm text-gray-500">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                            <tr>
                                <th scope="col" class="whitespace-nowrap px-6 py-3">Customer</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3">Barber</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3">Schedule</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3">Amount</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3">Status</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($bookingRows as $rowIndex => $booking)
                                @php
                                    $customerName = $booking->name ?? $booking->customer_name ?? $booking['customer'] ?? '-';
                                    $barberName = $booking->barber?->name ?? $booking['barber'] ?? '-';
                                    $scheduledAt = isset($booking->scheduled_at) ? $booking->scheduled_at?->format('d M Y, H:i') : ($booking['schedule'] ?? '-');
                                    $amount = isset($booking->total_amount) ? 'Rp ' . number_format($booking->total_amount, 0, ',', '.') : ($booking['amount'] ?? '-');
                                    $status = $booking->status ?? $booking['status'] ?? 'pending';
                                @endphp
                                <tr class="bg-white hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4 font-semibold text-gray-900">{{ $customerName }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $barberName }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $scheduledAt }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $amount }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            {{ $status }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <button
                                            type="button"
                                            data-modal-target="detailBookingModal-{{ $rowIndex }}"
                                            data-modal-toggle="detailBookingModal-{{ $rowIndex }}"
                                            class="font-medium text-primary hover:underline"
                                        >
                                            View
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center">
                                        No data found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Details (Struktur Modal Persis yang Anda Sediakan) -->
            @foreach ($bookingRows as $rowIndex => $booking)
                <div
                    id="detailBookingModal-{{ $rowIndex }}"
                    tabindex="-1"
                    aria-hidden="true"
                    class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0"
                >
                    <div class="relative max-h-full w-full max-w-2xl">
                        <div class="relative rounded-lg bg-white shadow">
                            <div class="flex items-center justify-between rounded-t border-b border-gray-200 p-4 md:p-5">
                                <h3 class="font-montserrat text-xl font-semibold text-gray-900">
                                    Bookings Detail
                                </h3>
                                <button
                                    type="button"
                                    data-modal-hide="detailBookingModal-{{ $rowIndex }}"
                                    class="ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900"
                                    aria-label="Close modal"
                                >
                                    <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="space-y-4 p-4 md:p-5">
                                @if (isset($booking['details']))
                                    @foreach ($booking['details'] as $detail)
                                        <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3 text-sm last:border-b-0">
                                            <span class="font-medium text-gray-500">{{ $detail['label'] }}</span>
                                            <span class="text-right font-semibold text-gray-900">{{ $detail['value'] }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3 text-sm">
                                        <span class="font-medium text-gray-500">Customer Name</span>
                                        <span class="text-right font-semibold text-gray-900">{{ $booking->name ?? $booking->customer_name ?? '-' }}</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3 text-sm">
                                        <span class="font-medium text-gray-500">Phone</span>
                                        <span class="text-right font-semibold text-gray-900">{{ $booking->phone ?? $booking->customer_phone ?? '-' }}</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3 text-sm">
                                        <span class="font-medium text-gray-500">Barber</span>
                                        <span class="text-right font-semibold text-gray-900">{{ $booking->barber?->name ?? '-' }}</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3 text-sm">
                                        <span class="font-medium text-gray-500">Total Amount</span>
                                        <span class="text-right font-semibold text-gray-900">Rp {{ number_format($booking->total_amount ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination Container -->
        <div class="flex flex-col justify-between gap-3 text-xs font-bold text-gray-600 sm:flex-row sm:items-center">
            <p>
                Showing {{ method_exists($bookingRows, 'firstItem') ? $bookingRows->firstItem() : 1 }} to {{ method_exists($bookingRows, 'lastItem') ? $bookingRows->lastItem() : count($bookingRows) }} of {{ $bookingCount ?? count($bookingRows) }} results
            </p>
            @if (is_object($bookingRows) && method_exists($bookingRows, 'links'))
                {{ $bookingRows->withQueryString()->links() }}
            @endif
        </div>
    </div>
@endsection