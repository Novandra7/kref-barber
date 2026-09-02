@extends('layouts.layout')

@section('content')
    <!-- Wrapper khusus Above-the-Fold (Header + Hero = 1 Layar Penuh) -->
    <div class="min-h-screen flex flex-col">
        @include('partials.header')
        
        <div class="flex-1 flex">
            @include('landing.sections.hero')
        </div>
    </div>

    <!-- Section Lainnya Mengalir Normal di Bawahnya -->
    <main>
        @include('landing.sections.about')
        @include('landing.sections.services')
        @include('landing.sections.barbers')
        @include('landing.sections.gallery')
        @include('landing.sections.testimonials')
        @include('landing.sections.location')
    </main>

    @include('partials.footer')
@endsection