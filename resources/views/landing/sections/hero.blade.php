<div class="flex-1 flex flex-col lg:flex-row items-center justify-evenly py-6">
    <!-- Kolom Kiri: Galeri Foto (Grid Layout) -->
    <div class="w-full lg:w-full flex flex-col gap-4">
        <!-- Foto Atas (Utama) -->
        <div class="w-full h-64 sm:h-80 md:h-96 rounded-2xl overflow-hidden shadow-sm">
            <img src="{{ asset('storage/hero1.jpg') }}" alt="Barber Cutting Hair" class="w-full h-full object-cover">
        </div>

        <!-- Foto Bawah (Grid 2 Kolom) -->
        <div class="grid grid-cols-2 gap-4">
            <!-- Foto Kiri Bawah (Logo Overlay / Red Tint) -->
            <div class="relative w-full h-40 sm:h-52 rounded-2xl overflow-hidden shadow-sm">
                <img src="{{ asset('storage/hero2.jpg') }}" alt="Kref Barber Interior" class="w-full h-full object-cover">
                <!-- Overlay Merah Datar/Transparan -->
                <div class="absolute inset-0 bg-[#A64545]/80 flex flex-col items-center justify-center p-4">
                    <img src="{{ asset('storage/Logo.svg') }}" alt="Kref Barber Logo" class="w-24 sm:w-28 h-auto object-contain">
                </div>
            </div>

            <!-- Foto Kanan Bawah (Detail Haircut) -->
            <div class="w-full h-40 sm:h-52 rounded-2xl overflow-hidden shadow-sm">
                <img src="{{ asset('storage/hero3.jpg') }}" alt="Hair Styling Detail" class="w-full h-full object-cover">
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Tipografi & Tombol CTA -->
    <div class="w-full lg:w-1/2 flex flex-col items-center lg:items-center text-center lg:text-right md:pl-7 pt-3">
        <!-- Headline -->
        <div class="font-montserrat tracking-tight leading-none">
            <!-- CUT & + Mascot Icon -->
            <div class="flex items-end justify-between lg:justify-between gap-3 text-5xl sm:text-8xl md:text-8xl font-black text-black">
                <span class="transform scale-y-120 pb-2 md:pb-5">CUT</span>
                <span class="transform scale-y-120 pb-2 md:pb-5">&</span>
                <img src="{{ asset('storage/Logo2.svg') }}" alt="Kref Barber Icon" class="w-14 sm:w-20 md:w-28 lg:w-32 h-auto object-fill shrink-0">
            </div>
            <!-- REFRESH (Warna Merah Bata) -->
            <h1 class="text-5xl sm:text-8xl md:text-8xl font-black text-primary transform scale-y-120">
                REFRESH
            </h1>
        </div>

        <!-- Deskripsi Teks -->
        <p class="mt-6 md:mt-9 font-dosis text-gray-700 text-lg sm:text-xl max-w-md leading-relaxed">
            Find a style that feels like you.<br class="hidden sm:inline">
            Simple, sharp, and made to last.
        </p>

        <!-- Tombol Booking Now -->
        <a href="#" class="mt-8 inline-flex items-center gap-3 bg-primary hover:bg-[#963333] text-white font-dosis text-xl font-semibold px-7 py-3 rounded-xl shadow-md transition-colors">
            <span>booking now</span>
            <!-- Icon Panah Kanan -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </a>
    </div>
</div>