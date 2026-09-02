<header
    x-data="{ open: false }"
    class="relative bg-white py-3 transition-all duration-200 shadow-none border-b-0 md:border-b md:border-black "
>
    <!-- Grid Layout: Mobile (2 Kolom), Desktop (3 Kolom) -->
    <div class="grid grid-cols-2 md:grid-cols-3 items-center min-h-10">
        
        <!-- Kolom 1 (Kiri): Hamburger di Mobile, Logo di Desktop -->
        <div class="flex items-center justify-start">
            <!-- Tombol Hamburger (Mobile Only - Pojok Kiri) -->
            <button 
                @click="open = !open" 
                class="md:hidden z-20 focus:outline-none cursor-pointer group p-1 rounded-md hover:bg-black/5 transition-colors" 
                aria-label="Toggle menu"
            >
                <div class="w-7 h-5 relative flex flex-col justify-between items-center">
                    <span
                        class="block absolute h-0.5 w-7 bg-black rounded-full transition-all duration-300 ease-in-out group-hover:bg-primary"
                        :class="open ? 'rotate-45 top-2' : 'top-0'"
                    ></span>
                    <span
                        class="block absolute h-0.5 w-7 bg-black rounded-full transition-all duration-300 ease-in-out top-2 group-hover:bg-primary"
                        :class="open ? 'opacity-0' : 'opacity-100'"
                    ></span>
                    <span
                        class="block absolute h-0.5 w-7 bg-black rounded-full transition-all duration-300 ease-in-out group-hover:bg-primary"
                        :class="open ? '-rotate-45 top-2' : 'bottom-0'"
                    ></span>
                </div>
            </button>

            <!-- Logo (Desktop Only - Pojok Kiri) -->
            <a href="#hero" class="hidden md:block z-20 shrink-0">
                <img 
                    src="{{ asset('storage/logo.svg') }}" 
                    alt="Logo Barbershop" 
                    class="h-9 w-auto object-contain"
                />
            </a>
        </div>

        <!-- Kolom 2 (Tengah): Navigasi Desktop -->
        <nav class="hidden md:flex items-center justify-center gap-8">
            <a href="#hero" class="font-dosis text-lg font-bold hover:text-primary transition-colors">Home</a>
            <a href="#about" class="font-dosis text-lg font-bold hover:text-primary transition-colors">About</a>
            <a href="#services" class="font-dosis text-lg font-bold hover:text-primary transition-colors">Services</a>
            <a href="#barbers" class="font-dosis text-lg font-bold hover:text-primary transition-colors">Barbers</a>
            <a href="#gallery" class="font-dosis text-lg font-bold hover:text-primary transition-colors">Gallery</a>
            <a href="#testimonial" class="font-dosis text-lg font-bold hover:text-primary transition-colors">Testimonial</a>
            <a href="#location" class="font-dosis text-lg font-bold hover:text-primary transition-colors">Location</a>
        </nav>

        <!-- Kolom 3 (Kanan): Empty Placeholder (Menjaga simetris grid 3 kolom desktop) -->
        <div class="hidden md:block"></div>

    </div>

    <!-- Overlay Mobile -->
    <div
        x-show="open"
        x-cloak
        @click="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/45 z-30 md:hidden"
    ></div>

    <!-- Navigasi Mobile Dropdown Overlay -->
    <nav
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="absolute left-4 right-4 top-full mt-2 bg-white z-40 md:hidden px-5 py-4 flex flex-col gap-3 text-left rounded-2xl shadow-xl border border-black/10"
    >
        <!-- Logo di Dalam Dropdown Mobile -->
        <div class="pb-2 border-b border-gray-100">
            <a @click="open = false" href="#hero" class="inline-block">
                <img 
                    src="{{ asset('storage/logo.svg') }}" 
                    alt="Logo Barbershop" 
                    class="h-8 w-auto object-contain"
                />
            </a>
        </div>

        <!-- Links Mobile -->
        <a @click="open = false" href="#hero" class="block w-full rounded-lg font-dosis text-lg font-bold text-black hover:text-primary">Home</a>
        <a @click="open = false" href="#about" class="block w-full rounded-lg font-dosis text-lg font-bold text-black hover:text-primary">About</a>
        <a @click="open = false" href="#services" class="block w-full rounded-lg font-dosis text-lg font-bold text-black hover:text-primary">Services</a>
        <a @click="open = false" href="#barbers" class="block w-full rounded-lg font-dosis text-lg font-bold text-black hover:text-primary">Barbers</a>
        <a @click="open = false" href="#gallery" class="block w-full rounded-lg font-dosis text-lg font-bold text-black hover:text-primary">Gallery</a>
        <a @click="open = false" href="#testimonial" class="block w-full rounded-lg font-dosis text-lg font-bold text-black hover:text-primary">Testimonial</a>
        <a @click="open = false" href="#location" class="block w-full rounded-lg font-dosis text-lg font-bold text-black hover:text-primary">Location</a>
    </nav>
</header>