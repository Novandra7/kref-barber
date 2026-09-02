<style>
    .barberpole {
        width: 100%;
        height: 24px;
        border-radius: 8px;
        background-image: repeating-linear-gradient(
            45deg,
            #e53935 0px,
            #e53935 20px,
            #ffffff 20px,
            #ffffff 40px,
            #1e3a8a 40px,
            #1e3a8a 60px
        );
        background-size: 84.85px 84.85px; /* 60px * sqrt(2), biar seamless */
        animation: barberpole-move 1.5s linear infinite;
        }

        @keyframes barberpole-move {
            from {
                background-position: 0 0;
            }
            to {
                background-position: 0 84.85px; /* gerak horizontal ke kiri */
        }
}

@media (max-width: 639px) {
    #hero > div {
        gap: 0.5rem;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    #hero .hero-gallery {
        flex: 1 1 auto;
        gap: 0.5rem;
    }

    #hero .hero-main-image {
        height: clamp(9rem, 24svh, 13rem);
    }

    #hero .hero-secondary-image {
        height: clamp(5.5rem, 13svh, 7.5rem);
    }

    #hero .hero-content {
        gap: 0.375rem;
    }

    #hero .hero-headline {
        font-size: clamp(3.5rem, 17vw, 4.5rem);
    }

    #hero .hero-description {
        margin-top: 0.125rem;
    }

    #hero .hero-cta {
        margin-top: 0.375rem;
    }
}

@media (max-width: 390px) and (max-height: 700px) {
    #hero > div {
        min-height: calc(100svh - 4rem);
    }

    #hero .hero-main-image {
        height: 9.5rem;
    }

    #hero .hero-secondary-image {
        height: 5.75rem;
    }

    #hero .hero-headline {
        font-size: 3.5rem;
    }

    #hero .hero-description {
        font-size: 0.8125rem;
        line-height: 1.15;
    }

    #hero .hero-cta {
        padding: 0.5rem 1.125rem;
        font-size: 0.9375rem;
    }
}
</style>
<section id="hero" class="w-full">
    <div class="flex min-h-[calc(100svh-4.5rem)] flex-col items-center gap-2 lg:min-h-0 lg:justify-between">
        <div class="flex min-h-0 w-full flex-1 flex-col gap-3 sm:mt-4 sm:gap-4 lg:flex-row lg:items-center lg:justify-between lg:gap-8">
            
            <!-- Kolom Kiri: Galeri Foto -->
            <div class="flex min-h-0 w-full flex-1 flex-col gap-2 sm:gap-4">
                <!-- Foto Atas -->
                <div class="h-[clamp(9rem,26svh,16rem)] w-full overflow-hidden rounded-xl shadow-sm sm:h-80 sm:aspect-auto md:h-96">
                    <img src="{{ asset('images/hero1.jpg') }}" alt="Barber Cutting Hair" class="h-full w-full object-cover">
                </div>
        
                <!-- Foto Bawah -->
                <div class="grid min-h-0 flex-1 grid-cols-2 gap-2 sm:flex-none sm:gap-4">
                    <div class="relative min-h-[6rem] w-full overflow-hidden rounded-xl shadow-sm sm:h-48 sm:min-h-0 sm:aspect-auto">
                        <img src="{{ asset('images/hero2.jpg') }}" alt="Kref Barber Interior" class="h-full w-full object-cover">
                        <div class="absolute inset-0 flex flex-col items-center justify-center bg-[#A64545]/80 p-2">
                            <img src="{{ asset('images/Logo.svg') }}" alt="Kref Barber Logo" class="h-auto w-16 sm:w-24 object-contain">
                        </div>
                    </div>
        
                    <div class="min-h-[6rem] w-full overflow-hidden rounded-xl shadow-sm sm:h-48 sm:min-h-0 sm:aspect-auto">
                        <img src="{{ asset('images/hero3.jpg') }}" alt="Hair Styling Detail" class="h-full w-full object-cover">
                    </div>
                </div>
            </div>
        
            <!-- Kolom Kanan: Teks & CTA -->
            <div class="flex shrink-0 flex-col items-center gap-2 text-center lg:items-end lg:text-right">
                <div class="inline-flex select-none flex-col font-league leading-none tracking-wide">
                    <div class="flex items-center justify-center gap-2 text-7xl font-black text-black sm:text-7xl lg:justify-between md:text-[140px]">
                        <span>CUT</span>
                        <span>&</span>
                        <img src="{{ asset('images/Logo2.svg') }}" alt="Kref Barber Icon" class="h-[0.85em] w-auto shrink-0 object-contain">
                    </div>
        
                    <span class="-mt-1 text-7xl font-black text-primary sm:text-7xl md:text-[140px]">
                        REFRESH
                    </span>
                </div>
        
                <p class="max-w-xs font-dosis text-sm leading-snug text-gray-700 sm:mt-4 sm:max-w-md sm:text-lg">
                    Find a style that feels like you.<br class="hidden sm:inline">
                    Simple, sharp, and made to last.
                </p>
        
                <a href="{{ route('booking.index') }}" class="my-2 inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 font-dosis text-base font-semibold text-white shadow-md transition-colors hover:bg-[#963333] sm:mt-6 sm:px-7 sm:py-3 sm:text-lg">
                    <span>booking now</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4 sm:h-5 sm:w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>
    
            <!-- Barberpole Footer -->
        </div>
        <div class="barberpole w-full shrink-0 border md:mt-2"></div>
    </div>
</section>