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
                <input type="hidden" name="_method" id="schedule-form-method" value="POST">
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

<!-- Modal Template Jadwal Mingguan Compact -->
<div id="template-schedule-modal"
     x-data="scheduleTemplateManager()"
     tabindex="-1"
     aria-hidden="true"
     class="fixed inset-0 z-50 hidden h-full w-full items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-xs">
    <div class="relative w-full max-w-3xl">
        <div class="relative rounded-2xl border-2 border-gray-900 bg-[#FAF8F5] p-5 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
            <!-- Header Modal -->
            <div class="flex items-center justify-between border-b-2 border-gray-900 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-gray-900 bg-emerald-400 text-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                        </svg>
                    </span>
                    <div>
                        <h3 class="font-league text-2xl font-black uppercase text-gray-900 leading-none">Template Jadwal Mingguan</h3>
                        <p class="text-[11px] font-bold text-gray-500 mt-0.5">Jadwal kerja tetap seminggu untuk Rizal & Sogi.</p>
                    </div>
                </div>
                <button type="button" data-modal-hide="template-schedule-modal" class="rounded-lg border-2 border-gray-900 bg-white px-2 py-0.5 font-black hover:bg-gray-100">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.schedules.apply-template') }}" class="mt-4 space-y-3" @submit="if(!confirm('Terapkan template jadwal untuk minggu ini?')) { $event.preventDefault(); }">
                @csrf
                <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                <input type="hidden" name="template_data" :value="JSON.stringify(templates)">
                <input type="hidden" name="clean_unbooked" :value="cleanUnbooked ? 1 : 0">

                <!-- Barber Switcher Tabs -->
                <div class="relative grid grid-cols-2 gap-2 rounded-xl border-2 border-gray-900 bg-gray-100 p-1">
                    <div class="pointer-events-none absolute inset-y-1 left-1 w-[calc(50%-0.5rem)] rounded-xl bg-brand will-change-transform transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]"
                        :class="currentBarberId === 2 ? 'translate-x-[calc(100%+0.5rem)]' : 'translate-x-0'"></div>

                    <button type="button"
                            @click="currentBarberId = 1"
                            :class="currentBarberId === 1 ? 'text-white' : 'text-gray-700 hover:text-gray-900'"
                            class="relative z-10 flex items-center justify-center gap-2 rounded-xl py-2 text-xs font-black uppercase transition-colors duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] active:scale-95">
                        <span>Rizal</span>
                        <span class="rounded bg-black/20 px-1.5 py-0.5 text-[10px]">Owner</span>
                    </button>
                    <button type="button"
                            @click="currentBarberId = 2"
                            :class="currentBarberId === 2 ? 'text-white' : 'text-gray-700 hover:text-gray-900'"
                            class="relative z-10 flex items-center justify-center gap-2 rounded-xl py-2 text-xs font-black uppercase transition-colors duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] active:scale-95">
                        <span>Sogi</span>
                        <span class="rounded bg-black/20 px-1.5 py-0.5 text-[10px]">Senior</span>
                    </button>
                </div>

                <!-- Floating/Docked Selection Toolbar (Muncul saat ada jam dipilih) -->
                <div x-show="selectedCountCurrentBarber() > 0"
                     x-transition
                     class="flex items-center justify-between rounded-xl border-2 border-gray-900 bg-amber-300 p-2 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                    <div class="flex items-center gap-2 pl-1">
                        <span class="flex h-5 w-5 items-center justify-center rounded-md bg-gray-900 text-amber-300 text-xs font-black" x-text="selectedCountCurrentBarber()"></span>
                        <span class="text-xs font-black text-gray-900">jam dipilih</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button"
                                @click="selectAllCurrentBarber()"
                                class="rounded-lg border-2 border-gray-900 bg-white px-2 py-1 text-[11px] font-black uppercase text-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] hover:bg-gray-50 active:shadow-none">
                            Pilih Semua
                        </button>
                        <button type="button"
                                @click="deselectAllCurrentBarber()"
                                class="rounded-lg border-2 border-gray-900 bg-white px-2 py-1 text-[11px] font-black uppercase text-gray-700 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] hover:bg-gray-50 active:shadow-none">
                            Batal
                        </button>
                        <button type="button"
                                @click="deleteSelectedCurrentBarber()"
                                class="flex items-center gap-1 rounded-lg border-2 border-gray-900 bg-red-500 px-2 py-1 text-[11px] font-black uppercase text-white shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] hover:bg-red-600 active:shadow-none">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus Terpilih
                        </button>
                    </div>
                </div>

                <!-- 7 Hari Terpisah (Senin s/d Minggu) -->
                <div class="space-y-2 max-h-[55vh] overflow-y-auto pr-1">
                    <template x-for="day in daysList" :key="day.offset">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 rounded-xl border-2 border-gray-900 bg-white px-2.5 py-1.5 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <!-- Kolom Hari & Jumlah Slot -->
                            <div class="sm:w-22 shrink-0 flex items-center justify-between sm:flex-col sm:items-start">
                                <span class="text-xs font-black uppercase text-gray-900" x-text="day.label"></span>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="rounded bg-gray-100 px-1.5 py-0.2 text-[10px] font-bold text-gray-600" x-text="(templates[currentBarberId]?.days[day.offset] || []).length + ' slot'"></span>
                                    <button type="button" @click="selectAllInDay(currentBarberId, day.offset)" class="text-[10px] font-bold text-brand hover:underline">Pilih</button>
                                </div>
                            </div>

                            <!-- Badges Slot Jam & Inline Add -->
                            <div class="flex flex-wrap items-center gap-1.5 flex-1 min-w-0">
                                <template x-for="time in (templates[currentBarberId]?.days[day.offset] || [])" :key="time">
                                    <div @click="handleSlotClick(currentBarberId, day.offset, time, $event)"
                                         :class="isSelected(currentBarberId, day.offset, time)
                                            ? 'bg-amber-300 border-gray-900 text-gray-900 font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] -translate-y-0.5'
                                            : 'bg-[#FAF8F5] border-gray-900 text-gray-800 font-bold hover:bg-gray-100 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]'"
                                         class="inline-flex items-center gap-1.5 rounded-lg border-2 px-2 py-0.5 text-xs select-none cursor-pointer transition-all">
                                        <span x-text="time"></span>
                                        <button type="button"
                                                @click.stop="deleteSingleSlot(currentBarberId, day.offset, time)"
                                                class="text-gray-400 hover:text-red-600 font-black text-sm leading-none"
                                                title="Hapus jam ini">&times;</button>
                                    </div>
                                </template>

                                <!-- Mini Inline Time Input & Add Button -->
                                <div class="inline-flex items-center gap-1 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 px-1.5 py-0.5 focus-within:border-gray-900">
                                    <input type="time"
                                           x-model="newTimeInputs[currentBarberId + '_' + day.offset]"
                                           @keydown.enter.prevent="addSlot(currentBarberId, day.offset)"
                                           class="w-18 border-0 bg-transparent p-0 text-xs font-bold text-gray-900 focus:ring-0">
                                    <button type="button"
                                            @click="addSlot(currentBarberId, day.offset)"
                                            class="rounded bg-brand px-1.5 py-0.5 text-[10px] font-black uppercase text-white hover:opacity-90"
                                            title="Tambah jam">+ Jam</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Clean Unbooked Checkbox (Simple) -->
                <label class="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-gray-700">
                    <input type="checkbox" x-model="cleanUnbooked" class="h-4 w-4 rounded border-2 border-gray-900 text-brand focus:ring-0">
                    <span>Hapus slot kosong di luar template (slot yang sudah dipesan tetap aman).</span>
                </label>

                <!-- Footer Actions -->
                <div class="flex items-center justify-between border-t border-gray-200 pt-3">
                    <button type="button"
                            @click="resetDefault()"
                            class="text-xs font-black uppercase text-gray-500 hover:text-gray-900 underline">
                        ↺ Reset Bawaan
                    </button>

                    <div class="flex items-center gap-2">
                        <button type="button"
                                data-modal-hide="template-schedule-modal"
                                class="rounded-xl border-2 border-gray-900 bg-white px-3.5 py-2 text-xs font-black uppercase text-gray-700 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-xl border-2 border-gray-900 bg-emerald-400 px-4 py-2 text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:translate-x-0 active:translate-y-0 active:shadow-none cursor-pointer">
                            <span>Terapkan Template</span>
                            <span class="rounded bg-gray-900 px-1.5 py-0.2 text-[10px] text-white" x-text="totalSlotsCount()"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

