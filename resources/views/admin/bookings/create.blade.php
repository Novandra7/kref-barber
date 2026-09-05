@extends('admin.layouts.app')

@section('title', 'Create Booking')
@section('header', 'Create Booking')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-brand">Bookings</p>
            <h2 class="mt-1 font-league text-4xl uppercase text-gray-900">Create Walk-in Booking</h2>
            <p class="mt-1 text-sm text-gray-500">Enter the customer, appointment, service, and payment details.</p>
        </div>
        @include('admin.bookings._form')
    </div>
@endsection
