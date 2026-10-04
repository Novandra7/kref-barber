function walkInApp(availableSchedules = []) {
    return {
        showModal: false,
        activeBooking: null,
        selectedServices: [],
        paymentType: 'full',
        paymentMethod: 'cash',
        bookingType: 'now',
        selectedBarberId: '',
        allSchedules: availableSchedules,

        get availableSlots() {
            if (!this.selectedBarberId) return [];
            return this.allSchedules.filter(s => s.barber_id == this.selectedBarberId);
        },

        get total() {
            return this.selectedServices.reduce((sum, s) => sum + s.price, 0);
        },

        openComplete(booking) {
            this.activeBooking = booking;
            this.selectedServices = [];
            this.paymentType = 'full';
            this.paymentMethod = 'cash';
            this.showModal = true;
        },

        toggleService(id, price, name) {
            const idx = this.selectedServices.findIndex(s => s.id === id);
            if (idx >= 0) {
                this.selectedServices.splice(idx, 1);
            } else {
                this.selectedServices.push({id, price, name});
            }
        },

        isSelected(id) {
            return this.selectedServices.some(s => s.id === id);
        }
    }
}
