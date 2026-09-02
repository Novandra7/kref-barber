@extends('layouts.layout')

@section('content')
    @include('partials.header')
    <main>
        @include('landing.sections.hero')
        @include('landing.sections.about')
        @include('landing.sections.services')
        @include('landing.sections.barbers')
        @include('landing.sections.gallery')
        @include('landing.sections.testimonials')
        @include('landing.sections.location')
    </main>
    @include('partials.footer')
@endsection