@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
    <div class="mb-6">
        <h2 class="font-montserrat text-2xl font-bold text-gray-900">Overview</h2>
        <p class="mt-1 text-sm text-gray-500">Monitor bookings, payments, services, and barbers.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Total Bookings', 'value' => $bookingCount],
            ['label' => 'Pending Bookings', 'value' => $pendingBookingCount],
            ['label' => 'Pending Payments', 'value' => $pendingPayments],
            ['label' => 'Active Barbers', 'value' => $barberCount],
            ['label' => 'Active Services', 'value' => $serviceCount],
        ] as $stat)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</p>
                <p class="mt-2 font-montserrat text-3xl font-bold text-primary">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8">
        <x-admin.booking-table :rows="$recentBookings" title="Recent Bookings" />
    </div>
@endsection
