<section id="services" class="md:px-0 mb-5">
    <div class="relative z-10 flex w-full flex-col items-center justify-center gap-3 overflow-hidden rounded-lg bg-primary p-8">
        <div
            class="absolute inset-0 bg-cover bg-center opacity-20"
            style="background-image: url('{{ asset('images/services-overlay.png') }}')">
        </div>
        
        {{-- Title --}}
        <div class="relative z-10 flex w-fit flex-col items-center">
            <h2 class="font-league text-5xl text-white md:text-7xl">OUR SERVICES</h2>
            <div class="mt-3 h-1.5 w-full bg-white"></div>
        </div>

        {{-- Services Container --}}
        <div class="relative z-10 mt-10 flex w-full flex-col justify-evenly gap-10 md:flex-row md:gap-8">
            @foreach ($services as $category => $items)
                <div class="flex w-full flex-col gap-2 md:w-1/3">
                    {{-- Header Kategori & Icon --}}
                    <div class="mb-2 flex items-center gap-3">
                        @if (strtoupper($category) === 'HAIRCUT')
                            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0">
                                <path d="M24.5333 4.26667H20.2666C19.2 4.26667 19.2 6.4 20.2666 6.4H24.5333C25.6 6.4 25.6 8.53333 24.5333 8.53333H20.2666C19.2 8.53333 19.2 10.6667 20.2666 10.6667H24.5333C25.6 10.6667 25.6 12.8 24.5333 12.8H20.2666C19.2 12.8 19.2 14.9333 20.2666 14.9333H24.5333C25.6 14.9333 25.6 17.0667 24.5333 17.0667H20.2666C19.2 17.0667 19.2 19.2 20.2666 19.2H24.5333C25.6 19.2 25.6 21.3333 24.5333 21.3333H20.2666C19.2 21.3333 19.2 23.4667 20.2666 23.4667H24.5333C25.6 25.6 25.6 27.7333 24.5333 27.7333H20.2666C19.2 27.7333 19.2 29.8667 20.2666 29.8667H28.8C29.8666 29.8667 29.8666 27.7333 29.8666 27.7333V2.13333C29.8666 2.13333 29.8666 0 28.8 0H20.2666C19.2 0 19.2 2.13333 20.2666 2.13333H24.5333C25.6 2.13333 25.6 4.26667 24.5333 4.26667ZM12.5866 21.696C14.9333 20.2667 17.0666 22.464 17.0666 24.5333C17.0666 25.8347 16.5333 26.9653 15.4026 27.4773C15.1253 28.8853 15.4666 29.2693 15.808 29.9307C16.1493 30.6133 15.296 31.4667 14.5706 30.8053C13.952 30.2293 13.632 28.8 13.6533 27.7973C12.0746 27.712 10.6666 26.368 10.6666 24.5333L10.1333 16H9.06663L8.5333 24.5333C8.5333 26.176 7.23197 27.7333 5.3333 27.7333C3.45597 27.7333 2.1333 26.3467 2.1333 24.5333C2.1333 22.5067 4.26663 20.2667 6.76263 21.696C6.9333 21.8667 8.5333 0 9.59997 0C10.6666 0 12.5866 21.696 12.5866 21.696ZM5.3333 22.9333C4.26663 22.9333 3.7333 23.4667 3.7333 24.5333C3.7333 25.6 4.26663 26.1333 5.3333 26.1333C6.39997 26.1333 6.9333 25.0667 6.9333 24.5333C6.9333 23.4667 6.39997 22.9333 5.3333 22.9333ZM13.8666 22.9333C12.8 22.9333 12.2666 23.4667 12.2666 24.5333C12.2666 25.0667 12.8 26.1333 13.8666 26.1333C14.9333 26.1333 15.4666 25.6 15.4666 24.5333C15.4666 23.4667 14.9333 22.9333 13.8666 22.9333Z" fill="white"/>
                            </svg>
                        @elseif (strtoupper($category) === 'CHEMICALS')
                            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0">
                                <path d="M12.8 3.19998V7.19998H19.2V5.99998H22.8C23.465 5.99998 24 5.46498 24 4.79998C24 4.13498 23.465 3.59998 22.8 3.59998H19.2V3.19998C19.2 2.31498 18.485 1.59998 17.6 1.59998H14.4C13.515 1.59998 12.8 2.31498 12.8 3.19998ZM11.2 9.59998C9.435 9.59998 8 11.035 8 12.8V25.6C8 27.365 9.435 28.8 11.2 28.8H20.8C22.565 28.8 24 27.365 24 25.6V12.8C24 11.035 22.565 9.59998 20.8 9.59998H11.2ZM19.2 20.4C19.2 22.165 17.765 23.2 16 23.2C14.235 23.2 12.8 22.165 12.8 20.4C12.8 18.775 14.65 16.355 15.345 15.505C15.505 15.31 15.75 15.2 16 15.2C16.25 15.2 16.495 15.31 16.655 15.505C17.35 16.355 19.2 18.775 19.2 20.4Z" fill="white"/>
                            </svg>
                        @elseif (strtoupper($category) === 'TREATMENT')
                            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" class="shrink-0">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M21.3332 16.9133L17.0332 17.6733L17.0265 17.6666L18.4265 23.26C18.5532 23.76 18.4465 24.28 18.1265 24.6866C17.8065 25.0933 17.3332 25.3266 16.8132 25.3266H16.6665V25.66C16.6647 26.6319 16.2779 27.5635 15.5906 28.2507C14.9034 28.938 13.9718 29.3249 12.9998 29.3266H6.6665V27.3266H12.9998C13.9198 27.3266 14.6665 26.58 14.6665 25.66V25.3266H14.1865C13.4265 25.3266 12.7598 24.8066 12.5732 24.0666L11.2132 18.64C11.0332 18.6533 10.8465 18.6666 10.6665 18.6666C6.25317 18.6666 2.6665 15.08 2.6665 10.6666C2.6665 6.25331 6.25317 2.66664 10.6665 2.66664C11.1341 2.66575 11.5985 2.70797 12.0598 2.79331H12.0998L21.3332 4.79331V16.9133ZM23.3332 5.22664L28.0198 6.23997V6.24664C28.7798 6.40664 29.3332 6.40664 29.3332 7.87331V14.1066C29.3327 14.4986 29.1941 14.8779 28.9418 15.1779C28.6894 15.4778 28.3393 15.6792 27.9532 15.7466L23.3332 16.56V5.22664Z" fill="white"/>
                            </svg>
                        @endif
                        <h3 class="font-league text-2xl text-white md:text-3xl">{{ strtoupper($category) }}</h3>
                    </div>

                    {{-- List Layanan --}}
                    @foreach ($items as $item)
                        <div class="relative flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-1.5">
                                @if ($item->description)
                                    {{-- Nama layanan + ikon 'i' dibungkus dalam satu tautan --}}
                                    <a
                                        href="{{ $item->description }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="Lihat contoh {{ $item->name }} di Instagram"
                                        class="group flex min-w-0 items-center gap-1.5 focus:outline-none"
                                    >
                                        <p class="truncate text-sm text-white transition-colors group-hover:underline">
                                            {{ $item->name }}
                                        </p>
                                        <span
                                            class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full border border-white/80 text-2xs font-bold leading-none text-white transition-colors group-hover:bg-white group-hover:text-primary group-focus:ring-2 group-focus:ring-white/60"
                                        >
                                            i
                                        </span>
                                    </a>
                                @else
                                    {{-- Tampilan biasa jika tidak ada deskripsi/link --}}
                                    <p class="truncate text-sm text-white">{{ $item->name }}</p>
                                @endif
                            </div>
                            <p class="shrink-0 text-white">{{ $item->formattedPrice }}</p>
                        </div>
                    @endforeach

                    @if (strtoupper($category) === 'HAIRCUT')
                        <p class="mt-2 text-sm text-white/80">*haircut sudah termasuk potong, cuci, styling, hair tonic, refreshing towel, dan pijat*</p>
                    @endif
                </div>

                {{-- Divider --}}
                @if (!$loop->last)
                    <div class="h-px w-full bg-white/70 md:h-auto md:w-px"></div>
                @endif
            @endforeach
        </div>
    </div>
</section>