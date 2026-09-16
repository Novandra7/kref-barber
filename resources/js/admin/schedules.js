document.addEventListener('DOMContentLoaded', () => {
    // 1. Single Slot Modal Handlers (Add / Edit slot)
    const scheduleForm = document.getElementById('schedule-form');
    const storeUrl = scheduleForm ? (scheduleForm.dataset.storeUrl || scheduleForm.action) : '';

    document.querySelectorAll('[data-barber-id][data-date]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!scheduleForm) return;

            const isEdit = Boolean(button.dataset.scheduleId);
            scheduleForm.action = isEdit ? button.dataset.updateUrl : storeUrl;

            const methodInput = document.getElementById('schedule-form-method');
            if (methodInput) methodInput.value = isEdit ? 'PATCH' : '';

            const modalTitle = document.getElementById('schedule-modal-title');
            if (modalTitle) modalTitle.textContent = isEdit ? 'Edit schedule slot' : 'Add schedule slot';

            const submitBtn = document.getElementById('schedule-submit');
            if (submitBtn) submitBtn.textContent = isEdit ? 'Update slot' : 'Save slot';

            const barberIdInput = document.getElementById('schedule-barber-id');
            if (barberIdInput) barberIdInput.value = button.dataset.barberId || '';

            const dateInput = document.getElementById('schedule-date');
            if (dateInput) dateInput.value = button.dataset.date || '';

            const slotTimeInput = document.getElementById('schedule-slot-time');
            if (slotTimeInput) slotTimeInput.value = button.dataset.slotTime || '';
        });
    });

    // 2. Week Datepicker Change Handler
    const weekInput = document.querySelector('[name="week"]');
    if (weekInput) {
        weekInput.addEventListener('changeDate', () => {
            weekInput.form?.submit();
        });
    }

    // 3. Drag & Drop Reschedule Logic (Day-to-Day Drop)
    let draggedBookingData = null;

    const bookedItems = document.querySelectorAll('.booked-drag-item');
    const dayZones = document.querySelectorAll('.day-drop-zone');

    bookedItems.forEach(item => {
        item.addEventListener('dragstart', (e) => {
            draggedBookingData = {
                bookingId: item.dataset.bookingId,
                customerName: item.dataset.customerName,
                customerPhone: item.dataset.customerPhone,
                sourceScheduleId: item.dataset.sourceScheduleId,
                sourceBarberId: item.dataset.sourceBarberId,
                sourceBarberName: item.dataset.sourceBarberName,
                sourceDateRaw: item.dataset.sourceDateRaw,
                sourceDate: item.dataset.sourceDate,
                sourceTime: item.dataset.sourceTime
            };
            e.dataTransfer.setData('text/plain', JSON.stringify(draggedBookingData));
            e.dataTransfer.effectAllowed = 'move';
            item.classList.add('opacity-40');

            // Highlight all other day drop zones
            dayZones.forEach(zone => {
                const isSame = zone.dataset.targetBarberId === draggedBookingData.sourceBarberId &&
                               zone.dataset.targetDateRaw === draggedBookingData.sourceDateRaw;
                if (!isSame) {
                    zone.classList.add('ring-2', 'ring-brand/30', 'bg-brand/5');
                }
            });
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('opacity-40');
            draggedBookingData = null;

            // Remove highlight from all day drop zones
            dayZones.forEach(zone => {
                zone.classList.remove('ring-2', 'ring-4', 'ring-brand/30', 'ring-brand', 'bg-brand/5', 'bg-brand/15');
            });
        });
    });

    dayZones.forEach(zone => {
        let dragCounter = 0;

        zone.addEventListener('dragenter', (e) => {
            e.preventDefault();
            if (!draggedBookingData) return;
            const isSame = zone.dataset.targetBarberId === draggedBookingData.sourceBarberId &&
                           zone.dataset.targetDateRaw === draggedBookingData.sourceDateRaw;
            if (isSame) return;

            dragCounter++;
            zone.classList.add('ring-4', 'ring-brand', 'bg-brand/15');
        });

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (!draggedBookingData) return;
            const isSame = zone.dataset.targetBarberId === draggedBookingData.sourceBarberId &&
                           zone.dataset.targetDateRaw === draggedBookingData.sourceDateRaw;
            if (isSame) {
                e.dataTransfer.dropEffect = 'none';
                return;
            }
            e.dataTransfer.dropEffect = 'move';
        });

        zone.addEventListener('dragleave', (e) => {
            dragCounter--;
            if (dragCounter <= 0) {
                dragCounter = 0;
                zone.classList.remove('ring-4', 'ring-brand', 'bg-brand/15');
            }
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            dragCounter = 0;
            zone.classList.remove('ring-4', 'ring-brand', 'bg-brand/15');

            let data = draggedBookingData;
            if (!data) {
                try {
                    data = JSON.parse(e.dataTransfer.getData('text/plain'));
                } catch (err) {
                    return;
                }
            }

            if (!data || !data.bookingId) return;

            const targetBarberId = zone.dataset.targetBarberId;
            const targetBarberName = zone.dataset.targetBarberName;
            const targetDateRaw = zone.dataset.targetDateRaw;
            const targetDate = zone.dataset.targetDate;

            // Jika di-drop di hari dan barber yang sama persis
            if (data.sourceBarberId === targetBarberId && data.sourceDateRaw === targetDateRaw) {
                return;
            }

            const targetTime = data.sourceTime; // Waktu booking berpindah tetap sama!
            let schedules = [];
            try {
                const raw = JSON.parse(zone.dataset.schedules || '[]');
                schedules = Array.isArray(raw) ? raw : Object.values(raw);
            } catch (err) {
                schedules = [];
            }

            // Cek apakah di hari tujuan sudah ada schedule dengan jam yang sama
            const existingSchedule = schedules.find(s => s.time === targetTime);

            // Kasus A: Sudah ada schedule dengan jam sama, tapi SUDAH TERISI BOOKING LAIN
            if (existingSchedule && !existingSchedule.is_available) {
                alert(`⚠️ Tidak dapat memindahkan jadwal: Slot jam ${targetTime} WITA pada ${targetDate} untuk ${targetBarberName} sudah terisi oleh pesanan pelanggan lain.`);
                return;
            }

            // Kasus B: Sudah ada schedule dengan jam sama dan MASIH KOSONG (available)
            const isExistingAvailable = Boolean(existingSchedule && existingSchedule.is_available);

            // Isi field form modal
            const dragBookingId = document.getElementById('drag-booking-id');
            const dragTargetScheduleId = document.getElementById('drag-target-schedule-id');
            const dragTargetBarberId = document.getElementById('drag-target-barber-id');
            const dragTargetDate = document.getElementById('drag-target-date');
            const dragTargetTime = document.getElementById('drag-target-time');

            if (dragBookingId) dragBookingId.value = data.bookingId;
            if (dragTargetScheduleId) dragTargetScheduleId.value = isExistingAvailable ? existingSchedule.id : '';
            if (dragTargetBarberId) dragTargetBarberId.value = targetBarberId;
            if (dragTargetDate) dragTargetDate.value = targetDateRaw;
            if (dragTargetTime) dragTargetTime.value = targetTime;

            // Tampilkan info di modal
            const customerNameEl = document.getElementById('drag-modal-customer-name');
            const customerPhoneEl = document.getElementById('drag-modal-customer-phone');
            if (customerNameEl) customerNameEl.textContent = data.customerName;
            if (customerPhoneEl) customerPhoneEl.textContent = data.customerPhone;

            const sourceBarberEl = document.getElementById('drag-modal-source-barber');
            const sourceDateEl = document.getElementById('drag-modal-source-date');
            const sourceTimeEl = document.getElementById('drag-modal-source-time');
            if (sourceBarberEl) sourceBarberEl.textContent = data.sourceBarberName;
            if (sourceDateEl) sourceDateEl.textContent = data.sourceDate;
            if (sourceTimeEl) sourceTimeEl.textContent = data.sourceTime + ' WITA';

            const targetBarberEl = document.getElementById('drag-modal-target-barber');
            const targetDateEl = document.getElementById('drag-modal-target-date');
            const targetTimeEl = document.getElementById('drag-modal-target-time');
            if (targetBarberEl) targetBarberEl.textContent = targetBarberName;
            if (targetDateEl) targetDateEl.textContent = targetDate;
            if (targetTimeEl) targetTimeEl.textContent = targetTime + ' WITA';

            // Catatan keterangan visual di modal
            const noteContainer = document.getElementById('drag-modal-note');
            if (noteContainer) {
                if (isExistingAvailable) {
                    noteContainer.innerHTML = `
                        <div class="flex items-center gap-2 rounded-xl border-2 border-gray-900 bg-green-100 p-2.5 text-xs text-green-900 font-bold shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-green-500 text-white text-xs">✓</span>
                            <span>Mengisi slot kosong jam <strong>${targetTime} WITA</strong> yang sudah ada di hari tujuan.</span>
                        </div>
                    `;
                } else {
                    noteContainer.innerHTML = `
                        <div class="flex items-center gap-2 rounded-xl border-2 border-gray-900 bg-blue-100 p-2.5 text-xs text-blue-900 font-bold shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-blue-500 text-white text-xs">✦</span>
                            <span>Slot jam <strong>${targetTime} WITA</strong> akan dibuatkan baru otomatis tanpa mengubah jadwal yang sudah ada.</span>
                        </div>
                    `;
                }
            }

            // Trigger modal buka via Flowbite
            const triggerBtn = document.getElementById('open-drag-modal-btn');
            if (triggerBtn) {
                triggerBtn.click();
            }
        });
    });
});
