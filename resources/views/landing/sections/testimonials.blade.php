@php
$testimonials = [
    ['name' => 'Jerome Bell', 'image' => 'https://randomuser.me/api/portraits/men/1.jpg', 'text' => 'Suka banget sama hasilnya. Potongannya rapi, barbernya ngerti apa yang aku mau, dan prosesnya juga nyaman.', 'rating' => 5],
    ['name' => 'Kristin Watson', 'image' => 'https://randomuser.me/api/portraits/men/2.jpg', 'text' => 'Barbernya ramah, tempatnya nyaman, dan hasil potongannya sesuai banget sama yang aku mau. Pasti balik lagi.', 'rating' => 5],
    ['name' => 'Annette Black', 'image' => 'https://randomuser.me/api/portraits/women/1.jpg', 'text' => 'Pertama kali coba di KREF dan langsung cocok. Potongannya rapi, detailnya bagus, dan pelayanannya juga oke.', 'rating' => 5],
    ['name' => 'Ralph Edwards', 'image' => 'https://randomuser.me/api/portraits/men/3.jpg', 'text' => 'Harga sesuai kualitas, tempatnya bersih, dan barbernya sabar dengerin request potongan.', 'rating' => 5],
    ['name' => 'Cody Fisher', 'image' => 'https://randomuser.me/api/portraits/men/4.jpg', 'text' => 'Udah langganan dari tahun lalu, ga pernah kecewa sama hasilnya.', 'rating' => 4],
    ['name' => 'Esther Howard', 'image' => 'https://randomuser.me/api/portraits/women/2.jpg', 'text' => 'Booking online gampang, ga perlu antre lama. Recommended banget.', 'rating' => 5],
];
@endphp

