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
