export default (
    availableSchedules = [],
    initialBarber = '',
    initialSource = '',
    initialFormState = {}
) => ({
    showModal: false,
    activeBooking: null,
    selectedServices: [],
    paymentType: 'full',
    paymentMethod: 'cash',
    bookingType: initialFormState.bookingType || 'now',
    isChemical: Boolean(initialFormState.isChemical),
    selectedBarberId: String(initialFormState.selectedBarberId || ''),
    selectedScheduledTime: String(initialFormState.selectedScheduledTime || ''),
    customerName: String(initialFormState.customerName || ''),
    customerPhone: String(initialFormState.customerPhone || ''),
    allSchedules: availableSchedules,

    // Filter state
    showFilter: false,
    filterBarber: String(initialBarber || ''),
    filterSource: String(initialSource || ''),

    get hasActiveFilter() {
        return Boolean(this.filterBarber || this.filterSource);
    },

    // Scheduled payment form state
    scheduledPaymentType: initialFormState.scheduledPaymentType || 'dp',
    scheduledDpAmount: Number(initialFormState.scheduledDpAmount) || 40000,
    scheduledPaymentMethod: initialFormState.scheduledPaymentMethod || 'cash',

    init() {
        if (this.selectedScheduledTime) {
            const preservedTime = this.selectedScheduledTime;
            this.$nextTick(() => {
                this.selectedScheduledTime = preservedTime;
            });
        }
        this.$watch('selectedBarberId', (newVal, oldVal) => {
            if (oldVal && newVal !== oldVal) {
                this.selectedScheduledTime = '';
            }
        });
    },

    // Modal Lengkapi: DP & Prepaid settlement state
    hasPrepaidDp: false,
    prepaidDpAmount: 0,
    isPrepaidFull: false,
    prepaidAmount: 0,
    tipAmount: 0,
    settlementPaymentMethod: 'cash',
    barberNotes: '',

    // Custom prices for flexible services (e.g. Long Trim start from 80k)
    customPrices: {},

    get availableSlots() {
        if (!this.selectedBarberId) return [];
        const slots = this.allSchedules.filter(s => s.barber_id == this.selectedBarberId);
        if (this.selectedScheduledTime && !slots.some(s => s.time === this.selectedScheduledTime)) {
            return [{ id: 'preserved', barber_id: this.selectedBarberId, time: this.selectedScheduledTime }, ...slots];
        }
        return slots;
    },

    get total() {
        return this.selectedServices.reduce((sum, s) => {
            const custom = Number(this.customPrices[s.id]);
            const effectivePrice = (!isNaN(custom) && custom > 0) ? custom : s.price;
            return sum + effectivePrice;
        }, 0);
    },

    get remainingAmount() {
        return Math.max(0, (this.total + this.tipAmount) - this.prepaidAmount);
    },

    openComplete(booking) {
        this.activeBooking = booking;
        this.barberNotes = booking.barber_notes || '';
        this.tipAmount = Number(booking.tip_amount || 0);

        const payment = booking.payment;
        this.hasPrepaidDp = Boolean(
            booking.payment_type === 'dp' || 
            (payment && payment.purpose === 'dp')
        );

        this.isPrepaidFull = Boolean(
            !this.hasPrepaidDp &&
            payment &&
            payment.status === 'paid' &&
            (payment.purpose === 'full_payment' || payment.purpose === 'walk_in')
        );

        if (this.hasPrepaidDp) {
            this.prepaidDpAmount = payment ? Number(payment.amount) : 0;
            this.prepaidAmount = this.prepaidDpAmount;
        } else if (this.isPrepaidFull) {
            this.prepaidAmount = payment ? Number(payment.amount) : Number(booking.total_amount || 0);
            this.prepaidDpAmount = 0;
        } else {
            this.prepaidDpAmount = 0;
            this.prepaidAmount = 0;
        }

        // Pre-populate services from booking.items if available
        this.selectedServices = [];
        this.customPrices = {};
        if (booking.items && Array.isArray(booking.items) && booking.items.length > 0) {
            booking.items.forEach(item => {
                if (item.service_id) {
                    const price = Number(item.price_snapshot || (item.service ? item.service.price : 0));
                    this.selectedServices.push({
                        id: item.service_id,
                        price: price,
                        name: item.service_name_snapshot || (item.service ? item.service.name : '')
                    });
                    if (item.price_snapshot) {
                        this.customPrices[item.service_id] = price;
                    }
                }
            });
        }

        this.paymentType = 'full';
        this.paymentMethod = 'cash';
        this.settlementPaymentMethod = 'cash';

        this.showModal = true;
    },

    setCustomPrice(serviceId, amount) {
        const parsed = Number(amount);
        if (!isNaN(parsed) && parsed > 0) {
            this.customPrices[serviceId] = parsed;
        }
    },

    getCustomPrice(serviceId, defaultPrice) {
        return this.customPrices[serviceId] || defaultPrice;
    },

    toggleService(id, price, name) {
        const idx = this.selectedServices.findIndex(s => s.id === id);
        if (idx >= 0) {
            this.selectedServices.splice(idx, 1);
        } else {
            const isOwner = this.activeBooking?.barber && String(this.activeBooking.barber.role).toLowerCase() === 'owner';
            const isOwnerRegular = isOwner && name.toLowerCase().includes('regular haircut');
            const finalPrice = isOwnerRegular ? price + 10000 : price;
            this.selectedServices.push({ id, price: finalPrice, name });
        }
    },

    isSelected(id) {
        return this.selectedServices.some(s => s.id === id);
    },

    formatRupiah(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
    }
});
