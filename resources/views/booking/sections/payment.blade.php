<section aria-labelledby="payment-title">
    <h1 id="payment-title" class="sr-only">Payment</h1>

    <div class="flex flex-col md:flex-row gap-4">
        <div class="w-full md:w-1/3 rounded-xl bg-white border border-gray-200 p-6">
            <span class="block pb-4 text-xl font-montserrat font-bold tracking-tight text-primary">CHOOSE PAYMENT TYPE *</span>
            <div class="flex flex-col gap-3">
                <!-- Opsi 1: DP (Down Payment) -->
                <div @click="paymentType = 'DP'" :class="paymentType === 'DP' ? 'border-primary' : 'border-default bg-neutral-primary-soft'" class="border border-default rounded-2xl p-4">
                    <div class="flex items-start">
                        <input x-model="paymentType" id="bordered-radio-1" type="radio" value="DP" name="bordered-radio" class="w-4 h-4 text-neutral-primary bg-neutral-secondary-medium rounded-full checked:border-brand focus:ring-2 focus:outline-none focus:ring-brand-subtle border border-default appearance-none shrink-0">
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
                <div @click="paymentType = 'Full'" :class="paymentType === 'Full' ? 'border-primary' : 'border-default bg-neutral-primary-soft'" class="border border-default rounded-2xl p-4">
                    <div class="flex items-start">
                        <input x-model="paymentType" checked id="bordered-radio-2" type="radio" value="Full" name="bordered-radio" class="w-4 h-4 text-neutral-primary bg-neutral-secondary-medium rounded-full checked:border-brand focus:ring-2 focus:outline-none focus:ring-brand-subtle border border-default appearance-none shrink-0">
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
            </div>
        </div>
        <div class="w-full md:w-1/3 rounded-xl bg-white border border-gray-200 p-6">
            <span class="block pb-4 text-xl font-montserrat font-bold tracking-tight text-primary">PAYMENT</span>
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
        <button type="button" class="btn rounded-xl border border-gray-200 hover:bg-gray-100" @click="currentStep = 'guest'">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22C6.477 22 2 17.523 2 12C2 6.477 6.477 2 12 2C17.523 2 22 6.477 22 12C22 17.523 17.523 22 12 22Z" stroke="black" stroke-width="2" stroke-linejoin="round"/>
                <path d="M13.5 16.5L9 12L13.5 7.5" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back : Customer
        </button>
    </div>
</section>