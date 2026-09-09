@extends('admin.auth.layout')

@section('title', 'Login')

@section('content')
    <div class="relative mx-auto w-full max-w-md">
        <!-- Vintage Decorative Badge (Pojok Kanan Atas) -->
        <div class="absolute -top-3 -right-3 z-10 hidden rotate-6 rounded-md border-2 border-gray-900 bg-amber-300 px-3 py-1 text-xs font-black uppercase tracking-widest text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] sm:block">
            Admin only
        </div>

        <form method="POST" action="{{ route('login') }}" class="relative overflow-hidden rounded-2xl border-2 border-gray-900 bg-[#FAF8F5] p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] transition-all sm:p-8">
            @csrf

            <!-- Header Section Retro Style -->
            <div class="mb-6 flex items-start justify-between gap-4 border-b-2 border-dashed border-gray-300 pb-4">
                <div>
                    <span class="inline-block rounded-full border border-gray-900 bg-brand/10 px-3 py-0.5 text-xs font-bold uppercase tracking-wider text-brand">
                        Administration Panel
                    </span>
                    <h3 class="mt-2 font-league text-3xl font-black uppercase tracking-tight text-gray-900">
                        Control Panel
                    </h3>
                    <p class="text-xs text-gray-500">Silakan masukkan kredensial untuk melanjutkan.</p>
                </div>

                <!-- Logo Section -->
                <div class="shrink-0 rounded-xl border-2 border-gray-900 bg-white p-2 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                    <img src="{{ asset('images/Logo.svg') }}" alt="Logo" class="h-20 w-auto object-contain">
                </div>
            </div>

            <div class="space-y-5">
                <!-- Input Email -->
                <div>
                    <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-700">
                        Alamat Email
                    </label>
                    <div class="relative">
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                            class="w-full rounded-xl border-2 border-gray-900 bg-white px-4 py-3 text-sm font-medium text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] outline-none transition placeholder:text-gray-400 focus:bg-amber-50/50 focus:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] @error('email') @enderror"
                            placeholder="admin@kref.com">
                    </div>
                    @error('email')
                        <p class="mt-2 text-xs font-bold text-red-600">⚠️ {{ $message }}</p>
                    @enderror
                </div>

                <!-- Input Password -->
                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                            Password
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-gray-500 underline decoration-gray-400 underline-offset-4 hover:text-brand">
                                Lupa password?
                            </a>
                        @endif
                    </div>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                        class="w-full rounded-xl border-2 border-gray-900 bg-white px-4 py-3 text-sm font-medium text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] outline-none transition placeholder:text-gray-400 focus:bg-amber-50/50 focus:shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] @error('password') @enderror"
                        placeholder="••••••••••••">
                    @error('password')
                        <p class="mt-2 text-xs font-bold text-red-600">⚠️ {{ $message }}</p>
                    @enderror
                </div>

                <!-- Checkbox Remember Me -->
                <div class="flex items-center justify-between pt-1">
                    <label class="group flex cursor-pointer items-center gap-2.5 text-xs font-bold text-gray-700 select-none">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                            class="h-4 w-4 rounded border-2 border-gray-900 text-brand focus:ring-0 focus:ring-offset-0">
                        <span class="group-hover:text-brand">Ingat Sesi Saya</span>
                    </label>
                </div>

                <!-- Alert Rate Limit -->
                @if ($errors->has('email') && str_contains($errors->first('email'), 'Too Many'))
                    <div class="rounded-lg border-2 border-red-900 bg-red-100 p-3 text-xs font-bold text-red-800">
                        {{ $errors->first('email') }}
                    </div>
                @endif

                <!-- Submit Button -->
                <button type="submit" 
                    class="group relative w-full overflow-hidden rounded-xl border-2 border-gray-900 bg-brand px-4 py-3.5 text-sm font-black uppercase tracking-wider text-white shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] transition-all hover:-translate-x-0.5 hover:-translate-y-0.5 hover:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                    <span class="relative z-10 flex items-center justify-center gap-2">
                        Masuk Sistem
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </span>
                </button>
            </div>

            <!-- Footer Stamp -->
            <div class="mt-6 text-center">
                <p class="text-2xs font-mono uppercase tracking-widest text-gray-400">
                    Encrypted Session &bull; Secured System
                </p>
            </div>
        </form>
    </div>
@endsection