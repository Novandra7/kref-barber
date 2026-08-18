<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Kref Barber') . ' | Online Booking & Appointment')</title>
    <meta name="description" content="@yield('meta_description', 'Book haircut online di Kref Barber. Lihat service, barber, portfolio, lokasi, dan jam operasional dalam satu halaman.')">

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&family=League+Gothic:wdth,wght@100..150,400&family=Dosis:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-white py-4 px-8 md:px-4 lg:px-14">
    @yield('content')
</body>
</html>
