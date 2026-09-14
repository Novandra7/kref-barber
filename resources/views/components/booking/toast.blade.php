@if (session('success') || session('error'))
    <style>
        @keyframes toastInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        @keyframes toastOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }
        .animate-toast-in {
            animation: toastInRight 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .animate-toast-out {
            animation: toastOutRight 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>

    <div id="toast-notification" 
         class="animate-toast-in fixed bottom-5 right-5 z-50 flex max-w-xs gap-3 rounded-2xl border-2 border-gray-900 p-4 font-mono shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] {{ session('success') ? 'bg-emerald-100 text-emerald-950' : 'bg-red-100 text-red-950' }}" 
         role="alert">
        
        <!-- Icon Toast -->
        <div class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border-2 border-gray-900 {{ session('success') ? 'bg-emerald-400' : 'bg-red-400' }} shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
            @if (session('success'))
                <svg class="h-5 w-5 stroke-gray-900" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            @else
                <svg class="h-5 w-5 stroke-gray-900" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            @endif
        </div>

        <!-- Pesan Flash -->
        <div class="flex-1 text-xs font-bold leading-tight">
            {{ session('success') ?? session('error') }}
        </div>

        <!-- Tombol Close Flowbite -->
        <button type="button" 
                id="btn-close-toast"
                class="inline-flex h-5 w-5 shrink-0 items-center justify-center border-2 border-gray-900 bg-white text-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] transition hover:bg-gray-100 focus:ring-0" 
                data-dismiss-target="#toast-notification" 
                aria-label="Close">
            <span class="sr-only">Close</span>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <script>
        (function() {
            const toast = document.getElementById('toast-notification');
            const btnClose = document.getElementById('btn-close-toast');
            let timer;

            // Fungsi untuk memicu animasi keluar lalu menghapus elemen
            function closeToastWithAnimation() {
                if (!toast || toast.classList.contains('animate-toast-out')) return;
                
                clearTimeout(timer); // Hentikan timer auto-dismiss jika diklik manual
                toast.classList.remove('animate-toast-in');
                toast.classList.add('animate-toast-out');
                
                setTimeout(function() {
                    toast.remove();
                }, 400); // Sesuaikan dengan durasi animasi CSS (0.4s)
            }

            if (toast) {
                // 1. Auto dismiss otomatis setelah 4 detik
                timer = setTimeout(closeToastWithAnimation, 4000);

                // 2. Click handler pada tombol close
                if (btnClose) {
                    btnClose.addEventListener('click', closeToastWithAnimation);
                }
            }
        })();
    </script>
@endif