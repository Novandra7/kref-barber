<form method="POST" action="{{ $formUrl }}" class="space-y-6" id="booking-form">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    {{-- Global Validation Errors --}}
    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">
            <p class="font-semibold">Please fix the following errors:</p>
            <ul class="mt-1 list-disc ps-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main Info Grid --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Customer Information Section -->
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-bold uppercase text-brand">Customer Information</h3>
            <div class="mt-5 space-y-4">
                <div>
                    <label for="name" class="block mb-2 text-sm font-medium text-gray-900">Customer Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $booking->name ?? '') }}" required class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand placeholder:text-gray-400" placeholder="Kylo Chandra" />
                </div>

                <div>
                    <label for="phone" class="block mb-2 text-sm font-medium text-gray-900">Phone <span class="text-red-500">*</span></label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $booking->phone ?? '') }}" required class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand placeholder:text-gray-400" placeholder="081234567890" />
                </div>

                <div>
                    <label for="description" class="block mb-2 text-sm font-medium text-gray-900">Notes</label>
                    <textarea id="description" name="description" rows="4" class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand placeholder:text-gray-400" placeholder="Additional details about the booking...">{{ old('description', $booking->description ?? '') }}</textarea>
                </div>
            </div>
        </section>

        <!-- Appointment Details Section -->
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-bold uppercase text-brand">Appointment Details</h3>

            <div class="mt-5 space-y-5">
                <!-- Barber Select -->
                <div>
                    <label for="barber_id" class="mb-2 block text-sm font-medium text-gray-900">Select Barber <span class="text-red-500">*</span></label>
                    <select id="barber_id" name="barber_id" required class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand">
                        <option value="" disabled @selected(!old('barber_id', $booking->barber_id ?? null))>Select Barber</option>
                        @foreach ($barbers as $barberOption)
                            <option value="{{ $barberOption->id }}" @selected((string) old('barber_id', $booking->barber_id ?? '') === (string) $barberOption->id)>
                                {{ $barberOption->name }} - {{ ucfirst($barberOption->role) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date & Time Grid -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <!-- Date Picker -->
                    <div>
                        <div class="mb-2 flex h-8 items-center">
                            <label for="date" class="block text-sm font-medium text-gray-900">
                                Date <span class="text-red-500">*</span>
                            </label>
                        </div>
                        <input 
                            datepicker 
                            datepicker-autohide
                            datepicker-format="yyyy-mm-dd"
                            type="text" 
                            id="date" 
                            name="date" 
                            value="{{ old('date', isset($booking->scheduled_at) ? $booking->scheduled_at->format('Y-m-d') : now()->toDateString()) }}" 
                            required 
                            class="block h-[42px] w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand" 
                            placeholder="Select date"
                        >
                    </div>

                    <!-- Time Section with Radio Toggle -->
                    <div>
                        <div class="mb-2 flex h-8 items-center justify-between gap-2">
                            <label class="block text-sm font-medium text-gray-900">
                                Time <span class="text-red-500">*</span>
                            </label>
                            <div class="inline-flex rounded-lg border border-gray-200 bg-gray-100 p-0.5 text-xs font-semibold">
                                <label id="btn-time-existing" class="cursor-pointer rounded-md px-2.5 py-1 text-xs font-semibold transition-all bg-white text-brand shadow-xs">
                                    <input type="radio" name="time_source" value="existing" id="radio-time-existing" class="sr-only" checked>
                                    <span>Pilih Jadwal</span>
                                </label>
                                <label id="btn-time-custom" class="cursor-pointer rounded-md px-2.5 py-1 text-xs font-semibold transition-all text-gray-600 hover:text-gray-900">
                                    <input type="radio" name="time_source" value="custom" id="radio-time-custom" class="sr-only">
                                    <span>Input Jam Baru</span>
                                </label>
                            </div>
                        </div>

                        <!-- 1. Dropdown Select Waktu yang Sudah Ada -->
                        <div id="wrapper-time-existing">
                            <select id="time_select" name="time" required class="block h-[42px] w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand">
                                <option value="">-- Pilih Slot Jadwal --</option>
                            </select>
                        </div>

                        <!-- 2. Time Picker Waktu Baru -->
                        <div id="wrapper-time-custom" class="hidden">
                            <input type="time" id="time_picker" name="time" value="{{ old('time', isset($booking->scheduled_at) ? $booking->scheduled_at->format('H:i') : now()->format('H:i')) }}" disabled class="block h-[42px] w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                </div>

                <!-- Operational Status Select -->
                <div>
                    <label for="status" class="mb-2 block text-sm font-medium text-gray-900">Operational Status <span class="text-red-500">*</span></label>
                    <select 
                        id="status" 
                        name="status" 
                        required 
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand {{ !$isEdit ? 'pointer-events-none bg-gray-200 opacity-80' : '' }}"
                        {{ !$isEdit ? 'tabindex="-1"' : '' }}
                    >
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $booking->status ?? 'confirmed') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>
    </div>

    {{-- Services & Payment Section --}}
    <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-brand mb-6 uppercase">Services & Payment</h3>
            <div class="flex flex-col gap-6">
                <!-- 2. PAYMENT DETAILS & SUMMARY -->
                <div class="w-full rounded-xl bg-base border border-gray-200 p-6 mb-3">
                    <h4 class="pb-3 text-md font-bold text-brand">Payment Details</h4>

                    <div class="space-y-4">
                        <!-- Payment Type -->
                        <div>
                            <label for="payment_type" class="mb-2 block text-sm font-semibold text-gray-900">Payment Type <span class="text-red-500">*</span></label>
                            <select 
                                id="payment_type"
                                name="payment_type" 
                                required 
                                class="block w-full rounded-xl border border-gray-200 bg-base px-3 py-2.5 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20"
                            >
                                <option value="full" @selected(old('payment_type', $booking->payment_type ?? 'full') === 'full')>Lunas / Full</option>
                                <option value="dp" @selected(old('payment_type', $booking->payment_type ?? '') === 'dp')>DP (Down Payment)</option>
                            </select>
                        </div>

                        <!-- Nominal DP (Muncul jika Payment Type = DP) -->
                        @php
                            $isDpSelected = old('payment_type', $booking->payment_type ?? 'full') === 'dp';
                            $defaultDpAmount = old('dp_amount', ($booking->payment_type ?? '') === 'dp' ? ($booking->payment?->amount ?? config('booking.dp_amount', 40000)) : config('booking.dp_amount', 40000));
                        @endphp
                        <div id="dp-amount-container" class="{{ $isDpSelected ? '' : 'hidden' }}">
                            <label for="dp_amount" class="mb-2 block text-sm font-semibold text-gray-900">
                                Nominal DP (Rp) <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                id="dp_amount" 
                                name="dp_amount" 
                                min="1000"
                                step="1000"
                                value="{{ $defaultDpAmount }}" 
                                class="block w-full rounded-xl border border-gray-200 bg-base px-3 py-2.5 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" 
                                placeholder="Contoh: 40000"
                                {{ $isDpSelected ? 'required' : '' }}
                            >
                            <p class="mt-1 text-xs text-gray-500">Masukkan besaran nominal uang muka (DP) yang dibayarkan.</p>
                        </div>

                        <!-- Payment Method (Create Mode Only) -->
                        @if (!$isEdit)
                            <div>
                                <label for="payment_method" class="mb-2 block text-sm font-semibold text-gray-900">Payment Method <span class="text-red-500">*</span></label>
                                <select 
                                    id="payment_method"
                                    name="payment_method" 
                                    required 
                                    class="block w-full rounded-xl border border-gray-200 bg-base px-3 py-2.5 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20"
                                >
                                    <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                                    <option value="qris_static" @selected(old('payment_method') === 'qris_static')>QRIS</option>
                                </select>
                            </div>
                        @endif
                    </div>

                    <!-- Booking Summary Cards (Edit Mode Only) -->
                    @if ($isEdit)
                        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-4 space-y-3 shadow-xs">
                            <span class="text-xs font-semibold text-brand uppercase tracking-wider block border-b border-gray-100 pb-2">Payment Summary</span>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Current Total</span>
                                <strong class="font-bold text-gray-900">Rp {{ number_format($booking->total_amount ?? 0, 0, ',', '.') }}</strong>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Outstanding Amount</span>
                                <strong class="font-bold text-amber-600">Rp {{ number_format($booking->outstanding_amount ?? 0, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 1. SELECT SERVICES (Dynamic Grid List) -->
            <div class="w-full rounded-xl bg-base border border-gray-200 p-6" id="services-container">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <div>
                        <h4 class="text-md font-bold tracking-tight text-brand">
                            Select Services <span class="text-red-500">*</span>
                        </h4>
                        <p class="text-xs text-gray-500">Pilih minimal 1 layanan yang akan dipesan.</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 bg-brand/10 text-brand rounded-full">
                        {{ $serviceCount }} Pilihan Layanan
                    </span>
                </div>

                {{-- Alert validasi layanan wajib pilih minimal 1 --}}
                <div id="service-selection-error" class="hidden mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-xs font-semibold text-red-700">
                    ⚠️ Wajib memilih minimal 1 layanan.
                </div>
                @error('service_ids')
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-xs font-semibold text-red-700">
                        ⚠️ {{ $message }}
                    </div>
                @enderror

                {{-- Dynamic Grid Layout berdasarkan jumlah Kategori --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ $categoryCount }} gap-6">
                    @foreach ($serviceCategories as $categoryData)
                        
                        <fieldset class="flex flex-col justify-between">
                            <div>
                                <legend class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-900 flex items-center justify-between w-full">
                                    <span class="flex items-center gap-1.5">
                                        {{ $categoryData['name'] }}
                                    </span>
                                </legend>

                                <div class="space-y-2.5">
                                    @foreach ($categoryData['services'] as $index => $service)
                                        <label class="group relative flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-base p-3 transition-all duration-150 hover:border-brand hover:bg-brand/5 has-checked:border-brand has-checked:bg-brand/10">
                                            <input
                                                type="checkbox"
                                                name="service_ids[]"
                                                value="{{ $service->id }}"
                                                @checked($service->is_selected)
                                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand"
                                            >
                                            <div class="flex-1 text-sm leading-tight">
                                                <div class="font-medium text-gray-900 group-hover:text-brand transition-colors">
                                                    {{ $service->name }}
                                                </div>
                                                <div class="text-xs font-semibold text-gray-500 mt-1">
                                                    Rp {{ number_format($service->price, 0, ',', '.') }}
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </fieldset>
                    @endforeach
                </div>
            </div>

            
    </section>

    {{-- Form Action Buttons --}}
    <div class="flex flex-wrap justify-end gap-3 pt-2">
        <a href="{{ route('admin.bookings.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-gray-50 transition">Cancel</a>
        <button type="submit" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90 transition">
            {{ $isEdit ? 'Save Booking Changes' : 'Create Booking' }}
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('booking-form');
        if (!form) return;

        // 1. Service Selection Validation
        const checkboxes = form.querySelectorAll('input[name="service_ids[]"]');
        const errorAlert = document.getElementById('service-selection-error');

        function hasSelectedService() {
            return Array.from(checkboxes).some(cb => cb.checked);
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                if (hasSelectedService() && errorAlert) {
                    errorAlert.classList.add('hidden');
                }
            });
        });

        form.addEventListener('submit', function (e) {
            if (!hasSelectedService()) {
                e.preventDefault();
                if (errorAlert) {
                    errorAlert.classList.remove('hidden');
                }
                const container = document.getElementById('services-container');
                if (container) {
                    container.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                if (checkboxes[0]) {
                    checkboxes[0].focus();
                }
            }
        });

        // 2. Schedule Time Toggle & Filter Logic
        const allSchedules = @json($schedules ?? []);
        const initialSelectedTime = @json(old('time', isset($booking->scheduled_at) ? $booking->scheduled_at->format('H:i') : ''));

        const radioExisting = document.getElementById('radio-time-existing');
        const radioCustom = document.getElementById('radio-time-custom');
        const timeSelect = document.getElementById('time_select');
        const timePicker = document.getElementById('time_picker');
        const barberSelect = document.getElementById('barber_id');
        const dateInput = document.getElementById('date');

        function setTimeMode(mode) {
            const btnExisting = document.getElementById('btn-time-existing');
            const btnCustom = document.getElementById('btn-time-custom');
            const wrapperExisting = document.getElementById('wrapper-time-existing');
            const wrapperCustom = document.getElementById('wrapper-time-custom');

            if (mode === 'existing') {
                if (radioExisting) radioExisting.checked = true;
                if (btnExisting) {
                    btnExisting.className = 'cursor-pointer rounded-md px-2.5 py-1 text-xs font-semibold transition-all bg-white text-brand shadow-xs';
                }
                if (btnCustom) {
                    btnCustom.className = 'cursor-pointer rounded-md px-2.5 py-1 text-xs font-semibold transition-all text-gray-600 hover:text-gray-900';
                }
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
                if (btnCustom) {
                    btnCustom.className = 'cursor-pointer rounded-md px-2.5 py-1 text-xs font-semibold transition-all bg-white text-brand shadow-xs';
                }
                if (btnExisting) {
                    btnExisting.className = 'cursor-pointer rounded-md px-2.5 py-1 text-xs font-semibold transition-all text-gray-600 hover:text-gray-900';
                }
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
        }

        function populateSchedules() {
            if (!timeSelect) return { matchingCount: 0, hasMatchedCurrent: false };

            const selectedBarberId = barberSelect ? barberSelect.value : '';
            const selectedDate = dateInput ? dateInput.value : '';

            const matching = allSchedules.filter(function (s) {
                return String(s.barber_id) === String(selectedBarberId) && s.date === selectedDate;
            });

            const currentVal = timeSelect.value || initialSelectedTime;
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

                matching.forEach(function (s) {
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

            return { matchingCount: matching.length, hasMatchedCurrent: hasMatchedCurrent };
        }

        if (radioExisting && radioCustom) {
            radioExisting.addEventListener('change', function () {
                if (radioExisting.checked) setTimeMode('existing');
            });
            radioCustom.addEventListener('change', function () {
                if (radioCustom.checked) setTimeMode('custom');
            });
        }

        if (barberSelect) {
            barberSelect.addEventListener('change', function () {
                populateSchedules();
            });
        }

        if (dateInput) {
            dateInput.addEventListener('change', function () {
                populateSchedules();
            });
            dateInput.addEventListener('changeDate', function () {
                populateSchedules();
            });
            dateInput.addEventListener('input', function () {
                populateSchedules();
            });
        }

        // Inisialisasi awal saat halaman pertama kali dimuat
        const initialRes = populateSchedules();
        if (initialRes && initialRes.hasMatchedCurrent) {
            setTimeMode('existing');
        } else if (initialSelectedTime && (!initialRes || initialRes.matchingCount === 0 || !initialRes.hasMatchedCurrent)) {
            setTimeMode('custom');
            if (timePicker) timePicker.value = initialSelectedTime;
        } else {
            setTimeMode('existing');
        }

        // 3. Payment Type DP Toggle Logic
        const paymentTypeSelect = document.getElementById('payment_type');
        const dpAmountContainer = document.getElementById('dp-amount-container');
        const dpAmountInput = document.getElementById('dp_amount');

        if (paymentTypeSelect && dpAmountContainer && dpAmountInput) {
            function updateDpVisibility() {
                if (paymentTypeSelect.value === 'dp') {
                    dpAmountContainer.classList.remove('hidden');
                    dpAmountInput.required = true;
                } else {
                    dpAmountContainer.classList.add('hidden');
                    dpAmountInput.required = false;
                }
            }

            paymentTypeSelect.addEventListener('change', updateDpVisibility);
            updateDpVisibility();
        }
    });
</script>