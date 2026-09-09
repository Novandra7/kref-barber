<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') | {{ config('app.name', 'Kref') }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dosis:wght@400;500;600;700&family=League+Gothic&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF8F5] text-gray-900 selection:bg-amber-300 selection:text-gray-900" x-data="{ mobileMenuOpen: false }">
    <div class="flex min-h-screen">
        <!-- Sidebar Component (Desktop & Mobile) -->
        @include('admin.partials.sidebar')

        <!-- Main Content Area -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Header Component -->
            @include('admin.partials.header')

            <!-- Page Content -->
            <main class="flex-1 p-4 md:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>