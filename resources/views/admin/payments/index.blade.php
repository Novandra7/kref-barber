@extends('admin.layouts.app')

@section('title', 'Payments')
@section('header', 'Payments')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-brand">Management</p>
                <div class="mt-1 flex items-center gap-3">
                    <h2 class="font-league text-4xl uppercase text-gray-900">Payments</h2>
                    <span class="rounded-full bg-brand/10 px-3 py-1 text-sm font-semibold text-brand">
                        {{ number_format($paymentCount) }} transactions
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500">Track and review all booking payments.</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white px-5 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Paid</p>
                <p class="text-xl font-bold text-green-600">Rp {{ number_format($paidTotal, 0, ',', '.') }}</p>
            </div>
        </div>

        <form method="GET" action="{{ $filterUrl ?? url()->current() }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <div class="relative lg:col-span-2">
                    <label for="payment-search" class="sr-only">Search payments</label>
                    <div class="pointer-events-none absolute inset-y-0 inset-s-0 flex items-center ps-3 text-gray-400">
                        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/>
                        </svg>
                    </div>
                    <input type="search" name="search" id="payment-search" value="{{ $currentFilters['search'] }}"
                           placeholder="Search customer, phone, booking ID, or reference"
                           class="block w-full rounded-lg border-gray-300 py-2.5 ps-10 text-sm text-gray-900 focus:border-brand focus:ring-brand">
                </div>

                <select name="method" class="rounded-lg border-gray-300 text-sm text-gray-700 focus:border-brand focus:ring-brand">
                    <option value="">All Methods</option>
                    @foreach ($methodOptions as $value => $label)
                        <option value="{{ $value }}" @selected($currentFilters['method'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="provider" class="rounded-lg border-gray-300 text-sm text-gray-700 focus:border-brand focus:ring-brand">
                    <option value="">All Providers</option>
                    @foreach ($providerOptions as $value => $label)
                        <option value="{{ $value }}" @selected($currentFilters['provider'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="status" class="rounded-lg border-gray-300 text-sm text-gray-700 focus:border-brand focus:ring-brand">
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
                <p class="text-xs text-gray-500">Search by customer name, phone number, booking ID, or payment reference.</p>
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
                <table class="w-full min-w-250 text-left text-sm text-gray-500">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <th scope="col" class="px-5 py-4">Payment</th>
                            <th scope="col" class="px-5 py-4">Booking / Customer</th>
                            <th scope="col" class="px-5 py-4">Method & Provider</th>
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
                                    <div class="text-xs uppercase text-gray-500">{{ $payment->payment_source ?? '-' }}</div>
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

        <div class="flex flex-col justify-between gap-3 text-sm text-gray-500 sm:flex-row sm:items-center">
            <p>
                Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $paymentCount }} results
            </p>
            {{ $payments->withQueryString()->links() }}
        </div>
    </div>
@endsection
