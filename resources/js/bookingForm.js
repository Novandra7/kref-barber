export default (
    initialServices = [],
    initialBarbers = [],
    initialDate = "",
) => ({
    currentStep: 1,
    guests: [
    //     {
    //     barber: 1,
    //     date: initialDate,
    //     time: "09.00",

    //     name: "novan",
    //     phone: "08123456789",
    //     notes: "Test notes",

    //     selectedHaircut: 'Regular Haircut',
    //     selectedChemical: null,
    //     selectedTreatments: [],
    // }
],
    currentGuest: null,
    paymentType: "",
    services: initialServices,
    barbers: initialBarbers, 
    validationAttempted: false,

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
