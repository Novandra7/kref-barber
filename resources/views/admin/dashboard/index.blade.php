@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
    <div class="space-y-8">
        <!-- Container Header Dashboard -->
        <div class="rounded-2xl border-2 border-gray-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
            <h2 class="font-montserrat text-2xl font-black uppercase text-gray-900">Overview</h2>
            <p class="mt-1 text-xs font-semibold text-gray-500">Monitor bookings, payments, services, and barbers.</p>
        </div>

        <!-- Container Grid Statistik -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($stats as $stat)
                <div class="rounded-2xl border-2 border-gray-900 bg-white p-5 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ $stat['label'] }}</p>
                    <p class="mt-2 font-montserrat text-3xl font-black text-brand">{{ $stat['value'] }}</p>
                </div>
            @endforeach
        </div>

        <!-- Container Tabel Recent Bookings -->
        <div class="overflow-hidden rounded-2xl border-2 border-gray-900 bg-white py-3 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
            <div class="overflow-hidden rounded-xl borderbg-white">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="font-montserrat font-bold text-gray-900">Recent Bookings</h3>
                </div>

                <div class="relative overflow-x-auto">
                    <table id="{{ $tableId }}" class="w-full text-left text-sm text-gray-500">
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
                            @forelse ($recentBookings as $rowIndex => $booking)
                                <tr class="bg-white hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4 font-semibold text-gray-900">{{ $booking['customer'] }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $booking['barber'] }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $booking['schedule'] }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">{{ $booking['amount'] }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                            {{ $booking['status'] }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <button
                                            type="button"
                                            data-modal-target="{{ $tableId }}-modal-{{ $rowIndex }}"
                                            data-modal-toggle="{{ $tableId }}-modal-{{ $rowIndex }}"
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

            @foreach ($recentBookings as $rowIndex => $booking)
                <div
                    id="{{ $tableId }}-modal-{{ $rowIndex }}"
                    tabindex="-1"
                    aria-hidden="true"
                    class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0"
                >
                    <div class="relative max-h-full w-full max-w-2xl">
                        <div class="relative rounded-lg bg-white shadow">
                            <div class="flex items-center justify-between rounded-t border-b border-gray-200 p-4 md:p-5">
                                <h3 class="font-montserrat text-xl font-semibold text-gray-900">
                                    Recent Bookings Detail
                                </h3>
                                <button
                                    type="button"
                                    data-modal-hide="{{ $tableId }}-modal-{{ $rowIndex }}"
                                    class="ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900"
                                    aria-label="Close modal"
                                >
                                    <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="space-y-4 p-4 md:p-5">
                                @foreach ($booking['details'] as $detail)
                                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3 text-sm last:border-b-0">
                                        <span class="font-medium text-gray-500">{{ $detail['label'] }}</span>
                                        <span class="text-right font-semibold text-gray-900">{{ $detail['value'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection