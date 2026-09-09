@extends('admin.auth.layout')

@section('title', 'Reset Password')
@section('subtitle', 'Buat password baru untuk akun admin')

@section('content')
    <form method="POST" action="{{ route('password.update') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="space-y-5">
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-gray-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-gray-700">Password baru</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-gray-700">Konfirmasi password baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
            </div>

            <button type="submit" class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-brand-strong focus:outline-none focus:ring-2 focus:ring-primary/30">
                Simpan Password Baru
            </button>
        </div>
    </form>
@endsection
