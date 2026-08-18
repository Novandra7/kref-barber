<section class="border-y border-white/10 bg-stone-900/40">
    <div class="mx-auto max-w-7xl px-6 py-16">
        <p class="text-sm uppercase tracking-[0.3em] text-amber-400">Testimonials</p>
        <h2 class="mt-2 text-3xl font-semibold text-white">Apa kata customer</h2>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach ($testimonials as $testimonial)
                <blockquote class="rounded-3xl border border-white/10 bg-white/5 p-6 text-stone-300">
                    <p>{{ $testimonial['text'] }}</p>
                    <footer class="mt-4 font-semibold text-white">{{ $testimonial['name'] }}</footer>
                </blockquote>
            @endforeach
        </div>
    </div>
</section>
