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
<body class="bg-[#FAF8F5] text-gray-900 min-h-screen selection:bg-amber-300 selection:text-gray-900 font-montserrat">
    <div class="max-w-lg mx-auto pb-20 pt-6 px-4" x-data="walkInApp(@js($availableSchedules))">
        
        <!-- Header -->
        <div class="mb-8 rounded-2xl border-2 border-gray-900 bg-brand p-4 text-center shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
            <h1 class="text-3xl font-black uppercase tracking-wider font-montserrat text-[#FAF8F5]">KREF Walk-in</h1>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
        <div class="mb-6 rounded-xl border-2 border-gray-900 bg-emerald-200 p-4 text-sm font-bold text-gray-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
            {{ session('success') }}
        </div>
        @endif
        @if($errors->any())
        <div class="mb-6 rounded-xl border-2 border-gray-900 bg-red-300 p-4 text-sm font-bold text-gray-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Quick Register Form -->
        <div class="mb-8 rounded-2xl border-2 border-gray-900 bg-white p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
            <h2 class="font-montserrat text-xl font-black uppercase text-gray-900 mb-5">Daftarkan Walk-in</h2>
            <form action="{{ route('walkin.store') }}" method="POST">
                @csrf
                
                <!-- Toggle Sekarang / Jadwalkan -->
                <div class="mb-5 grid grid-cols-2 gap-3">
                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 p-3 transition-all"
                           :class="bookingType === 'now' ? 'border-gray-900 bg-emerald-300 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50 grayscale'">
                        <input type="radio" name="booking_type" value="now" x-model="bookingType" class="hidden">
                        <span class="font-bold text-sm text-gray-900 uppercase">Sekarang</span>
                    </label>
                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 p-3 transition-all"
                           :class="bookingType === 'scheduled' ? 'border-gray-900 bg-amber-300 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50 grayscale'">
                        <input type="radio" name="booking_type" value="scheduled" x-model="bookingType" class="hidden">
                        <span class="font-bold text-sm text-gray-900 uppercase">Jadwalkan</span>
                    </label>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-bold mb-2 uppercase tracking-wider text-gray-500">Pilih Barber</label>
                    <select name="barber_id" x-model="selectedBarberId" required class="w-full rounded-xl border-2 border-gray-900 bg-[#FAF8F5] px-4 py-3 text-sm font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all cursor-pointer">
                        <option value="" disabled selected>-- Pilih Barber --</option>
                        @foreach($barbers as $barber)
                            <option value="{{ $barber->id }}">{{ $barber->name }} ({{ $barber->role }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Fields for Jadwalkan -->
                <div x-show="bookingType === 'scheduled'" x-cloak class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-xs font-bold mb-2 uppercase tracking-wider text-gray-500">Jam (Hari Ini)</label>
                        <select name="scheduled_time" :required="bookingType === 'scheduled'" class="w-full rounded-xl border-2 border-gray-900 bg-[#FAF8F5] px-4 py-3 text-sm font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all cursor-pointer">
                            <option value="">-- Pilih Jam --</option>
                            <template x-for="slot in availableSlots" :key="slot.time">
                                <option :value="slot.time" x-text="slot.time"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold mb-2 uppercase tracking-wider text-gray-500">No. WhatsApp</label>
                        <input type="tel" name="phone" :required="bookingType === 'scheduled'" class="w-full rounded-xl border-2 border-gray-900 bg-[#FAF8F5] px-4 py-3 text-sm font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-bold mb-2 uppercase tracking-wider text-gray-500">Nama Customer</label>
                    <input type="text" name="name" required placeholder="Ketik nama..." class="w-full rounded-xl border-2 border-gray-900 bg-[#FAF8F5] px-4 py-3 text-sm font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all" autocomplete="off">
                </div>

                
                <div x-show="bookingType === 'scheduled'" x-cloak class="mb-5">
                    <!-- Opsi Pembayaran Jadwalkan (DP / Full) -->
                    <div class="w-full h-0.5 bg-gray-300 mb-2"></div>

                    <label class="block text-xs font-bold mb-2 uppercase tracking-wider text-gray-500">Tipe Pembayaran</label>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 p-3 transition-all"
                               :class="scheduledPaymentType === 'dp' ? 'border-gray-900 bg-amber-200 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50'">
                            <input type="radio" name="payment_type" value="dp" x-model="scheduledPaymentType" class="hidden">
                            <span class="font-bold text-sm text-gray-900 uppercase">DP</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 p-3 transition-all"
                               :class="scheduledPaymentType === 'full' ? 'border-gray-900 bg-emerald-200 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50'">
                            <input type="radio" name="payment_type" value="full" x-model="scheduledPaymentType" class="hidden">
                            <span class="font-bold text-sm text-gray-900 uppercase">Full</span>
                        </label>
                    </div>

                    <!-- DP Input Fields -->
                    <div x-show="scheduledPaymentType === 'dp'" x-cloak class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider text-gray-500">Nominal DP</label>
                            <input type="number" name="dp_amount" x-model.number="scheduledDpAmount" min="1000" step="1000" class="w-full rounded-xl border-2 border-gray-900 bg-[#FAF8F5] px-4 py-3 text-sm font-bold text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] focus:outline-none focus:ring-0 focus:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1.5 uppercase tracking-wider text-gray-500">Metode Bayar DP</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-3 transition-all"
                                       :class="scheduledPaymentMethod === 'cash' ? 'border-gray-900 bg-gray-300 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50'">
                                    <input type="radio" name="dp_payment_method" value="cash" x-model="scheduledPaymentMethod" class="hidden">
                                    <span class="font-bold text-sm text-gray-900 uppercase">Cash</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-3 transition-all"
                                       :class="scheduledPaymentMethod === 'qris_static' ? 'border-gray-900 bg-gray-300 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50'">
                                    <input type="radio" name="dp_payment_method" value="qris_static" x-model="scheduledPaymentMethod" class="hidden">
                                    <span class="font-bold text-sm text-gray-900 uppercase">QRIS</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Full Info -->
                    <div x-show="scheduledPaymentType === 'full'" x-cloak class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-3 text-center">
                        <span class="text-xs font-bold text-gray-500">Nominal awal: Rp 0 (Pelunasan setelah layanan selesai)</span>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-xl border-2 border-gray-900 bg-brand px-4 py-3 text-center text-sm font-black uppercase tracking-wider text-[#FAF8F5] shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] cursor-pointer">
                    <span x-text="bookingType === 'now' ? '+ Daftarkan Sekarang' : '+ Jadwalkan'"></span>
                </button>
            </form>
        </div>

        <!-- History List -->
        <div>
            <div class="flex items-center justify-between mb-5">
                <h2 class="font-montserrat text-xl font-black uppercase text-gray-900">Hari Ini</h2>
                <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-white px-3 py-1 text-xs font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                    Total: {{ $walkIns->count() }}
                </span>
            </div>

            <div class="space-y-5">
                @forelse($walkIns as $walkin)
                    <div class="rounded-2xl border-2 border-gray-900 {{ $walkin->status === 'completed' ? 'bg-emerald-50/50' : ($walkin->status === 'cancelled' ? 'bg-gray-100 opacity-60' : 'bg-white') }} p-5 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] relative overflow-hidden transition-all">
                        
                        <!-- Status & Type Badges -->
                        <div class="absolute top-4 right-4 flex items-center gap-1.5">
                            @if(!empty($walkin->phone))
                                <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-fuchsia-300 px-2 py-0.5 text-2xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">Jadwalkan</span>
                            @else
                                <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-cyan-300 px-2 py-0.5 text-2xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">Sekarang</span>
                            @endif

                            @if($walkin->status === 'completed')
                                <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-emerald-300 px-2 py-0.5 text-2xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">Selesai</span>
                            @elseif($walkin->status === 'cancelled')
                                <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-red-300 px-2 py-0.5 text-2xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">Batal</span>
                            @else
                                <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-amber-300 px-2 py-0.5 text-2xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">Pending</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 mb-2 pr-44">
                            <span class="font-black text-lg text-gray-900 bg-white border-2 border-gray-900 rounded-lg px-2 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">{{ Carbon\Carbon::parse($walkin->scheduled_at)->format('H:i') }}</span>
                            <span class="font-black text-xl text-gray-900 uppercase truncate">{{ $walkin->name }}</span>
                        </div>
                        
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span>Barber: <span class="text-gray-900">{{ $walkin->barber->name }}</span></span>
                            @if(!empty($walkin->phone))
                                <span>• WA: <span class="text-gray-900">{{ $walkin->phone }}</span></span>
                            @endif
                        </div>

                        {{-- Badge DP / Jadwal Belum Bayar --}}
                        @if($walkin->status !== 'completed' && $walkin->payment && $walkin->payment->purpose === 'dp')
                            <div class="mb-3">
                                <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-amber-200 px-2.5 py-1 text-2xs font-black uppercase tracking-wider shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] text-gray-900">
                                    DP Rp {{ number_format($walkin->payment->amount, 0, ',', '.') }} ({{ $walkin->payment->method === 'cash' ? 'Cash' : 'QRIS' }})
                                </span>
                            </div>
                        @elseif($walkin->status !== 'completed' && !empty($walkin->phone) && (!$walkin->payment || $walkin->payment_type === 'full'))
                            <div class="mb-3">
                                <span class="inline-flex items-center rounded-lg border-2 border-dashed border-gray-400 bg-gray-50 px-2.5 py-1 text-2xs font-bold uppercase tracking-wider text-gray-500">
                                    Jadwal (Belum Bayar)
                                </span>
                            </div>
                        @endif

                        @if($walkin->status === 'completed')
                            <div class="mt-4 pt-4 border-t-2 border-dashed border-gray-300">
                                <ul class="list-disc list-inside text-sm font-bold text-gray-700 mb-3 space-y-1">
                                    @foreach($walkin->items as $item)
                                        <li>{{ $item->service_name_snapshot }}</li>
                                    @endforeach
                                </ul>
                                <div class="flex justify-between items-end">
                                    <div>
                                        <span class="text-2xs font-bold text-gray-500 uppercase tracking-wider block mb-1">Total</span>
                                        <span class="font-black text-xl text-emerald-700">Rp {{ number_format($walkin->total_amount, 0, ',', '.') }}</span>
                                    </div>
                                    <span class="inline-flex items-center rounded-lg border-2 border-gray-900 bg-white px-2.5 py-1 text-2xs font-black uppercase tracking-wider shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                        {{ $walkin->payment_type_label }} - {{ $walkin->payment_method_label }}
                                    </span>
                                </div>
                            </div>
                        @elseif($walkin->status === 'confirmed')
                            <div class="flex gap-3 mt-5 border-t-2 border-dashed border-gray-300 pt-4">
                                <button type="button" @click='openComplete(@json($walkin))' class="flex-1 rounded-xl border-2 border-gray-900 bg-emerald-400 px-3 py-2.5 text-center text-sm font-black uppercase tracking-wider text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] cursor-pointer">
                                    Lengkapi
                                </button>
                                
                                <form action="{{ route('walkin.cancel', ['booking' => $walkin->id]) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan walk-in ini?')" class="flex-none">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl border-2 border-gray-900 bg-white px-4 py-2.5 text-center text-sm font-black uppercase tracking-wider text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] hover:bg-red-100 cursor-pointer">
                                        Cancel
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-2xl border-2 border-dashed border-gray-400 bg-white/50 p-8 text-center shadow-sm">
                        <span class="text-sm font-bold text-gray-400 uppercase tracking-wider">Belum ada data walk-in</span>
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
                <div class="flex items-center justify-between border-b-2 border-gray-900 bg-white p-5">
                    <div>
                        <h3 class="font-montserrat text-lg font-black uppercase text-gray-900 leading-none mb-1">
                            Lengkapi: <span x-text="activeBooking?.name" class="text-brand"></span>
                        </h3>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                            Barber: <span x-text="activeBooking?.barber?.name" class="text-gray-900"></span> • <span x-text="activeBooking ? activeBooking.scheduled_at.substring(11,16) : ''" class="text-gray-900 bg-amber-100 px-1 rounded border border-gray-900"></span>
                        </p>
                    </div>
                    <button type="button" @click="showModal = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border-2 border-gray-900 bg-white text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:bg-gray-100 hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] cursor-pointer">
                        <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18 17.94 6M18 18 6.06 6"/>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="overflow-y-auto p-5">
                    <form id="completeForm" :action="`/vv4lk-1n/${activeBooking?.id}/complete`" method="POST">
                        @csrf
                        @method('PATCH')
                        
                        <h4 class="mb-3 font-montserrat text-sm font-black uppercase tracking-widest text-gray-900">Pilih Service</h4>
                        
                        @foreach($serviceCategories as $category)
                            <div class="mb-5">
                                <h5 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-500">{{ $category['name'] }}</h5>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach($category['services'] as $service)
                                        @php
                                            $isRegularHaircut = strtoupper($service->code) === 'RH';
                                            $isOwnerHaircut = strtoupper($service->code) === 'HBR';
                                        @endphp
                                        <label class="relative flex cursor-pointer flex-col justify-between rounded-xl border-2 p-3 transition-all h-full"
                                             @if ($isRegularHaircut) x-show="activeBooking?.barber?.role?.toLowerCase() !== 'owner'" @endif
                                             @if ($isOwnerHaircut) x-show="activeBooking?.barber?.role?.toLowerCase() === 'owner'" @endif
                                             :class="isSelected({{ $service->id }}) ? 'border-gray-900 bg-red-100 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] -translate-x-0.5 -translate-y-0.5' : 'border-gray-300 bg-white hover:border-gray-900 hover:shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]'">
                                            <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" class="hidden" @change="toggleService({{ $service->id }}, {{ $service->price }}, '{{ addslashes($service->name) }}')" :checked="isSelected({{ $service->id }})">
                                            
                                            <div class="mb-2 text-sm font-bold leading-tight text-gray-900 pr-4">
                                                {{ $service->name }}
                                            </div>
                                            <div class="text-xs font-black text-gray-700 bg-white border-2 border-gray-900 px-2 py-0.5 rounded-lg w-max">
                                                {{ number_format($service->price, 0, ',', '.') }}
                                            </div>
                                            
                                            <div x-show="isSelected({{ $service->id }})" class="absolute top-2 right-2 flex h-4 w-4 items-center justify-center rounded-full border-2 border-gray-900 bg-white">
                                                <div class="h-1.5 w-1.5 rounded-full bg-gray-900"></div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="my-6 border-t-2 border-dashed border-gray-300"></div>

                        {{-- Pembayaran: Hanya tampil jika BELUM ada DP (booking Sekarang / Jadwalkan Full) --}}
                        <template x-if="!hasPrepaidDp">
                            <div>
                                <h4 class="mb-3 font-montserrat text-sm font-black uppercase tracking-widest text-gray-900">Pembayaran</h4>
                                
                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-3 transition-all"
                                           :class="paymentType === 'full' ? 'border-gray-900 bg-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50 grayscale'">
                                        <input type="radio" name="payment_type" value="full" x-model="paymentType" class="hidden">
                                        <div class="flex h-5 w-5 items-center justify-center rounded-full border-2 border-gray-900 bg-white">
                                            <div class="h-2 w-2 rounded-full bg-gray-900" x-show="paymentType === 'full'"></div>
                                        </div>
                                        <span class="font-bold text-sm text-gray-900 uppercase">Lunas</span>
                                    </label>
                                    <label class="flex items-center gap-2 rounded-xl border-2 p-3 transition-all"
                                           :class="!activeBooking?.phone ? 'border-gray-300 bg-gray-100 opacity-50 cursor-not-allowed' : (paymentType === 'dp' ? 'border-gray-900 bg-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] cursor-pointer' : 'border-gray-300 bg-white/50 grayscale cursor-pointer')">
                                        <input type="radio" name="payment_type" value="dp" x-model="paymentType" class="hidden" :disabled="!activeBooking?.phone">
                                        <div class="flex h-5 w-5 items-center justify-center rounded-full border-2 border-gray-900 bg-white" :class="!activeBooking?.phone ? 'opacity-50' : ''">
                                            <div class="h-2 w-2 rounded-full bg-gray-900" x-show="paymentType === 'dp'"></div>
                                        </div>
                                        <span class="font-bold text-sm text-gray-900 uppercase">DP</span>
                                    </label>
                                </div>

                                <div class="grid grid-cols-2 gap-3 mb-2">
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-3 transition-all"
                                           :class="paymentMethod === 'cash' ? 'border-gray-900 bg-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50 grayscale'">
                                        <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="hidden">
                                        <div class="flex h-5 w-5 items-center justify-center rounded-full border-2 border-gray-900 bg-white">
                                            <div class="h-2 w-2 rounded-full bg-gray-900" x-show="paymentMethod === 'cash'"></div>
                                        </div>
                                        <span class="font-bold text-sm text-gray-900 uppercase">Cash</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-3 transition-all"
                                           :class="paymentMethod === 'qris_static' ? 'border-gray-900 bg-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50 grayscale'">
                                        <input type="radio" name="payment_method" value="qris_static" x-model="paymentMethod" class="hidden">
                                        <div class="flex h-5 w-5 items-center justify-center rounded-full border-2 border-gray-900 bg-white">
                                            <div class="h-2 w-2 rounded-full bg-gray-900" x-show="paymentMethod === 'qris_static'"></div>
                                        </div>
                                        <span class="font-bold text-sm text-gray-900 uppercase">QRIS</span>
                                    </label>
                                </div>
                            </div>
                        </template>

                        {{-- Pelunasan: Metode bayar sisa, hanya muncul jika ada DP dan sisa > 0 --}}
                        <template x-if="hasPrepaidDp && remainingAmount > 0">
                            <div>
                                <h4 class="mb-3 font-montserrat text-sm font-black uppercase tracking-widest text-gray-900">Metode Pelunasan</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-3 transition-all"
                                           :class="settlementPaymentMethod === 'cash' ? 'border-gray-900 bg-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50'">
                                        <input type="radio" name="settlement_payment_method" value="cash" x-model="settlementPaymentMethod" class="hidden">
                                        <span class="font-bold text-sm text-gray-900 uppercase">Cash</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border-2 p-3 transition-all"
                                           :class="settlementPaymentMethod === 'qris_static' ? 'border-gray-900 bg-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-gray-300 bg-white/50'">
                                        <input type="radio" name="settlement_payment_method" value="qris_static" x-model="settlementPaymentMethod" class="hidden">
                                        <span class="font-bold text-sm text-gray-900 uppercase">QRIS</span>
                                    </label>
                                </div>
                            </div>
                        </template>

                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="border-t-2 border-gray-900 bg-white p-5">
                    {{-- Footer untuk booking dengan DP: tampilkan rincian pelunasan --}}
                    <template x-if="hasPrepaidDp">
                        <div class="mb-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Layanan</span>
                                <span class="font-bold text-gray-900" x-text="formatRupiah(total)"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">DP Terbayar</span>
                                <span class="font-bold text-emerald-600" x-text="'- ' + formatRupiah(prepaidDpAmount)"></span>
                            </div>
                            <div class="flex items-center justify-between border-t-2 border-dashed border-gray-300 pt-2">
                                <span class="text-xs font-black uppercase tracking-wider text-gray-900">Sisa Pelunasan</span>
                                <span class="font-montserrat text-2xl font-black" :class="remainingAmount > 0 ? 'text-red-600' : 'text-emerald-600'" x-text="formatRupiah(remainingAmount)"></span>
                            </div>
                        </div>
                    </template>

                    {{-- Footer untuk booking tanpa DP: total saja --}}
                    <template x-if="!hasPrepaidDp">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Nilai</span>
                            <span class="font-montserrat text-2xl font-black text-gray-900" x-text="formatRupiah(total)"></span>
                        </div>
                    </template>

                    <button type="submit" form="completeForm" class="w-full rounded-xl border-2 border-gray-900 bg-emerald-400 px-4 py-3.5 text-center text-sm font-black uppercase tracking-wider text-gray-900 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[5px_5px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] disabled:opacity-50 disabled:cursor-not-allowed" :disabled="selectedServices.length === 0">
                        <span x-text="hasPrepaidDp ? (remainingAmount > 0 ? 'Selesaikan Pelunasan' : 'Simpan & Selesai') : 'Simpan & Selesai'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
