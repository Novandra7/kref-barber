<div class="flex items-center justify-center w-full max-w-2xl px-4 md:pr-8 mt-4 md:mt-9">
    <!-- STEP 1 -->
    <div class="flex flex-col items-center shrink-0">
        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg shadow-sm transition-all"
            :class="(currentStep >= 1 || currentStep === 'guest') ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500'">
            
            <!-- Tampilkan Centang Jika currentStep > 1 -->
            <template x-if="currentStep > 1 || currentStep === 'guest'">
                <svg class="w-6 h-6 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </template>
            
            <!-- Tampilkan Angka 1 Jika Masih Di Step 1 -->
            <template x-if="currentStep <= 1">
                <span>1</span>
            </template>
        </div>
        <span class="mt-2 text-xs sm:text-sm font-semibold text-center whitespace-nowrap"
            :class="(currentStep >= 1 || currentStep === 'guest') ? 'text-primary' : 'text-gray-400'">
            Barbers & Time
        </span>
    </div>

    <div class="flex-1 h-0.5 mt-3 mx-2 sm:mx-6"
        :class="currentStep > 1 ? 'bg-primary' : 'bg-gray-200'"></div>

    <!-- STEP 2 -->
    <div class="flex flex-col items-center shrink-0">
        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg shadow-sm transition-all"
            :class="(currentStep >= 2 || currentStep === 'guest')? 'bg-primary text-white' : 'bg-gray-200 text-gray-500'">
            
            <!-- Tampilkan Centang Jika currentStep > 2 -->
            <template x-if="currentStep > 2">
                <svg class="w-6 h-6 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </template>

            <!-- Tampilkan Angka 2 Jika Belum Melewati Step 2 -->
            <template x-if="currentStep <= 2 || currentStep === 'guest'">
                <span>2</span>
            </template>
        </div>
        <span class="mt-2 text-xs sm:text-sm font-semibold text-center whitespace-nowrap"
            :class="currentStep >= 2 || currentStep === 'guest' ? 'text-primary' : 'text-gray-400'">
            Confirm Details
        </span>
    </div>

    <div class="flex-1 h-0.5 mx-2 sm:mx-6 mt-3"
        :class="currentStep > 2 ? 'bg-primary' : 'bg-gray-200'"></div>

    <!-- STEP 3 -->
    <div class="flex flex-col items-center shrink-0">
        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg shadow-sm transition-all"
            :class="currentStep >= 3 ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500'">
            
            <!-- Jika Step 3 Selesai (currentStep > 3) -->
            <template x-if="currentStep > 3">
                <svg class="w-6 h-6 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </template>

            <!-- Tampilkan Angka 3 -->
            <template x-if="currentStep <= 3 || currentStep === 'guest'">
                <span>3</span>
            </template>
        </div>
        <span class="mt-2 text-xs sm:text-sm font-semibold text-center whitespace-nowrap"
            :class="currentStep >= 3 ? 'text-primary' : 'text-gray-400'">
            Payment
        </span>
    </div>
</div>