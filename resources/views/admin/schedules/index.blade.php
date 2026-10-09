@extends('admin.layouts.app')

@section('title', 'Schedule Management')

@section('content')
    <div class="space-y-6" x-data="scheduleBulkManager(@js($allAvailableScheduleIds), @js($cellAvailableMap))">
        <!-- Header Banner & Action Controls -->
        <div class="rounded-2xl border-2 border-gray-900 bg-white p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
            <div class="flex flex-col gap-5">
                <!-- Top Row: Title & Primary Actions -->
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <span class="inline-block rounded-full border border-gray-900 bg-brand/10 px-3 py-0.5 text-xs font-bold uppercase tracking-wider text-brand">
                            Management
                        </span>
                        <h1 class="mt-1 font-league text-4xl font-black uppercase text-gray-900">Weekly Schedules</h1>
                        <p class="mt-0.5 text-xs font-bold text-gray-500">
                            {{ $weekStart->format('d M Y') }} &ndash; {{ $weekEnd->format('d M Y') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button"
                                data-modal-target="bulk-schedule-modal"
                                data-modal-toggle="bulk-schedule-modal"
                                class="inline-flex items-center gap-1.5 rounded-xl border-2 border-gray-900 bg-brand px-4 py-2.5 text-xs font-black uppercase text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none cursor-pointer">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Bulk Set
                        </button>

                        <form method="POST" action="{{ route('admin.schedules.copy-previous-week') }}" class="inline">
                            @csrf
                            <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-xl border-2 border-gray-900 bg-amber-300 px-4 py-2.5 text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none cursor-pointer">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                                </svg>
                                Copy Previous
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Bottom Row: Filter Controls & Week Navigation -->
                <div class="flex flex-col justify-between gap-3 border-t border-gray-100 pt-4 lg:flex-row lg:items-center">
                    <!-- Filters -->
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                                <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 8v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0Zm5 3h2v2H5v-2Zm4 0h2v2H9v-2Zm4 0h2v2h-2v-2Z"/>
                                </svg>
                            </div>
                            <input type="text"
                                   name="week"
                                   value="{{ $weekStart->format('Y-m-d') }}"
                                   datepicker
                                   datepicker-format="yyyy-mm-dd"
                                   class="block rounded-xl border-2 border-gray-900 bg-white py-2 ps-9 pe-3 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0"
                                   placeholder="Pilih tanggal">
                        </div>

                        <select name="role" onchange="this.form.submit()" class="rounded-xl border-2 border-gray-900 py-2 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                            <option value="">Semua role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
                            @endforeach
                        </select>

                        <select name="barber" onchange="this.form.submit()" class="rounded-xl border-2 border-gray-900 py-2 text-xs font-bold text-gray-900 focus:border-brand focus:ring-0">
                            <option value="">Semua barber</option>
                            @foreach ($allBarbers as $filterBarber)
                                <option value="{{ $filterBarber->id }}" @selected((string) $selectedBarber === (string) $filterBarber->id)>
                                    {{ $filterBarber->name }}
                                </option>
                            @endforeach
                        </select>

                        @if ($selectedRole || $selectedBarber)
                            <a href="{{ route('admin.schedules.index', ['week' => $weekStart->toDateString()]) }}"
                               class="rounded-xl border-2 border-gray-900 bg-gray-100 px-3 py-2 text-xs font-black uppercase text-gray-700 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] hover:bg-gray-200">
                                Reset
                            </a>
                        @endif
                    </form>

                    <!-- Week Pagination Navigator -->
                    <div class="flex items-center gap-1.5 self-start lg:self-auto">
                        <a href="{{ route('admin.schedules.index', ['week' => $weekStart->subWeek()->toDateString(), 'role' => $selectedRole, 'barber' => $selectedBarber]) }}"
                           class="inline-flex items-center gap-1 rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:bg-amber-300 active:translate-x-0.5 active:translate-y-0.5 active:shadow-none"
                           title="Minggu Sebelumnya">
                            &larr; Prev
                        </a>

                        <a href="{{ route('admin.schedules.index', ['week' => $weekStart->addWeek()->toDateString(), 'role' => $selectedRole, 'barber' => $selectedBarber]) }}"
                           class="inline-flex items-center gap-1 rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:bg-amber-300 active:translate-x-0.5 active:translate-y-0.5 active:shadow-none"
                           title="Minggu Berikutnya">
                            Next &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Alerts -->
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

        <!-- Dismissible Drag & Drop Hint -->
        <div x-data="{ showHint: true }" x-show="showHint" class="rounded-xl border-2 border-dashed border-gray-900 bg-amber-50 p-3 text-xs font-bold text-gray-900 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-gray-900 bg-amber-300 font-black text-xs">
                    💡
                </span>
                <span>
                    <strong>Reschedule Cepat:</strong> Tarik (drag) kotak booking abu-abu ke kolom hari mana pun untuk memindahkan pesanan pelanggan.
                </span>
            </div>
            <button type="button" @click="showHint = false" class="text-gray-400 hover:text-gray-900 font-black text-base px-1 leading-none" title="Tutup petunjuk">&times;</button>
        </div>

        <!-- Grid Matrix Container Retro -->
        <div class="overflow-hidden rounded-2xl border-2 border-gray-900 bg-white shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
            <div class="overflow-x-auto">
                <div class="min-w-[990px]">
                    <!-- Header Row -->
                    <div class="grid grid-cols-[220px_repeat(7,minmax(110px,1fr))] border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-wide text-gray-500">
                        <div class="p-4 flex items-center font-bold text-gray-900">Barber</div>
                        @foreach ($days as $day)
                            <div class="border-l border-gray-200 p-3 text-center">
                                <div class="text-xs text-gray-500 font-bold uppercase">{{ $day->format('D') }}</div>
                                <div class="mt-0.5 text-sm font-black text-gray-900">{{ $day->format('d M') }}</div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Rows Barber & Schedules -->
                    @forelse ($barbers as $barber)
                        <div class="grid grid-cols-[220px_repeat(7,minmax(110px,1fr))] border-b border-gray-100 last:border-0">
                            <!-- Barber Column -->
                            <div class="flex items-center gap-3 p-4">
                                @if ($barber->photo)
                                    <img src="{{ asset('storage/' . $barber->photo) }}" class="h-10 w-10 rounded-full object-cover" alt="{{ $barber->name }}">
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-bold text-primary">
                                        {{ strtoupper(substr($barber->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="font-semibold text-gray-900 truncate">{{ $barber->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $barber->role }}</div>
                                </div>
                            </div>

                            <!-- Day Columns -->
                            @foreach ($days as $day)
                                @php
                                    $cellKey = $barber->id . '_' . $day->toDateString();
                                    $cellAvailableCount = count($cellAvailableMap[$cellKey] ?? []);
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

                                    <!-- Per-Barber Cell Day Subheader -->
                                    @if ($cellAvailableCount > 0)
                                        <div class="flex items-center justify-between gap-1 mb-1.5 px-1 py-0.5 rounded-md bg-gray-50 border border-gray-100">
                                            <span class="text-[10px] font-bold text-gray-400 whitespace-nowrap">
                                                {{ $cellAvailableCount }} slot
                                            </span>
                                            <button type="button"
                                                    @click="toggleCell('{{ $cellKey }}')"
                                                    title="Pilih / batalkan semua slot kosong barber ini di hari ini"
                                                    class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider transition-all cursor-pointer whitespace-nowrap"
                                                    :class="isCellAllSelected('{{ $cellKey }}') ? 'border border-gray-900 bg-amber-300 text-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]' : (isCellPartiallySelected('{{ $cellKey }}') ? 'bg-amber-100 text-gray-800' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-200/70')">
                                                <span x-text="isCellAllSelected('{{ $cellKey }}') ? '✓ Terpilih' : 'Pilih Hari'"></span>
                                            </button>
                                        </div>
                                    @endif

                                    <!-- Slot List -->
                                    <div class="space-y-1">
                                        @forelse ($daySchedules as $schedule)
                                            @php
                                                $booking = $schedule->bookings->first();
                                            @endphp
                                            <div class="group/slot relative" x-data="{ tooltipOpen: false }" @click.outside="tooltipOpen = false">
                                                @if ($schedule->is_available)
                                                    <!-- Available Slot Card -->
                                                    <div class="flex items-center justify-between gap-1.5 rounded-lg border-2 px-1.5 py-1 text-xs transition-all select-none cursor-pointer"
                                                         @click="handleSlotClick({{ $schedule->id }}, {{ $barber->id }}, '{{ $day->toDateString() }}', '{{ $schedule->slot_time->format('H:i') }}', $event)"
                                                         :class="isSelected({{ $schedule->id }}) ? 'border-gray-900 bg-amber-200 text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'border-transparent bg-green-50 text-green-700 hover:border-green-300'">
                                                        <div class="flex items-center gap-1.5 min-w-0">
                                                            <input type="checkbox"
                                                                   value="{{ $schedule->id }}"
                                                                   :checked="isSelected({{ $schedule->id }})"
                                                                   @click.stop="handleSlotClick({{ $schedule->id }}, {{ $barber->id }}, '{{ $day->toDateString() }}', '{{ $schedule->slot_time->format('H:i') }}', $event)"
                                                                   class="h-3.5 w-3.5 rounded border-2 border-gray-900 text-brand focus:ring-0 cursor-pointer">
                                                            <button type="button"
                                                                    data-modal-target="schedule-modal"
                                                                    data-modal-toggle="schedule-modal"
                                                                    data-barber-id="{{ $barber->id }}"
                                                                    data-date="{{ $day->toDateString() }}"
                                                                    data-slot-time="{{ $schedule->slot_time->format('H:i') }}"
                                                                    data-schedule-id="{{ $schedule->id }}"
                                                                    data-update-url="{{ route('admin.schedules.update', $schedule) }}"
                                                                    @click.stop
                                                                    class="hover:underline font-bold truncate">
                                                                {{ $schedule->slot_time->format('H:i') }}
                                                            </button>
                                                        </div>
                                                        <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}" onsubmit="return confirm('Hapus slot jadwal ini?')" @click.stop>
                                                            @csrf @method('DELETE')
                                                            <button type="submit"
                                                                    class="font-bold opacity-0 group-hover/slot:opacity-100 hover:text-red-600 transition-opacity cursor-pointer text-gray-400"
                                                                    aria-label="Delete schedule">&times;</button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <!-- Booked / Unavailable Slot -->
                                                    @if ($booking)
                                                        <div draggable="true"
                                                             title="Tarik (drag) ke kolom hari mana pun untuk reschedule"
                                                             data-booking-id="{{ $booking->id }}"
                                                             data-customer-name="{{ $booking->name ?? 'Customer' }}"
                                                             data-customer-phone="{{ $booking->formatted_phone }}"
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

                                                        <!-- Tooltip Pelanggan -->
                                                        <div x-show="tooltipOpen" x-cloak
                                                             class="py-3 mb-2 pointer-events-none absolute bottom-full left-1/2 z-20 hidden w-full -translate-x-1/2 rounded-lg bg-gray-900 text-center text-xs text-white shadow-lg md:block! md:opacity-0 md:group-hover/slot:opacity-100">
                                                            <p class="font-semibold">{{ $booking->name ?? 'Customer' }}</p>
                                                            <p class="mt-1 text-gray-300">{{ $booking->phone ? $booking->formatted_phone : 'Phone unavailable' }}</p>
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

                                    <!-- Add Slot Quick Button -->
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
                        <div class="p-10 text-center text-sm text-gray-500">Tidak ada barber aktif yang cocok dengan filter yang dipilih.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Floating Bulk Action Bar Retro -->
        <div x-show="selectedIds.length > 0"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-6"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-6"
             x-cloak
             class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 w-[95%] max-w-2xl">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border-2 border-gray-900 bg-white p-4 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl border-2 border-gray-900 bg-amber-300 font-black text-sm text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                        ✓
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-montserrat text-sm font-black uppercase text-gray-900">
                                <span x-text="selectedIds.length"></span> Slot Terpilih
                            </span>
                            <span class="text-xs font-bold text-gray-400">/ <span x-text="allAvailableIds.length"></span> total kosong</span>
                        </div>
                        <p class="text-[11px] font-bold text-gray-500">Tahan <strong>Shift + Klik</strong> untuk pilih rentang jam</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="selectAll()"
                            class="rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-gray-100 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none cursor-pointer">
                        Pilih Semua
                    </button>

                    <button type="button"
                            @click="deselectAll()"
                            class="rounded-xl border-2 border-gray-900 bg-white px-3 py-2 text-xs font-black uppercase text-gray-700 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-gray-100 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none cursor-pointer">
                        Batal
                    </button>

                    <form method="POST"
                          action="{{ route('admin.schedules.bulk-destroy') }}"
                          @submit="if(!confirm('Yakin ingin menghapus ' + selectedIds.length + ' slot jadwal terpilih?')) { $event.preventDefault(); }">
                        @csrf
                        @method('DELETE')
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="schedule_ids[]" :value="id">
                        </template>
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-xl border-2 border-gray-900 bg-red-500 px-4 py-2 text-xs font-black uppercase text-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:bg-red-600 hover:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Hapus Terpilih
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modals Partial --}}
    @include('admin.schedules._modals')
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/schedules.js') }}"></script>
@endpush