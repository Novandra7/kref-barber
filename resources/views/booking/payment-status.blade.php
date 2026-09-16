@extends('layouts.layout')

@section('content')
    @include('components.booking.toast')

    <main class="mx-auto flex min-h-screen max-w-lg items-center justify-center px-4 py-10 font-sans">
        <div class="relative w-full">
            <!-- Vintage Decorative Badge (Pojok Kanan Atas) -->
            <div class="absolute -top-3 -right-3 z-10 hidden rotate-6 rounded-md border-2 border-gray-900 bg-amber-300 px-3 py-1 text-xs font-black uppercase tracking-widest text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] sm:block">
                Receipt
            </div>

            <!-- Card Utama Neo-Brutalist -->
            <section class="relative overflow-hidden rounded-2xl border-2 border-gray-900 bg-[#FAF8F5] p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] sm:p-8">

                <!-- Header Section -->
                <div class="mb-6 flex items-start justify-between gap-4 border-b-2 border-dashed border-gray-300 pb-4">
                    <div>
                        <span class="inline-block rounded-full border border-gray-900 bg-brand/10 px-3 py-0.5 text-xs font-bold uppercase tracking-wider text-brand">
                            KREF Barber
                        </span>
                        <h3 class="mt-2 font-league text-5xl font-black uppercase text-gray-900">
                            {{ strtolower($paymentStatus) === 'pending' ? 'Booking Payment' : 'Payment Receipt' }}
                        </h3>
                        <p class="font-mono text-xs font-medium text-gray-500">Ref: {{ $reference }}</p>
                    </div>

                    <!-- Logo Section -->
                    <div class="shrink-0 rounded-xl border-2 border-gray-900 bg-white p-2 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                        <img src="{{ asset('images/Logo.svg') }}" alt="Logo" class="h-14 w-auto object-contain">
                    </div>
                </div>

                <!-- Payment Status Box (Full-width) -->
                <div class="mb-6">
                    <div class="rounded-xl border-2 border-gray-900 p-3 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] {{ strtolower($paymentStatus) === 'paid' ? 'bg-emerald-100' : 'bg-amber-100' }}">
                        <p class="text-2xs font-bold uppercase tracking-wider text-gray-700">Payment Status</p>
                        <p class="mt-0.5 text-lg font-black uppercase tracking-wide {{ strtolower($paymentStatus) === 'paid' ? 'text-emerald-900' : 'text-gray-900' }}">
                            {{ Str::headline($paymentStatus) }}
                        </p>
                    </div>
                </div>

                <!-- Section QRIS jika Pending -->
                @if (strtolower($paymentStatus) === 'pending' && $qrContent)
                    <div class="mb-6 rounded-xl border-2 border-gray-900 bg-white p-4 text-center shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-900">Scan QRIS Untuk Membayar</p>

                        <div class="mt-3 flex flex-col items-center justify-center rounded-lg border-2 border-gray-900 bg-[#FAF8F5] p-4 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            {!! QrCode::size(200)->generate($qrContent) !!}
                            <div class="mt-3 text-xs font-medium text-gray-600">
                                <span>Valid until:</span>
                                <strong class="block font-bold text-gray-900">{{ $expiresAt }}</strong>
                            </div>
                        </div>

                        <p class="mt-4 text-xs font-bold uppercase tracking-wider text-gray-700">
                            Nominal Pembayaran: <strong class="text-sm font-black text-gray-900">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</strong>
                        </p>
                        <p class="mt-1 text-[11px] font-medium text-gray-500">Link halaman ini juga telah dikirim via pesan WhatsApp.</p>
                    </div>
                @endif

                <!-- Section List Bookings (Booking Status per-card) -->
                @if ($bookings->isNotEmpty())
                    <div class="space-y-4">
                        @foreach ($bookings as $booking)
                            @php
                                $bStatus = $booking->status ?? 'pending';
                                $isCancelledOrRequested = in_array(strtolower($bStatus), ['cancel_requested', 'cancelled']);
                                $bookingStatusColor = $isCancelledOrRequested ? 'bg-red-100 text-red-900' : 'bg-blue-100 text-blue-900';
                            @endphp
                            <div class="rounded-xl border-2 border-gray-900 bg-white p-4 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">

                                <!-- Header card: nama + tanggal -->
                                <div class="flex items-center justify-between border-b-2 border-dashed border-gray-200 pb-2">
                                    <p class="font-black uppercase text-gray-900">{{ $booking->name }}</p>
                                    <span class="text-xs font-bold text-gray-500">
                                        {{ $booking->scheduled_at?->format('d M Y, H:i') ?? '-' }}
                                    </span>
                                </div>

                                <!-- Barber + Booking Status dalam satu baris -->
                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <p class="text-xs font-semibold text-gray-600">
                                        Barber: <span class="text-gray-900">{{ $booking->barber?->name ?? '-' }}</span>
                                    </p>
                                    <span class="shrink-0 rounded-full border border-gray-900 px-2 py-0.5 text-2xs font-black uppercase tracking-wider {{ $bookingStatusColor }}">
                                        {{ str_replace('_', ' ', $bStatus) }}
                                    </span>
                                </div>

                                @if ($booking->items->isNotEmpty())
                                    <div class="mt-3 space-y-2 pt-1">
                                        <p class="text-2xs font-black uppercase tracking-widest text-gray-400">Services</p>
                                        @foreach ($booking->items as $item)
                                            <div class="flex items-center justify-between text-xs font-medium text-gray-800">
                                                <span>
                                                    {{ $item->service_name_snapshot ?? $item->service?->name ?? 'Service' }}
                                                    @if ($item->qty > 1)
                                                        <span class="font-bold text-gray-500">({{ $item->qty }}x)</span>
                                                    @endif
                                                </span>
                                                <span class="font-bold text-gray-900">
                                                    Rp {{ number_format($item->price_snapshot * $item->qty, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-3 flex items-center justify-between border-t-2 border-gray-900 pt-2 text-xs font-black uppercase text-gray-900">
                                        <span>Total Booking</span>
                                        <span>Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Action Buttons -->
                @php
                    $cancelableBookings    = $bookings->filter(fn($b) => !in_array(strtolower((string)$b->status), ['cancelled', 'cancel_requested']));
                    $reschedulableBookings = $bookings->filter(fn($b) => !in_array(strtolower((string)$b->status), ['cancelled', 'cancel_requested', 'reschedule_requested']));

                    $allCancelled          = $cancelableBookings->isEmpty();
                    $allRequested          = $bookings->isNotEmpty() && $bookings->every(fn($b) => strtolower((string)$b->status) === 'cancel_requested');
                    $hasReschedulable      = $reschedulableBookings->isNotEmpty();

                    $mainPayment           = $bookings->first()?->payment;
                    $isDokuRefundSupported = $mainPayment?->canBeRefundedViaDoku() ?? false;
                    $paymentSourceLabel    = $mainPayment?->payment_source ?: 'QRIS';
                @endphp

                @if (session('wa_refund_url'))
                    <div class="mt-5 rounded-2xl border-2 border-emerald-600 bg-emerald-50 p-4 text-center shadow-[3px_3px_0px_0px_rgba(5,150,105,1)]">
                        <div class="flex items-center justify-center gap-1.5 text-xs font-black uppercase text-emerald-900">
                            <span>✅ Pembatalan Diajukan</span>
                        </div>
                        <p class="mt-1 text-xs font-medium text-emerald-800">
                            Metode <strong>{{ $paymentSourceLabel }}</strong> diproses secara manual. Silakan kirimkan nomor rekening Anda ke WhatsApp Admin.
                        </p>
                        <a href="{{ session('wa_refund_url') }}" target="_blank" rel="noopener noreferrer"
                           class="mt-3 inline-flex items-center justify-center gap-2 rounded-xl border-2 border-gray-900 bg-emerald-600 px-4 py-2 text-xs font-black uppercase tracking-wider text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:bg-emerald-700 hover:shadow-none">
                            <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824zm-3.423-14.416c-6.627 0-12 5.373-12 12 0 2.164.577 4.195 1.583 5.952l-1.683 6.149 6.331-1.661c1.704.931 3.659 1.471 5.769 1.471 6.627 0 12-5.373 12-12s-5.373-12-12-12z"/>
                            </svg>
                            Kirim Rekening ke Admin WA
                        </a>
                    </div>
                @endif

                <div class="mt-6 flex flex-col items-center justify-between gap-3 sm:flex-row">

                    {{-- Tombol Cancel --}}
                    @if ($allCancelled)
                        <button type="button" disabled
                                class="w-full flex-1 cursor-not-allowed rounded-xl border-2 border-gray-400 bg-gray-200 px-3 py-3 text-center text-[11px] font-black uppercase tracking-wider text-gray-500 opacity-80 shadow-none">
                            {{ $allRequested ? 'Cancel Requested' : 'Cancelled' }}
                        </button>
                    @else
                        <button data-modal-target="cancel-modal"
                                data-modal-toggle="cancel-modal"
                                class="w-full flex-1 rounded-xl border-2 border-gray-900 bg-red-100 px-3 py-3 text-center text-[11px] font-black uppercase tracking-wider text-red-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]"
                                type="button">
                            Cancel / Refund
                        </button>
                    @endif

                    {{-- Tombol Reschedule --}}
                    @if (! $hasReschedulable)
                        <button type="button" disabled
                                class="w-full flex-1 cursor-not-allowed rounded-xl border-2 border-gray-400 bg-gray-200 px-4 py-3 text-center text-xs font-black uppercase tracking-wider text-gray-500 opacity-80 shadow-none">
                            Reschedule
                        </button>
                    @elseif ($reschedulableBookings->count() > 1)
                        <button data-modal-target="reschedule-modal"
                                data-modal-toggle="reschedule-modal"
                                class="w-full flex-1 rounded-xl border-2 border-gray-900 bg-brand px-4 py-3 text-center text-xs font-black uppercase tracking-wider text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]"
                                type="button">
                            Reschedule
                        </button>
                    @else
                        <a href="{{ route('booking.reschedule', ['reference' => $reschedulableBookings->first()?->id ?? $reference]) }}"
                           class="w-full flex-1 rounded-xl border-2 border-gray-900 bg-brand px-4 py-3 text-center text-xs font-black uppercase tracking-wider text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                            Reschedule
                        </a>
                    @endif
                </div>

                <!-- Footer Stamp -->
                <div class="mt-6 text-center">
                    <p class="text-2xs font-mono uppercase tracking-widest text-gray-400">
                        Official Invoice &bull; KREF Barber System
                    </p>
                </div>
            </section>
        </div>
    </main>

    <!-- Modal Cancel -->
    <div id="cancel-modal" tabindex="-1" class="backdrop-blur-xs fixed top-0 right-0 left-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 md:inset-0">
        <div class="relative max-h-full w-full max-w-md p-4">
            <div class="relative rounded-2xl border-2 border-gray-900 bg-[#FAF8F5] p-4 text-gray-900 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] md:p-6">

                <!-- Tombol Close -->
                <button type="button"
                        class="absolute top-3 inset-e-3 inline-flex h-8 w-8 items-center justify-center rounded-lg border-2 border-gray-900 bg-transparent text-sm text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:bg-gray-200"
                        data-modal-hide="cancel-modal">
                    <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18 17.94 6M18 18 6.06 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>

                <!-- Isi Modal -->
                <div class="p-2 text-center md:p-4">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl border-2 border-gray-900 bg-amber-300 text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                        <svg class="h-8 w-8" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 13V8m0 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>

                    <h3 class="text-md mb-3 font-semibold text-gray-900">
                        Apakah Anda yakin ingin membatalkan atau me-refund booking ini?
                    </h3>

                    @if (! $isDokuRefundSupported)
                        <div class="mb-4 rounded-xl border-2 border-amber-400 bg-amber-50 p-3 text-left shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <p class="text-xs font-bold text-amber-900 flex items-center gap-1.5">
                                <svg class="h-4 w-4 shrink-0 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Refund Manual via Admin
                            </p>
                            <p class="mt-1 text-[11px] leading-relaxed text-amber-800">
                                Pembayaran via <strong>{{ $paymentSourceLabel }}</strong> diproses secara manual. Setelah mengajukan, Anda akan diarahkan ke WhatsApp Admin untuk konfirmasi nomor rekening.
                            </p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('booking.cancel', ['reference' => $reference]) }}">
                        @csrf

                        {{-- Pilih booking jika lebih dari 1 --}}
                        @if ($cancelableBookings->count() > 1)
                            <div class="mb-4 text-left">
                                <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-700">
                                    Pilih Booking yang Ingin Dibatalkan
                                </label>
                                <select name="booking_id"
                                        class="w-full rounded-xl border-2 border-gray-900 bg-white px-3 py-2.5 text-sm font-semibold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:outline-none">
                                    @foreach ($cancelableBookings as $cb)
                                        <option value="{{ $cb->id }}">
                                            {{ $cb->name }} — {{ $cb->scheduled_at?->format('d M Y, H:i') ?? '-' }}
                                            ({{ $cb->barber?->name ?? '-' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="booking_id" value="{{ $cancelableBookings->first()?->id }}">
                        @endif

                        <div class="flex items-center justify-center gap-3">
                            <!-- Tombol Utama: Lebar otomatis menyesuaikan teks panjang -->
                            <button type="submit"
                                    class="whitespace-nowrap rounded-xl border-2 border-gray-900 bg-red-500 px-4 py-2.5 text-xs font-black uppercase tracking-wider text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                {{ ! $isDokuRefundSupported ? 'Ya, Ajukan & Buka WA' : 'Ya, Batalkan' }}
                            </button>

                            <!-- Tombol Kembali: Mengikuti tinggi & mengisi sisa ruang fleksibel -->
                            <button data-modal-hide="cancel-modal" type="button"
                                    class="flex-1 self-stretch rounded-xl border-2 border-gray-900 bg-white px-4 py-2.5 text-center text-xs font-black uppercase tracking-wider text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                Kembali
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    {{-- Modal Reschedule (hanya muncul jika lebih dari 1 booking yang dapat di-reschedule) --}}
    @if ($reschedulableBookings->count() > 1)
        <div id="reschedule-modal" tabindex="-1" class="backdrop-blur-xs fixed top-0 right-0 left-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 md:inset-0">
            <div class="relative max-h-full w-full max-w-md p-4">
                <div class="relative rounded-2xl border-2 border-gray-900 bg-[#FAF8F5] p-4 text-gray-900 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] md:p-6">

                    <!-- Tombol Close -->
                    <button type="button"
                            class="absolute top-3 inset-e-3 inline-flex h-8 w-8 items-center justify-center rounded-lg border-2 border-gray-900 bg-transparent text-sm text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:bg-gray-200"
                            data-modal-hide="reschedule-modal">
                        <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18 17.94 6M18 18 6.06 6"/>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>

                    <!-- Isi Modal -->
                    <div class="p-2 text-center md:p-4">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl border-2 border-gray-900 bg-brand/20 text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <svg class="h-8 w-8" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16M8 14h.01M12 14h.01M16 14h.01M5 20h14a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1Z"/>
                            </svg>
                        </div>

                        <h3 class="text-md mb-4 font-semibold text-gray-900">
                            Pilih booking yang ingin di-reschedule
                        </h3>

                        <div class="mb-4 text-left">
                            <label class="mb-1 block text-xs font-black uppercase tracking-wider text-gray-700">
                                Pilih Booking
                            </label>
                            <select id="reschedule-select"
                                    class="w-full rounded-xl border-2 border-gray-900 bg-white px-3 py-2.5 text-sm font-semibold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:outline-none">
                                @foreach ($reschedulableBookings as $rb)
                                    <option value="{{ $rb->id }}">
                                        {{ $rb->name }} — {{ $rb->scheduled_at?->format('d M Y, H:i') ?? '-' }}
                                        ({{ $rb->barber?->name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <a id="reschedule-link" href="#"
                           class="block w-full rounded-xl border-2 border-gray-900 bg-brand px-4 py-2.5 text-xs font-black uppercase tracking-wider text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                            Lanjut Reschedule
                        </a>
                    </div>

                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Toast notification
                const toastEl = document.getElementById('toast-notification');
                if (toastEl) {
                    setTimeout(() => {
                        toastEl.classList.remove('translate-y-8', 'opacity-0');
                        toastEl.classList.add('translate-y-0', 'opacity-100');
                    }, 50);

                    setTimeout(() => {
                        toastEl.classList.remove('translate-y-0', 'opacity-100');
                        toastEl.classList.add('translate-y-8', 'opacity-0');
                        setTimeout(() => toastEl.remove(), 500);
                    }, 4000);
                }

                // Reschedule modal: update link saat dropdown berubah
                const rescheduleSelect = document.getElementById('reschedule-select');
                const rescheduleLink   = document.getElementById('reschedule-link');

                if (rescheduleSelect && rescheduleLink) {
                    const updateLink = () => {
                        const bookingId = rescheduleSelect.value;
                        rescheduleLink.href = bookingId ? `/booking/reschedule/${bookingId}` : '#';
                    };
                    rescheduleSelect.addEventListener('change', updateLink);
                    updateLink(); // set initial
                }

                @if (session('wa_refund_url'))
                    setTimeout(() => {
                        window.open(@json(session('wa_refund_url')), '_blank');
                    }, 600);
                @endif
            });
        </script>
    @endpush
@endsection
