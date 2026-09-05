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

    initDatepicker(element, inline = false) {
        const availableDates = this.availableDates.map((date) => new Date(`${date}T00:00:00`));
        const disabledDates = [];

        if (availableDates.length > 0) {
            const firstDate = availableDates[0];
            const lastDate = availableDates[availableDates.length - 1];

            for (
                const date = new Date(firstDate);
                date <= lastDate;
                date.setDate(date.getDate() + 1)
            ) {
                const dateValue = this.toDateValue(date);

                if (!this.availableDates.includes(dateValue)) {
                    disabledDates.push(dateValue);
                }
            }
        }

        const datepicker = new Datepicker(element, {
            format: 'yyyy-mm-dd',
            minDate: this.availableDates[0] ?? null,
            maxDate: this.availableDates[this.availableDates.length - 1] ?? null,
            datesDisabled: disabledDates,
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
            date: initialDate,
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
            console.log("Payment data:", this.paymentData);
            this.paymentTypeConfirmed = true;
            await this.$nextTick();
            if (payload.qrContent) {
                await QRCode.toCanvas(this.$refs.qrisCanvas, payload.qrContent, {
                    width: 220,
                    margin: 2,
                });
            }
            this.paymentState = "ready";
            this.startPaymentPolling();
        } catch (error) {
            this.paymentState = "error";
            this.paymentError = error.message;
        }
    },

    startPaymentPolling() {
        if (!this.paymentData?.reference || this.paymentPolling) return;

        this.paymentPolling = window.setInterval(async () => {
            try {
                const response = await fetch(
                    `/booking/payment/${encodeURIComponent(this.paymentData.reference)}/status`,
                    { headers: { Accept: "application/json" } },
                );
                if (!response.ok) return;

                const payload = await response.json();
                this.paymentData.status = payload.status;

                if (payload.status === "paid") {
                    window.clearInterval(this.paymentPolling);
                    this.paymentPolling = null;
                }
            } catch (error) {
                console.error("Unable to refresh payment status.", error);
            }
        }, 10000);
    },


    isOwnerSelected() {
        const barber = this.selectedBarberObj();
        return barber && barber.role && barber.role.toLowerCase() === "owner";
    },

    getGuestSelectedServices(guest) {
        if (!guest) return [];

        let selected = [];
        const barber =
            this.barbers.find(
                (barber) => barber.id == guest.barber || barber.name === guest.barber,
            ) || {};
        const isOwner =
            barber.role && barber.role.toLowerCase() === "owner";

        if (guest.selectedHaircut) {
            const haircut = this.services.find(
                (service) => service.name === guest.selectedHaircut,
            );

            if (haircut) {
                const isRegularCut = haircut.name.toLowerCase().includes("regular");
                const extraFee = isOwner && isRegularCut ? 10000 : 0;

                selected.push({
                    ...haircut,
                    name:
                        isOwner && isRegularCut
                            ? `${haircut.name} - By Owner`
                            : haircut.name,
                    price: haircut.price + extraFee,
                });
            }
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
            const isOwner =
                guest.barber &&
                this.barbers
                    .find((b) => b.id === guest.barber)
                    ?.role?.toLowerCase() === "owner";
            const isRegular = guest.selectedHaircut
                .toLowerCase()
                .includes("regular");

            services.push(
                isOwner && isRegular
                    ? `${guest.selectedHaircut} - By Owner`
                    : guest.selectedHaircut,
            );
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
