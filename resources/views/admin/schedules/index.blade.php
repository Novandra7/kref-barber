@extends('admin.layouts.app')

@section('title', 'Schedule Management')

@section('content')
    <div class="space-y-6">
        <!-- Banner Header Container Retro -->
        <div class="rounded-2xl border-2 border-gray-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                <div>
                    <span class="inline-block rounded-full border border-gray-900 bg-brand/10 px-3 py-0.5 text-xs font-bold uppercase tracking-wider text-brand">
                        Management
                    </span>
                    <h1 class="mt-1 font-league text-4xl font-black uppercase text-gray-900">Weekly Schedules</h1>
                    <p class="mt-1 text-xs font-bold text-gray-500">
                        {{ $weekStart->format('d M Y') }} - {{ $weekEnd->format('d M Y') }}
                    </p>
                </div>

                <!-- Action Controls Retro -->
                <div class="flex flex-wrap gap-2">
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                                <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 8v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0Zm5 3h2v2H5v-2Zm4 0h2v2H9v-2Zm4 0h2v2h-2v-2Z"/>
                                </svg>
                            </div>

                            <input
                                type="text"
                                name="week"
                                value="{{ $weekStart->format('Y-m-d') }}"
                                datepicker
                                datepicker-format="yyyy-mm-dd"
                                class="block w-full rounded-xl border-2 border-gray-900 bg-white
                                    py-2.5 ps-10 pe-3 text-xs font-bold text-gray-900
                                    focus:border-brand focus:ring-0"
                                placeholder="Select date"
                            >
                        </div>                       
                        <select name="role" onchange="this.form.submit()" class="rounded-xl border-2 border-gray-900 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                            <option value="">All roles</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
                            @endforeach
                        </select>

                        <select name="barber" onchange="this.form.submit()" class="rounded-xl border-2 border-gray-900 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                            <option value="">All barbers</option>
                            @foreach ($allBarbers as $filterBarber)
                                <option value="{{ $filterBarber->id }}" @selected((string) $selectedBarber === (string) $filterBarber->id)>
                                    {{ $filterBarber->name }}
                                </option>
                            @endforeach
                        </select>

                        <a href="{{ route('admin.schedules.index', ['week' => $weekStart->toDateString()]) }}"
                           class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-black uppercase text-gray-700 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-gray-100 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none">
                            Reset filter
                        </a>
                    </form>

                    <a href="{{ route('admin.schedules.index', ['week' => $weekStart->subWeek()->toDateString(), 'role' => $selectedRole, 'barber' => $selectedBarber]) }}"
                       class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-amber-300 active:translate-x-0 active:translate-y-0 active:shadow-none">Previous</a>

                    <a href="{{ route('admin.schedules.index', ['week' => $weekStart->addWeek()->toDateString(), 'role' => $selectedRole, 'barber' => $selectedBarber]) }}"
                       class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-amber-300 active:translate-x-0 active:translate-y-0 active:shadow-none">Next</a>

                    <button type="button" data-modal-target="bulk-schedule-modal" data-modal-toggle="bulk-schedule-modal"
                            class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-brand px-4 py-2 text-xs font-black uppercase text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none">Bulk set</button>

                    <form method="POST" action="{{ route('admin.schedules.copy-previous-week') }}" class="inline">
                        @csrf
                        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                        <button class="inline-flex items-center rounded-xl border-2 border-gray-900 bg-amber-300 px-4 py-2 text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none">Copy previous</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Flash Alerts Retro -->
        @if (session('success'))
            <div class="rounded-xl border-2 border-gray-900 bg-green-100 p-4 text-xs font-bold text-green-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border-2 border-gray-900 bg-red-100 p-4 text-xs font-bold text-red-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                ⚠️ {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border-2 border-gray-900 bg-red-100 p-4 text-xs font-bold text-red-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <!-- Drag & Drop Hint Banner Retro -->
        <div class="rounded-xl border-2 border-dashed border-gray-900 bg-amber-50 p-3 text-xs font-bold text-gray-900 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-gray-900 bg-amber-300 font-black text-xs">
                    💡
                </span>
                <span>
                    <strong>Reschedule Cepat:</strong> Tarik (drag) kotak booking abu-abu ke kolom hari mana pun. Waktu booking akan berpindah otomatis tanpa menimpa jadwal available lainnya (hanya mengisi jika sudah ada slot kosong di jam yang sama).
                </span>
            </div>
        </div>

        <!-- Grid Matrix Container Retro -->
        <div class="overflow-hidden rounded-2xl border-2 border-gray-900 bg-white shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
            <div class="overflow-x-auto">
                <div class="min-w-[990px]">
                    <!-- Header Row -->
                    <div class="grid grid-cols-[220px_repeat(7,minmax(110px,1fr))] border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-wide text-gray-500">
                        <div class="p-4 flex items-center font-bold text-gray-900">Barber</div>
                        @foreach ($days as $day)
                            <div class="border-l border-gray-200 p-4 text-center">
                                <div>{{ $day->format('D') }}</div>
                                <div class="mt-1 text-sm text-gray-900">{{ $day->format('d M') }}</div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Rows Barber & Schedules -->
                    @forelse ($barbers as $barber)
                        <div class="grid grid-cols-[220px_repeat(7,minmax(110px,1fr))] border-b border-gray-100 last:border-0">
                            <div class="flex items-center gap-3 p-4">
                                @if ($barber->photo)
                                    <img src="{{ asset('storage/' . $barber->photo) }}" class="h-10 w-10 rounded-full object-cover" alt="{{ $barber->name }}">
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-bold text-primary">
                                        {{ strtoupper(substr($barber->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $barber->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $barber->role }}</div>
                                </div>
                            </div>
                            @foreach ($days as $day)
                                @php
                                    $daySchedules = $barber->schedules->filter(
                                        fn ($schedule) => $schedule->date->isSameDay($day)
                                    );
                                @endphp
                                <div class="day-drop-zone group relative border-l border-gray-100 p-2 transition-all min-h-[110px] rounded-xl"
                                     data-target-barber-id="{{ $barber->id }}"
                                     data-target-barber-name="{{ $barber->name }}"
                                     data-target-date-raw="{{ $day->toDateString() }}"
                                     data-target-date="{{ $day->format('d M Y') }}"
                                     data-schedules='@json($daySchedules->values()->map(fn ($s) => [
                                         "id" => $s->id,
                                         "time" => $s->slot_time->format("H:i"),
                                         "is_available" => (bool) $s->is_available
                                     ]))'>
                                    <div class="space-y-1">
                                        @forelse ($daySchedules as $schedule)
                                            @php
                                                $booking = $schedule->bookings->first();
                                            @endphp
                                            <div class="group/slot relative" x-data="{ tooltipOpen: false }" @click.outside="tooltipOpen = false">
                                                @if ($schedule->is_available)
                                                    <div class="flex items-center justify-between gap-1 rounded-lg border-2 border-transparent bg-green-50 px-2 py-1.5 text-xs text-green-700 transition-all">
                                                        <button type="button" data-modal-target="schedule-modal" data-modal-toggle="schedule-modal"
                                                                data-barber-id="{{ $barber->id }}" data-date="{{ $day->toDateString() }}"
                                                                data-slot-time="{{ $schedule->slot_time->format('H:i') }}"
                                                                data-schedule-id="{{ $schedule->id }}"
                                                                data-update-url="{{ route('admin.schedules.update', $schedule) }}"
                                                                class="hover:underline font-bold">
                                                            {{ $schedule->slot_time->format('H:i') }}
                                                        </button>
                                                        <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}" onsubmit="return confirm('Delete this schedule slot?')">
                                                            @csrf @method('DELETE')
                                                            <button class="font-bold hover:text-red-600" aria-label="Delete schedule">&times;</button>
                                                        </form>
                                                    </div>
                                                @else
                                                    @if ($booking)
                                                        <div draggable="true"
                                                             title="Tarik (drag) ke kolom hari mana pun untuk reschedule"
                                                             data-booking-id="{{ $booking->id }}"
                                                             data-customer-name="{{ $booking->name ?? 'Customer' }}"
                                                             data-customer-phone="0{{ $booking->phone ?? '-' }}"
                                                             data-source-schedule-id="{{ $schedule->id }}"
                                                             data-source-barber-id="{{ $barber->id }}"
                                                             data-source-barber-name="{{ $barber->name }}"
                                                             data-source-date-raw="{{ $day->toDateString() }}"
                                                             data-source-date="{{ $day->format('d M Y') }}"
                                                             data-source-time="{{ $schedule->slot_time->format('H:i') }}"
                                                             class="booked-drag-item cursor-grab active:cursor-grabbing flex items-center justify-between gap-1 rounded-lg border-2 border-transparent bg-gray-100 hover:border-gray-900 px-2 py-1.5 text-xs text-gray-700 transition-all select-none">
                                                            
                                                            <div class="flex items-center gap-1 font-bold text-gray-900">
                                                                <span class="text-gray-400">⠿</span>
                                                                <span>{{ $schedule->slot_time->format('H:i') }}</span>
                                                            </div>

                                                            @if ($booking->payment_type === 'dp')
                                                                <button type="button" @click.stop="tooltipOpen = !tooltipOpen" class="rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-700">
                                                                    DP
                                                                </button>
                                                            @elseif ($booking->payment_type === 'full')
                                                                <button type="button" @click.stop="tooltipOpen = !tooltipOpen" class="rounded-full bg-blue-100 px-2 py-0.5 font-semibold text-blue-700">
                                                                    FULL
                                                                </button>
                                                            @else
                                                                <button type="button" @click.stop="tooltipOpen = !tooltipOpen" class="rounded-full bg-gray-200 px-2 py-0.5 font-semibold text-gray-600">
                                                                    BOOKED
                                                                </button>
                                                            @endif
                                                        </div>

                                                        <div x-show="tooltipOpen" x-cloak
                                                             class="py-3 mb-2 pointer-events-none absolute bottom-full left-1/2 z-20 hidden w-full -translate-x-1/2 rounded-lg bg-gray-900 text-center text-xs text-white shadow-lg md:block! md:opacity-0 md:group-hover/slot:opacity-100">
                                                            <p class="font-semibold">{{ $booking->name ?? 'Customer' }}</p>
                                                            <p class="mt-1 text-gray-300">0{{ $booking->phone ?? 'Phone unavailable' }}</p>
                                                            <span class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-900"></span>
                                                        </div>
                                                    @else
                                                        <div class="flex items-center justify-between gap-1 rounded-lg bg-gray-100 px-2 py-1.5 text-xs text-gray-500">
                                                            <span>{{ $schedule->slot_time->format('H:i') }}</span>
                                                            <span class="rounded-full bg-gray-200 px-2 py-0.5 font-semibold text-gray-600">UNAVAIL</span>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        @empty
                                            <span class="block rounded-lg bg-red-50 px-2 py-1.5 text-center text-xs font-semibold text-red-600">OFF</span>
                                        @endforelse
                                    </div>
                                    <button type="button" data-modal-target="schedule-modal" data-modal-toggle="schedule-modal"
                                            data-barber-id="{{ $barber->id }}" data-date="{{ $day->toDateString() }}"
                                            data-create-schedule
                                            class="mt-2 w-full rounded-lg border border-dashed border-gray-300 py-1 text-xs text-gray-400 opacity-0 transition group-hover:opacity-100 hover:border-primary hover:text-primary">
                                        + slot
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="p-10 text-center text-sm text-gray-500">No active barbers match the selected filters.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Single Slot Retro -->
    <div id="schedule-modal" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden h-full w-full items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-xs">
        <div class="relative w-full max-w-md">
            <div class="relative rounded-2xl border-2 border-gray-900 bg-[#FAF8F5] p-5 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
                <div class="flex items-center justify-between border-b-2 border-gray-900 pb-3">
                    <h3 id="schedule-modal-title" class="font-league text-2xl font-black uppercase text-gray-900">Add schedule slot</h3>
                    <button type="button" data-modal-hide="schedule-modal" class="rounded-lg border-2 border-gray-900 bg-white px-2 py-0.5 font-black">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.schedules.store') }}" data-store-url="{{ route('admin.schedules.store') }}" id="schedule-form" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="_method" id="schedule-form-method" value="">
                    <input type="hidden" name="barber_id" id="schedule-barber-id">
                    <input type="hidden" name="date" id="schedule-date">
                    <div>
                        <label class="mb-1 block text-xs font-black uppercase text-gray-700">Time</label>
                        <input type="time" name="slot_time" id="schedule-slot-time" required class="block w-full rounded-xl border-2 border-gray-900 bg-white p-2.5 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                    </div>
                    <button id="schedule-submit" class="w-full rounded-xl border-2 border-gray-900 bg-brand px-4 py-2.5 text-xs font-black uppercase text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:shadow-none">Save slot</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Schedule Retro -->
    <div id="bulk-schedule-modal" x-data="{ timeSlots: [''] }" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden h-full w-full items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-xs">
        <div class="relative w-full max-w-md">
            <div class="relative rounded-2xl border-2 border-gray-900 bg-[#FAF8F5] p-5 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
                <div class="flex items-center justify-between border-b-2 border-gray-900 pb-3">
                    <h3 class="font-league text-2xl font-black uppercase text-gray-900">Atur Jadwal Sekaligus</h3>
                    <button @click="timeSlots = ['']" type="button" data-modal-hide="bulk-schedule-modal" class="rounded-lg border-2 border-gray-900 bg-white px-2 py-0.5 font-black">&times;</button>
                </div>
                
                <!-- Wrapper Alpine.js untuk mengelola dynamic slot time -->
                <form method="POST" action="{{ route('admin.schedules.bulk') }}" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                    
                    <div>
                        <label class="mb-1 block text-xs font-black uppercase text-gray-700">Pilih Barber</label>
                        <select name="barber_id" required class="block w-full rounded-xl border-2 border-gray-900 bg-white p-2.5 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                            <option value="">Pilih barber</option>
                            @foreach ($barbers as $barber)
                                <option value="{{ $barber->id }}">{{ $barber->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section Dynamic Slot Time -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-black uppercase text-gray-700">Waktu Slot</label>
                            <button type="button" @click="timeSlots.push('')" class="flex items-center gap-1 rounded-lg border-2 border-gray-900 bg-brand px-2 py-0.5 text-[10px] font-black uppercase text-white shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] hover:opacity-90">
                                + Tambah Waktu
                            </button>
                        </div>

                        <div class="space-y-2 max-h-36 overflow-y-auto pr-1">
                            <template x-for="(slot, index) in timeSlots" :key="index">
                                <div class="flex items-center gap-2">
                                    <input type="time" name="slot_times[]" required class="block w-full rounded-xl border-2 border-gray-900 bg-white p-2.5 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0" x-model="timeSlots[index]">
                                    
                                    <!-- Tombol Hapus Slot (Hanya muncul jika slot lebih dari 1) -->
                                    <button type="button" x-show="timeSlots.length > 1" @click="timeSlots.splice(index, 1)" class="shrink-0 rounded-xl border-2 border-gray-900 bg-red-500 p-2 text-xs font-black text-white shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] hover:bg-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-black uppercase text-gray-700">Terapkan Pada Hari</label>
                        <div class="grid grid-cols-2 gap-2 text-xs font-bold">
                            @foreach ($days as $index => $day)
                                <label class="flex items-center gap-2 rounded-xl border-2 border-gray-900 bg-white p-2 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                    <input type="checkbox" name="days[]" value="{{ $index }}" class="rounded-md border-2 border-gray-900 text-brand focus:ring-0"> {{ $day->format('D d M') }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <input type="hidden" name="is_available" value="1">
                    <button class="w-full rounded-xl border-2 border-gray-900 bg-brand px-4 py-2.5 text-xs font-black uppercase text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:shadow-none">Simpan Jadwal Sekaligus</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Trigger Modal Drag Reschedule (Hidden) -->
    <button id="open-drag-modal-btn" type="button" data-modal-target="drag-reschedule-modal" data-modal-toggle="drag-reschedule-modal" class="hidden"></button>

    <!-- Modal Konfirmasi Drag & Drop Reschedule -->
    <div id="drag-reschedule-modal" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden h-full w-full items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-xs">
        <div class="relative w-full max-w-lg">
            <div class="relative rounded-2xl border-2 border-gray-900 bg-[#FAF8F5] p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
                <!-- Header Modal -->
                <div class="flex items-center justify-between border-b-2 border-gray-900 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-gray-900 bg-brand text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-black uppercase tracking-wider text-gray-900">Konfirmasi Reschedule</h3>
                            <p class="text-[11px] font-bold text-gray-500">Pindahkan pesanan pelanggan ke slot jadwal baru</p>
                        </div>
                    </div>
                    <button type="button" data-modal-hide="drag-reschedule-modal" class="rounded-lg border-2 border-gray-900 bg-white px-2.5 py-1 text-sm font-black hover:bg-gray-100 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5">&times;</button>
                </div>

                <form method="POST" action="{{ route('admin.schedules.reschedule-booking') }}" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="booking_id" id="drag-booking-id">
                    <input type="hidden" name="target_schedule_id" id="drag-target-schedule-id">
                    <input type="hidden" name="target_barber_id" id="drag-target-barber-id">
                    <input type="hidden" name="target_date" id="drag-target-date">
                    <input type="hidden" name="target_time" id="drag-target-time">

                    <!-- Customer Info Box -->
                    <div class="rounded-xl border-2 border-gray-900 bg-white p-3 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                        <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Pelanggan</span>
                        <div class="mt-1 flex items-center justify-between">
                            <p id="drag-modal-customer-name" class="font-black text-gray-900 text-sm">-</p>
                            <span id="drag-modal-customer-phone" class="rounded-md border border-gray-900 bg-amber-100 px-2 py-0.5 text-xs font-bold text-gray-900">-</span>
                        </div>
                    </div>

                    <!-- From -> To Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-center">
                        <!-- Dari Jadwal Semula -->
                        <div class="rounded-xl border-2 border-gray-900 bg-red-50 p-3 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-black uppercase text-red-600 tracking-wider">Jadwal Semula</span>
                                <span class="rounded bg-red-200 px-1.5 py-0.5 text-[9px] font-black text-red-800 uppercase">Lama</span>
                            </div>
                            <p id="drag-modal-source-barber" class="font-bold text-gray-900 text-xs">-</p>
                            <p id="drag-modal-source-date" class="text-xs text-gray-600 font-semibold mt-0.5">-</p>
                            <p id="drag-modal-source-time" class="text-xs font-black text-red-700 mt-0.5">-</p>
                        </div>

                        <!-- Ke Jadwal Baru -->
                        <div class="rounded-xl border-2 border-gray-900 bg-green-50 p-3 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[10px] font-black uppercase text-green-700 tracking-wider">Jadwal Baru</span>
                                <span class="rounded bg-green-200 px-1.5 py-0.5 text-[9px] font-black text-green-800 uppercase">Baru</span>
                            </div>
                            <p id="drag-modal-target-barber" class="font-bold text-gray-900 text-xs">-</p>
                            <p id="drag-modal-target-date" class="text-xs text-gray-600 font-semibold mt-0.5">-</p>
                            <p id="drag-modal-target-time" class="text-xs font-black text-green-700 mt-0.5">-</p>
                        </div>
                    </div>

                    <!-- Dynamic Note Container -->
                    <div id="drag-modal-note"></div>

                    <!-- Release Old Slot Checkbox -->
                    <label class="flex items-start gap-3 cursor-pointer rounded-xl border-2 border-gray-900 bg-white p-3 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] hover:bg-gray-50 transition">
                        <input type="checkbox" name="release_old_slot" value="1" checked class="mt-0.5 h-4 w-4 rounded border-2 border-gray-900 text-brand focus:ring-0">
                        <div class="text-xs">
                            <span class="font-black text-gray-900">Bebaskan Slot Jadwal Lama</span>
                            <p class="text-gray-500 font-medium text-[11px] mt-0.5">Jadikan slot jadwal asal berstatus tersedia (hijau) agar dapat dipesan pelanggan lain.</p>
                        </div>
                    </label>

                    <!-- WhatsApp Notification Checkbox -->
                    <label class="flex items-start gap-3 cursor-pointer rounded-xl border-2 border-gray-900 bg-white p-3 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] hover:bg-gray-50 transition">
                        <input type="checkbox" name="notify_customer" value="1" checked class="mt-0.5 h-4 w-4 rounded border-2 border-gray-900 text-brand focus:ring-0">
                        <div class="text-xs">
                            <span class="font-black text-gray-900">Kirim Notifikasi WhatsApp</span>
                            <p class="text-gray-500 font-medium text-[11px] mt-0.5">Otomatis kirim detail jadwal baru ke nomor WhatsApp pelanggan.</p>
                        </div>
                    </label>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-2 pt-2 border-t-2 border-gray-900">
                        <button type="button" data-modal-hide="drag-reschedule-modal" class="rounded-xl border-2 border-gray-900 bg-white px-4 py-2.5 text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] hover:bg-gray-100 active:shadow-none active:translate-x-0.5 active:translate-y-0.5">
                            Batal
                        </button>
                        <button type="submit" class="rounded-xl border-2 border-gray-900 bg-brand px-5 py-2.5 text-xs font-black uppercase text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] hover:opacity-90 active:shadow-none active:translate-x-0.5 active:translate-y-0.5">
                            Ya, Pindahkan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/schedules.js') }}"></script>
@endpush