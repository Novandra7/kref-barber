@extends('admin.auth.layout')

@section('title', 'Lupa Password')
@section('subtitle', 'Pulihkan akses ke panel administrasi')

@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
        @csrf
        <div class="space-y-5">
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-gray-700">Email admin</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                    placeholder="admin@example.com">
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-brand-strong focus:outline-none focus:ring-2 focus:ring-primary/30">
                Kirim Link Reset Password
            </button>

            <a href="{{ route('login') }}" class="block text-center text-sm font-semibold text-primary hover:underline">Kembali ke login</a>
        </div>
    </form>
@endsection
