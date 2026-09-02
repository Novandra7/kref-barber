@extends('layouts.layout')

@section('content')
    <div x-data="bookingForm(@js($services), @js($barbers), @js($selectedDate), @js($scheduleData), @js($availableDates))" class="min-h-screen">
        @include('partials.header')

        <div class="flex flex-col items-center w-full mt-5">
            <div class="flex flex-col md:grid md:grid-cols-3 items-center w-full py-2 gap-3 md:gap-0">
                <!-- Tombol Back (Kiri di Desktop, Atas di Mobile) -->
                <a href="/" class="flex items-center gap-2 text-left cursor-pointer hover:opacity-75 transition-opacity justify-self-start self-start md:self-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 rotate-180">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                    <span class="underline underline-offset-2">back home</span>
                </a>

                <!-- Title Booking Page (Tengah di Desktop, Bawah Tombol Back di Mobile) -->
                <div class="flex items-center justify-center font-league text-brand">
                    <span class="text-2xl inline-block">BOOKING PAGE</span>
                </div>

                <!-- Spacer Kolom Kanan Desktop -->
                <div class="hidden md:block"></div>
            </div>

            <div class="w-full text-center">
                <h2 class="inline-block text-2xl sm:text-4xl font-league text-black origin-top">
                    LET'S SET UP YOUR APPOINTMENT
                </h2>
            </div>

            @include('components.booking.stepper')

            <div class="w-full mt-8 mb-3">
                <div x-cloak x-show="currentStep === 1">
                    @include('booking.sections.time-and-barber')
                </div>

                <div x-cloak x-show="currentStep === 2">
                    @include('booking.sections.detail-and-service')
                </div>
              
                <div x-cloak x-show="currentStep === 'guest'">
                    @include('booking.sections.guest')
                </div>

                <div x-cloak x-show="currentStep === 3">
                    @include('booking.sections.payment')
                </div>
            </div>
        </div>
    </div>
@endsection