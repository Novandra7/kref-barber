<section id="hero">
    <div class="flex-1 flex flex-col lg:flex-row items-center justify-evenly py-6">
        <!-- Kolom Kiri: Galeri Foto (Grid Layout) -->
        <div class="w-full lg:w-full flex flex-col gap-4">
            <!-- Foto Atas (Utama) -->
            <div class="w-full h-64 sm:h-80 md:h-96 rounded-2xl overflow-hidden shadow-sm">
                <img src="{{ asset('images/hero1.jpg') }}" alt="Barber Cutting Hair" class="w-full h-full object-cover">
            </div>
    
            <!-- Foto Bawah (Grid 2 Kolom) -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Foto Kiri Bawah (Logo Overlay / Red Tint) -->
                <div class="relative w-full h-40 sm:h-52 rounded-2xl overflow-hidden shadow-sm">
                    <img src="{{ asset('images/hero2.jpg') }}" alt="Kref Barber Interior" class="w-full h-full object-cover">
                    <!-- Overlay Merah Datar/Transparan -->
                    <div class="absolute inset-0 bg-[#A64545]/80 flex flex-col items-center justify-center p-4">
                        <img src="{{ asset('images/Logo.svg') }}" alt="Kref Barber Logo" class="w-24 sm:w-28 h-auto object-contain">
                    </div>
                </div>
    
                <!-- Foto Kanan Bawah (Detail Haircut) -->
                <div class="w-full h-40 sm:h-52 rounded-2xl overflow-hidden shadow-sm">
                    <img src="{{ asset('images/hero3.jpg') }}" alt="Hair Styling Detail" class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    
        <!-- Kolom Kanan: Tipografi & Tombol CTA -->
        <div class="w-full lg:w-1/2 flex flex-col items-center text-center lg:text-right md:pl-7 pt-3">
            <!-- Headline Wrapper -->
            <div class="inline-flex flex-col font-league leading-none select-none">
                
                <!-- Baris 1: CUT & [Icon] -->
                <div class="flex items-center gap-3 text-8xl sm:text-7xl md:text-[180px] font-black text-black">
                    <span>CUT</span>
                    <span>&</span>
                    <img src="{{ asset('images/Logo2.svg') }}" alt="Kref Barber Icon" class="h-[1.1em] w-auto object-contain shrink-0">
                </div>
    
                <!-- Baris 2: REFRESH -->
                <span class="text-8xl sm:text-8xl md:text-[180px] font-black text-primary -mt-1 sm:-mt-2">
                    REFRESH
                </span>
    
            </div>
    
            <!-- Deskripsi Teks -->
            <p class="mt-4 md:mt-9 font-dosis text-gray-700 text-lg sm:text-xl max-w-md leading-relaxed">
                Find a style that feels like you.<br class="hidden sm:inline">
                Simple, sharp, and made to last.
            </p>
    
            <!-- Tombol Booking Now -->
            <a href="{{ route('booking.index') }}" class="mt-4 md:mt-8 inline-flex items-center gap-3 bg-primary hover:bg-[#963333] text-white font-dosis text-xl font-semibold px-7 py-3 rounded-xl shadow-md transition-colors">
                <span>booking now</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>
        </div>
    </div>
</section>