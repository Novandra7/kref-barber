<section id="services" class="border-y border-white/10 bg-stone-900/40">
    <div class="mx-auto max-w-7xl px-6 py-16">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-[0.3em] text-amber-400">Services</p>
                <h2 class="mt-2 text-3xl font-semibold text-white">Pilihan service utama</h2>
            </div>
            <p class="max-w-xl text-sm text-stone-400">Contoh data sementara dari master service, akan diganti dari database nanti.</p>
        </div>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach ($services as $service)
                <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
                    <h3 class="text-xl font-semibold text-white">{{ $service['name'] }}</h3>
                    <p class="mt-2 text-3xl font-bold text-amber-400">{{ $service['price'] }}</p>
                    <p class="mt-1 text-sm text-stone-400">{{ $service['duration'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
