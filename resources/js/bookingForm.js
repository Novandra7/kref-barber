import Datepicker from 'flowbite-datepicker/Datepicker';
import QRCode from 'qrcode';

export default (
    initialServices = [],
    initialBarbers = [],
    initialDate = "",
    initialSchedules = [],
    initialAvailableDates = [],
) => ({
    currentStep: 1,
    guests: [],
    currentGuest: null,

    services: initialServices,
    barbers: initialBarbers,
    schedules: initialSchedules,
    availableDates: initialAvailableDates,

    paymentType: "",
    paymentTypeConfirmed: false,

    paymentState: 'idle',
    paymentData: null,
    paymentError: null,

    validationAttempted: false,
    isSubmittingBooking: false,

    bookingId: null,
    paymentChannel: null,

    // Helper mendapatkan string tanggal hari ini (YYYY-MM-DD) lokal
    getTodayString() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    },

    // 1. DIBENAHI: Mengecek apakah slot jam sudah lewat (khusus untuk tanggal hari ini)
    isTimePassed(slotTime) {
        if (!this.currentGuest?.date || !slotTime) return false;

        const todayStr = this.getTodayString();

        // Jika tanggal yang dipilih adalah esok/masa depan, jam TIDAK dianggap lewat
        if (this.currentGuest.date > todayStr) {
            return false;
        }

        // Jika tanggal yang dipilih adalah kemarin/lampau, semua jam dianggap lewat
        if (this.currentGuest.date < todayStr) {
            return true;
        }

        // Jika tanggal yang dipilih ADALAH HARI INI -> Cek jam & menit saat ini
        const now = new Date();
        const timeParts = slotTime.split(':');
        const hours = parseInt(timeParts[0], 10);
        const minutes = parseInt(timeParts[1], 10);

        const slotDate = new Date();
        slotDate.setHours(hours, minutes, 0, 0);

        return slotDate <= now;
    },

    // 2. DIBENAHI: Hanya mengambil tanggal yang >= HARI INI
    getBarberAvailableDates(barberId) {
        if (!barberId) return [];

        const todayStr = this.getTodayString();

        return [...new Set(
            this.schedules
                .filter((schedule) => schedule.barber_id == barberId && schedule.date >= todayStr)
                .map((schedule) => schedule.date)
        )].sort();
    },

    barberHasSchedule(barberId) {
        return this.getBarberAvailableDates(barberId).length > 0;
    },

    // 3. DIBENAHI: Konfigurasi datepicker mengunci penuh tanggal lampau
    buildDatepickerConfig(barberId) {
        const todayStr = this.getTodayString();
        const availableDates = this.getBarberAvailableDates(barberId);

        if (availableDates.length === 0) {
            return {
                minDate: todayStr,
                maxDate: todayStr,
                datesDisabled: [todayStr],
                dates: []
            };
        }

        const minDate = availableDates[0] >= todayStr ? availableDates[0] : todayStr;
        const maxDate = availableDates[availableDates.length - 1];

        // Matikan semua tanggal di antara minDate & maxDate yang tidak punya slot aktif
        const disabledDates = [];
        const startDate = new Date(`${minDate}T00:00:00`);
        const endDate = new Date(`${maxDate}T00:00:00`);

        for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
            const dateStr = this.toDateValue(d);
            if (!availableDates.includes(dateStr)) {
                disabledDates.push(dateStr);
            }
        }

        return {
            minDate,
            maxDate,
            datesDisabled: disabledDates,
            dates: availableDates,
        };
    },

    getDefaultDateForBarber(dates) {
        if (!dates.length) return "";
        const todayStr = this.getTodayString();
        return dates.includes(todayStr) ? todayStr : dates[0];
    },

    // 4. DIBENAHI: Flowbite Datepicker dengan penanganan status disabled yang ketat
    initDatepicker(element, inline = false) {
        const initialConfig = this.buildDatepickerConfig(this.currentGuest.barber);

        const makeBeforeShowDay = (availableDates) => (date) => {
            const dateStr = this.toDateValue(date);
            const todayStr = this.getTodayString();
            
            // Tanggal kemarin/lampau ATAU tanggal yang tidak ada di jadwal otomatis disabled
            const isAvailable = dateStr >= todayStr && availableDates.includes(dateStr);

            return {
                enabled: isAvailable,
                classes: isAvailable ? '' : 'disabled opacity-30 cursor-not-allowed pointer-events-none'
            };
        };

        const datepicker = new Datepicker(element, {
            format: 'yyyy-mm-dd',
            minDate: initialConfig.minDate,
            maxDate: initialConfig.maxDate,
            datesDisabled: initialConfig.datesDisabled,
            beforeShowDay: makeBeforeShowDay(initialConfig.dates),
            autohide: !inline,
        });

        element.addEventListener('changeDate', (event) => {
            const date = event.detail.date;
            const selectedStr = this.toDateValue(date);
            const config = this.buildDatepickerConfig(this.currentGuest.barber);

            // Validasi keras: cegah pengisian jika user memaksa pilih tanggal tidak valid
            if (!config.dates.includes(selectedStr) || selectedStr < this.getTodayString()) {
                this.currentGuest.date = "";
                this.currentGuest.time = "";
                return;
            }

            this.currentGuest.date = selectedStr;
            this.currentGuest.time = "";
        });

        if (this.currentGuest.date) {
            datepicker.setDate(this.currentGuest.date);
        }

        this.$watch('currentGuest.barber', (barberId) => {
            const config = this.buildDatepickerConfig(barberId);

            datepicker.setOptions({
                minDate: config.minDate,
                maxDate: config.maxDate,
                datesDisabled: config.datesDisabled,
                beforeShowDay: makeBeforeShowDay(config.dates),
            });

            const dateStillValid = this.currentGuest.date && config.dates.includes(this.currentGuest.date);

            if (!this.currentGuest.time || !dateStillValid) {
                this.currentGuest.time = "";
                this.currentGuest.date = this.getDefaultDateForBarber(config.dates);

                if (this.currentGuest.date) {
                    datepicker.setDate(this.currentGuest.date);
                } else {
                    datepicker.setDate({ clear: true });
                }
            }
        });
    },

    toDateValue(date) {
        if (!date) return "";
        if (typeof date === "string") return date.split("T")[0];

        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
    },

    getSelectedSchedules() {
        if (!this.currentGuest?.date || !this.currentGuest?.barber) return [];

        return this.schedules.filter((schedule) =>
            schedule.date === this.currentGuest.date &&
            schedule.barber_id == this.currentGuest.barber
        );
    },

    isScheduleTaken(schedule) {
        return this.guests.some((guest, index) =>
            index !== this.editingIndex &&
            guest.barber == schedule.barber_id &&
            guest.date === schedule.date &&
            guest.time === schedule.slot_time
        );
    },

    createGuest() {
        return {
            barber: null,
            date: "",
            time: "",
            name: "",
            phone: "",
            notes: "",
            selectedHaircut: null,
            selectedChemical: null,
            selectedTreatments: [],
        };
    },

    init() {
        this.startNewGuest();
        this.$watch('currentGuest.barber', () => this.syncHaircutWithBarber());
    },

    syncHaircutWithBarber() {
        if (!this.currentGuest?.selectedHaircut) return;

        const isOwner = this.selectedBarberObj()?.role?.toLowerCase() === "owner";

        if (isOwner && this.currentGuest.selectedHaircut === "Regular Haircut") {
            this.currentGuest.selectedHaircut = "Haircut By Rizal";
        } else if (!isOwner && this.currentGuest.selectedHaircut === "Haircut By Rizal") {
            this.currentGuest.selectedHaircut = "Regular Haircut";
        }
    },

    startNewGuest() {
        this.currentGuest = this.createGuest();
        this.validationAttempted = false;
    },

    saveGuest() {
        if (!this.currentGuest) return;

        if (this.editingIndex !== null && this.editingIndex !== undefined) {
            this.guests[this.editingIndex] = {
                ...this.currentGuest,
                selectedTreatments: [...this.currentGuest.selectedTreatments],
            };
            this.editingIndex = null;
        } else {
            this.guests.push({
                ...this.currentGuest,
                selectedTreatments: [...this.currentGuest.selectedTreatments],
            });
        }
    },

    addGuest() {
        this.currentGuest = this.createGuest();
        this.validationAttempted = false;
        this.currentStep = 1;
    },

    editGuest(index) {
        this.editingIndex = index;
        this.currentGuest = JSON.parse(JSON.stringify(this.guests[index]));
        this.currentStep = 1;
    },

    removeGuest(index) {
        this.guests.splice(index, 1);
    },

    finishGuests() {
        this.currentGuest = null;
        this.currentStep = 3;
    },

    async createPayment() {
        this.paymentError = "";
        this.paymentState = "loading";

        try {
            const response = await fetch("/booking/checkout", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content"),
                },
                body: JSON.stringify({
                    payment_type: this.paymentType,
                    guests: this.guests,
                }),
            });

            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || "Unable to create payment.");
            }

            this.paymentData = payload;
            this.paymentTypeConfirmed = true;
            await this.$nextTick();
            if (payload.qrContent) {
                await QRCode.toCanvas(this.$refs.qrisCanvas, payload.qrContent, {
                    width: 220,
                    margin: 2,
                });
            }
            this.paymentState = "ready";
            this.subscribeToPaymentUpdates();
        } catch (error) {
            this.paymentState = "error";
            this.paymentError = error.message;
        }
    },

    subscribeToPaymentUpdates() {
        if (!this.paymentData?.reference || !window.Echo || this.paymentChannel) return;

        const channelName = `payment.${this.paymentData.reference}`;
        this.paymentChannel = window.Echo
            .channel(channelName)
            .listen(".payment.updated", (payload) => {
                this.paymentData = {
                    ...this.paymentData,
                    status: payload.status,
                    doku_data: payload.doku_data,
                };

                if (payload.status === "paid") {
                    this.paymentState = "paid";
                    this.paymentError = null;
                    this.leavePaymentChannel();
                } else if (["failed", "expired", "cancelled"].includes(payload.status)) {
                    this.paymentState = "error";
                    this.paymentError = `Payment ${payload.status}.`;
                    this.leavePaymentChannel();
                }
            });
    },

    leavePaymentChannel() {
        if (!this.paymentChannel || !window.Echo || !this.paymentData?.reference) return;

        window.Echo.leave(`payment.${this.paymentData.reference}`);
        this.paymentChannel = null;
    },

    getGuestSelectedServices(guest) {
        if (!guest) return [];

        let selected = [];

        if (guest.selectedHaircut) {
            const haircut = this.services.find(
                (service) => service.name === guest.selectedHaircut,
            );
            if (haircut) selected.push({ ...haircut });
        }

        if (guest.selectedChemical) {
            const chemical = this.services.find(
                (service) => service.name === guest.selectedChemical,
            );
            if (chemical) selected.push({ ...chemical });
        }

        if (Array.isArray(guest.selectedTreatments)) {
            guest.selectedTreatments.forEach((name) => {
                const treatment = this.services.find(
                    (service) => service.name === name,
                );
                if (treatment) selected.push({ ...treatment });
            });
        }
        return selected;
    },

    selectedServices() {
        return this.getGuestSelectedServices(this.currentGuest);
    },

    formatDate(date) {
        if (!date) return "-";

        return new Intl.DateTimeFormat("id-ID", {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric",
        }).format(new Date(date));
    },

    formatDateShort(dateString) {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        return `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear()}`;
    },

    formatPrice(price) {
        return new Intl.NumberFormat("id-ID").format(price);
    },

    formatPriceK(price) {
        if (!price) return "0K";
        return `${Math.round(price / 1000)}K`;
    },

    selectedBarberObj() {
        if (!this.currentGuest?.barber) return null;

        return (
            this.barbers.find(
                (barber) =>
                    barber.id == this.currentGuest.barber ||
                    barber.name === this.currentGuest.barber,
            ) || { name: this.currentGuest.barber }
        );
    },

    validateStep1() {
        if (
            !this.currentGuest?.barber ||
            !this.currentGuest?.date ||
            !this.currentGuest?.time
        ) {
            this.validationAttempted = true;
            return;
        }

        this.validationAttempted = false;
        this.currentStep = 2;
    },

    validateStep2() {
        if (
            !this.currentGuest?.name ||
            !this.currentGuest?.phone ||
            (!this.currentGuest?.selectedHaircut &&
                !this.currentGuest?.selectedChemical &&
                this.currentGuest?.selectedTreatments.length === 0)
        ) {
            this.validationAttempted = true;
            return;
        }

        this.validationAttempted = false;
        this.saveGuest();
        this.currentStep = "guest";
    },

    getTotalPrice() {
        if (!Array.isArray(this.guests)) return 0;

        return this.guests.reduce((total, guest) => {
            const guestServices = this.getGuestSelectedServices(guest);
            const guestTotal = guestServices.reduce((sum, service) => sum + (service.price || 0), 0);
            return total + guestTotal;
        }, 0);
    },

    getGuestServicesText(guest) {
        if (!guest) return "No service selected";

        let services = [];

        if (guest.selectedHaircut) {
            services.push(guest.selectedHaircut);
        }

        if (guest.selectedChemical) {
            services.push(guest.selectedChemical);
        }

        if (
            Array.isArray(guest.selectedTreatments) &&
            guest.selectedTreatments.length > 0
        ) {
            services.push(...guest.selectedTreatments);
        }

        return services.length > 0
            ? services.join(", ")
            : "No service selected";
    },
});