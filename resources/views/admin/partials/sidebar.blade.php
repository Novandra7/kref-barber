<aside class="hidden w-64 shrink-0 border-r border-gray-200 bg-white md:flex md:flex-col">
    <!-- Logo Header -->
    <div class="flex items-center gap-3 border-b border-gray-200 px-6 py-5">
        <img src="{{ asset('images/Logo.svg') }}" class="h-10 w-auto" alt="KREF Logo">
        <a href="{{ route('admin.dashboard') }}" class="font-league text-3xl font-bold text-primary">
            KREF ADMIN
        </a>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="flex flex-1 flex-col gap-1 p-4 text-sm font-semibold" aria-label="Admin navigation">
        <!-- Dashboard -->
        <a 
            href="{{ route('admin.dashboard') }}"
            class="rounded-lg px-4 py-3 transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
        >
            Dashboard
        </a>
        <a 
            href="{{ route('admin.bookings.index') }}"
            class="rounded-lg px-4 py-3 transition-colors {{ request()->routeIs('admin.booking.*', 'admin.bookings.*') ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
        >
            Bookings
        </a>
        <a 
            href="{{ route('admin.services.index') }}"
            class="rounded-lg px-4 py-3 transition-colors {{ request()->routeIs('admin.services.*') ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
        >
            Services
        </a>
        <a
            href="{{ route('admin.barbers.index') }}"
            class="rounded-lg px-4 py-3 transition-colors {{ request()->routeIs('admin.barbers.*') ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
        >
            Barbers
        </a>
        <a
            href="{{ route('admin.schedules.index') }}"
            class="rounded-lg px-4 py-3 transition-colors {{ request()->routeIs('admin.schedules.*') ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
        >
            Schedules
        </a>
        <a 
            href="#" 
            class="rounded-lg px-4 py-3 transition-colors {{ request()->routeIs('admin.payments.*') ? 'bg-primary/10 text-primary' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
        >
            Payments
        </a>
    </nav>
</aside>