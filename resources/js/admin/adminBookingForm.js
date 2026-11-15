export default (initialState = {}) => ({
    allSchedules: Array.isArray(initialState.schedules) ? initialState.schedules : [],
    timelineSchedules: Array.isArray(initialState.timelineSchedules) ? initialState.timelineSchedules : [],
    slotRules: Array.isArray(initialState.slotRules) ? initialState.slotRules : [],
    servicesMeta: Array.isArray(initialState.servicesMeta) ? initialState.servicesMeta : [],
    initialSelectedTime: initialState.initialSelectedTime || '',
    timeRuleState: {
        ruleLabel: '',
        requiredSlots: 1,
        extraSlots: 0,
        needsTimeSelection: false,
        hasConflict: false,
        lockedTimesLabel: '',
        message: '',
    },

    resetTimeRuleState() {
        this.timeRuleState = {
            ruleLabel: '',
            requiredSlots: 1,
            extraSlots: 0,
            needsTimeSelection: false,
            hasConflict: false,
            lockedTimesLabel: '',
            message: '',
        };
    },

    ruleMatchesServices(rule, selectedServices) {
        const items = Array.isArray(rule.items) ? rule.items : [];
        if (items.length === 0) return false;

        const sortedItems = [...items].sort((a, b) => (b.service_id ? 1 : 0) - (a.service_id ? 1 : 0));
        const remaining = [...selectedServices];

        for (const item of sortedItems) {
            const matchIdx = remaining.findIndex((svc) => {
                if (item.service_id !== null && item.service_id !== undefined) {
                    return String(svc.id) === String(item.service_id);
                }
                if (item.category) {
                    return String(svc.category || '').toLowerCase() === String(item.category).toLowerCase();
                }
                return false;
            });

            if (matchIdx === -1) return false;
            remaining.splice(matchIdx, 1);
        }

        return true;
    },

    evaluateTimeRules() {
        const form = this.$el;
        if (!form) return;

        const barberSelect = document.getElementById('barber_id');
        const dateInput = document.getElementById('date');
        const radioCustom = document.getElementById('radio-time-custom');
        const timeSelect = document.getElementById('time_select');
        const timePicker = document.getElementById('time_picker');
        const statusSelect = document.getElementById('status');

        const status = statusSelect ? statusSelect.value : 'confirmed';
        if (status === 'cancelled') {
            this.resetTimeRuleState();
            return;
        }

        const checkedIds = Array.from(form.querySelectorAll('input[name="service_ids[]"]:checked')).map((cb) => String(cb.value));
        const selectedServices = checkedIds
            .map((id) => this.servicesMeta.find((s) => String(s.id) === id))
            .filter(Boolean);

        if (selectedServices.length === 0 || this.slotRules.length === 0) {
            this.resetTimeRuleState();
            return;
        }

        const barberId = barberSelect ? barberSelect.value : '';
        const matchedRules = this.slotRules.filter((rule) => {
            if (rule.barber_id !== null && rule.barber_id !== undefined) {
                if (!barberId || String(rule.barber_id) !== String(barberId)) {
                    return false;
                }
            }
            return this.ruleMatchesServices(rule, selectedServices);
        });

        if (matchedRules.length === 0) {
            this.resetTimeRuleState();
            return;
        }

        const requiredSlots = Math.max(
            1,
            matchedRules.reduce((sum, rule) => sum + Number(rule.slots_required || 1), 0)
        );
        const extraSlots = Math.max(0, requiredSlots - 1);

        if (extraSlots <= 0) {
            this.resetTimeRuleState();
            return;
        }

        const ruleLabel = matchedRules.map((r) => r.name).join(' + ');
        const date = dateInput ? dateInput.value : '';
        const selectedTime = radioCustom && radioCustom.checked
            ? (timePicker?.value || '')
            : (timeSelect && !timeSelect.disabled ? (timeSelect.value || '') : '');

        if (!barberId || !date || !selectedTime) {
            this.timeRuleState = {
                ruleLabel,
                requiredSlots,
                extraSlots,
                needsTimeSelection: true,
                hasConflict: false,
                lockedTimesLabel: '',
                message: `Kombinasi layanan (${ruleLabel}) membutuhkan ${requiredSlots} slot berurutan. Pilih barber, tanggal, dan jam untuk memeriksa ketersediaan slot lanjutan.`,
            };
            return;
        }

        const normalizedTime = selectedTime.substring(0, 5);
        const candidates = this.timelineSchedules
            .filter((s) => String(s.barber_id) === String(barberId) && s.date === date && s.slot_time > normalizedTime)
            .sort((a, b) => a.slot_time.localeCompare(b.slot_time))
            .slice(0, extraSlots);

        if (candidates.length < extraSlots) {
            this.timeRuleState = {
                ruleLabel,
                requiredSlots,
                extraSlots,
                needsTimeSelection: false,
                hasConflict: true,
                lockedTimesLabel: '',
                message: `Kombinasi layanan (${ruleLabel}) membutuhkan ${requiredSlots} slot berurutan mulai ${normalizedTime} WITA (1 slot utama + ${extraSlots} slot lanjutan), tetapi slot jadwal lanjutan pada ${date} tidak mencukupi (hanya tersedia ${candidates.length} dari ${extraSlots} slot lanjutan). Silakan pilih jam lebih awal atau buat slot jadwal terlebih dahulu.`,
            };
            return;
        }

        const sequenceTimes = [normalizedTime, ...candidates.map((s) => s.slot_time.substring(0, 5))];
        const occupiedTimes = candidates
            .filter((s) => !s.is_available && !s.is_self_slot)
            .map((s) => s.slot_time.substring(0, 5));

        if (occupiedTimes.length > 0) {
            this.timeRuleState = {
                ruleLabel,
                requiredSlots,
                extraSlots,
                needsTimeSelection: false,
                hasConflict: true,
                lockedTimesLabel: sequenceTimes.join(', '),
                message: `Kombinasi layanan (${ruleLabel}) membutuhkan ${requiredSlots} slot berurutan (${sequenceTimes.join(', ')} WITA), namun slot lanjutan (${occupiedTimes.join(', ')} WITA) sudah terisi atau tidak tersedia. Silakan pilih jam lain atau ubah layanan.`,
            };
            return;
        }

        const lockedLabel = sequenceTimes.join(', ');
        this.timeRuleState = {
            ruleLabel,
            requiredSlots,
            extraSlots,
            needsTimeSelection: false,
            hasConflict: false,
            lockedTimesLabel: lockedLabel,
            message: `Membutuhkan ${requiredSlots} slot jadwal berurutan. Slot ${lockedLabel} WITA tersedia dan akan otomatis terkunci.`,
        };
    },

    init() {
        const form = this.$el;
        if (!form) return;

        // 1. Service Selection Validation
        const checkboxes = form.querySelectorAll('input[name="service_ids[]"]');
        const errorAlert = document.getElementById('service-selection-error');

        const hasSelectedService = () => Array.from(checkboxes).some((cb) => cb.checked);

        checkboxes.forEach((cb) => {
            cb.addEventListener('change', () => {
                if (hasSelectedService() && errorAlert) {
                    errorAlert.classList.add('hidden');
                }
                this.evaluateTimeRules();
            });
        });

        form.addEventListener('submit', (e) => {
            if (!hasSelectedService()) {
                e.preventDefault();
                if (errorAlert) {
                    errorAlert.classList.remove('hidden');
                }
                const container = document.getElementById('services-container');
                if (container) {
                    container.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });
                }
                if (checkboxes[0]) {
                    checkboxes[0].focus();
                }
                return;
            }

            this.evaluateTimeRules();
            if (this.timeRuleState.hasConflict) {
                e.preventDefault();
                const feedback = document.getElementById('time-rule-feedback');
                if (feedback) {
                    feedback.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                    });
                }
            }
        });

        // 2. Schedule Time Toggle & Filter Logic
        const radioExisting = document.getElementById('radio-time-existing');
        const radioCustom = document.getElementById('radio-time-custom');
        const timeSelect = document.getElementById('time_select');
        const timePicker = document.getElementById('time_picker');
        const barberSelect = document.getElementById('barber_id');
        const dateInput = document.getElementById('date');
        const statusSelect = document.getElementById('status');

        const activeBtnClass =
            'inline-flex cursor-pointer items-center whitespace-nowrap rounded-md px-2.5 py-1 text-xs font-semibold transition-all bg-white text-brand shadow-xs';
        const inactiveBtnClass =
            'inline-flex cursor-pointer items-center whitespace-nowrap rounded-md px-2.5 py-1 text-xs font-semibold transition-all text-gray-600 hover:text-gray-900';

        const setTimeMode = (mode) => {
            const btnExisting = document.getElementById('btn-time-existing');
            const btnCustom = document.getElementById('btn-time-custom');
            const wrapperExisting = document.getElementById('wrapper-time-existing');
            const wrapperCustom = document.getElementById('wrapper-time-custom');

            if (mode === 'existing') {
                if (radioExisting) radioExisting.checked = true;
                if (btnExisting) btnExisting.className = activeBtnClass;
                if (btnCustom) btnCustom.className = inactiveBtnClass;
                if (wrapperExisting) wrapperExisting.classList.remove('hidden');
                if (wrapperCustom) wrapperCustom.classList.add('hidden');

                if (timeSelect) {
                    const isEmpty = timeSelect.dataset.isEmpty === 'true';
                    timeSelect.disabled = isEmpty;
                    timeSelect.required = !isEmpty;
                    if (isEmpty) {
                        timeSelect.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
                        timeSelect.classList.remove('bg-gray-50', 'text-gray-900');
                    } else {
                        timeSelect.classList.remove('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
                        timeSelect.classList.add('bg-gray-50', 'text-gray-900');
                    }
                }
                if (timePicker) {
                    timePicker.disabled = true;
                    timePicker.required = false;
                }
            } else {
                if (radioCustom) radioCustom.checked = true;
                if (btnCustom) btnCustom.className = activeBtnClass;
                if (btnExisting) btnExisting.className = inactiveBtnClass;
                if (wrapperCustom) wrapperCustom.classList.remove('hidden');
                if (wrapperExisting) wrapperExisting.classList.add('hidden');

                if (timePicker) {
                    timePicker.disabled = false;
                    timePicker.required = true;
                }
                if (timeSelect) {
                    timeSelect.disabled = true;
                    timeSelect.required = false;
                }
            }

            this.evaluateTimeRules();
        };

        const populateSchedules = () => {
            if (!timeSelect) {
                this.evaluateTimeRules();
                return { matchingCount: 0, hasMatchedCurrent: false };
            }

            const selectedBarberId = barberSelect ? barberSelect.value : '';
            const selectedDate = dateInput ? dateInput.value : '';

            const matching = this.allSchedules.filter(
                (s) => String(s.barber_id) === String(selectedBarberId) && s.date === selectedDate
            );

            const currentVal = timeSelect.value || this.initialSelectedTime;
            let hasMatchedCurrent = false;

            if (matching.length === 0) {
                timeSelect.dataset.isEmpty = 'true';
                timeSelect.innerHTML = '<option value="" disabled selected>Slot masih kosong</option>';
                timeSelect.disabled = true;
                timeSelect.required = false;
                timeSelect.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
                timeSelect.classList.remove('bg-gray-50', 'text-gray-900');
            } else {
                timeSelect.dataset.isEmpty = 'false';
                timeSelect.innerHTML = '<option value="">-- Pilih Slot Jadwal --</option>';

                matching.forEach((s) => {
                    const opt = document.createElement('option');
                    opt.value = s.slot_time;
                    opt.textContent = s.slot_time + ' WITA' + (!s.is_available ? ' (Jadwal Saat Ini)' : '');
                    if (s.slot_time === currentVal) {
                        opt.selected = true;
                        hasMatchedCurrent = true;
                    }
                    timeSelect.appendChild(opt);
                });

                if (radioExisting && radioExisting.checked) {
                    timeSelect.disabled = false;
                    timeSelect.required = true;
                    timeSelect.classList.remove('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
                    timeSelect.classList.add('bg-gray-50', 'text-gray-900');
                }
            }

            this.evaluateTimeRules();

            return {
                matchingCount: matching.length,
                hasMatchedCurrent,
            };
        };

        if (radioExisting && radioCustom) {
            radioExisting.addEventListener('change', () => {
                if (radioExisting.checked) setTimeMode('existing');
            });
            radioCustom.addEventListener('change', () => {
                if (radioCustom.checked) setTimeMode('custom');
            });
        }

        if (barberSelect) {
            barberSelect.addEventListener('change', populateSchedules);
        }

        if (dateInput) {
            dateInput.addEventListener('change', populateSchedules);
            dateInput.addEventListener('changeDate', populateSchedules);
            dateInput.addEventListener('input', populateSchedules);
        }

        if (timeSelect) {
            timeSelect.addEventListener('change', () => this.evaluateTimeRules());
        }

        if (timePicker) {
            timePicker.addEventListener('change', () => this.evaluateTimeRules());
            timePicker.addEventListener('input', () => this.evaluateTimeRules());
        }

        if (statusSelect) {
            statusSelect.addEventListener('change', () => this.evaluateTimeRules());
        }

        // Inisialisasi awal saat halaman pertama kali dimuat
        const initialRes = populateSchedules();
        if (initialRes && initialRes.hasMatchedCurrent) {
            setTimeMode('existing');
        } else if (
            this.initialSelectedTime &&
            (!initialRes || initialRes.matchingCount === 0 || !initialRes.hasMatchedCurrent)
        ) {
            setTimeMode('custom');
            if (timePicker) timePicker.value = this.initialSelectedTime;
            this.evaluateTimeRules();
        } else {
            setTimeMode('existing');
        }

        // 3. Payment Type DP Toggle Logic
        const paymentTypeSelect = document.getElementById('payment_type');
        const dpAmountContainer = document.getElementById('dp-amount-container');
        const dpAmountInput = document.getElementById('dp_amount');

        if (paymentTypeSelect && dpAmountContainer && dpAmountInput) {
            const updateDpVisibility = () => {
                if (paymentTypeSelect.value === 'dp') {
                    dpAmountContainer.classList.remove('hidden');
                    dpAmountInput.required = true;
                } else {
                    dpAmountContainer.classList.add('hidden');
                    dpAmountInput.required = false;
                }
            };

            paymentTypeSelect.addEventListener('change', updateDpVisibility);
            updateDpVisibility();
        }
    },
});
