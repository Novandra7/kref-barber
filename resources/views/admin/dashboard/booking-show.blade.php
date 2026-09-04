@extends('admin.layouts.app')

@section('title', 'Booking #' . $booking->id)
@section('header', 'Booking Detail')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.dashboard.index') }}" class="text-sm font-semibold text-primary hover:underline">
            &larr; Back to dashboard
        </a>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-montserrat text-2xl font-bold text-gray-900">Booking #{{ $booking->id }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $booking->created_at?->format('d M Y H:i') }}</p>
            </div>
            <span class="rounded-full bg-primary/10 px-3 py-1 text-sm font-semibold text-primary">
                {{ str_replace('_', ' ', ucfirst($booking->status)) }}
            </span>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <h3 class="font-montserrat font-bold text-gray-900">Customer & Services</h3>
            <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <p><span class="text-gray-500">Name:</span> {{ $booking->walk_in_customer_name ?? '-' }}</p>
                <p><span class="text-gray-500">Phone:</span> {{ $booking->walk_in_customer_phone ?? '-' }}</p>
                <p><span class="text-gray-500">Barber:</span> {{ $booking->barber?->name ?? '-' }}</p>
                <p><span class="text-gray-500">Schedule:</span> {{ $booking->scheduled_at?->format('d M Y H:i') ?? '-' }}</p>
            </div>

            <div class="mt-6 divide-y divide-gray-100 border-t border-gray-100">
                @foreach ($booking->items as $item)
                    <div class="flex items-center justify-between gap-4 py-3 text-sm">
                        <span>{{ $item->service_name_snapshot ?? $item->service?->name ?? 'Service' }}</span>
                        <span class="font-semibold">Rp {{ number_format($item->price_snapshot * $item->qty, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="font-montserrat font-bold text-gray-900">Payment Summary</h3>
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Type</span>
                    <span class="font-semibold">{{ strtoupper($booking->payment_type) }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Payment status</span>
                    <span class="font-semibold">{{ str_replace('_', ' ', ucfirst($booking->payment_status)) }}</span>
                </div>
                <div class="flex justify-between gap-3 border-t border-gray-100 pt-3">
                    <span class="font-bold">Total</span>
                    <span class="font-bold text-primary">Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-gray-500">Outstanding</span>
                    <span class="font-semibold">Rp {{ number_format($booking->outstanding_amount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
