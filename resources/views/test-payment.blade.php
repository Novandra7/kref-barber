<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Coba DOKU QRIS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 p-6 min-h-screen flex items-center justify-center font-sans">

    <div class="w-full max-w-md bg-white rounded-xl shadow-md border border-gray-100 p-6 space-y-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Uji Coba Generate QRIS DOKU</h2>
            <p class="text-xs text-gray-500 mt-1">Masukkan nominal untuk membuat QRIS dinamis.</p>
        </div>

        {{-- Form Input Nominal --}}
        <form action="{{ route('doku-test.generate') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="amount" class="block mb-1.5 text-sm font-medium text-gray-700">Nominal Pembayaran (Rp) <span class="text-red-500">*</span></label>
                <input 
                    type="number" 
                    id="amount" 
                    name="amount" 
                    placeholder="Contoh: 50000" 
                    min="1000"
                    value="{{ old('amount', $qrisResult['amount'] ?? '') }}"
                    required 
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand"
                >
                @error('amount')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 transition">
                Generate QRIS
            </button>
        </form>

        {{-- Hasil Display QRIS --}}
        @if ($qrisResult)
            <div class="border-t border-gray-100 pt-6 space-y-4 text-center">
                <div class="inline-block bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full text-xs font-semibold">
                    {{ $qrisResult['responseMessage'] ?? $qrisResult['response_message'] ?? 'QRIS Berhasil Dibuat' }}
                </div>

                <div class="flex justify-center p-4 bg-white rounded-lg border border-gray-200 inline-block">
                    {{-- Render QR Code dari string qrContent --}}
                    @php
                        $qrContent = $qrisResult['qrContent'] ?? $qrisResult['qr_content'] ?? '';
                    @endphp

                    @if ($qrContent)
                        {!! QrCode::size(220)->generate($qrContent) !!}
                    @else
                        <p class="text-xs text-red-500">Field qrContent tidak ditemukan dalam respon API.</p>
                    @endif
                </div>

                <div class="space-y-1 text-left bg-gray-50 p-3.5 rounded-lg text-xs font-mono text-gray-600">
                    <p><strong class="text-gray-900">Partner Ref No:</strong> {{ $qrisResult['partnerReferenceNo'] ?? $qrisResult['partner_reference_no'] ?? '-' }}</p>
                    <p><strong class="text-gray-900">Total Nominal:</strong> Rp {{ number_format($qrisResult['amount'] ?? 0, 0, ',', '.') }}</p>
                    @if (isset($qrisResult['additionalInfo']['validityPeriod']) || isset($qrisResult['validity_period']))
                        <p><strong class="text-gray-900">Berlaku Hingga:</strong> {{ \Carbon\Carbon::parse($qrisResult['additionalInfo']['validityPeriod'] ?? $qrisResult['validity_period'])->format('d M Y, H:i:s T') }}</p>
                    @endif
                </div>
            </div>
        @endif
    </div>

</body>
</html>