<section id="gallery" class="py-5">
    {{-- <hr class="border-t-2 border-gray-200 my-5"> --}}
    <div class="flex flex-col items-start gap-8">
        <div class="inline-block">
            <h2 class="text-5xl md:text-7xl font-league">GALLERY</h2>
            <div class="h-1.5 mt-3 bg-red-500 w-full"></div>
        </div>
        <p>Fresh cuts, clean details, real results.</p>
        <div class="grid w-full grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-4">
                <div class="h-48 md:h-52 overflow-hidden rounded-2xl">
                    <img 
                        src={{ asset("storage/hero1.jpg") }} 
                        alt="Barber chair" 
                        class="w-full h-full object-cover"
                    />
                </div>

                <!-- Gambar 2 (Bawah Kiri - Haircut Process) -->
                <div class="h-64 md:h-72 overflow-hidden rounded-2xl">
                    <img 
                        src={{ asset("storage/hero2.jpg") }} 
                        alt="Hairdresser styling hair" 
                        class="w-full h-full object-cover"
                    />
                </div>
            </div>
            <div class="flex flex-col gap-4">
                <!-- Gambar 3 (Atas Kanan - Haircut Result) -->
                <div class="h-64 md:h-72 overflow-hidden rounded-2xl">
                    <img 
                        src={{ asset("storage/hero3.jpg") }} 
                        alt="Man with a fresh haircut" 
                        class="w-full h-full object-cover"
                    />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <!-- Gambar 4 (Bawah Kanan Kiri - Close-up Clipper) -->
                    <div class="h-48 md:h-52 overflow-hidden rounded-2xl">
                        <img 
                            src={{ asset("storage/hero1.jpg") }} 
                            alt="Hair trimmer close up" 
                            class="w-full h-full object-cover"
                        />
                    </div>

                    <!-- Gambar 5 (Bawah Kanan Kanan - Hair Spray) -->
                    <div class="h-48 md:h-52 overflow-hidden rounded-2xl">
                        <img 
                            src={{ asset("storage/hero2.jpg") }} 
                            alt="Barber applying hair spray" 
                            class="w-full h-full object-cover"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
