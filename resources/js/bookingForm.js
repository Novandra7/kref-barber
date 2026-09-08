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
    guests: [
        // {
        //     barber: 1,
        //     date: initialDate,
        //     time: "09:00",

        //     name: "novan",
        //     phone: "08123456789",
        //     notes: "Test notes",

        //     selectedHaircut: 'Regular Haircut',
        //     selectedChemical: null,
        //     selectedTreatments: [],
        // }
    ],
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

    // Menghitung tanggal-tanggal yang tersedia untuk barber tertentu saja
    // (bukan gabungan semua barber), karena tiap barber bisa punya hari kerja berbeda.
    getBarberAvailableDates(barberId) {
        if (!barberId) return [];

        return [...new Set(
            this.schedules
                .filter((schedule) => schedule.barber_id == barberId)
                .map((schedule) => schedule.date)
        )].sort();
    },

    // Menghitung config minDate/maxDate/datesDisabled datepicker berdasarkan
    // tanggal-tanggal yang tersedia untuk barber yang diberikan.
    buildDatepickerConfig(barberId) {
        const dates = this.getBarberAvailableDates(barberId);
        const disabledDates = [];

        if (dates.length > 0) {
            const firstDate = new Date(`${dates[0]}T00:00:00`);
            const lastDate = new Date(`${dates[dates.length - 1]}T00:00:00`);

            for (
                const date = new Date(firstDate);
                date <= lastDate;
                date.setDate(date.getDate() + 1)
            ) {
                const dateValue = this.toDateValue(date);

                if (!dates.includes(dateValue)) {
                    disabledDates.push(dateValue);
                }
            }
        }

        return {
            minDate: dates[0] ?? null,
            maxDate: dates[dates.length - 1] ?? null,
            datesDisabled: disabledDates,
            dates,
        };
    },

    // Tanggal default untuk barber tertentu: hari ini jika barber tersedia hari ini,
    // kalau tidak pakai tanggal paling awal yang tersedia untuk barber tersebut.
    getDefaultDateForBarber(dates) {
        if (!dates.length) return "";

        const today = this.toDateValue(new Date());

        return dates.includes(today) ? today : dates[0];
    },

    initDatepicker(element, inline = false) {
        const initialConfig = this.buildDatepickerConfig(this.currentGuest.barber);

        const datepicker = new Datepicker(element, {
            format: 'yyyy-mm-dd',
            minDate: initialConfig.minDate,
            maxDate: initialConfig.maxDate,
            datesDisabled: initialConfig.datesDisabled,
            autohide: !inline,
        });

        element.addEventListener('changeDate', (event) => {
            const date = event.detail.date;
            this.currentGuest.date = this.toDateValue(date);
            this.currentGuest.time = "";
        });

        if (this.currentGuest.date) {
            datepicker.setDate(this.currentGuest.date);
        }

        // Setiap kali barber berganti, min/max/disabled date dihitung ulang
        // khusus untuk barber tersebut (bukan gabungan semua barber).
        this.$watch('currentGuest.barber', (barberId) => {
            const config = this.buildDatepickerConfig(barberId);

            datepicker.setOptions({
                minDate: config.minDate,
                maxDate: config.maxDate,
                datesDisabled: config.datesDisabled,
            });

            const dateStillValid = this.currentGuest.date && config.dates.includes(this.currentGuest.date);

            // Kalau guest ini belum punya waktu tersimpan (belum bagian dari edit guest
            // yang sudah lengkap), berarti pemilihan barber ini masih "baru" -> langsung
            // arahkan ke hari ini (jika barber tersedia) atau tanggal paling awal barber
            // tersebut. Kalau sedang edit guest dan tanggal masih valid, biarkan tetap.
            if (!this.currentGuest.time || !dateStillValid) {
                this.currentGuest.time = "";
                this.currentGuest.date = this.getDefaultDateForBarber(config.dates);

                if (this.currentGuest.date) {
                    datepicker.setDate(this.currentGuest.date);
                } else {
                    datepicker.setDate();
                }
            }
        });
    },

    toDateValue(date) {
        return date instanceof Date
            ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`
            : date;
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
            // Tanggal dikosongkan dulu, nanti diisi otomatis (hari ini/tanggal paling
            // awal) begitu barber dipilih, karena jadwal tiap barber berbeda-beda.
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

    // Haircut By Rizal menggantikan Regular Haircut saat barber yang dipilih adalah owner
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

        // Jika sedang edit (ada editingIndex), update data di index tersebut. Jika baru, push ke array.
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
        // Fungsi opsional untuk mengedit guest dari list
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
                console.log("Payment data update received:", this.paymentData);

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
        // return format D/M/YYYY (cth: 2/9/2026)
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

        // Mendukung pencarian berdasarkan ID (number/string) atau Nama Barber
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

        // Haircut
        if (guest.selectedHaircut) {
            services.push(guest.selectedHaircut);
        }

        // Chemical
        if (guest.selectedChemical) {
            services.push(guest.selectedChemical);
        }

        // Treatments
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
