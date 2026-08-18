@extends('layouts.landing')

@section('title', 'Kref Barber | Booking')
@section('meta_description', 'Halaman booking Kref Barber untuk memilih service, barber, jadwal, data customer, dan payment type.')

@section('content')
@php
    $services = [
        ['name' => 'Haircut', 'price' => 'Rp 50.000', 'duration' => '30 menit'],
        ['name' => 'Hair Wash', 'price' => 'Rp 20.000', 'duration' => '15 menit'],
        ['name' => 'Beard Trim', 'price' => 'Rp 25.000', 'duration' => '20 menit'],
    ];

    $barbers = [
        ['name' => 'Barber A', 'specialty' => 'Fade & classic cut'],
        ['name' => 'Barber B', 'specialty' => 'Textured crop'],
        ['name' => 'Barber C', 'specialty' => 'Beard shaping'],
    ];
@endphp

<div class="min-h-screen bg-stone-950 text-stone-100">
    @include('partials.booking.header')

    <main class="mx-auto max-w-7xl px-6 py-10">
        <section class="rounded-3xl border border-white/10 bg-white/5 p-8">
            <div class="grid gap-6 lg:grid-cols-3 lg:items-end">
                <div class="lg:col-span-2">
                    <p class="text-sm uppercase tracking-[0.3em] text-amber-400">Booking</p>
                    <h1 class="mt-2 text-3xl font-bold text-white sm:text-5xl">Pilih service, barber, jadwal, lalu lanjut ke payment.</h1>
                    <p class="mt-4 max-w-2xl text-stone-300">
                        Template halaman booking sementara ini mengikuti alur PRD: service → barber → date/time → customer info → summary → payment type.
                    </p>
                </div>
                <div class="rounded-2xl border border-amber-400/20 bg-amber-400/10 p-5 text-sm text-amber-100">
                    <p class="font-semibold text-amber-300">Catatan PRD</p>
                    <p class="mt-2">Customer pilih DP atau Full Payment saat checkout. Walk-in tidak memakai halaman ini.</p>
                </div>
            </div>
        </section>

        <section id="services" class="mt-10 rounded-3xl border border-white/10 bg-stone-900/40 p-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-amber-400">Step 1</p>
                    <h2 class="mt-2 text-2xl font-semibold text-white">Choose Service</h2>
                </div>
                <p class="max-w-xl text-sm text-stone-400">Pilih service utama yang akan dihitung ke summary booking.</p>
            </div>
            <div class="mt-8 grid gap-4 md:grid-cols-3">
                @foreach ($services as $service)
                    <button class="rounded-3xl border border-white/10 bg-white/5 p-6 text-left transition hover:border-amber-400/40 hover:bg-white/10">
                        <p class="text-lg font-semibold text-white">{{ $service['name'] }}</p>
                        <p class="mt-2 text-2xl font-bold text-amber-400">{{ $service['price'] }}</p>
                        <p class="mt-1 text-sm text-stone-400">{{ $service['duration'] }}</p>
                    </button>
                @endforeach
            </div>
        </section>

        <section id="barbers" class="mt-10">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-amber-400">Step 2</p>
                    <h2 class="mt-2 text-2xl font-semibold text-white">Choose Barber</h2>
                </div>
                <p class="max-w-xl text-sm text-stone-400">Barber adalah data operasional, dikelola admin.</p>
            </div>
            <div class="mt-8 grid gap-4 md:grid-cols-3">
                @foreach ($barbers as $barber)
                    <button class="rounded-3xl border border-white/10 bg-white/5 p-6 text-left transition hover:border-amber-400/40 hover:bg-white/10">
                        <div class="h-40 rounded-2xl bg-gradient-to-br from-stone-800 to-stone-700"></div>
                        <p class="mt-4 text-lg font-semibold text-white">{{ $barber['name'] }}</p>
                        <p class="mt-1 text-sm text-stone-400">{{ $barber['specialty'] }}</p>
                    </button>
                @endforeach
            </div>
        </section>

        <section id="schedule" class="mt-10 rounded-3xl border border-white/10 bg-white/5 p-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-amber-400">Step 3</p>
                    <h2 class="mt-2 text-2xl font-semibold text-white">Choose Date and Time</h2>
                </div>
                <p class="max-w-xl text-sm text-stone-400">Slot harus tersedia dan tidak bentrok dengan booking lain.</p>
            </div>

            <div class="mt-8 grid gap-4 lg:grid-cols-2">
                <div class="rounded-3xl border border-white/10 bg-stone-950 p-6">
                    <label class="mb-3 block text-sm font-medium text-stone-300">Date</label>
                    <div class="grid gap-2 sm:grid-cols-3">
                        <button class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-left">Mon, 17 Aug</button>
                        <button class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-left">Tue, 18 Aug</button>
                        <button class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-left">Wed, 19 Aug</button>
                    </div>
                </div>
                <div class="rounded-3xl border border-white/10 bg-stone-950 p-6">
                    <label class="mb-3 block text-sm font-medium text-stone-300">Time Slot</label>
                    <div class="grid gap-2 sm:grid-cols-3">
                        <button class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-emerald-200">09:00</button>
                        <button class="rounded-2xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-rose-200">10:00</button>
                        <button class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-emerald-200">11:00</button>
                    </div>
                </div>
            </div>
        </section>

        <section id="customer" class="mt-10 grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                <p class="text-sm uppercase tracking-[0.3em] text-amber-400">Step 4</p>
                <h2 class="mt-2 text-2xl font-semibold text-white">Customer Information</h2>
                <div class="mt-6 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm text-stone-300">Nama</label>
                        <input type="text" class="w-full rounded-2xl border border-white/10 bg-stone-950 px-4 py-3 text-white placeholder:text-stone-500" placeholder="Nama customer">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-stone-300">Nomor WhatsApp</label>
                        <input type="text" class="w-full rounded-2xl border border-white/10 bg-stone-950 px-4 py-3 text-white placeholder:text-stone-500" placeholder="08xxxxxxxxxx">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-stone-300">Email (optional)</label>
                        <input type="email" class="w-full rounded-2xl border border-white/10 bg-stone-950 px-4 py-3 text-white placeholder:text-stone-500" placeholder="nama@email.com">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm text-stone-300">Catatan (optional)</label>
                        <textarea rows="4" class="w-full rounded-2xl border border-white/10 bg-stone-950 px-4 py-3 text-white placeholder:text-stone-500" placeholder="Preferensi model rambut, catatan tambahan, dll."></textarea>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                <p class="text-sm uppercase tracking-[0.3em] text-amber-400">Customer Flow Notes</p>
                <ul class="mt-4 space-y-3 text-stone-300">
                    <li>• Online booking butuh data customer minimal.</li>
                    <li>• Walk-in tidak lewat halaman ini.</li>
                    <li>• Data nanti dipakai untuk confirmation dan booking history.</li>
                </ul>
            </div>
        </section>

        <section id="summary" class="mt-10 grid gap-6 lg:grid-cols-[2fr_1fr]">
            <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                <p class="text-sm uppercase tracking-[0.3em] text-amber-400">Step 5</p>
                <h2 class="mt-2 text-2xl font-semibold text-white">Booking Summary</h2>
                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-sm text-stone-400">Service</p>
                        <p class="mt-1 font-semibold text-white">Haircut</p>
                    </div>
                    <div>
                        <p class="text-sm text-stone-400">Barber</p>
                        <p class="mt-1 font-semibold text-white">Barber A</p>
                    </div>
                    <div>
                        <p class="text-sm text-stone-400">Date</p>
                        <p class="mt-1 font-semibold text-white">Mon, 17 Aug 2026</p>
                    </div>
                    <div>
                        <p class="text-sm text-stone-400">Time</p>
                        <p class="mt-1 font-semibold text-white">09:00</p>
                    </div>
                    <div>
                        <p class="text-sm text-stone-400">Duration</p>
                        <p class="mt-1 font-semibold text-white">30 menit</p>
                    </div>
                    <div>
                        <p class="text-sm text-stone-400">Total price</p>
                        <p class="mt-1 font-semibold text-white">Rp 50.000</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                <p class="text-sm text-stone-400">Booking Status</p>
                <p class="mt-2 text-3xl font-bold text-amber-400">Pending</p>
                <p class="mt-4 text-sm text-stone-300">Ringkasan ini akan dipakai sebelum lanjut ke payment type selection.</p>
            </div>
        </section>

        <section id="payment" class="mt-10 rounded-3xl border border-white/10 bg-stone-900/40 p-8">
            <p class="text-sm uppercase tracking-[0.3em] text-amber-400">Step 6</p>
            <h2 class="mt-2 text-2xl font-semibold text-white">Choose Payment Type</h2>
            <p class="mt-3 max-w-3xl text-stone-300">
                Sesuai PRD, customer memilih DP atau Full Payment untuk service di awal. Produk tambahan akan ditagihkan terpisah oleh Admin.
            </p>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <button class="rounded-3xl border border-amber-400/40 bg-amber-400/10 p-6 text-left">
                    <p class="text-lg font-semibold text-white">DP</p>
                    <p class="mt-2 text-sm text-stone-300">Bayar sebagian sekarang via Midtrans, sisanya dilunasi setelah service selesai.</p>
                    <p class="mt-4 text-2xl font-bold text-amber-400">Rp 25.000</p>
                </button>
                <button class="rounded-3xl border border-white/10 bg-white/5 p-6 text-left">
                    <p class="text-lg font-semibold text-white">Full Payment</p>
                    <p class="mt-2 text-sm text-stone-300">Bayar seluruh total service sekarang via Midtrans.</p>
                    <p class="mt-4 text-2xl font-bold text-amber-400">Rp 50.000</p>
                </button>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="#" class="rounded-full bg-amber-400 px-6 py-3 font-semibold text-stone-950">Proceed to Payment</a>
                <a href="{{ route('booking.create') }}" class="rounded-full border border-white/15 px-6 py-3 font-semibold text-white">Reset Selection</a>
            </div>
        </section>
    </main>

    @include('partials.booking.footer')
</div>
@endsection
