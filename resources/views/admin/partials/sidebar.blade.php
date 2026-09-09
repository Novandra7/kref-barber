<!-- ================= DESKTOP SIDEBAR ================= -->
<aside class="hidden w-64 shrink-0 border-r-2 border-gray-900 bg-[#FAF8F5] md:flex md:flex-col">
    <!-- Logo Header -->
    <div class="flex items-center gap-3 px-5 py-4">
        <div class="shrink-0 rounded-lg border-2 border-gray-900 bg-brand p-1.5 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
            <img src="{{ asset('images/Logo.svg') }}" class="h-7 w-auto object-contain" alt="KREF Logo">
        </div>
        <a href="{{ route('admin.dashboard') }}" class="font-league text-2xl uppercase text-gray-900 hover:text-brand">
            Kref Admin
        </a>
    </div>

    <!-- Navigation Menu Desktop -->
    <nav class="flex flex-1 flex-col gap-2 p-4 text-xs font-black uppercase tracking-wider" aria-label="Admin navigation">
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center gap-3 rounded-xl border-2 border-gray-900 px-4 py-3 transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-brand text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' : 'bg-white text-gray-700 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-amber-50 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' }}">
            <span class="text-base">📊</span>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('admin.bookings.index') }}"
           class="flex items-center gap-3 rounded-xl border-2 border-gray-900 px-4 py-3 transition-all {{ request()->routeIs('admin.booking.*', 'admin.bookings.*') ? 'bg-brand text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' : 'bg-white text-gray-700 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-amber-50 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' }}">
            <span class="text-base">📅</span>
            <span>Bookings</span>
        </a>

        <a href="{{ route('admin.services.index') }}"
           class="flex items-center gap-3 rounded-xl border-2 border-gray-900 px-4 py-3 transition-all {{ request()->routeIs('admin.services.*') ? 'bg-brand text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' : 'bg-white text-gray-700 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-amber-50 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' }}">
            <span class="text-base">💈</span>
            <span>Services</span>
        </a>

        <a href="{{ route('admin.barbers.index') }}"
           class="flex items-center gap-3 rounded-xl border-2 border-gray-900 px-4 py-3 transition-all {{ request()->routeIs('admin.barbers.*') ? 'bg-brand text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' : 'bg-white text-gray-700 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-amber-50 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' }}">
            <span class="text-base">✂️</span>
            <span>Barbers</span>
        </a>

        <a href="{{ route('admin.schedules.index') }}"
           class="flex items-center gap-3 rounded-xl border-2 border-gray-900 px-4 py-3 transition-all {{ request()->routeIs('admin.schedules.*') ? 'bg-brand text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' : 'bg-white text-gray-700 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-amber-50 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' }}">
            <span class="text-base">⏰</span>
            <span>Schedules</span>
        </a>

        <a href="{{ route('admin.payments.index') }}" 
           class="flex items-center gap-3 rounded-xl border-2 border-gray-900 px-4 py-3 transition-all {{ request()->routeIs('admin.payments.*') ? 'bg-brand text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' : 'bg-white text-gray-700 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-amber-50 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' }}">
            <span class="text-base">💳</span>
            <span>Payments</span>
        </a>
    </nav>

    <!-- Footer Status -->
    <div class="border-t-2 border-gray-900 p-4">
        <div class="rounded-xl border-2 border-gray-900 bg-white p-3 text-center shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
            <p class="text-2xs font-mono font-bold uppercase text-gray-500">System Status</p>
            <p class="text-xs font-black uppercase text-green-600">● Operational</p>
        </div>
    </div>
</aside>

<!-- ================= MOBILE SIDEBAR DRAWER ================= -->
<div x-show="mobileMenuOpen" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex md:hidden" 
     style="display: none;">

    <!-- Backdrop Overlay Gelap -->
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="mobileMenuOpen = false"></div>

    <!-- Panel Off-Canvas -->
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="relative flex w-4/5 max-w-xs flex-1 flex-col border-r-2 border-gray-900 bg-[#FAF8F5] pb-4 pt-4 shadow-[6px_0px_0px_0px_rgba(17,24,39,1)]">

        <!-- Header Drawer & Close Button -->
        <div class="flex items-center justify-between border-b-2 border-gray-900 bg-white px-4 pb-3 pt-1">
            <div class="flex items-center gap-2.5">
                <div class="rounded-lg border-2 border-gray-900 bg-brand p-1 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                    <img src="{{ asset('images/Logo.svg') }}" class="h-6 w-auto object-contain" alt="KREF Logo">
                </div>
                <span class="font-league text-xl uppercase text-gray-900">Kref Admin</span>
            </div>
            <button @click="mobileMenuOpen = false" class="rounded-lg border-2 border-gray-900 bg-red-400 p-1.5 text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Navigation Menu Mobile -->
        <nav class="mt-4 flex-1 space-y-2 px-3 text-xs font-black uppercase tracking-wider">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 rounded-xl border-2 border-gray-900 px-4 py-3 transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-brand text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' : 'bg-white text-gray-700 active:bg-amber-50' }}">
                <span class="text-base">📊</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('admin.bookings.index') }}"
               class="flex items-center gap-3 rounded-xl border-2 border-gray-900 px-4 py-3 transition-all {{ request()->routeIs('admin.booking.*', 'admin.bookings.*') ? 'bg-brand text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' : 'bg-white text-gray-700 active:bg-amber-50' }}">
                <span class="text-base">📅</span>
                <span>Bookings</span>
            </a>

            <a href="{{ route('admin.services.index') }}"
               class="flex items-center gap-3 rounded-xl border-2 border-gray-900 px-4 py-3 transition-all {{ request()->routeIs('admin.services.*') ? 'bg-brand text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' : 'bg-white text-gray-700 active:bg-amber-50' }}">
                <span class="text-base">💈</span>
                <span>Services</span>
            </a>

            <a href="{{ route('admin.barbers.index') }}"
               class="flex items-center gap-3 rounded-xl border-2 border-gray-900 px-4 py-3 transition-all {{ request()->routeIs('admin.barbers.*') ? 'bg-brand text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' : 'bg-white text-gray-700 active:bg-amber-50' }}">
                <span class="text-base">✂️</span>
                <span>Barbers</span>
            </a>

            <a href="{{ route('admin.schedules.index') }}"
               class="flex items-center gap-3 rounded-xl border-2 border-gray-900 px-4 py-3 transition-all {{ request()->routeIs('admin.schedules.*') ? 'bg-brand text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' : 'bg-white text-gray-700 active:bg-amber-50' }}">
                <span class="text-base">⏰</span>
                <span>Schedules</span>
            </a>

            <a href="{{ route('admin.payments.index') }}"
               class="flex items-center gap-3 rounded-xl border-2 border-gray-900 px-4 py-3 transition-all {{ request()->routeIs('admin.payments.*') ? 'bg-brand text-white shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' : 'bg-white text-gray-700 active:bg-amber-50' }}">
                <span class="text-base">💳</span>
                <span>Payments</span>
            </a>
        </nav>

        <!-- Footer Drawer -->
        <div class="mt-auto border-t-2 border-gray-900 px-3 pt-3">
            <div class="rounded-xl border-2 border-gray-900 bg-white p-3 text-center shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                <p class="text-2xs font-mono font-bold uppercase text-gray-500">System Status</p>
                <p class="text-xs font-black uppercase text-green-600">● Operational</p>
            </div>
        </div>
    </div>
</div>