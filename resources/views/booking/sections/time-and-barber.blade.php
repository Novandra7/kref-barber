<section aria-labelledby="time-and-barber-title">
    <h1 id="time-and-barber-title" class="sr-only">Barbers & Time</h1>

    {{-- Alert --}}
    <div
        x-cloak
        x-show="validationAttempted && (!currentGuest.barber || !currentGuest.date || !currentGuest.time)"
        role="alert"
        class="mb-5 flex items-center gap-3 rounded-xl border-2 border-red-500 bg-red-100 px-4 py-3 text-sm font-bold text-red-800 shadow-sm"
    >
        <svg class="size-6 shrink-0 text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
            <path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
        </svg>
        <span>Please complete all required fields before continuing.</span>
    </div>

    <!-- Layout Container -->
    <div class="flex flex-col lg:flex-row gap-4">
        
        <!-- Barber Column -->
        <div class="w-full lg:w-1/2 rounded-xl bg-white border border-gray-200 p-6">
            <div class="pb-4 text-xl font-montserrat font-bold tracking-tight text-primary">CHOOSE BARBER *</div>
            <ul class="list flex flex-col gap-3.5 max-h-85 overflow-y-auto pr-1">
                
                <!-- Dynamic Barber Options from Database -->
                @foreach ($barbers as $barber)
                    <li class="list-row rounded-xl border border-gray-200 transition-colors items-center cursor-pointer"
                        :class="currentGuest.barber == '{{ $barber['id'] ?? $barber['name'] }}' ? 'border-primary bg-red-50' : 'hover:bg-gray-100'"
                        @click="currentGuest.barber = '{{ $barber['id'] ?? $barber['name'] }}'">
                        
                        <div>
                            <img 
                                class="size-10 rounded-full object-cover border border-gray-200" 
                                alt="{{ $barber['name'] }}" 
                                src="{{ $barber['photo_url'] ?? 'https://img.daisyui.com/images/profile/demo/1@94.webp' }}"
                            />
                        </div>
                        
                        <div class="text-black">
                            <div>{{ $barber['name'] }}</div>
                            <div class="text-xs uppercase font-semibold opacity-60">
                                {{ $barber['role'] ?? $barber['role'] ?? 'Barber' }}
                            </div>
                        </div>

                        <label class="cursor-pointer ms-auto">
                            <input 
                                type="radio" 
                                name="barber" 
                                value="{{ $barber['id'] ?? $barber['name'] }}" 
                                class="sr-only" 
                                x-model="currentGuest.barber"
                            >
                            <span 
                                class="flex size-7 items-center justify-center rounded-full border-2"
                                :class="currentGuest.barber == '{{ $barber['id'] ?? $barber['name'] }}' ? 'border-primary bg-primary text-white' : 'border-gray-400 text-transparent'"
                            >
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                                </svg>
                            </span>
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Date & Time Column -->
        <div class="w-full lg:w-1/2 rounded-xl bg-white border border-gray-200 p-6">
            <span class="block pb-4 text-xl font-montserrat font-bold tracking-tight text-primary">CHOOSE DATE & TIME *</span>
            
            <div class="flex flex-col sm:flex-row gap-6 items-start">
                
                <!-- Datepicker Container -->
                <div class="w-full sm:w-1/2">
                    <!-- Mobile Datepicker -->
                    <div class="block sm:hidden w-full">
                        <label for="mobile-datepicker" class="block mb-2 text-sm font-semibold text-gray-700">Select Date</label>
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 inset-s-0 flex items-center ps-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/>
                                </svg>
                            </div>
                            <input 
                                datepicker 
                                id="mobile-datepicker" 
                                type="text" 
                                :data-date="currentGuest.date"
                                :value="currentGuest.date"
                                class="block w-full ps-10 pe-3 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-primary focus:border-primary cursor-pointer" 
                                placeholder="Select date"
                                x-init="$nextTick(() => {
                                    $el.addEventListener('changeDate', (event) => {
                                        currentGuest.date = event.detail.date;
                                    });
                                })"
                            >
                        </div>
                    </div>

                    <!-- Desktop Inline Datepicker -->
                    <div class="hidden sm:flex justify-start overflow-x-auto overflow-y-visible">
                        <div class="rounded-xl w-full max-w-[320px]">
                            <div
                                id="datepicker-inline"
                                inline-datepicker
                                :data-date="currentGuest.date"
                                x-init="$nextTick(() => {
                                    $el.addEventListener('changeDate', (event) => {
                                        currentGuest.date = event.detail.date;
                                    });
                                })"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Available Time Grid -->
                <div class="w-full sm:w-1/2">
                    <p class="mb-3 text-sm font-semibold text-gray-700">Available time</p>
                    <div class="grid grid-cols-3 gap-2.5">
                        @foreach (['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '17:00'] as $time)
                            <button 
                                type="button"
                                class="rounded-xl border px-2.5 py-2 text-sm font-semibold transition-colors"
                                :class="currentGuest.time === '{{ $time }}'
                                    ? 'border-primary bg-primary text-white'
                                    : 'border-gray-200 bg-white text-gray-700 hover:border-primary hover:text-primary'"
                                @click="currentGuest.time = '{{ $time }}'"
                            >
                                {{ $time }}
                            </button>
                        @endforeach
                    </div>
                    
                    {{-- Timezone Info --}}
                    <div class="mt-6 rounded-2xl bg-red-50 px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 7v5l3 2" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-900">Times are displayed in</p>
                                <p class="font-bold text-gray-950">WITA (GMT+8)</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    {{-- Bottom Action Button --}}
    <div class="flex items-center md:justify-between mt-6 w-full">
        <button type="button" class="btn rounded-xl border border-gray-200 hover:bg-gray-100" @click="currentStep = 'guest'">
            <svg class="size-[1.4em]" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22C6.477 22 2 17.523 2 12C2 6.477 6.477 2 12 2C17.523 2 22 6.477 22 12C22 17.523 17.523 22 12 22Z" stroke="black" stroke-width="2" stroke-linejoin="round"/>
                <path d="M13.5 16.5L9 12L13.5 7.5" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back : Guest
        </button>
        <button 
            type="button" 
            class="btn btn-primary w-full md:w-auto rounded-xl flex items-center justify-center gap-2 px-6 py-3 font-semibold transition-all duration-200" 
            @click="validateStep1()"
        >
            <span class="text-base md:text-sm">Next : Your Detail & Service</span>
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22C17.523 22 22 17.523 22 12C22 6.477 17.523 2 12 2C6.477 2 2 6.477 2 12C2 17.523 6.477 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M10.5 16.5L15 12L10.5 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>
</section>