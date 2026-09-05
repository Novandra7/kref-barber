@extends('layouts.layout')

@section('content')
    <main class="mx-auto flex min-h-screen max-w-xl items-center justify-center px-4 py-10">
        <section class="w-full rounded-xl border border-gray-200 bg-white p-6 text-center shadow-sm">
            <div class="flex justify-center items-center gap-2">
                <img src="{{ asset("images/Logo.svg") }} " alt="Logo" class="h-5 w-auto">
                <p class="text-sm font-semibold uppercase tracking-wide text-brand">KREF Barber</p>
            </div>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Booking Payment</h1>
            <p class="mt-2 text-sm text-gray-500">Reference: {{ $reference }}</p>

            <div class="mt-6 rounded-lg bg-gray-50 p-4">
                <p class="text-sm text-gray-500">Payment status</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ ucfirst($status) }}</p>
            </div>

            @if ($status === 'pending' && $qrContent)
                <div class="mt-6 border-t border-gray-200 pt-6">
                    <p class="text-sm font-semibold text-gray-900">Scan QRIS untuk menyelesaikan pembayaran</p>
                    <div class="mt-4 flex justify-center rounded-lg border border-gray-200 bg-white p-4">
                        {!! QrCode::size(220)->generate($qrContent) !!}
                    </div>
                    <p class="mt-3 text-sm text-gray-500">
                        Nominal pembayaran: <strong class="text-gray-900">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</strong>
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Halaman ini dapat dibuka kembali menggunakan link pembayaran pada pesan WhatsApp.</p>
                </div>
            @endif

            @if ($bookings->isNotEmpty())
                <div class="mt-6 space-y-3 text-left">
                    @foreach ($bookings as $booking)
                        <div class="rounded-lg border border-gray-200 p-4">
                            <p class="font-semibold text-gray-900">{{ $booking->name }}</p>
                            <p class="text-sm text-gray-500">
                                {{ $booking->barber?->name ?? '-' }} · {{ $booking->scheduled_at?->format('d M Y, H:i') ?? '-' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
@endsection
