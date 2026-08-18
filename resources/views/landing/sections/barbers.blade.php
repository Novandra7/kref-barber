<section id="barbers" class="mx-auto max-w-7xl px-6 py-16">
    <p class="text-sm uppercase tracking-[0.3em] text-amber-400">Barbers</p>
    <h2 class="mt-2 text-3xl font-semibold text-white">Barber yang dikelola Admin</h2>
    <div class="mt-8 grid gap-4 md:grid-cols-3">
        @foreach ($barbers as $barber)
            <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
                <div class="h-40 rounded-2xl bg-gradient-to-br from-stone-800 to-stone-700"></div>
                <h3 class="mt-4 text-xl font-semibold text-white">{{ $barber['name'] }}</h3>
                <p class="mt-1 text-stone-400">{{ $barber['specialty'] }}</p>
            </article>
        @endforeach
    </div>
</section>
