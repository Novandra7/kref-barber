<header class="flex items-center justify-between border-b-2 border-gray-900 bg-[#FAF8F5] px-4 py-3 md:px-6">
    <!-- Sisi Kiri: Mobile Trigger & Title -->
    <div class="flex items-center gap-3">
        <!-- Tombol Hamburger (Mobile Only) -->
        <button @click="mobileMenuOpen = true" class="rounded-xl border-2 mr-2 border-gray-900 bg-red-500 p-2 text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all active:translate-x-0.5 active:translate-y-0.5 active:shadow-none md:hidden">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-brand">Admin Area</p>
            <h1 class="font-montserrat text-lg font-black uppercase text-gray-900">@yield('header', 'Dashboard')</h1>
        </div>
    </div>

    <!-- Sisi Kanan: Action Buttons -->
    <div class="flex items-center gap-3">
        <!-- View Website Button -->
        <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-gray-900 bg-white px-3.5 py-2 text-xs font-bold uppercase tracking-wider text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-amber-50 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none">
            <svg class="h-4 w-4 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            <span class="hidden sm:inline">View Website</span>
        </a>

        <!-- Logout Button -->
        <form method="POST" action="{{ route('logout') }}" class="inline-block">
            @csrf
            <button type="submit" title="Logout" class="group flex items-center justify-center rounded-xl border-2 border-gray-900 bg-red-500 p-2 text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-red-600 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none sm:gap-2 sm:px-3 sm:py-2">
                <svg class="h-4 w-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                </svg>
                <span class="hidden text-xs font-black uppercase tracking-wider sm:inline">Logout</span>
            </button>
        </form>
    </div>
</header>