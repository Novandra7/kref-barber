@extends('admin.layouts.app')

@section('title', 'Payments')
@section('header', 'Payments')

@section('content')
    <div class="space-y-6">
        <!-- Header Banner Container Retro -->
        <div class="rounded-2xl border-2 border-gray-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <span class="inline-block rounded-full border border-gray-900 bg-brand/10 px-3 py-0.5 text-xs font-bold uppercase tracking-wider text-brand">
                        Management
                    </span>
                    <div class="mt-1 flex items-center gap-3">
                        <h2 class="font-league text-4xl font-black uppercase text-gray-900">Payments</h2>
                        <span class="rounded-xl border-2 border-gray-900 bg-amber-300 px-3 py-1 text-xs font-black text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            {{ number_format($paymentCount) }} transactions
                        </span>
                    </div>
                    <p class="mt-1 text-xs font-semibold text-gray-500">Track and review all booking payments.</p>
                </div>

                <!-- Card Summary Total Paid -->
                <div class="text-right rounded-xl border-2 border-gray-900 bg-green-100 px-5 py-3 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                    <p class="text-3xs font-black uppercase tracking-wider text-green-900">Total Paid</p>
                    <p class="font-montserrat text-3xl font-black text-green-900">Rp {{ number_format($paidTotal, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <!-- Filter Form Container Retro -->
        <form method="GET" action="{{ $filterUrl ?? url()->current() }}" class="rounded-2xl border-2 border-gray-900 bg-white p-5 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <div class="relative lg:col-span-2">
                    <label for="payment-search" class="sr-only">Search payments</label>
                    <div class="pointer-events-none absolute inset-y-0 inset-s-0 flex items-center ps-3 text-gray-400">
                        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2.5" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/>
                        </svg>
                    </div>
                    <input type="search" name="search" id="payment-search" value="{{ $currentFilters['search'] }}"
                           placeholder="Search customer, phone, booking ID, or reference"
                           class="block w-full rounded-xl border-2 border-gray-900 py-2.5 ps-10 text-xs font-bold text-gray-900 placeholder:text-gray-400 focus:border-brand focus:ring-0">
                </div>

                <select name="method" class="rounded-xl border-2 border-gray-900 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                    <option value="">All Methods</option>
                    @foreach ($methodOptions as $value => $label)
                        <option value="{{ $value }}" @selected($currentFilters['method'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="payment_source" class="rounded-xl border-2 border-gray-900 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                    <option value="">All Sources</option>
                    @foreach ($sourceOptions as $value => $label)
                        <option value="{{ $value }}" @selected($currentFilters['payment_source'] === $value)>
                            {{ strtoupper($label) }}
                        </option>
                    @endforeach
                </select>

                <select name="status" class="rounded-xl border-2 border-gray-900 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                    <option value="">All Status</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($currentFilters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <div id="payment-date-range" date-rangepicker datepicker-format="yyyy-mm-dd" datepicker-autohide data-range-picker class="flex items-center gap-2 lg:col-span-2">
                    <div class="relative w-full">
                        <div class="pointer-events-none absolute inset-y-0 inset-s-0 flex items-center ps-3">
                            <svg class="h-4 w-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1H8V1a1 1 0 0 0-2 0v1H4a2 2 0 0 0-2 2v2h18V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1Z"/>
                            </svg>
                        </div>
                        <input name="date_from" type="text" data-date-format="yyyy-mm-dd" value="{{ $currentFilters['date_from'] }}" placeholder="Start date" autocomplete="off"
                               class="block w-full rounded-xl border-2 border-gray-900 bg-white p-2.5 ps-10 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                    </div>
                    <span class="text-xs font-black text-gray-500">to</span>
                    <div class="relative w-full">
                        <div class="pointer-events-none absolute inset-y-0 inset-s-0 flex items-center ps-3">
                            <svg class="h-4 w-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1H8V1a1 1 0 0 0-2 0v1H4a2 2 0 0 0-2 2v2h18V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1Z"/>
                            </svg>
                        </div>
                        <input name="date_to" type="text" data-date-format="yyyy-mm-dd" value="{{ $currentFilters['date_to'] }}" placeholder="End date" autocomplete="off"
                               class="block w-full rounded-xl border-2 border-gray-900 bg-white p-2.5 ps-10 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t-2 border-dashed border-gray-200 pt-3">
                <p class="text-[11px] font-semibold text-gray-500">Search by customer name, phone number, booking ID, or payment reference.</p>
                <div class="flex gap-2">
                    <a href="{{ $resetUrl ?? url()->current() }}" 
                       class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-white px-4 py-2 text-xs font-black uppercase text-gray-700 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-gray-100 hover:shadow-[5px_5px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none">
                        Reset
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-brand px-4 py-2 text-xs font-black uppercase text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[5px_5px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none">
                        Apply Filter
                    </button>
                </div>
            </div>
        </form>

        <!-- Flash Alerts Retro -->
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

        <!-- Table Outer Container Retro -->
        <div class="overflow-hidden rounded-2xl border-2 border-gray-900 bg-white shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
            <div class="relative overflow-x-auto">
                <table class="w-full min-w-250 text-left text-sm text-gray-500">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <th scope="col" class="px-5 py-4">Payment</th>
                            <th scope="col" class="px-5 py-4">Booking / Customer</th>
                            <th scope="col" class="px-5 py-4">Method & Source</th>
                            <th scope="col" class="px-5 py-4">Purpose</th>
                            <th scope="col" class="px-5 py-4">Amount</th>
                            <th scope="col" class="px-5 py-4">Status</th>
                            <th scope="col" class="px-5 py-4">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($payments as $payment)
                            @php
                                $booking = $payment->booking;
                            @endphp
                            <tr class="bg-white hover:bg-gray-50">
                                <td class="px-5 py-4 align-top">
                                    <div class="font-mono font-bold text-gray-900">#PAY-{{ $payment->id }}</div>
                                    @if ($payment->partner_reference_no)
                                        <div class="mt-1 max-w-50 truncate text-xs text-gray-500" title="{{ $payment->partner_reference_no }}">
                                            Ref: {{ $payment->partner_reference_no }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="font-semibold text-gray-900">
                                        #BK-{{ $booking->id ?? $payment->booking_id }} · {{ $booking->name ?? '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $booking->phone ?? '-' }}</div>
                                    <div class="text-xs text-gray-400">{{ $booking?->barber?->name ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="font-semibold text-gray-900">{{ $methodOptions[$payment->method] ?? str_replace('_', ' ', ucfirst($payment->method)) }}</div>
                                    <div class="text-xs text-gray-500">{{ $payment->payment_source ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        {{ $purposeOptions[$payment->purpose] ?? str_replace('_', ' ', ucfirst($payment->purpose)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-top font-semibold text-gray-900">
                                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $payment->status === 'paid' ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $payment->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                                        {{ $payment->status === 'refunded' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ in_array($payment->status, ['failed', 'expired', 'cancelled'], true) ? 'bg-red-100 text-red-700' : '' }}">
                                        {{ $statusOptions[$payment->status] ?? str_replace('_', ' ', ucfirst($payment->status)) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 align-top whitespace-nowrap text-xs text-gray-500">
                                    {{ $payment->created_at?->format('d M Y, H:i') ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">No payments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Container (Justify Between) -->
        <div class="flex flex-col justify-between gap-3 text-xs font-bold text-gray-600 sm:flex-row sm:items-center">
            <p>
                Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $paymentCount }} results
            </p>
            <div class="w-full sm:w-auto [&>nav]:flex [&>nav]:w-full [&>nav]:items-center [&>nav]:justify-between">
                {{ $payments->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection