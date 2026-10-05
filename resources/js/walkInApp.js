export default (availableSchedules = []) => ({
    showModal: false,
    activeBooking: null,
    selectedServices: [],
    paymentType: 'full',
    paymentMethod: 'cash',
    bookingType: 'now',
    selectedBarberId: '',
    allSchedules: availableSchedules,

    // Scheduled payment form state
    scheduledPaymentType: 'dp',
    scheduledDpAmount: 40000,
    scheduledPaymentMethod: 'cash',

    // Modal Lengkapi: DP settlement state
    hasPrepaidDp: false,
    prepaidDpAmount: 0,
    settlementPaymentMethod: 'cash',

    get availableSlots() {
        if (!this.selectedBarberId) return [];
        return this.allSchedules.filter(s => s.barber_id == this.selectedBarberId);
    },

    get total() {
        return this.selectedServices.reduce((sum, s) => sum + s.price, 0);
    },

    get remainingAmount() {
        return Math.max(0, this.total - this.prepaidDpAmount);
    },

    openComplete(booking) {
        this.activeBooking = booking;
        this.selectedServices = [];
        this.paymentType = 'full';
        this.paymentMethod = 'cash';

        // Detect if booking has prepaid DP
        this.hasPrepaidDp = Boolean(booking.payment && booking.payment.purpose === 'dp');
        this.prepaidDpAmount = this.hasPrepaidDp ? Number(booking.payment.amount) : 0;
        this.settlementPaymentMethod = 'cash';

        this.showModal = true;
    },

    toggleService(id, price, name) {
        const idx = this.selectedServices.findIndex(s => s.id === id);
        if (idx >= 0) {
            this.selectedServices.splice(idx, 1);
        } else {
            this.selectedServices.push({ id, price, name });
        }
    },

    isSelected(id) {
        return this.selectedServices.some(s => s.id === id);
    },

    formatRupiah(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
    }
});