<section id="testimonial" x-data="carousel()" class="mt-10 w-screen relative left-1/2 -translate-x-1/2 z-10 overflow-hidden bg-black p-8 md:px-15 md:py-31">
    <div
        class="absolute inset-0 bg-cover bg-center opacity-20 rotate-180 pointer-events-none"
        style="background-image: url('{{ asset('images/services-overlay.png') }}')">
    </div>

    <div class="flex flex-col items-center relative z-10">
        <div class="w-full flex justify-between items-center mb-10">
            <button class="p-2 bg-white text-primary rounded-full hover:bg-primary hover:text-white" @click="scroll(-1,'testimonialList')">
                <svg class="w-4 md:w-8" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5.51518 9.64017C5.58548 9.56983 5.62498 9.47446 5.62498 9.37501C5.62498 9.27556 5.58548 9.18019 5.51518 9.10985L2.78032 6.37501H10.125C10.2245 6.37501 10.3198 6.3355 10.3902 6.26517C10.4605 6.19485 10.5 6.09947 10.5 6.00001C10.5 5.90055 10.4605 5.80517 10.3902 5.73484C10.3198 5.66452 10.2245 5.62501 10.125 5.62501H2.78032L5.51518 2.89017C5.55 2.85534 5.57763 2.814 5.59647 2.7685C5.61531 2.723 5.62501 2.67424 5.62501 2.62499C5.62501 2.57575 5.6153 2.52698 5.59646 2.48148C5.57761 2.43599 5.54998 2.39465 5.51516 2.35983C5.48034 2.325 5.43899 2.29738 5.39349 2.27854C5.348 2.2597 5.29923 2.25 5.24998 2.25C5.20074 2.25 5.15197 2.2597 5.10648 2.27855C5.06098 2.2974 5.01964 2.32502 4.98482 2.35985L1.60982 5.73485L1.60638 5.73865C1.59898 5.74625 1.59179 5.75405 1.58505 5.76225L1.57449 5.77639C1.57069 5.78147 1.56674 5.78646 1.56319 5.79175L1.55303 5.80848C1.55011 5.81338 1.54703 5.81816 1.54432 5.82322L1.53604 5.84041C1.5335 5.84577 1.53084 5.85103 1.52856 5.8565L1.52241 5.87335C1.52028 5.87925 1.51801 5.88511 1.51618 5.89115L1.51181 5.90814C1.51025 5.91432 1.50851 5.92045 1.50727 5.92675L1.50426 5.94645C1.50347 5.95199 1.50241 5.95742 1.50187 5.963C1.50065 5.97531 1.5 5.98765 1.5 6.00001C1.5 6.01237 1.50065 6.02471 1.50187 6.03702L1.50426 6.05357C1.50519 6.06013 1.50597 6.06673 1.50727 6.07327L1.51181 6.09188C1.51324 6.09756 1.51449 6.10326 1.51618 6.10887L1.52241 6.12667C1.52442 6.1323 1.52627 6.13798 1.52856 6.14352L1.53604 6.15961C1.53875 6.16535 1.54131 6.17117 1.54432 6.1768L1.55303 6.19154C1.55637 6.19714 1.55954 6.20282 1.56319 6.20827L1.57449 6.22363C1.578 6.22834 1.58129 6.23319 1.58505 6.23777C1.59191 6.24613 1.59922 6.25407 1.60677 6.2618L1.60982 6.26517L4.98482 9.64017C5.01964 9.67499 5.06098 9.70262 5.10648 9.72147C5.15198 9.74031 5.20075 9.75001 5.25 9.75001C5.29925 9.75001 5.34802 9.74031 5.39352 9.72147C5.43902 9.70262 5.48036 9.67499 5.51518 9.64017Z" fill="currentColor"/>
                </svg>
            </button>
            <div class="inline-block">
                <h2 class="text-white text-3xl md:text-6xl font-league">WHAT CLIENT SAYS</h2>
                <div class="h-1 mt-1 bg-white w-full"></div>
            </div>
            <button class="p-2 bg-white text-primary rounded-full hover:bg-primary hover:text-white" @click="scroll(1,'testimonialList')">
                <svg class="w-4 md:w-8" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.48482 9.64017C6.41452 9.56983 6.37502 9.47446 6.37502 9.37501C6.37502 9.27556 6.41452 9.18019 6.48482 9.10985L9.21968 6.37501H1.875C1.77554 6.37501 1.68016 6.3355 1.60984 6.26517C1.53951 6.19485 1.5 6.09947 1.5 6.00001C1.5 5.90055 1.53951 5.80517 1.60984 5.73484C1.68016 5.66452 1.77554 5.62501 1.875 5.62501H9.21968L6.48482 2.89017C6.45 2.85534 6.42237 2.814 6.40353 2.7685C6.38469 2.723 6.37499 2.67424 6.37499 2.62499C6.37499 2.57575 6.3847 2.52698 6.40354 2.48148C6.42239 2.43599 6.45002 2.39465 6.48484 2.35983C6.51966 2.325 6.56101 2.29738 6.60651 2.27854C6.652 2.2597 6.70077 2.25 6.75002 2.25C6.79926 2.25 6.84803 2.2597 6.89352 2.27855C6.93902 2.2974 6.98036 2.32502 7.01518 2.35985L10.3902 5.73485L10.3936 5.73865C10.401 5.74625 10.4082 5.75405 10.4149 5.76225L10.4255 5.77639C10.4293 5.78147 10.4333 5.78646 10.4368 5.79175L10.447 5.80848C10.4499 5.81338 10.453 5.81816 10.4557 5.82322L10.464 5.84041C10.4665 5.84577 10.4692 5.85103 10.4714 5.8565L10.4776 5.87335C10.4797 5.87925 10.482 5.88511 10.4838 5.89115L10.4882 5.90814C10.4897 5.91432 10.4915 5.92045 10.4927 5.92675L10.4957 5.94645C10.4965 5.95199 10.4976 5.95742 10.4981 5.963C10.4993 5.97531 10.5 5.98765 10.5 6.00001C10.5 6.01237 10.4993 6.02471 10.4981 6.03702L10.4957 6.05357C10.4948 6.06013 10.494 6.06673 10.4927 6.07327L10.4882 6.09188C10.4868 6.09756 10.4855 6.10326 10.4838 6.10887L10.4776 6.12667C10.4756 6.1323 10.4737 6.13798 10.4714 6.14352L10.464 6.15961C10.4613 6.16535 10.4587 6.17117 10.4557 6.1768L10.447 6.19154C10.4436 6.19714 10.4405 6.20282 10.4368 6.20827L10.4255 6.22363C10.422 6.22834 10.4187 6.23319 10.4149 6.23777C10.4081 6.24613 10.4008 6.25407 10.3932 6.2618L10.3902 6.26517L7.01518 9.64017C6.98036 9.67499 6.93902 9.70262 6.89352 9.72147C6.84802 9.74031 6.79925 9.75001 6.75 9.75001C6.70075 9.75001 6.65198 9.74031 6.60648 9.72147C6.56098 9.70262 6.51964 9.67499 6.48482 9.64017Z" fill="currentColor"/>
                </svg>
            </button>
        </div>

        <div x-ref="testimonialList" class="w-full flex gap-5 pt-5 md:pb-5 overflow-x-auto scroll-smooth snap-x snap-mandatory scrollbar-hide">
            @foreach ($testimonials as $testimonial)
                <div class="snap-start snap-always shrink-0 w-full md:w-[calc(33.333%-0.833rem)] min-w-0">
                    @include("components.landing.testi-card", ['testimonial' => $testimonial])
                </div>
            @endforeach
        </div>
    </div>
</section>