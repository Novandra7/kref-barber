<header class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-4 md:px-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Admin Area</p>
        <h1 class="font-montserrat text-lg font-bold text-gray-900">@yield('header', 'Dashboard')</h1>
    </div>

    <a href="{{ route('landing') }}" class="text-sm font-semibold text-primary hover:underline">
        View Website
    </a>
</header>
