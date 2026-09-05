<section aria-labelledby="guest">
    <h1 id="guest" class="sr-only">Guest</h1>

    <div class="flex flex-col md:flex-row gap-4">
        <!-- CUSTOMER LIST -->
        <div class="w-full md:w-2/3 rounded-xl bg-base border border-gray-200 p-6">
            <h2 class="pb-4 text-xl font-montserrat font-bold tracking-tight text-brand">CUSTOMER</h2>
            
            <div class="flex flex-col gap-4">
                <template x-for="(guest, index) in guests" :key="index">
                    <div class="flex items-center justify-between py-4 gap-4 border border-gray-200 rounded-xl px-4">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <!-- Avatar Icon (Inisial Huruf Depan) -->
                            <div class="size-15 rounded-full bg-brand/10 text-brand font-bold flex items-center justify-center shrink-0 uppercase border border-brand/20">
                                <span x-text="guest.name ? guest.name.charAt(0) : 'G'"></span>
                            </div>

                            <!-- Detail Guest & Service -->
                            <div class="flex flex-col min-w-0 gap-1">
                                <!-- Nama Guest -->
                                <div class="text-gray-900 text-lg font-bold truncate" x-text="guest.name || 'Guest Name'"></div>

                                <!-- Service yang Dipilih -->
                                <div class="flex items-center gap-1.5 text-xs text-gray-600 flex-wrap">
                                    <span class="font-medium text-gray-800" x-text="getGuestServicesText(guest)"></span>
                                </div>

                                <!-- Tanggal & Waktu -->
                                <div class="flex items-center gap-3 text-xs text-gray-500 mt-0.5">
                                    <span class="flex items-center gap-1">
                                        <svg class="size-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        <span class="inline sm:hidden" x-text="formatDateShort(guest.date)"></span>
                                        <span class="hidden sm:inline" x-text="formatDate(guest.date)"></span>
                                    </span>

                                    <span>•</span>

                                    <span class="flex items-center gap-1">
                                        <svg class="size-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <span x-text="guest.time || '-'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Action (Edit & Remove) -->
                        <div class="flex items-center gap-2 shrink-0">
                            <button 
                                type="button" 
                                class="group text-xs font-semibold transition-colors px-2 py-1 rounded-md"
                                @click="editGuest(index)">
                                <svg class="text-brand group-hover:text-red-700 transition-colors duration-300" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <!-- Frame/bingkai (statis) -->
                                    <path d="M26.1877 22.8801C26.5521 19.7654 26.6685 16.6266 26.5357 13.4934C26.5326 13.4196 26.5448 13.3459 26.5717 13.277C26.5985 13.2082 26.6394 13.1457 26.6917 13.0934L28.0037 11.7814C28.0395 11.7454 28.085 11.7204 28.1347 11.7096C28.1844 11.6988 28.2362 11.7026 28.2838 11.7205C28.3314 11.7383 28.3728 11.7696 28.403 11.8104C28.4333 11.8513 28.4511 11.9 28.4544 11.9508C28.7006 15.6724 28.6069 19.4088 28.1744 23.1134C27.8597 25.8094 25.6944 27.9228 23.0104 28.2228C18.3508 28.7384 13.6486 28.7384 8.98903 28.2228C6.30636 27.9228 4.13969 25.8094 3.82503 23.1134C3.27317 18.3873 3.27317 13.6129 3.82503 8.88677C4.13969 6.19077 6.30503 4.07744 8.98903 3.77744C12.5256 3.38683 16.0886 3.29184 19.641 3.49344C19.6919 3.49709 19.7406 3.51523 19.7814 3.54572C19.8223 3.57621 19.8535 3.61776 19.8715 3.66546C19.8895 3.71316 19.8934 3.76501 19.8828 3.81487C19.8722 3.86472 19.8475 3.9105 19.8117 3.94677L18.4877 5.26944C18.4359 5.32123 18.3741 5.36181 18.3059 5.38865C18.2378 5.41549 18.1649 5.42801 18.0917 5.42544C15.1274 5.3239 12.1596 5.43753 9.21169 5.76544C8.35028 5.86078 7.54616 6.24373 6.92923 6.85244C6.3123 7.46114 5.91859 8.26005 5.81169 9.1201C5.27684 13.6912 5.27684 18.309 5.81169 22.8801C5.91859 23.7402 6.3123 24.5391 6.92923 25.1478C7.54616 25.7565 8.35028 26.1394 9.21169 26.2348C13.685 26.7348 18.3144 26.7348 22.789 26.2348C23.6504 26.1394 24.4546 25.7565 25.0715 25.1478C25.6884 24.5391 26.0808 23.7402 26.1877 22.8801Z" fill="currentColor"/>

                                    <!-- Pensil (yang bergerak) -->
                                    <g class="transition-transform duration-300 ease-out group-hover:-translate-y-0.5 group-hover:translate-x-0.5">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M28.6065 7.22143C28.7332 7.41371 28.7897 7.64381 28.7663 7.87288C28.743 8.10195 28.6414 8.31596 28.4785 8.47876L16.2212 20.7348C16.0958 20.8601 15.9393 20.9498 15.7679 20.9948L10.6625 22.3281C10.4938 22.3721 10.3164 22.3712 10.1481 22.3255C9.97981 22.2798 9.82637 22.1909 9.70305 22.0676C9.57973 21.9443 9.4908 21.7908 9.4451 21.6225C9.3994 21.4542 9.39851 21.2769 9.44253 21.1081L10.7759 16.0041C10.8157 15.8512 10.889 15.7092 10.9905 15.5881L23.2932 3.29343C23.4807 3.10616 23.7349 3.00098 23.9999 3.00098C24.2649 3.00098 24.519 3.10616 24.7065 3.29343L28.4785 7.0641C28.5251 7.11321 28.5679 7.16581 28.6065 7.22143ZM26.3572 7.77076L23.9999 5.41476L12.6425 16.7721L11.8092 19.9628L14.9999 19.1294L26.3572 7.77076Z" fill="currentColor"/>
                                    </g>
                                </svg>
                            </button>
                            <button 
                                type="button" 
                                class="group text-xs font-semibold transition-colors px-2 py-1 rounded-md" 
                                @click="removeGuest(index)"
                                x-show="guests.length > 1">
                                <svg class="text-brand group-hover:text-red-700 transition-colors duration-300" width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <!-- Badan tong sampah (statis) -->
                                    <path d="M6 7L6.5 19C6.55 20.1 7.45 21 8.55 21H15.45C16.55 21 17.45 20.1 17.5 19L18 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M10 11V17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M14 11V17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>

                                    <!-- Tutup tong sampah (yang bergerak) -->
                                    <g class="transition-transform duration-300 ease-out group-hover:rotate-20" style="transform-origin: 19px 7px;">
                                        <path d="M4 7H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <path d="M9.5 7V4.5C9.5 3.94772 9.94772 3.5 10.5 3.5H13.5C14.0523 3.5 14.5 3.94772 14.5 4.5V7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </g>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Add Guest Button -->
            <button 
                type="button" 
                class="mt-6 w-full sm:w-auto px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-xl hover:bg-brand/90 flex items-center justify-center gap-2 transition-all shadow-sm" 
                @click="addGuest()"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Guest
            </button>
        </div>

        <!-- YOUR BOOKING -->
        <div class="w-full md:w-1/3 rounded-xl bg-base border border-gray-200 p-6">
            <h2 class="pb-4 text-xl font-montserrat font-bold tracking-tight text-brand">YOUR BOOKING</h2>
            <template x-for="(guest, guestIndex) in guests" :key="guestIndex">
                <div class="flex flex-col gap-2.5 py-4 border-b border-gray-200 last:border-b-0">
                    <div class="flex items-center justify-between gap-2.5">
                        <div class="text-md font-bold text-gray-900 truncate" x-text="guest.name || 'Guest Name'"></div>
                    </div>

                    <template x-for="service in getGuestSelectedServices(guest)" :key="service.name + '-' + guestIndex">
                        <div class="flex items-center justify-between gap-2 text-xs text-gray-600">
                            <span class="font-medium text-gray-800" x-text="service.name"></span>
                            <span class="font-semibold text-brand" x-text="formatPrice(service.price)"></span>
                        </div>
                    </template>

                </div>
            </template>
            <div class="flex items-center justify-between gap-2 pt-2 border-t border-gray-200 text-lg font-bold">
                <span class="text-black uppercase">Total</span>
                <span class="font-bold text-brand" x-text="formatPrice(getTotalPrice())"></span>
            </div>
        </div>
    </div>
    <div class="flex items-center justify-center md:justify-end mt-6 w-full">
        <button 
            type="button" 
            class="btn btn-primary w-full md:w-auto rounded-xl flex items-center justify-center gap-2 px-6 py-3 font-semibold transition-all duration-200" 
            @click="finishGuests()"
            onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
        >
            <span class="text-base md:text-sm">Next : Payment</span>
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22C17.523 22 22 17.523 22 12C22 6.477 17.523 2 12 2C6.477 2 2 6.477 2 12C2 17.523 6.477 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M10.5 16.5L15 12L10.5 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>
</section>