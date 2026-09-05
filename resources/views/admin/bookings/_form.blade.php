<form method="POST" action="{{ $formUrl }}" class="space-y-6">
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
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 inset-s-0 flex items-center ps-3.5">
                            <svg class="h-4 w-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <select id="barber_id" name="barber_id" required class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-brand focus:ring-brand">
                            <option value="" disabled @selected(!old('barber_id', $booking->barber_id ?? null))>Select Barber</option>
                            @foreach ($barbers as $barberOption)
                                <option value="{{ $barberOption->id }}" @selected((string) old('barber_id', $booking->barber_id ?? '') === (string) $barberOption->id)>
                                    {{ $barberOption->name }} - {{ ucfirst($barberOption->role) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Date & Time Grid -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <!-- Date Picker -->
                    <div>
                        <label for="date" class="mb-2 block text-sm font-medium text-gray-900">Date <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 inset-s-0 flex items-center ps-3.5">
                                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1H8V1a1 1 0 0 0-2 0v1H4a2 2 0 0 0-2 2v2h18V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1Z"/>
                                </svg>
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
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-brand focus:ring-brand" 
                                placeholder="Select date"
                            >
                        </div>
                    </div>

                    <!-- Time Picker -->
                    <div>
                        <label for="time" class="mb-2 block text-sm font-medium text-gray-900">Time <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 inset-s-0 flex items-center ps-3.5">
                                <svg class="h-4 w-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <input type="time" id="time" name="time" value="{{ old('time', isset($booking->scheduled_at) ? $booking->scheduled_at->format('H:i') : now()->format('H:i')) }}" required class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                </div>

                <!-- Operational Status Select -->
                <div>
                    <label for="status" class="mb-2 block text-sm font-medium text-gray-900">Operational Status <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 inset-s-0 flex items-center ps-3.5">
                            <svg class="h-4 w-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <select 
                            id="status" 
                            name="status" 
                            required 
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-brand focus:ring-brand {{ !$isEdit ? 'pointer-events-none bg-gray-200 opacity-80' : '' }}"
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
                                class="block w-full rounded-xl border border-gray-200 bg-base px-3 py-2.5 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20 {{ !$isEdit ? 'pointer-events-none bg-gray-200 opacity-80' : '' }}"
                            >
                                <option value="full" @selected(old('payment_type', $booking->payment_type ?? 'full') === 'full')>Lunas / Full</option>
                                <option value="dp" @selected(old('payment_type', $booking->payment_type ?? '') === 'dp')>DP (Down Payment)</option>
                            </select>
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
            <div class="w-full rounded-xl bg-base border border-gray-200 p-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <div>
                        <h4 class="text-md font-bold tracking-tight text-brand">
                            Select Services <span class="text-red-500">*</span>
                        </h4>
                        <p class="text-xs text-gray-500">Pilih layanan yang akan dipesan.</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 bg-brand/10 text-brand rounded-full">
                        {{ $serviceCount }} Pilihan Layanan
                    </span>
                </div>

                {{-- Dynamic Grid Layout berdasarkan jumlah Kategori --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ $categoryCount }} gap-6">
                    @foreach ($serviceCategories as $categoryData)
                        
                        <fieldset class="flex flex-col justify-between">
                            <div>
                                <legend class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-900 flex items-center justify-between w-full">
                                    <span class="flex items-center gap-1.5">
                                        {{ $categoryData['name'] }}
                                    </span>
                                    @if ($categoryData['isHaircut'])
                                        <span class="text-2xs font-medium text-red-600 bg-red-50 px-2 py-0.5 rounded border border-red-200">
                                            Wajib (Pilih 1)
                                        </span>
                                    @endif
                                </legend>

                                <div class="space-y-2.5">
                                    @foreach ($categoryData['services'] as $index => $service)
                                        <label class="group relative flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-base p-3 transition-all duration-150 hover:border-brand hover:bg-brand/5 has-checked:border-brand has-checked:bg-brand/10">
                                            <input
                                                type="{{ $categoryData['isHaircut'] ? 'radio' : 'checkbox' }}"
                                                name="service_ids[]"
                                                value="{{ $service->id }}"
                                                @checked($service->is_selected)
                                                @if ($categoryData['isHaircut'] && $index === 0) required @endif
                                                class="mt-0.5 h-4 w-4 border-gray-300 text-brand focus:ring-brand"
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
                                    @if ($categoryData['isHaircut'])
                                        <div class="mt-4 text-[11px] font-medium text-gray-500 pt-2 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-brand shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span>Layanan dasar haircut wajib dipilih salah satu.</span>
                                        </div>
                                    @endif
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