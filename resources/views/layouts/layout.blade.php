<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Kref') . ' | Online Booking & Appointment')</title>
    <meta name="description" content="@yield('meta_description', 'Book haircut online di Kref Barber. Lihat service, barber, portfolio, lokasi, dan jam operasional dalam satu halaman.')">

    <!-- Preconnect & Fonts diletakkan sebelum Asset/Vite -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dosis:wght@400;500;600;700&family=League+Gothic&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <!-- Hapus @fonts jika sudah menggunakan link Google Fonts di atas -->

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-white px-4 md:px-8 lg:px-14 overflow-x-hidden">
    @yield('content')
</body>
</html>