<section aria-labelledby="payment-title">
    <h1 id="payment-title" class="sr-only">Payment</h1>

    <div class="flex flex-col md:flex-row gap-4">
        <div class="w-full md:w-1/3 rounded-xl bg-white border border-gray-200 p-6">
            <span class="block pb-4 text-xl font-montserrat font-bold tracking-tight text-primary">CHOOSE PAYMENT TYPE *</span>
            <div class="flex flex-col gap-3">
                <!-- Opsi 1: DP (Down Payment) -->
                <div @click="if (!paymentTypeConfirmed && paymentData?.status !== 'paid') paymentType = 'DP'" :class="paymentType === 'DP' ? 'border-primary' : 'border-default bg-neutral-primary-soft'" class="border border-default rounded-2xl p-4">
                    <div class="flex items-start">
                        <input x-model="paymentType" :disabled="paymentTypeConfirmed || paymentData?.status === 'paid'" id="bordered-radio-1" type="radio" value="DP" name="bordered-radio" class="w-4 h-4 text-neutral-primary bg-neutral-secondary-medium rounded-full checked:border-brand focus:ring-2 focus:outline-none focus:ring-brand-subtle border border-default appearance-none shrink-0">
                        <label for="bordered-radio-1" class="w-full select-none ms-3 cursor-pointer flex flex-col">
                            <span class="text-lg font-bold text-black leading-none mb-2">DP (Down Payment)</span>
                            <span class="text-2xs text-gray-500 font-normal">Pay 40.000 now, the rest at shop</span>
                        </label>
                    </div>
                    <div :class="paymentType === 'DP' ? 'bg-primary/10' : 'bg-neutral-100'" class="flex flex-col items-center justify-center mt-3 rounded-[10px] py-2">
                        <span class="text-sm text-black mb-2">you will pay now</span>
                        <span class="text-[20px] font-semibold text-black">40.000</span>
                    </div>
                </div>

                <!-- Opsi 2: Full Payment (Sudah Disamakan Structurnya) -->
                <div @click="if (!paymentTypeConfirmed && paymentData?.status !== 'paid') paymentType = 'Full'" :class="paymentType === 'Full' ? 'border-primary' : 'border-default bg-neutral-primary-soft'" class="border border-default rounded-2xl p-4">
                    <div class="flex items-start">
                        <input x-model="paymentType" :disabled="paymentTypeConfirmed || paymentData?.status === 'paid'" checked id="bordered-radio-2" type="radio" value="Full" name="bordered-radio" class="w-4 h-4 text-neutral-primary bg-neutral-secondary-medium rounded-full checked:border-brand focus:ring-2 focus:outline-none focus:ring-brand-subtle border border-default appearance-none shrink-0">
                        <label for="bordered-radio-2" class="w-full select-none ms-3 cursor-pointer flex flex-col">
                            <span class="text-lg font-bold text-black leading-none mb-2">Full Payment</span>
                            <span class="text-2xs text-gray-500 font-normal">Pay the total amount now</span>
                        </label>
                    </div>
                    <div :class="paymentType === 'Full' ? 'bg-primary/10' : 'bg-neutral-100'" class="flex flex-col items-center justify-center mt-3 rounded-[10px] py-2">
                        <span class="text-sm text-black mb-2">you will pay now</span>
                        <span class="text-[20px] font-semibold text-black" x-text="formatPrice(getTotalPrice())"></span>
                    </div>
                </div>
               <button
                    class="submit btn bg-brand w-full rounded-xl text-white py-3 font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex justify-center items-center gap-2"
                    @click="createPayment()"
                    :disabled="!paymentType || paymentState === 'loading' || paymentState === 'ready' || paymentState === 'paid' || paymentData?.status === 'paid'"
                >
                    <!-- Saat Belum Diklik / Idle -->
                    <span x-show="paymentState !== 'loading' && paymentState !== 'ready'">Confirm Payment Type</span>

                    <!-- Saat Proses Simpan Booking & Generate QRIS -->
                    <span x-show="paymentState === 'loading'">Processing Booking...</span>

                    <!-- Saat Berhasil & QRIS Sudah Siap -->
                    <span x-show="paymentState === 'ready'">Payment Type Confirmed ✓</span>
                </button>
            </div>
        </div>
        <div class="w-full md:w-1/3 rounded-xl bg-white border border-gray-200 p-6">
            <span class="block pb-4 text-xl font-montserrat font-bold tracking-tight text-primary">PAYMENT</span>
            <div class="flex flex-col items-center gap-4 text-center w-full">

                <!-- Placeholder saat QRIS Belum Di-generate (Presisi di Tengah) -->
                <div x-show="!paymentData?.qrContent" class="flex flex-col py-5 lg:pt-32 items-center justify-center w-full text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-primary mb-3">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />
                        </svg>
                    </div>
                    <p class="font-semibold text-gray-800">QRIS Payment Code</p>
                    <p class="text-xs text-gray-500 mt-1 max-w-xs">Select your payment type above and click confirm to generate the QRIS code.</p>
                    
                    <!-- Error Message -->
                    <p x-show="paymentError" x-text="paymentError" class="text-sm text-red-600"></p>
                </div>
                

                <!-- QRIS Display -->
                <template x-if="paymentData?.qrContent && paymentData?.status !== 'paid'">
                    <div class="flex flex-col items-center justify-center gap-3 w-full text-center">
                        <div class="flex gap-3 items-center">
                            <img src="{{ asset("images/qris.png") }}" alt="QRIS Payment Code" class="h-10 w-auto object-contain">
                            <p class="font-medium text-start">QR Code Standar<br>Pembayaran Nasional</p>
                        </div>
                        <div class="w-full py-3 flex flex-col items-center justify-center border rounded-xl border-gray-200">
                            <canvas x-ref="qrisCanvas" aria-label="DOKU QRIS payment code" class="h-56 w-56"></canvas>
                             <!-- Payment Link -->
                            <a
                                x-show="paymentData?.paymentUrl"
                                :href="paymentData?.paymentUrl"
                                target="_blank"
                                rel="noopener"
                                class="text-sm font-semibold text-primary underline mt-2">
                                View booking payment page
                            </a>
                        </div>

                        <div class="flex flex-col items-center justify-center bg-brand/10 rounded-xl w-full py-3">
                            <span>Valid Until:</span>
                            <strong class="font-montserrat" x-text="paymentData?.expiresAt"></strong>
                        </div>
                    </div>
                </template>

                <div x-show="paymentData?.status === 'paid'" class="w-full flex flex-col justify-center items-center rounded-xl bg-green-50 px-4 py-4 text-center text-green-700">
                    <svg class="w-18 h-18" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M91.2006 47.4678C93.0094 37.7103 106.991 37.7103 108.799 47.4678C110.093 54.4476 118.609 57.2147 123.759 52.3284C130.957 45.4976 142.268 53.7156 137.996 62.6727C134.941 69.08 140.204 76.3243 147.242 75.398C157.081 74.103 161.401 87.3999 152.68 92.1354C146.442 95.5228 146.442 104.477 152.68 107.865C161.401 112.6 157.081 125.897 147.242 124.602C140.204 123.676 134.941 130.92 137.996 137.327C142.268 146.284 130.957 154.502 123.759 147.672C118.609 142.785 110.093 145.552 108.799 152.532C106.991 162.29 93.0094 162.29 91.2006 152.532C89.9067 145.552 81.3906 142.785 76.2412 147.672C69.0425 154.502 57.7316 146.284 62.0035 137.327C65.0594 130.92 59.7961 123.676 52.7581 124.602C42.9192 125.897 38.5988 112.6 47.3198 107.865C53.5581 104.477 53.5581 95.5228 47.3198 92.1354C38.5988 87.3999 42.9192 74.103 52.7581 75.398C59.7961 76.3243 65.0594 69.08 62.0035 62.6727C57.7316 53.7156 69.0425 45.4976 76.2412 52.3284C81.3906 57.2147 89.9067 54.4476 91.2006 47.4678Z" fill="#02740F"/>
                        <path d="M92.14 110.106L119.331 82.9156C119.972 82.274 120.721 81.9531 121.576 81.9531C122.432 81.9531 123.181 82.274 123.822 82.9156C124.464 83.5573 124.785 84.3198 124.785 85.2032C124.785 86.0865 124.464 86.848 123.822 87.4875L94.3858 117.004C93.7442 117.646 92.9956 117.967 92.14 117.967C91.2844 117.967 90.5358 117.646 89.8942 117.004L76.0983 103.208C75.4567 102.567 75.1487 101.805 75.1743 100.924C75.2 100.043 75.5347 99.2803 76.1785 98.6365C76.8223 97.9927 77.5849 97.6718 78.4661 97.674C79.3473 97.6761 80.1087 97.9969 80.7504 98.6365L92.14 110.106Z" fill="white"/>
                    </svg>
                    <p class="font-semibold">Payment received successfully</p>
                    <hr class="my-2 w-full border-gray-300">
                    <div class="flex flex-col px-5 pt-3 w-full justify-center gap-1">
                        <div class="flex items-center justify-between text-gray-800">
                            <p>Booking ID :</p>
                            <span x-text="paymentData?.doku_data?.order.invoice_number ?? 'N/A'"></span>
                        </div>
                        <div class="flex items-center justify-between text-gray-800">
                            <p>Source of Funds :</p>
                            <span x-text="paymentData?.doku_data?.issuer.name ?? 'N/A'"></span>
                        </div>
                        <div class="flex items-center justify-between text-gray-800">
                            <p>Status :</p>
                            <span class="font-bold text-green-700" x-text="paymentData?.status ?? 'N/A'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
    <div class="flex justify-between mt-4">
        <button 
            type="button" 
            class="btn rounded-xl border border-gray-200 hover:bg-gray-100"
            :disabled="paymentData?.status === 'paid'" 
            @click="currentStep = 'guest'"
        >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22C6.477 22 2 17.523 2 12C2 6.477 6.477 2 12 2C17.523 2 22 6.477 22 12C22 17.523 17.523 22 12 22Z" stroke="black" stroke-width="2" stroke-linejoin="round"/>
                <path d="M13.5 16.5L9 12L13.5 7.5" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back : Customer
        </button>
        <button
            type="button"
            class="btn rounded-xl bg-brand text-white disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="paymentData?.status !== 'paid'"
            @click="window.location.href = '{{ route('landing') }}'"
        >
            Back Home
            <svg class="size-4 sm:size-5 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22C17.523 22 22 17.523 22 12C22 6.477 17.523 2 12 2C6.477 2 2 6.477 2 12C2 17.523 6.477 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M10.5 16.5L15 12L10.5 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>
</section>