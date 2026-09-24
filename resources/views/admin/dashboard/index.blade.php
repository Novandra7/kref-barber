@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
    <div class="space-y-8">
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
        @if (session('info'))
            <div class="rounded-xl border-2 border-gray-900 bg-blue-100 p-4 text-xs font-bold text-blue-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                ℹ️ {{ session('info') }}
            </div>
        @endif

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

        <!-- Section Dual Table (Recent Bookings & Pending Requests) -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            
            <!-- 1. Tabel Recent Bookings -->
            <div class="lg:col-span-7">
                <div class="overflow-hidden rounded-2xl border-2 border-gray-900 bg-white py-3 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="font-montserrat font-bold text-gray-900">Recent Bookings</h3>
                    </div>

                    <div class="relative overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="whitespace-nowrap px-6 py-3">Customer</th>
                                    <th scope="col" class="whitespace-nowrap px-6 py-3">Barber</th>
                                    <th scope="col" class="whitespace-nowrap px-6 py-3">Schedule</th>
                                    <th scope="col" class="whitespace-nowrap px-6 py-3">Amount</th>
                                    <th scope="col" class="whitespace-nowrap px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($recentBookings as $booking)
                                    @php
                                        $rawStatus = strtolower(str_replace(' ', '_', $booking['status']));                                        
                                        $badgeStyle = match ($rawStatus) {
                                            'pending'               => 'bg-amber-300 text-gray-900',
                                            'confirmed'             => 'bg-sky-300 text-gray-900',
                                            'completed'             => 'bg-emerald-300 text-gray-900',
                                            'cancel_requested'      => 'bg-orange-300 text-gray-900',
                                            'reschedule_requested'  => 'bg-indigo-300 text-gray-900',
                                            'cancelled', 'canceled' => 'bg-red-300 text-gray-900',
                                            default                 => 'bg-gray-200 text-gray-700',
                                        };
                                    @endphp
                                    <tr class="bg-white hover:bg-gray-50">
                                        <td class="max-w-35 truncate whitespace-nowrap px-6 py-4 font-semibold text-gray-900" title="{{ $booking['customer'] }}">
                                            {{ $booking['customer'] }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">{{ $booking['barber'] }}</td>
                                        <td class="whitespace-nowrap px-6 py-4">{{ $booking['schedule'] }}</td>
                                        <td class="whitespace-nowrap px-6 py-4">{{ $booking['amount'] }}</td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <!-- Neo-Brutalist Badge -->
                                            <span class="inline-flex items-center rounded-lg border-2 border-gray-900 px-2.5 py-0.5 text-2xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] {{ $badgeStyle }}">
                                                {{ $booking['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-xs text-gray-400">
                                            No data found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 2. Tabel Pending Requests -->
            <div class="lg:col-span-5">
                <div class="overflow-hidden rounded-2xl border-2 border-gray-900 bg-white py-3 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                        <h3 class="font-montserrat font-bold text-gray-900">Pending Requests</h3>
                        <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-amber-300 px-2.5 py-0.5 text-2xs font-black uppercase tracking-wider text-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                            Action Needed
                        </span>
                    </div>

                    <div class="relative overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Customer</th>
                                    <th scope="col" class="px-4 py-3">Request</th>
                                    <th scope="col" class="px-4 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($pendingRequests as $req)
                                    <tr class="bg-white hover:bg-gray-50">
                                        <td class="px-4 py-3 font-semibold text-gray-900">
                                            <p class="max-w-30 truncate" title="{{ $req['customer'] }}">{{ $req['customer'] }}</p>
                                            <p class="text-2xs font-normal text-gray-400">{{ $req['phone'] }}</p>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($req['type'] === 'cancel_requested')
                                                <!-- Neo-Brutalist Badge Cancel -->
                                                <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-red-300 px-2.5 py-0.5 text-2xs font-black uppercase tracking-wider text-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                                    Cancel
                                                </span>
                                            @else
                                                <!-- Neo-Brutalist Badge Reschedule -->
                                                <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-blue-300 px-2.5 py-0.5 text-2xs font-black uppercase tracking-wider text-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                                    Reschedule
                                                </span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-center">
                                            <button 
                                                type="button" 
                                                data-modal-target="processModal-{{ $req['id'] }}" 
                                                data-modal-toggle="processModal-{{ $req['id'] }}"
                                                class="cursor-pointer rounded-lg border-2 border-gray-900 bg-amber-300 px-3 py-1 text-xs font-black uppercase tracking-wider text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none"
                                            >
                                                Process
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-xs text-gray-400">
                                            No pending requests.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modals for Pending Requests (Cancel / Reschedule) -->
    @foreach ($pendingRequests as $req)
        <div id="processModal-{{ $req['id'] }}" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 p-4 backdrop-blur-xs">
            <div class="relative w-full max-w-md">
                <div class="relative rounded-2xl border-2 border-gray-900 bg-[#FAF8F5] p-6 text-gray-900 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
                    
                    <!-- Header Modal -->
                    <div class="flex items-center justify-between border-b-2 border-dashed border-gray-300 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-block rounded-full border border-gray-900 px-2.5 py-0.5 text-2xs font-black uppercase tracking-wider {{ $req['type'] === 'cancel_requested' ? 'bg-red-200 text-red-900' : 'bg-blue-200 text-blue-900' }}">
                                {{ $req['type'] === 'cancel_requested' ? 'Cancellation' : 'Reschedule' }}
                            </span>
                            <h3 class="font-montserrat text-lg font-black uppercase text-gray-900">#BK-{{ $req['id'] }}</h3>
                        </div>
                        <button type="button" data-modal-hide="processModal-{{ $req['id'] }}" class="inline-flex h-8 w-8 items-center justify-center border-2 border-gray-900 bg-white text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:bg-gray-100 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] cursor-pointer">
                            <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18 17.94 6M18 18 6.06 6"/>
                            </svg>
                            <span class="sr-only">Close</span>
                        </button>
                    </div>

                    <!-- Detail Booking Ringkas -->
                    <div class="mt-4 rounded-xl border-2 border-gray-900 bg-white p-3.5 text-xs shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] space-y-2">
                        <div class="flex justify-between">
                            <span class="font-bold text-gray-500">Customer:</span>
                            <span class="font-black text-gray-900">{{ $req['customer'] }} ({{ $req['phone'] }})</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-bold text-gray-500">Barber:</span>
                            <span class="font-semibold text-gray-900">{{ $req['barber'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-bold text-gray-500">Amount:</span>
                            <span class="font-black text-gray-900">{{ $req['amount'] }}</span>
                        </div>
                        @if ($req['type'] === 'cancel_requested')
                            <div class="flex justify-between border-t border-dashed border-gray-200 pt-2">
                                <span class="font-bold text-gray-500">Jadwal:</span>
                                <span class="font-semibold text-gray-900">{{ $req['schedule'] }}</span>
                            </div>
                        @else
                            <div class="border-t border-dashed border-gray-200 pt-2 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-gray-400">Jadwal Semula:</span>
                                    <span class="font-medium text-gray-400 line-through">{{ $req['schedule'] }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-black text-gray-900">Jadwal Baru:</span>
                                    <span class="font-black text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-300">
                                        {{ $req['requested_schedule'] ?? '-' }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($req['type'] === 'cancel_requested')
                        <!-- Tindakan Cancel Request -->
                        <div class="mt-4 text-xs font-semibold text-gray-600">
                            <p>Menyetujui pembatalan ini akan:</p>
                            <ul class="mt-1 list-disc list-inside space-y-0.5 text-gray-700">
                                <li>Mengubah status booking menjadi <strong class="text-red-600">Cancelled</strong></li>
                                <li>Membebaskan slot jadwal barber agar tersedia kembali</li>
                                @if (($req['payment']?->provider ?? '') === 'doku')
                                    <li>Memproses refund otomatis melalui DOKU QRIS</li>
                                @endif
                            </ul>
                        </div>

                        <div class="mt-6 flex items-center justify-center gap-3">
                            <form method="POST" action="{{ $req['action_url'] }}" class="flex-1">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="w-full rounded-xl border-2 border-gray-900 bg-red-500 px-4 py-2.5 text-center text-xs font-black uppercase tracking-wider text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] cursor-pointer">
                                    Accept & Cancel
                                </button>
                            </form>
                            <button type="button" data-modal-hide="processModal-{{ $req['id'] }}" class="flex-1 rounded-xl border-2 border-gray-900 bg-white px-4 py-2.5 text-center text-xs font-black uppercase tracking-wider text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] cursor-pointer">
                                Tutup
                            </button>
                        </div>
                    @else
                        <p class="mt-4 text-center text-2xs text-gray-500 leading-relaxed">
                            Pilih <strong>Accept</strong> untuk menyetujui jadwal baru, atau <strong>Tolak</strong> untuk membatalkan permintaan dan mempertahankan jadwal semula.
                        </p>

                        <!-- Action Buttons: Tolak, Accept & Tutup -->
                        <div class="mt-6 space-y-2.5">
                            <div class="flex items-center justify-center gap-3">
                                <form method="POST" action="{{ $req['action_url'] }}" class="flex-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="confirmed">
                                    <input type="hidden" name="action" value="reject_reschedule">
                                    <button type="submit" class="w-full rounded-xl border-2 border-gray-900 bg-red-400 px-3 py-2.5 text-center text-xs font-black uppercase tracking-wider text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] cursor-pointer">
                                        Tolak
                                    </button>
                                </form>
                                <form method="POST" action="{{ $req['action_url'] }}" class="flex-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="confirmed">
                                    <button type="submit" class="w-full rounded-xl border-2 border-gray-900 bg-emerald-400 px-3 py-2.5 text-center text-xs font-black uppercase tracking-wider text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] cursor-pointer">
                                        Accept & Reschedule
                                    </button>
                                </form>
                            </div>
                            <button type="button" data-modal-hide="processModal-{{ $req['id'] }}" class="w-full rounded-xl border-2 border-gray-900 bg-white px-4 py-2 text-center text-xs font-black uppercase tracking-wider text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] cursor-pointer">
                                Tutup
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    @endforeach
@endsection
