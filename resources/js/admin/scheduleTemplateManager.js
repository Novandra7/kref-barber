/**
 * Alpine.js component for Schedule Template Manager
 * Supports 7 separate days (Senin s/d Minggu) for Rizal & Sogi with Multi-Select and Shift + Click.
 */
export default () => {
    const daysList = [
        { offset: 0, label: 'Senin', short: 'Sen' },
        { offset: 1, label: 'Selasa', short: 'Sel' },
        { offset: 2, label: 'Rabu', short: 'Rab' },
        { offset: 3, label: 'Kamis', short: 'Kam' },
        { offset: 4, label: 'Jumat', short: 'Jum' },
        { offset: 5, label: 'Sabtu', short: 'Sab' },
        { offset: 6, label: 'Minggu', short: 'Min' }
    ];

    const defaultTemplates = {
        1: {
            name: 'Rizal',
            role: 'Owner',
            active: true,
            days: {
                0: ['14:00', '14:50', '15:40', '16:30', '18:10', '19:00', '19:50', '20:40', '21:30'],
                1: ['14:00', '14:50', '15:40', '16:30', '18:10', '19:00', '19:50', '20:40', '21:30'],
                2: ['14:00', '14:50', '15:40', '16:30', '18:10', '19:00', '19:50', '20:40', '21:30'],
                3: ['14:00', '14:50', '15:40', '16:30', '18:10', '19:00', '19:50', '20:40', '21:30'],
                4: ['10:00', '10:50', '13:20', '14:00', '14:50', '15:40', '16:30', '17:20', '18:10', '19:00'],
                5: ['10:00', '10:50', '11:40', '12:30', '13:20', '14:10', '15:00', '15:50', '16:40', '17:30'],
                6: ['10:00', '10:50', '11:40', '12:30', '13:20', '14:10', '15:00', '15:50', '16:40', '17:30']
            }
        },
        2: {
            name: 'Sogi',
            role: 'Senior',
            active: true,
            days: {
                0: ['10:00', '10:40', '11:20', '12:00', '12:40', '13:20', '14:00', '14:40', '16:20', '17:00', '17:40'],
                1: ['10:00', '10:40', '11:20', '12:00', '12:40', '13:20', '14:00', '14:40', '16:20', '17:00', '17:40'],
                2: ['10:00', '10:40', '11:20', '12:00', '12:40', '13:20', '14:00', '14:40', '16:20', '17:00', '17:40'],
                3: ['10:00', '10:40', '11:20', '12:00', '12:40', '13:20', '14:00', '14:40', '16:20', '17:00', '17:40'],
                4: ['14:00', '14:40', '15:20', '17:00', '17:40', '18:20', '19:00', '19:40', '20:20', '21:00', '21:40'],
                5: ['14:00', '14:40', '15:20', '17:00', '17:40', '18:20', '19:00', '19:40', '20:20', '21:00', '21:40'],
                6: ['14:00', '14:40', '15:20', '17:00', '17:40', '18:20', '19:00', '19:40', '20:20', '21:00', '21:40']
            }
        }
    };

    return {
        currentBarberId: 1,
        daysList: daysList,
        templates: JSON.parse(JSON.stringify(defaultTemplates)),
        selectedSlots: [],
        lastClicked: null,
        newTimeInputs: {},
        cleanUnbooked: true,

        slotKey(barberId, dayOffset, time) {
            return `${barberId}_${dayOffset}_${time}`;
        },

        isSelected(barberId, dayOffset, time) {
            return this.selectedSlots.includes(this.slotKey(barberId, dayOffset, time));
        },

        handleSlotClick(barberId, dayOffset, time, event) {
            const key = this.slotKey(barberId, dayOffset, time);

            if (event.shiftKey && this.lastClicked) {
                // Scope Shift + Click pada barber dan hari yang sama persis
                if (this.lastClicked.barberId === barberId && this.lastClicked.dayOffset === dayOffset) {
                    const times = this.templates[barberId].days[dayOffset] || [];
                    const prevIdx = times.indexOf(this.lastClicked.time);
                    const currentIdx = times.indexOf(time);

                    if (prevIdx !== -1 && currentIdx !== -1) {
                        const start = Math.min(prevIdx, currentIdx);
                        const end = Math.max(prevIdx, currentIdx);
                        const rangeTimes = times.slice(start, end + 1);

                        rangeTimes.forEach(t => {
                            const rKey = this.slotKey(barberId, dayOffset, t);
                            if (!this.selectedSlots.includes(rKey)) {
                                this.selectedSlots.push(rKey);
                            }
                        });

                        this.lastClicked = { barberId, dayOffset, time };
                        return;
                    }
                }
            }

            const idx = this.selectedSlots.indexOf(key);
            if (idx !== -1) {
                this.selectedSlots.splice(idx, 1);
            } else {
                this.selectedSlots.push(key);
                this.lastClicked = { barberId, dayOffset, time };
            }
        },

        selectedCountCurrentBarber() {
            const prefix = `${this.currentBarberId}_`;
            return this.selectedSlots.filter(k => k.startsWith(prefix)).length;
        },

        selectAllCurrentBarber() {
            const barber = this.templates[this.currentBarberId];
            if (!barber) return;
            for (let offset = 0; offset <= 6; offset++) {
                const times = barber.days[offset] || [];
                times.forEach(t => {
                    const key = this.slotKey(this.currentBarberId, offset, t);
                    if (!this.selectedSlots.includes(key)) {
                        this.selectedSlots.push(key);
                    }
                });
            }
        },

        deselectAllCurrentBarber() {
            const prefix = `${this.currentBarberId}_`;
            this.selectedSlots = this.selectedSlots.filter(k => !k.startsWith(prefix));
            if (this.lastClicked && this.lastClicked.barberId === this.currentBarberId) {
                this.lastClicked = null;
            }
        },

        deleteSelectedCurrentBarber() {
            const barber = this.templates[this.currentBarberId];
            if (!barber) return;

            for (let offset = 0; offset <= 6; offset++) {
                const times = barber.days[offset] || [];
                barber.days[offset] = times.filter(t => {
                    const key = this.slotKey(this.currentBarberId, offset, t);
                    return !this.selectedSlots.includes(key);
                });
            }

            this.deselectAllCurrentBarber();
        },

        selectAllInDay(barberId, dayOffset) {
            const times = this.templates[barberId].days[dayOffset] || [];
            times.forEach(time => {
                const key = this.slotKey(barberId, dayOffset, time);
                if (!this.selectedSlots.includes(key)) {
                    this.selectedSlots.push(key);
                }
            });
        },

        deleteSingleSlot(barberId, dayOffset, time) {
            const key = this.slotKey(barberId, dayOffset, time);
            const times = this.templates[barberId].days[dayOffset] || [];
            const idx = times.indexOf(time);
            if (idx !== -1) {
                times.splice(idx, 1);
            }

            const selIdx = this.selectedSlots.indexOf(key);
            if (selIdx !== -1) {
                this.selectedSlots.splice(selIdx, 1);
            }

            if (this.lastClicked && this.lastClicked.barberId === barberId && this.lastClicked.dayOffset === dayOffset && this.lastClicked.time === time) {
                this.lastClicked = null;
            }
        },

        addSlot(barberId, dayOffset) {
            const inputKey = `${barberId}_${dayOffset}`;
            const val = (this.newTimeInputs[inputKey] || '').trim();
            if (!val) return;

            const times = this.templates[barberId].days[dayOffset] || [];
            if (!times.includes(val)) {
                times.push(val);
                times.sort();
            }
            this.newTimeInputs[inputKey] = '';
        },

        resetDefault() {
            if (confirm('Kembalikan template jadwal Rizal & Sogi ke jadwal bawaan?')) {
                this.templates = JSON.parse(JSON.stringify(defaultTemplates));
                this.selectedSlots = [];
                this.lastClicked = null;
            }
        },

        totalSlotsCount() {
            let total = 0;
            for (const bId of [1, 2]) {
                const b = this.templates[bId];
                if (b && b.active && b.days) {
                    for (let offset = 0; offset <= 6; offset++) {
                        total += (b.days[offset] || []).length;
                    }
                }
            }
            return total;
        }
    };
};
