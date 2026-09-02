@extends('layouts.layout')

@section('content')
    <div x-data="bookingForm(@js($services), @js($barbers), '{{ now()->format('m-d-Y') }}')" class="min-h-screen">
        @include('partials.header')

        <div class="flex flex-col items-center w-full mt-5">
            <div class="grid grid-cols-3 items-center w-full py-2">
                <a href="/" class="flex items-center gap-2 text-left cursor-pointer hover:opacity-75 transition-opacity justify-self-start">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 rotate-180">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                    <span class="underline underline-offset-2">back home</span>
                </a>

                <div class="flex items-center justify-center font-league text-primary">
                    <span class="inline-block">BOOKING PAGE</span>
                    <img src="{{ asset('storage/Logo2.svg') }}" alt="Logo" class="w-7 h-7 object-contain shrink-0">
                </div>

                <div></div>
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