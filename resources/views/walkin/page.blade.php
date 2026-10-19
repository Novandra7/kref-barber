<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KREF Walk-in</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dosis:wght@400;500;600;700&family=League+Gothic&family=Montserrat:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
    @php
        $initialFormState = [
            'bookingType'           => old('booking_type', 'now'),
            'selectedBarberId'      => (string) old('barber_id', $selectedBarber ?? ''),
            'selectedScheduledTime' => (string) old('scheduled_time', ''),
            'customerName'          => (string) old('name', ''),
            'customerPhone'         => (string) old('phone', ''),
            'isChemical'            => (bool) old('is_chemical', false),
            'scheduledPaymentType'  => (string) old('payment_type', 'dp'),
            'scheduledDpAmount'     => (int) old('dp_amount', 40000),
            'scheduledPaymentMethod'=> (string) old('dp_payment_method', 'cash'),
        ];
    @endphp
    <div class="max-w-lg mx-auto pb-16 sm:pb-20 pt-4 sm:pt-6 px-3 sm:px-4" x-data="walkInApp(@js($availableSchedules), '{{ $selectedBarber ?? '' }}', '{{ $selectedSource ?? '' }}', @js($initialFormState))">
        
        <!-- Header -->
        <div class="mb-5 sm:mb-8 rounded-2xl border-2 border-gray-900 bg-brand p-3 sm:p-4 text-center shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
            <h1 class="text-xl sm:text-2xl font-black uppercase tracking-wider font-montserrat text-[#FAF8F5]">KREF Walk-in</h1>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
        <div class="mb-4 sm:mb-6 rounded-xl border-2 border-gray-900 bg-emerald-200 p-3 sm:p-4 text-xs sm:text-sm font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] sm:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
            {{ session('success') }}
        </div>
        @endif
        @if($errors->any())
        <div class="mb-4 sm:mb-6 rounded-xl border-2 border-gray-900 bg-red-300 p-3 sm:p-4 text-xs sm:text-sm font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] sm:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Quick Register Form -->
        <div class="mb-6 sm:mb-8 rounded-2xl border-2 border-gray-900 bg-white p-4 sm:p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
            <h2 class="font-montserrat text-gray-900 sm:text-lg font-black uppercase mb-4 sm:mb-5">Daftarkan Walk-in</h2>
            <form action="{{ route('walkin.store') }}" method="POST">
                @csrf
                
                <!-- Toggle Sekarang / Jadwalkan -->
                <div class="mb-4 sm:mb-5 grid grid-cols-2 gap-2.5 sm:gap-3">
                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 p-2.5 sm:p-3 transition-all"
                           :class="bookingType === 'now' ? 'border-gray-900 bg-emerald-300 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50 grayscale'">
                        <input type="radio" name="booking_type" value="now" x-model="bookingType" class="hidden">
                        <span class="font-bold text-xs sm:text-sm text-gray-900 uppercase">Sekarang</span>
                    </label>
                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 p-2.5 sm:p-3 transition-all"
                           :class="bookingType === 'scheduled' ? 'border-gray-900 bg-amber-300 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50 grayscale'">
                        <input type="radio" name="booking_type" value="scheduled" x-model="bookingType" class="hidden">
                        <span class="font-bold text-xs sm:text-sm text-gray-900 uppercase">Jadwalkan</span>
                    </label>
                </div>

                <div class="mb-4 sm:mb-5">
                    <label class="block text-[11px] sm:text-xs font-bold mb-1.5 uppercase tracking-wider text-gray-500">Pilih Barber</label>
                    <select name="barber_id" x-model="selectedBarberId" required class="w-full rounded-xl border-2 border-gray-900 bg-[#FAF8F5] px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all cursor-pointer">
                        <option value="" disabled selected>-- Pilih Barber --</option>
                        @foreach($barbers as $barber)
                            <option value="{{ $barber->id }}">{{ $barber->name }} ({{ $barber->role }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Fields for Jadwalkan -->
                <div x-show="bookingType === 'scheduled'" x-cloak class="grid grid-cols-2 gap-2.5 sm:gap-4 mb-4 sm:mb-5">
                    <div>
                        <label class="block text-[11px] sm:text-xs font-bold mb-1.5 uppercase tracking-wider text-gray-500">Jam (Hari Ini)</label>
                        <select name="scheduled_time" x-model="selectedScheduledTime" :required="bookingType === 'scheduled'" class="w-full rounded-xl border-2 border-gray-900 bg-[#FAF8F5] px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all cursor-pointer">
                            <option value="">-- Pilih Jam --</option>
                            <template x-for="slot in availableSlots" :key="slot.time">
                                <option :value="slot.time" x-text="slot.time"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] sm:text-xs font-bold mb-1.5 uppercase tracking-wider text-gray-500">No. WhatsApp</label>
                        <div class="flex">
                            <span class="inline-flex shrink-0 items-center rounded-l-xl border-2 border-r-0 border-gray-900 bg-gray-200 px-2.5 sm:px-3 text-2xs sm:text-xs font-black text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                +62
                            </span>
                            <input type="tel" name="phone" x-model="customerPhone" :required="bookingType === 'scheduled'"
                                placeholder="81234567890"
                                oninput="this.value = this.value.replace(/^(\+?62|0)+/, '').replace(/[^0-9]/g, '')"
                                class="w-full rounded-r-xl border-2 border-gray-900 bg-[#FAF8F5] px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all">
                        </div>
                    </div>
                </div>

                <div class="mb-4 sm:mb-6">
                    <label class="block text-[11px] sm:text-xs font-bold mb-1.5 uppercase tracking-wider text-gray-500">Nama Customer</label>
                    <input type="text" name="name" x-model="customerName" required placeholder="Ketik nama..." class="w-full rounded-xl border-2 border-gray-900 bg-[#FAF8F5] px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all" autocomplete="off">
                </div>

                <!-- Toggle Layanan Chemical (Kunci 2 Slot Jam) -->
                <div class="mb-4 sm:mb-6">
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border-2 p-3 transition-all select-none"
                           :class="isChemical ? 'border-gray-900 bg-amber-200 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-[#FAF8F5]'">
                        <input type="checkbox" name="is_chemical" value="1" x-model="isChemical" class="size-4.5 rounded border-2 border-gray-900 text-brand focus:ring-0 cursor-pointer">
                        <div class="flex flex-col">
                            <span class="text-xs sm:text-sm font-black uppercase text-gray-900">Layanan Chemical (Kunci 2 Slot)</span>
                            <span class="text-[11px] font-semibold text-gray-600">Otomatis mengunci jadwal terpilih + 1 slot berikutnya</span>
                        </div>
                    </label>
                </div>

                
                <div x-show="bookingType === 'scheduled'" x-cloak class="mb-4 sm:mb-5">
                    <!-- Opsi Pembayaran Jadwalkan (DP / Full) -->
                    <div class="w-full h-0.5 bg-gray-300 mb-2"></div>

                    <label class="block text-[11px] sm:text-xs font-bold mb-1.5 uppercase tracking-wider text-gray-500">Tipe Pembayaran</label>
                    <div class="grid grid-cols-2 gap-2.5 sm:gap-3 mb-3">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 p-2.5 sm:p-3 transition-all"
                               :class="scheduledPaymentType === 'dp' ? 'border-gray-900 bg-amber-200 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50'">
                            <input type="radio" name="payment_type" value="dp" x-model="scheduledPaymentType" class="hidden">
                            <span class="font-bold text-xs sm:text-sm text-gray-900 uppercase">DP</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 p-2.5 sm:p-3 transition-all"
                               :class="scheduledPaymentType === 'full' ? 'border-gray-900 bg-emerald-200 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50'">
                            <input type="radio" name="payment_type" value="full" x-model="scheduledPaymentType" class="hidden">
                            <span class="font-bold text-xs sm:text-sm text-gray-900 uppercase">Full</span>
                        </label>
                    </div>

                    <!-- DP Input Fields -->
                    <div x-show="scheduledPaymentType === 'dp'" x-cloak class="space-y-2.5 sm:space-y-3">
                        <div>
                            <label class="block text-[11px] sm:text-xs font-bold mb-1.5 uppercase tracking-wider text-gray-500">Nominal DP</label>
                            <input type="number" name="dp_amount" x-model.number="scheduledDpAmount" min="1000" step="1000" class="w-full rounded-xl border-2 border-gray-900 bg-[#FAF8F5] px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all">
                        </div>
                        <div>
                            <label class="block text-[11px] sm:text-xs font-bold mb-1.5 uppercase tracking-wider text-gray-500">Metode Bayar DP</label>
                            <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-2.5 sm:p-3 transition-all"
                                       :class="scheduledPaymentMethod === 'cash' ? 'border-gray-900 bg-gray-300 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50'">
                                    <input type="radio" name="dp_payment_method" value="cash" x-model="scheduledPaymentMethod" class="hidden">
                                    <span class="font-bold text-xs sm:text-sm text-gray-900 uppercase">Cash</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-2.5 sm:p-3 transition-all"
                                       :class="scheduledPaymentMethod === 'qris_static' ? 'border-gray-900 bg-gray-300 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50'">
                                    <input type="radio" name="dp_payment_method" value="qris_static" x-model="scheduledPaymentMethod" class="hidden">
                                    <span class="font-bold text-xs sm:text-sm text-gray-900 uppercase">QRIS</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Full Info -->
                    <div x-show="scheduledPaymentType === 'full'" x-cloak class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-2.5 sm:p-3 text-center">
                        <span class="text-[11px] sm:text-xs font-bold text-gray-500">Nominal awal: Rp 0 (Pelunasan setelah layanan selesai)</span>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-xl border-2 border-gray-900 bg-brand py-2.5 sm:py-3 px-4 text-center text-xs sm:text-sm font-black uppercase tracking-wider text-[#FAF8F5] shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[5px_5px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] cursor-pointer">
                    <span x-text="bookingType === 'now' ? '+ Daftarkan Sekarang' : '+ Jadwalkan'"></span>
                </button>
            </form>
        </div>

        <!-- History List -->
        <div>
            <div class="flex items-center justify-between mb-3 sm:mb-4">
                <h2 class="font-montserrat sm:text-lg font-black uppercase text-gray-900">Hari Ini</h2>
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <!-- Tombol Kirim Rekap ke Grup WA -->
                    <form action="{{ route('walkin.send-recap') }}" method="POST" onsubmit="return confirm('Kirim rekapan hari ini ke grup WhatsApp?')" class="m-0 p-0">
                        @csrf
                        <button type="submit" 
                                title="Kirim Rekapan ke Grup WA"
                                class="inline-flex items-center gap-1 sm:gap-1.5 rounded-lg border-2 border-gray-900 bg-emerald-300 px-2 sm:px-2.5 py-1 text-[11px] sm:text-xs font-black uppercase tracking-wider text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 cursor-pointer">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.275.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824zm-3.423-14.416c-6.627 0-12 5.373-12 12 0 2.158.572 4.184 1.571 5.931l-1.571 5.738 5.894-1.546c1.706.945 3.666 1.477 5.751 1.477 6.627 0 12-5.373 12-12 0-6.627-5.373-12-12-12z"/>
                            </svg>
                            <span>Kirim Rekap</span>
                        </button>
                    </form>

                    <!-- Filter Pill Button (Disamping Kiri Pil Total) -->
                    <button type="button" 
                            @click="showFilter = !showFilter"
                            class="inline-flex items-center gap-1.5 rounded-lg border-2 border-gray-900 px-2.5 py-1 text-[11px] sm:text-xs font-black uppercase tracking-wider transition-all cursor-pointer shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5"
                            :class="showFilter || hasActiveFilter ? 'bg-amber-300 text-gray-900' : 'bg-white text-gray-900'">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.972.206 7.372.601a.75.75 0 01.628.74v2.288a2.25 2.25 0 01-.659 1.59l-4.682 4.683a2.25 2.25 0 00-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 018 18.25v-5.757a2.25 2.25 0 00-.659-1.591L2.659 6.22A2.25 2.25 0 012 4.629V2.34a.75.75 0 01.628-.74z" clip-rule="evenodd" />
                        </svg>
                        <span>Filter</span>
                        <template x-if="hasActiveFilter">
                            <span class="inline-flex h-2 w-2 rounded-full bg-red-600"></span>
                        </template>
                    </button>

                    <!-- Total Pill (Kanan) -->
                    <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-white px-2.5 py-1 text-[11px] sm:text-xs font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                        Total: {{ $walkIns->count() }}
                    </span>
                </div>
            </div>

            <!-- Filter Accordion Panel -->
            <div x-show="showFilter" 
                 x-cloak 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="mb-4 sm:mb-5 rounded-2xl border-2 border-gray-900 bg-white p-3.5 sm:p-5 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
                
                <form method="GET" action="{{ route('walkin.index') }}">
                    <!-- Opsi Tipe (Source) -->
                    <div class="mb-4">
                        <label class="block text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                            Tipe Booking
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="flex cursor-pointer items-center justify-center rounded-xl border-2 p-2 text-center transition-all"
                                   :class="filterSource === '' ? 'border-gray-900 bg-emerald-200 font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/60 font-bold hover:border-gray-900'">
                                <input type="radio" name="source" value="" x-model="filterSource" class="hidden">
                                <span class="text-xs sm:text-sm text-gray-900">Semua</span>
                            </label>
                            <label class="flex cursor-pointer items-center justify-center rounded-xl border-2 p-2 text-center transition-all"
                                   :class="filterSource === 'walk_in' ? 'border-gray-900 bg-emerald-200 font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/60 font-bold hover:border-gray-900'">
                                <input type="radio" name="source" value="walk_in" x-model="filterSource" class="hidden">
                                <span class="text-xs sm:text-sm text-gray-900">Walk-in</span>
                            </label>
                            <label class="flex cursor-pointer items-center justify-center rounded-xl border-2 p-2 text-center transition-all"
                                   :class="filterSource === 'online' ? 'border-gray-900 bg-emerald-200 font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/60 font-bold hover:border-gray-900'">
                                <input type="radio" name="source" value="online" x-model="filterSource" class="hidden">
                                <span class="text-xs sm:text-sm text-gray-900">Online</span>
                            </label>
                        </div>
                    </div>

                    <!-- Opsi Barber -->
                    <div class="mb-4 sm:mb-5">
                        <label class="block text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                            Pilih Barber
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <label class="flex cursor-pointer items-center justify-center rounded-xl border-2 p-2 text-center transition-all"
                                   :class="filterBarber === '' ? 'border-gray-900 bg-amber-200 font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/60 font-bold hover:border-gray-900'">
                                <input type="radio" name="barber" value="" x-model="filterBarber" class="hidden">
                                <span class="text-xs sm:text-sm text-gray-900">Semua</span>
                            </label>
                            @foreach($barbers as $barber)
                                <label class="flex cursor-pointer items-center justify-center rounded-xl border-2 p-2 text-center transition-all"
                                       :class="filterBarber === '{{ $barber->id }}' ? 'border-gray-900 bg-amber-200 font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/60 font-bold hover:border-gray-900'">
                                    <input type="radio" name="barber" value="{{ $barber->id }}" x-model="filterBarber" class="hidden">
                                    <span class="text-xs sm:text-sm text-gray-900 truncate">{{ $barber->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Tombol Aksi: Terapkan & Reset -->
                    <div class="flex items-center gap-2 pt-2 border-t-2 border-dashed border-gray-300">
                        <button type="submit" 
                                class="flex-1 rounded-xl border-2 border-gray-900 bg-brand py-2 px-3 text-center text-xs sm:text-sm font-black uppercase tracking-wider text-[#FAF8F5] shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 cursor-pointer">
                            Terapkan Filter
                        </button>
                        <a href="{{ route('walkin.index') }}" 
                           class="rounded-xl border-2 border-gray-900 bg-white py-2 px-3 text-center text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:bg-red-50 hover:text-red-700 active:translate-x-0.5 active:translate-y-0.5 cursor-pointer">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            @if(!empty($selectedBarber ?? request('barber')) || !empty($selectedSource ?? request('source')))
                <div class="mb-3 flex items-center justify-between text-xs font-bold text-gray-500">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span>Filter aktif:</span>
                        @if(!empty($selectedSource ?? request('source')))
                            <span class="rounded-md bg-sky-100 text-sky-800 px-2 py-0.5 border border-sky-400 font-black uppercase text-2xs">
                                {{ ($selectedSource ?? request('source')) === 'online' ? 'Online' : 'Walk-in' }}
                            </span>
                        @endif
                        @if(!empty($selectedBarber ?? request('barber')))
                            @php
                                $activeBarber = $barbers->firstWhere('id', $selectedBarber ?? request('barber'));
                            @endphp
                            @if($activeBarber)
                                <span class="rounded-md bg-amber-100 text-amber-800 px-2 py-0.5 border border-amber-400 font-black uppercase text-2xs">
                                    {{ $activeBarber->name }}
                                </span>
                            @endif
                        @endif
                    </div>
                    <a href="{{ route('walkin.index') }}" class="text-[11px] font-black text-red-600 hover:underline">Hapus Filter ✕</a>
                </div>
            @endif

            <div class="space-y-4 sm:space-y-5">
                @forelse($walkIns as $walkin)
                    <div class="rounded-2xl border-2 border-gray-900 {{ $walkin->status === 'completed' ? 'bg-emerald-50/50' : ($walkin->status === 'cancelled' ? 'bg-gray-100 opacity-60' : 'bg-white') }} p-3.5 sm:p-5 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all">
                        
                        <!-- Header Baris Atas: Jam & Nama di Kiri, Badges di Kanan -->
                        <div class="flex items-center justify-between gap-2 sm:gap-3 mb-2 sm:mb-2.5">
                            <!-- Left: Jam + Nama Customer -->
                            <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1">
                                <span class="shrink-0 font-black text-xs sm:text-sm text-gray-900 bg-white border-2 border-gray-900 rounded-lg px-1.5 py-0.5 sm:px-2 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] font-mono">
                                    {{ Carbon\Carbon::parse($walkin->scheduled_at)->format('H:i') }}
                                </span>
                                <span class="font-black text-sm text-gray-900 uppercase truncate block min-w-0 flex-1 leading-tight">
                                    {{ $walkin->name }}
                                </span>
                            </div>

                            <!-- Right: Badges (Tipe + Status) -->
                            <div class="flex items-center gap-1 sm:gap-1.5 shrink-0">
                                @if($walkin->source === 'online')
                                    <span class="inline-flex items-center rounded-md sm:rounded-lg border-[1.5px] sm:border-2 border-gray-900 bg-sky-300 px-1.5 py-0.5 sm:px-2 text-2xs sm:text-xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">Online</span>
                                @elseif(!empty($walkin->phone))
                                    <span class="inline-flex items-center rounded-md sm:rounded-lg border-[1.5px] sm:border-2 border-gray-900 bg-fuchsia-300 px-1.5 py-0.5 sm:px-2 text-2xs sm:text-xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">Jadwalkan</span>
                                @else
                                    <span class="inline-flex items-center rounded-md sm:rounded-lg border-[1.5px] sm:border-2 border-gray-900 bg-cyan-300 px-1.5 py-0.5 sm:px-2 text-2xs sm:text-xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">Sekarang</span>
                                @endif

                                @if($walkin->status === 'completed')
                                    <span class="inline-flex items-center rounded-md sm:rounded-lg border-[1.5px] sm:border-2 border-gray-900 bg-emerald-300 px-1.5 py-0.5 sm:px-2 text-2xs sm:text-xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">Selesai</span>
                                @elseif($walkin->status === 'confirmed')
                                    <span class="inline-flex items-center rounded-md sm:rounded-lg border-[1.5px] sm:border-2 border-gray-900 bg-violet-300 px-1.5 py-0.5 sm:px-2 text-2xs sm:text-xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">Dikonfirmasi</span>
                                @elseif($walkin->status === 'cancelled')
                                    <span class="inline-flex items-center rounded-md sm:rounded-lg border-[1.5px] sm:border-2 border-gray-900 bg-red-300 px-1.5 py-0.5 sm:px-2 text-2xs sm:text-xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">Batal</span>
                                @else
                                    <span class="inline-flex items-center rounded-md sm:rounded-lg border-[1.5px] sm:border-2 border-gray-900 bg-amber-300 px-1.5 py-0.5 sm:px-2 text-2xs sm:text-xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">Pending</span>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Metadata Tags: Barber, WA, Status Pembayaran -->
                        <div class="flex flex-wrap items-center gap-y-1.5 gap-x-2 text-[11px] sm:text-xs font-bold text-gray-600 mb-2.5">
                            <div class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 border border-gray-300 text-gray-800">
                                <span class="text-gray-500 text-2xs uppercase tracking-wider">Barber:</span>
                                <span class="font-extrabold text-gray-900">{{ $walkin->barber->name }}</span>
                            </div>

                            @if(!empty($walkin->phone))
                                <div class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 border border-gray-300 text-gray-800">
                                    <span class="text-gray-500 text-2xs uppercase tracking-wider">WA:</span>
                                    <span class="font-extrabold text-gray-900">{{ $walkin->formatted_phone ?? $walkin->phone }}</span>
                                </div>
                            @endif

                            {{-- Badge Pembayaranss --}}
                            @if($walkin->status !== 'completed' && $walkin->payment && $walkin->payment->purpose === 'dp')
                                <span class="inline-flex items-center rounded-md border-2 border-gray-900 bg-amber-200 px-2 py-0.5 text-2xs sm:text-xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">
                                    DP Rp {{ number_format($walkin->payment->amount, 0, ',', '.') }} ({{ $walkin->payment_method_label }})
                                </span>
                            @elseif($walkin->status !== 'completed' && $walkin->source === 'online' && ($walkin->payment_type === 'full' || ($walkin->payment && in_array($walkin->payment->purpose, ['full_payment', 'walk_in']))))
                                <span class="inline-flex items-center rounded-md border-2 border-gray-900 bg-emerald-200 px-2 py-0.5 text-2xs sm:text-xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">
                                    Lunas Online ({{ $walkin->payment_method_label }})
                                </span>
                            @elseif($walkin->status !== 'completed' && !empty($walkin->phone) && (!$walkin->payment || $walkin->payment_type === 'full'))
                                <span class="inline-flex items-center rounded-md border border-dashed border-gray-400 bg-gray-100 px-2 py-0.5 text-2xs sm:text-xs font-bold uppercase tracking-wider text-gray-600">
                                    Jadwal (Belum Bayar)
                                </span>
                            @endif
                        </div>

                        {{-- Catatan (Description) jika ada --}}
                        @if(!empty($walkin->description))
                            <div class="mb-2.5 sm:mb-3 rounded-xl border border-amber-300 bg-amber-50/90 px-3 py-2 text-xs text-gray-800">
                                <div class="flex items-center gap-1.5 text-amber-900 font-extrabold uppercase text-2xs tracking-wider mb-0.5">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                    </svg>
                                    <span>Catatan</span>
                                </div>
                                <p class="italic text-gray-700 leading-relaxed font-medium wrap-break-word">{{ $walkin->description }}</p>
                            </div>
                        @endif

                        @if($walkin->status === 'completed')
                            <div class="mt-3 pt-3 border-t-2 border-dashed border-gray-300">
                                <ul class="list-disc list-inside text-xs sm:text-sm font-bold text-gray-700 mb-2.5 space-y-0.5">
                                    @foreach($walkin->items as $item)
                                        <li>{{ $item->service_name_snapshot }}</li>
                                    @endforeach
                                </ul>
                                <div class="flex justify-between items-end">
                                    <div>
                                        <span class="text-2xs sm:text-xs font-bold text-gray-500 uppercase tracking-wider block mb-0.5">Total</span>
                                        <span class="font-black sm:text-lg text-emerald-700">Rp {{ number_format($walkin->grand_total, 0, ',', '.') }}</span>
                                        @if(($walkin->tip_amount ?? 0) > 0)
                                            <span class="text-2xs font-bold text-gray-500 block leading-tight">(Termasuk Tip Rp {{ number_format($walkin->tip_amount, 0, ',', '.') }})</span>
                                        @endif
                                    </div>
                                    <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-white px-2 py-0.5 sm:px-2.5 sm:py-1 text-2xs sm:text-xs font-black uppercase tracking-wider shadow-[1.5px_1.5px_0px_0px_rgba(17,24,39,1)] sm:shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                        {{ $walkin->payment_type_label }} - {{ $walkin->payment_method_label }}
                                    </span>
                                </div>
                            </div>
                        @elseif($walkin->status === 'confirmed')
                            <div class="flex gap-2 sm:gap-3 mt-3 pt-3 border-t-2 border-dashed border-gray-300">
                                <button type="button" @click='openComplete(@json($walkin))' class="flex-1 rounded-xl border-2 border-gray-900 bg-emerald-400 py-2 sm:py-2.5 px-3 text-center text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] cursor-pointer">
                                    Lengkapi
                                </button>
                                
                                <form action="{{ route('walkin.cancel', ['booking' => $walkin->id]) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan {{ $walkin->source === 'online' ? 'booking online' : 'walk-in' }} ini?')" class="flex-none">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl border-2 border-gray-900 bg-white py-2 sm:py-2.5 px-3 sm:px-4 text-center text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] hover:bg-red-100 cursor-pointer">
                                        Cancel
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border-2 border-dashed border-gray-400 bg-white/50 p-6 sm:p-8 text-center shadow-sm">
                        <span class="text-xs sm:text-sm font-bold text-gray-400 uppercase tracking-wider">Belum ada data walk-in / booking</span>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Modal Lengkapi -->
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4" 
             x-show="showModal" 
             x-cloak 
             style="display: none;">
            
            <!-- Backdrop with smooth synchronized fade -->
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"
                 x-show="showModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showModal = false"></div>

            <!-- Modal Dialog Panel -->
            <div class="relative w-full max-w-md max-h-[90vh] flex flex-col rounded-3xl border-2 border-gray-900 bg-[#FAF8F5] shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] overflow-hidden" 
                 x-show="showModal" 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b-2 border-gray-900 bg-white p-3.5 sm:p-5">
                    <div>
                        <h3 class="font-montserrat text-sm font-black uppercase text-gray-900 leading-tight mb-1">
                            Lengkapi: <span x-text="activeBooking?.name" class="text-brand"></span>
                        </h3>
                        <p class="text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Barber: <span x-text="activeBooking?.barber?.name" class="text-gray-900"></span> • <span x-text="activeBooking ? activeBooking.scheduled_at.substring(11,16) : ''" class="text-gray-900 bg-amber-100 px-1 rounded border border-gray-900"></span>
                        </p>
                    </div>
                    <button type="button" @click="showModal = false" class="inline-flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-lg border-2 border-gray-900 bg-white text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:bg-gray-100 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] cursor-pointer">
                        <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18 17.94 6M18 18 6.06 6"/>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="overflow-y-auto p-3.5 sm:p-5">
                    <form id="completeForm" :action="`/vv4lk-1n/${activeBooking?.id}/complete`" method="POST">
                        @csrf
                        @method('PATCH')
                        
                        <h4 class="mb-2.5 font-montserrat text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900">Pilih Service</h4>
                        
                        @foreach($serviceCategories as $category)
                            <div class="mb-4 sm:mb-5">
                                <h5 class="mb-2 text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500">{{ $category['name'] }}</h5>
                                <div class="grid grid-cols-2 gap-2 sm:gap-3">
                                    @foreach($category['services'] as $service)
                                        @php
                                            $isRegularHaircut = strtoupper($service->code) === 'RH';
                                            $isOwnerHaircut = strtoupper($service->code) === 'HBR';
                                            $isLongTrim = strtoupper($service->code) === 'LT' || str_contains(strtolower($service->name), 'long trim');
                                        @endphp
                                        <label class="relative flex cursor-pointer flex-col justify-between rounded-xl border-2 p-2.5 sm:p-3 transition-all h-full"
                                             @if ($isRegularHaircut) x-show="activeBooking?.barber?.role?.toLowerCase() !== 'owner'" @endif
                                             @if ($isOwnerHaircut) x-show="activeBooking?.barber?.role?.toLowerCase() === 'owner'" @endif
                                             :class="isSelected({{ $service->id }}) ? 'border-gray-900 bg-red-100 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] -translate-x-0.5 -translate-y-0.5' : 'border-gray-300 bg-white hover:border-gray-900 hover:shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]'">
                                            <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" class="hidden" @change="toggleService({{ $service->id }}, {{ $service->price }}, '{{ addslashes($service->name) }}')" :checked="isSelected({{ $service->id }})">
                                            
                                            <div class="mb-1.5 text-xs sm:text-sm font-bold leading-snug text-gray-900 pr-3 sm:pr-4">
                                                {{ $service->name }}
                                            </div>
                                            <div class="text-2xs sm:text-xs font-black text-gray-700 bg-white border-2 border-gray-900 px-1.5 py-0.5 rounded-lg w-max">
                                                {{ number_format($service->price, 0, ',', '.') }}
                                                @if($isLongTrim)
                                                    <span class="text-[9px] font-bold text-gray-500 lowercase">(mulai)</span>
                                                @endif
                                            </div>
                                            
                                            <div x-show="isSelected({{ $service->id }})" class="absolute top-2 right-2 flex h-3.5 w-3.5 sm:h-4 sm:w-4 items-center justify-center rounded-full border-2 border-gray-900 bg-white">
                                                <div class="h-1.5 w-1.5 rounded-full bg-gray-900"></div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Panel Penyesuaian Harga Full-Width di Bawah Grid (Agar Kartu di Sebelahnya Tidak Ikut Memanjang) --}}
                                @foreach($category['services'] as $service)
                                    @if(strtoupper($service->code) === 'LT' || str_contains(strtolower($service->name), 'long trim'))
                                        <div x-show="isSelected({{ $service->id }})" 
                                             x-cloak 
                                             x-transition:enter="ease-out duration-200"
                                             x-transition:enter-start="opacity-0 -translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             x-transition:leave="ease-in duration-150"
                                             x-transition:leave-start="opacity-100 translate-y-0"
                                             x-transition:leave-end="opacity-0 -translate-y-1"
                                             class="mt-2.5 rounded-xl border-2 border-gray-900 bg-amber-100 p-2.5 sm:p-3 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                <div>
                                                    <div class="flex items-center gap-1.5">
                                                        <svg class="h-3.5 w-3.5 text-amber-900 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                                        </svg>
                                                        <span class="text-xs font-black uppercase text-gray-900">Penyesuaian Harga</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-center">
                                                    <span class="text-xs font-black text-gray-700">Rp</span>
                                                    <input type="number" 
                                                           name="custom_prices[{{ $service->id }}]"
                                                           :value="getCustomPrice({{ $service->id }}, {{ $service->price }})"
                                                           @input="setCustomPrice({{ $service->id }}, $event.target.value)"
                                                           min="{{ $service->price }}"
                                                           step="5000"
                                                           class="w-32 rounded-lg border-2 border-gray-900 bg-white px-2.5 py-1 text-xs font-black text-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] focus:outline-none focus:ring-0">
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach

                        <div class="my-4 sm:my-6 border-t-2 border-dashed border-gray-300"></div>

                        <!-- Catatan (Notes) -->
                        <div class="mb-4 sm:mb-5">
                            <label class="block text-[11px] sm:text-xs font-bold mb-1.5 uppercase tracking-wider text-gray-500">Catatan (Notes)</label>
                            <textarea name="description" x-model="bookingNotes" rows="2" placeholder="Tambahkan catatan jika ada (opsional)..." class="w-full rounded-xl border-2 border-gray-900 bg-[#FAF8F5] px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all resize-none"></textarea>
                        </div>

                        {{-- Info Online Prepaid Full --}}
                        <template x-if="isPrepaidFull">
                            <div class="rounded-xl border-2 border-gray-900 bg-emerald-100 p-3 sm:p-4 mb-3 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="h-4 w-4 text-emerald-900 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="font-montserrat text-xs sm:text-sm font-black uppercase text-emerald-900">Lunas Online</span>
                                </div>
                                <p class="text-[11px] sm:text-xs font-bold text-emerald-800">
                                    Booking ini telah dibayar penuh secara online (<span x-text="formatRupiah(prepaidAmount)"></span>).
                                    <template x-if="tipAmount > 0">
                                        <span class="block mt-0.5 font-extrabold text-emerald-950">
                                            Termasuk Tip Barber: <span x-text="formatRupiah(tipAmount)"></span>
                                        </span>
                                    </template>
                                    <span x-show="remainingAmount > 0" class="block mt-1 text-red-700 font-extrabold">
                                        Terdapat sisa pembayaran karena penyesuaian/penambahan layanan.
                                    </span>
                                </p>
                            </div>
                        </template>

                        {{-- Pembayaran: Hanya tampil jika BELUM ada DP & BELUM bayar penuh online --}}
                        <template x-if="!hasPrepaidDp && !isPrepaidFull">
                            <div>
                                <h4 class="mb-2.5 font-montserrat text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900">Pembayaran</h4>
                                
                                <div class="grid grid-cols-2 gap-2 sm:gap-3 mb-3">
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-2.5 sm:p-3 transition-all"
                                           :class="paymentType === 'full' ? 'border-gray-900 bg-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50 grayscale'">
                                        <input type="radio" name="payment_type" value="full" x-model="paymentType" class="hidden">
                                        <div class="flex h-4 w-4 sm:h-5 sm:w-5 items-center justify-center rounded-full border-2 border-gray-900 bg-white">
                                            <div class="h-1.5 w-1.5 sm:h-2 sm:w-2 rounded-full bg-gray-900" x-show="paymentType === 'full'"></div>
                                        </div>
                                        <span class="font-bold text-xs sm:text-sm text-gray-900 uppercase">Lunas</span>
                                    </label>
                                    <label class="flex items-center gap-2 rounded-xl border-2 p-2.5 sm:p-3 transition-all"
                                           :class="!activeBooking?.phone ? 'border-gray-300 bg-gray-100 opacity-50 cursor-not-allowed' : (paymentType === 'dp' ? 'border-gray-900 bg-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] cursor-pointer' : 'border-gray-300 bg-white/50 grayscale cursor-pointer')">
                                        <input type="radio" name="payment_type" value="dp" x-model="paymentType" class="hidden" :disabled="!activeBooking?.phone">
                                        <div class="flex h-4 w-4 sm:h-5 sm:w-5 items-center justify-center rounded-full border-2 border-gray-900 bg-white" :class="!activeBooking?.phone ? 'opacity-50' : ''">
                                            <div class="h-1.5 w-1.5 sm:h-2 sm:w-2 rounded-full bg-gray-900" x-show="paymentType === 'dp'"></div>
                                        </div>
                                        <span class="font-bold text-xs sm:text-sm text-gray-900 uppercase">DP</span>
                                    </label>
                                </div>

                                <div class="grid grid-cols-2 gap-2 sm:gap-3">
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-2.5 sm:p-3 transition-all"
                                           :class="paymentMethod === 'cash' ? 'border-gray-900 bg-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50 grayscale'">
                                        <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="hidden">
                                        <div class="flex h-4 w-4 sm:h-5 sm:w-5 items-center justify-center rounded-full border-2 border-gray-900 bg-white">
                                            <div class="h-1.5 w-1.5 sm:h-2 sm:w-2 rounded-full bg-gray-900" x-show="paymentMethod === 'cash'"></div>
                                        </div>
                                        <span class="font-bold text-xs sm:text-sm text-gray-900 uppercase">Cash</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-2.5 sm:p-3 transition-all"
                                           :class="paymentMethod === 'qris_static' ? 'border-gray-900 bg-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50 grayscale'">
                                        <input type="radio" name="payment_method" value="qris_static" x-model="paymentMethod" class="hidden">
                                        <div class="flex h-4 w-4 sm:h-5 sm:w-5 items-center justify-center rounded-full border-2 border-gray-900 bg-white">
                                            <div class="h-1.5 w-1.5 sm:h-2 sm:w-2 rounded-full bg-gray-900" x-show="paymentMethod === 'qris_static'"></div>
                                        </div>
                                        <span class="font-bold text-xs sm:text-sm text-gray-900 uppercase">QRIS</span>
                                    </label>
                                </div>
                            </div>
                        </template>

                        {{-- Pelunasan: Metode bayar sisa, muncul jika ada DP atau Prepaid Full dengan remainingAmount > 0 --}}
                        <template x-if="(hasPrepaidDp || isPrepaidFull) && remainingAmount > 0">
                            <div>
                                <h4 class="mb-2.5 font-montserrat text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900" x-text="hasPrepaidDp ? 'Metode Pelunasan' : 'Metode Pembayaran Sisa'"></h4>
                                <div class="grid grid-cols-2 gap-2 sm:gap-3">
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-2.5 sm:p-3 transition-all"
                                           :class="settlementPaymentMethod === 'cash' ? 'border-gray-900 bg-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50'">
                                        <input type="radio" name="settlement_payment_method" value="cash" x-model="settlementPaymentMethod" class="hidden">
                                        <span class="font-bold text-xs sm:text-sm text-gray-900 uppercase">Cash</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-2.5 sm:p-3 transition-all"
                                           :class="settlementPaymentMethod === 'qris_static' ? 'border-gray-900 bg-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50'">
                                        <input type="radio" name="settlement_payment_method" value="qris_static" x-model="settlementPaymentMethod" class="hidden">
                                        <span class="font-bold text-xs sm:text-sm text-gray-900 uppercase">QRIS</span>
                                    </label>
                                </div>
                            </div>
                        </template>

                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="border-t-2 border-gray-900 bg-white p-3.5 sm:p-5">
                    {{-- Footer untuk booking dengan DP: tampilkan rincian pelunasan --}}
                    <template x-if="hasPrepaidDp">
                        <div class="mb-3 space-y-1.5 text-xs sm:text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500">Total Layanan</span>
                                <span class="font-bold text-gray-900" x-text="formatRupiah(total)"></span>
                            </div>
                            <template x-if="tipAmount > 0">
                                <div class="flex items-center justify-between text-emerald-700 font-extrabold">
                                    <span class="text-[11px] sm:text-xs uppercase tracking-wider">Tip Barber</span>
                                    <span x-text="'+ ' + formatRupiah(tipAmount)"></span>
                                </div>
                            </template>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500">DP Terbayar</span>
                                <span class="font-bold text-emerald-600" x-text="'- ' + formatRupiah(prepaidDpAmount)"></span>
                            </div>
                            <div class="flex items-center justify-between border-t-2 border-dashed border-gray-300 pt-1.5">
                                <span class="text-[11px] sm:text-xs font-black uppercase tracking-wider text-gray-900">Sisa Pelunasan</span>
                                <span class="font-montserrat text-base sm:text-xl font-black" :class="remainingAmount > 0 ? 'text-red-600' : 'text-emerald-600'" x-text="formatRupiah(remainingAmount)"></span>
                            </div>
                        </div>
                    </template>

                    {{-- Footer untuk online booking bayar penuh --}}
                    <template x-if="isPrepaidFull">
                        <div class="mb-3 space-y-1.5 text-xs sm:text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500">Total Layanan</span>
                                <span class="font-bold text-gray-900" x-text="formatRupiah(total)"></span>
                            </div>
                            <template x-if="tipAmount > 0">
                                <div class="flex items-center justify-between text-emerald-700 font-extrabold">
                                    <span class="text-[11px] sm:text-xs uppercase tracking-wider">Tip Barber</span>
                                    <span x-text="'+ ' + formatRupiah(tipAmount)"></span>
                                </div>
                            </template>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500">Dibayar Online</span>
                                <span class="font-bold text-emerald-600" x-text="'- ' + formatRupiah(prepaidAmount)"></span>
                            </div>
                            <div class="flex items-center justify-between border-t-2 border-dashed border-gray-300 pt-1.5">
                                <span class="text-[11px] sm:text-xs font-black uppercase tracking-wider text-gray-900">Sisa Pembayaran</span>
                                <span class="font-montserrat text-base sm:text-xl font-black" :class="remainingAmount > 0 ? 'text-red-600' : 'text-emerald-600'" x-text="formatRupiah(remainingAmount)"></span>
                            </div>
                        </div>
                    </template>

                    {{-- Footer untuk booking tanpa DP: total saja --}}
                    <template x-if="!hasPrepaidDp && !isPrepaidFull">
                        <div class="mb-3 space-y-1.5 text-xs sm:text-sm">
                            <template x-if="tipAmount > 0">
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500">Total Layanan</span>
                                        <span class="font-bold text-gray-900" x-text="formatRupiah(total)"></span>
                                    </div>
                                    <div class="flex items-center justify-between text-emerald-700 font-extrabold">
                                        <span class="text-[11px] sm:text-xs uppercase tracking-wider">Tip Barber</span>
                                        <span x-text="'+ ' + formatRupiah(tipAmount)"></span>
                                    </div>
                                    <div class="flex items-center justify-between border-t-2 border-dashed border-gray-300 pt-1.5">
                                        <span class="text-[11px] sm:text-xs font-black uppercase tracking-wider text-gray-900">Total Nilai</span>
                                        <span class="font-montserrat text-base sm:text-xl font-black text-gray-900" x-text="formatRupiah(total + tipAmount)"></span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="tipAmount === 0">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-500">Total Nilai</span>
                                    <span class="font-montserrat text-base sm:text-xl font-black text-gray-900" x-text="formatRupiah(total)"></span>
                                </div>
                            </template>
                        </div>
                    </template>

                    <button type="submit" form="completeForm" class="w-full rounded-xl border-2 border-gray-900 bg-emerald-400 py-2.5 sm:py-3 px-4 text-center text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[5px_5px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] disabled:opacity-50 disabled:cursor-not-allowed" :disabled="selectedServices.length === 0">
                        <span x-text="(hasPrepaidDp || isPrepaidFull) ? (remainingAmount > 0 ? 'Selesaikan Pelunasan' : 'Simpan & Selesai') : 'Simpan & Selesai'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
