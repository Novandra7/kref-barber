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

            <div class="mt-6 rounded-lg bg-brand/10 p-4">
                <p class="text-sm text-gray-500">Payment status</p>
                <p class="mt-1 text-xl font-bold text-gray-900">{{ ucfirst($status) }}</p>
            </div>

            @if ($status === 'pending' && $qrContent)
                <div class="mt-6 border-t border-gray-200 pt-6">
                    <p class="text-sm font-semibold text-gray-900">Scan QRIS untuk menyelesaikan pembayaran</p>
                    <div class="mt-4 flex flex-col justify-center items-center gap-2 rounded-lg border border-gray-200 bg-white p-4">
                        {!! QrCode::size(220)->generate($qrContent) !!}
                        <div class="mt-2 flex flex-col items-center justify-center gap-1 text-sm text-gray-500">
                            <p>Valid until:</p>
                            <strong>{{ $expiresAt }}</strong>
                        </div>
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

                            @if ($booking->items->isNotEmpty())
                                <div class="mt-4 border-t border-gray-100 pt-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Services</p>
                                    <div class="mt-2 space-y-2">
                                        @foreach ($booking->items as $item)
                                            <div class="flex items-start justify-between gap-4 text-sm">
                                                <div class="text-gray-700">
                                                    <p>{{ $item->service_name_snapshot ?? $item->service?->name ?? 'Service' }}</p>
                                                    @if ($item->qty > 1)
                                                        <p class="text-xs text-gray-500">{{ $item->qty }}x</p>
                                                    @endif
                                                </div>
                                                <span class="shrink-0 font-medium text-gray-900">
                                                    Rp {{ number_format($item->price_snapshot * $item->qty, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 text-sm font-semibold text-gray-900">
                                        <span>Total booking</span>
                                        <span>Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
@endsection
