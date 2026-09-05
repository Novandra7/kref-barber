<section aria-labelledby="time-and-barber-title">

    <h1 id="time-and-barber-title" class="sr-only">Detail & Service</h1>

    <!-- Alert Validasi -->
    <div
        x-cloak
        x-show="validationAttempted && (!currentGuest.name || !currentGuest.phone || (!currentGuest.selectedHaircut && !currentGuest.selectedChemical && currentGuest.selectedTreatments.length === 0))"
        role="alert"
        class="mb-5 flex items-center gap-3 rounded-xl border-2 border-red-500 bg-red-100 px-4 py-3 text-sm font-bold text-red-800 shadow-sm"
    >
        <svg class="size-6 shrink-0 text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
            <path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Please complete all required fields and select at least one service before continuing.</span>
    </div>

    <div class="flex flex-col md:flex-row gap-4">
        <!-- YOUR DETAILS -->
        <div class="w-full md:w-1/3 rounded-xl bg-base border border-gray-200 p-6">
            <h2 class="pb-4 text-xl font-montserrat font-bold tracking-tight text-brand">YOUR DETAILS *</h2>
            
            <!-- Input Nama -->
            <div class="mb-4">
                <label for="name" class="mb-2 block text-sm font-semibold text-gray-700">Name</label>
                <input
                    type="text"
                    id="name"
                    class="block w-full rounded-xl border border-gray-200 bg-base px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20"
                    placeholder="Kylo Chandra"
                    x-model="currentGuest.name"
                    required
                />
            </div>

            <!-- Input No. Telepon -->
            <div class="mb-4">
                <label for="phone-input" class="mb-2 block text-sm font-semibold text-gray-700">Phone Number</label>
                <div class="flex items-center">
                    <span class="z-10 inline-flex shrink-0 items-center rounded-s-xl border border-e-0 border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-900">
                        <svg class="me-2 h-4 w-4 overflow-hidden rounded-full border border-gray-200" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="16" height="8" fill="#E70011"/>
                            <rect y="8" width="16" height="8" fill="#FFFFFF"/>
                        </svg>
                        +62
                    </span>

                    <div class="relative w-full">
                        <input
                            type="tel"
                            id="phone-input"
                            x-model="currentGuest.phone"
                            @input="currentGuest.phone = currentGuest.phone.replace(/^0+/, '').replace(/[^0-9]/g, '')"
                            class="block w-full rounded-e-xl border border-gray-200 bg-base px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20"
                            placeholder="81234567890"
                            required
                        />
                    </div>
                </div>
            </div>

            <!-- Input Catatan -->
            <div class="mb-3">
                <label for="notes" class="mb-2 block text-sm font-semibold text-gray-700">Notes (Optional)</label>
                <textarea
                    id="notes"
                    x-model="currentGuest.notes"
                    rows="4"
                    class="block w-full rounded-xl border border-gray-200 bg-base px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20"
                    placeholder="Any special request?"
                ></textarea>
            </div>
        </div>

        <!-- SELECT SERVICE -->
        <div class="w-full md:w-1/3 rounded-xl bg-base border border-gray-200 p-6">
            <h2 class="pb-4 text-xl font-montserrat font-bold tracking-tight text-brand">
                SELECT SERVICE *
            </h2>

            @foreach (collect($services)->groupBy('category') as $category => $categoryServices)
                @php
                    $catSlug = Str::slug($category);
                @endphp

                <!-- Category Title -->
                <h3 class="mb-3 text-md font-semibold text-gray-700 uppercase">
                    {{ $category }}
                </h3>

                <!-- List Layanan per Kategori -->
                <div class="grid lg:grid-cols-2 gap-4 mb-5">
                    @foreach ($categoryServices as $index => $service)
                        <div class="flex items-center">
                            @if ($catSlug === 'haircut')
                                <input
                                    id="service-{{ $service['id'] ?? $loop->parent->index . '-' . $index }}"
                                    type="radio"
                                    name="category_haircut"
                                    value="{{ $service['name'] }}"
                                    x-model="currentGuest.selectedHaircut"
                                    class="h-4 w-4 border-gray-300 text-brand focus:ring-2 focus:ring-brand/20"
                                >
                            @elseif ($catSlug === 'chemical')
                                <input
                                    id="service-{{ $service['id'] ?? $loop->parent->index . '-' . $index }}"
                                    type="radio"
                                    name="category_chemical"
                                    value="{{ $service['name'] }}"
                                    x-model="currentGuest.selectedChemical"
                                    @click="currentGuest.selectedChemical === '{{ $service['name'] }}'
                                        ? currentGuest.selectedChemical = null
                                        : currentGuest.selectedChemical = '{{ $service['name'] }}'"
                                    class="h-4 w-4 border-gray-300 text-brand focus:ring-2 focus:ring-brand/20"
                                >
                            @else
                                <input
                                    id="service-{{ $service['id'] ?? $loop->parent->index . '-' . $index }}"
                                    type="checkbox"
                                    value="{{ $service['name'] }}"
                                    x-model="currentGuest.selectedTreatments"
                                    class="h-4 w-4 border-gray-300 text-brand focus:ring-2 focus:ring-brand/20"
                                >
                            @endif

                            <label
                                for="service-{{ $service['id'] ?? $loop->parent->index . '-' . $index }}"
                                class="ms-3 flex flex-1 items-center justify-between select-none text-md text-gray-900 cursor-pointer"
                            >
                                <span>{{ $service['name'] }}</span>
                                
                                <!-- Menggunakan formatPriceK yang otomatis tambah 10k jika Haircut + Owner -->
                               <span
                                    class="font-bold text-primary"
                                    x-text="formatPriceK({{ $service['price'] }} + (isOwnerSelected() && '{{ strtolower($service['name']) }}'.includes('regular haircut') ? 10000 : 0))">
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <!-- YOUR BOOKING SUMMARY -->
        <div class="w-full md:w-1/3 rounded-xl bg-base border border-gray-200 p-6">
            <h2 class="pb-4 text-xl font-montserrat font-bold tracking-tight text-brand">YOUR BOOKING</h2>
            
            <div class="flex flex-col gap-3">
                <!-- Barber Profile -->
                <div class="flex items-center gap-5 mb-5">
                    <img 
                        class="size-15 rounded-full object-cover border border-gray-200" 
                        :src="selectedBarberObj()?.photo_url || 'https://img.daisyui.com/images/profile/demo/1@94.webp'" 
                        :alt="selectedBarberObj()?.name || 'Barber'"
                    />
                    <div class="flex flex-col gap-1">
                        <span class="text-sm font-semibold text-gray-900" x-text="selectedBarberObj()?.name || '-'"></span>
                        <span class="text-xs uppercase font-semibold opacity-60" x-text="selectedBarberObj()?.role || 'Barber'"></span>
                    </div>
                </div>

                <!-- Date -->
                <div class="flex items-center gap-5">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 19H5V8H19M16 1V3H8V1H6V3H5C3.89 3 3 3.89 3 5V19C3 19.5304 3.21071 20.0391 3.58579 20.4142C3.96086 20.7893 4.46957 21 5 21H19C19.5304 21 20.0391 20.7893 20.4142 20.4142C20.7893 20.0391 21 19.5304 21 19V5C21 4.46957 20.7893 3.96086 20.4142 3.58579C20.0391 3.21071 19.5304 3 19 3H18V1M17 12H12V17H17V12Z" fill="#C83E3E"/>
                    </svg>
                    <span class="text-sm font-semibold text-gray-900" x-text="formatDate(currentGuest?.date)"></span>
                </div>

                <!-- Time -->
                <div class="flex items-center gap-5">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 7V12L15 15M21 12C21 13.1819 20.7672 14.3522 20.3149 15.4442C19.8626 16.5361 19.1997 17.5282 18.364 18.364C17.5282 19.1997 16.5361 19.8626 15.4442 20.3149C14.3522 20.7672 13.1819 21 12 21C10.8181 21 9.64778 20.7672 8.55585 20.3149C7.46392 19.8626 6.47177 19.1997 5.63604 18.364C4.80031 17.5282 4.13738 16.5361 3.68508 15.4442C3.23279 14.3522 3 13.1819 3 12C3 9.61305 3.94821 7.32387 5.63604 5.63604C7.32387 3.94821 9.61305 3 12 3C14.3869 3 16.6761 3.94821 18.364 5.63604C20.0518 7.32387 21 9.61305 21 12Z" stroke="#C83E3E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="text-sm font-semibold text-gray-900" x-text="currentGuest?.time || '-'"></span>
                </div>

                <hr class="my-2 border-gray-200">
                <span class="text-sm font-semibold text-brand">SELECTED SERVICES</span>
                
                <!-- Dynamic List Selected Services -->
                <template x-for="service in selectedServices()" :key="service.name">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-900" x-text="service.name"></span>
                        <span class="text-sm font-semibold text-brand" x-text="formatPrice(service.price)"></span>
                    </div>
                </template>
                
                <hr class="my-2 border-gray-200">

                <!-- Total Price -->
                <div class="flex items-center justify-between">
                    <span class="text-md font-black text-gray-900">TOTAL</span>
                    <span class="text-md font-semibold text-brand" x-text="formatPrice(selectedServices().reduce((sum, s) => sum + s.price, 0))"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="flex items-center justify-between mt-4 gap-2">
        <button type="button" class="btn rounded-xl border border-gray-200 hover:bg-gray-100" @click="currentStep = 1">
            <svg class="size-[1.4em]" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22C6.477 22 2 17.523 2 12C2 6.477 6.477 2 12 2C17.523 2 22 6.477 22 12C22 17.523 17.523 22 12 22Z" stroke="black" stroke-width="2" stroke-linejoin="round"/>
                <path d="M13.5 16.5L9 12L13.5 7.5" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back : Barbers & Time
        </button>
        
        <button type="button" class="btn btn-primary rounded-xl" @click="validateStep2()" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })">
            Save & Continue
        </button>
    </div>
</section>