@php
    $initialAdminBookingState = [
        'schedules' => $schedules ?? [],
        'timelineSchedules' => $timelineSchedules ?? [],
        'slotRules' => $slotRules ?? [],
        'servicesMeta' => $servicesMeta ?? [],
        'initialSelectedTime' => old('time', isset($booking->scheduled_at) ? $booking->scheduled_at->format('H:i') : ''),
    ];
@endphp

<form method="POST" action="{{ $formUrl }}" x-data="adminBookingForm(@js($initialAdminBookingState))" class="space-y-6" id="booking-form">
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
                    <div class="flex">
                        <span class="inline-flex shrink-0 items-center rounded-s-lg border border-e-0 border-gray-300 bg-gray-100 px-3.5 text-sm font-bold text-gray-700">
                            +62
                        </span>
                        <input type="tel" id="phone" name="phone"
                            value="{{ old('phone', ltrim(preg_replace('/^(\+?62|0)+/', '', $booking->phone ?? ''), '0')) }}"
                            required
                            placeholder="81234567890"
                            oninput="this.value = this.value.replace(/^(\+?62|0)+/, '').replace(/[^0-9]/g, '')"
                            class="block w-full rounded-e-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand placeholder:text-gray-400" />
                    </div>
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
                            placeholder="Select date">
                    </div>

                    <!-- Time Section with Radio Toggle -->
                    <div>
                        <div class="mb-2 flex h-8 items-center justify-between gap-2">
                            <label class="block shrink-0 whitespace-nowrap text-sm font-medium text-gray-900">
                                Time <span class="text-red-500">*</span>
                            </label>
                            <div class="inline-flex shrink-0 whitespace-nowrap rounded-lg border border-gray-200 bg-gray-100 p-0.5 text-xs font-semibold">
                                <label id="btn-time-existing" class="inline-flex cursor-pointer items-center whitespace-nowrap rounded-md px-2.5 py-1 text-xs font-semibold transition-all bg-white text-brand shadow-xs">
                                    <input type="radio" name="time_source" value="existing" id="radio-time-existing" class="sr-only" checked>
                                    <span>Pilih Jadwal</span>
                                </label>
                                <label id="btn-time-custom" class="inline-flex cursor-pointer items-center whitespace-nowrap rounded-md px-2.5 py-1 text-xs font-semibold transition-all text-gray-600 hover:text-gray-900">
                                    <input type="radio" name="time_source" value="custom" id="radio-time-custom" class="sr-only">
                                    <span>Input Jam Baru</span>
                                </label>
                            </div>
                        </div>

                        <!-- 1. Dropdown Select Waktu yang Sudah Ada -->
                        <div id="wrapper-time-existing">
                            <select id="time_select" name="time" required class="block h-[42px] w-full rounded-lg border {{ $errors->has('time') ? 'border-red-500' : 'border-gray-300' }} bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="">-- Pilih Slot Jadwal --</option>
                            </select>
                        </div>

                        <!-- 2. Time Picker Waktu Baru -->
                        <div id="wrapper-time-custom" class="hidden">
                            <input type="time" id="time_picker" name="time" value="{{ old('time', isset($booking->scheduled_at) ? $booking->scheduled_at->format('H:i') : now()->format('H:i')) }}" disabled class="block h-[42px] w-full rounded-lg border {{ $errors->has('time') ? 'border-red-500' : 'border-gray-300' }} bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand disabled:cursor-not-allowed disabled:opacity-50">
                        </div>

                        @error('time')
                        <p class="mt-1.5 text-xs font-semibold text-red-600">⚠️ {{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Operational Status Select -->
                <div>
                    <label for="status" class="mb-2 block text-sm font-medium text-gray-900">Operational Status <span class="text-red-500">*</span></label>
                    <select
                        id="status"
                        name="status"
                        required
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand {{ !$isEdit ? 'pointer-events-none bg-gray-200 opacity-50' : '' }}"
                        tabindex="{{ !$isEdit ? '-1' : '0' }}">
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
                            class="block w-full rounded-xl border border-gray-200 bg-base px-3 py-2.5 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
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
                            @required($isDpSelected)>
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
                            class="block w-full rounded-xl border border-gray-200 bg-base px-3 py-2.5 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
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
                    @if (($booking->tip_amount ?? 0) > 0)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Tip Barber</span>
                        <strong class="font-bold text-emerald-600">+ Rp {{ number_format($booking->tip_amount, 0, ',', '.') }}</strong>
                    </div>
                    @endif
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

    {{-- Live Time Rules Feedback (Info / Bentrok Multi-Slot) --}}
    <div id="time-rule-feedback"
         x-cloak
         x-show="timeRuleState.requiredSlots > 1"
         role="alert"
         :class="timeRuleState.hasConflict
             ? 'rounded-xl border-2 border-red-400 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-xs'
             : (timeRuleState.needsTimeSelection
                 ? 'rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 shadow-xs'
                 : 'rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 shadow-xs')">
        <div class="flex items-start gap-3">
            <svg x-show="timeRuleState.hasConflict" class="mt-0.5 size-5 shrink-0 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <svg x-show="!timeRuleState.hasConflict" class="mt-0.5 size-5 shrink-0" :class="timeRuleState.needsTimeSelection ? 'text-amber-600' : 'text-blue-600'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <div class="space-y-0.5">
                <p class="font-bold" x-text="timeRuleState.hasConflict ? ('Bentrok Time Rule (' + timeRuleState.ruleLabel + ')') : ('Time Rule Aktif (' + timeRuleState.ruleLabel + ' • ' + timeRuleState.requiredSlots + ' Slot)')"></p>
                <p class="text-xs leading-relaxed font-medium" x-text="timeRuleState.message"></p>
            </div>
        </div>
    </div>

    {{-- Form Action Buttons --}}
    <div class="flex flex-wrap justify-end gap-3 pt-2">
        <a href="{{ route('admin.bookings.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-gray-50 transition">Cancel</a>
        <button type="submit"
                :disabled="timeRuleState.hasConflict"
                class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90 transition disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:opacity-50">
            {{ $isEdit ? 'Save Booking Changes' : 'Create Booking' }}
        </button>
    </div>
</form>