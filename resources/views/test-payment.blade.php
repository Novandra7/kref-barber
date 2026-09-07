<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Coba DOKU QRIS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 p-6 font-sans">
    <div class="mx-auto w-full max-w-md space-y-6">

        {{-- Generate --}}
        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-md">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900">Uji Coba DOKU QRIS</h2>
                <p class="mt-1 text-xs text-gray-500">Generate QRIS dinamis dan cek status pembayarannya.</p>
            </div>

            <form action="{{ route('doku-test.generate') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="amount" class="mb-1.5 block text-sm font-medium text-gray-700">Nominal Pembayaran</label>
                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        min="1000"
                        value="{{ old('amount', $qrisResult['amount'] ?? '') }}"
                        placeholder="Contoh: 50000"
                        required
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-brand focus:ring-brand"
                    >
                    @error('amount')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="w-full rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                    Generate QRIS
                </button>
            </form>
        </div>

        @php
            $latestTransactionStatus = (string) data_get($qrisResult, 'queryResult.latestTransactionStatus', '');
            $isPaymentSuccessful = in_array(strtoupper($latestTransactionStatus), ['00', 'SUCCESS'], true);
        @endphp

        {{-- QRIS tetap ditampilkan selama pembayaran belum sukses --}}
        @if (!empty($qrisResult['qrContent']) && !$isPaymentSuccessful)
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-md">
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-gray-900">QRIS Pembayaran</h3>
                    <p class="mt-1 text-xs text-gray-500">Scan QR code berikut menggunakan aplikasi pembayaran.</p>
                </div>

                <div class="flex justify-center rounded-lg border border-gray-200 bg-white p-4">
                    {!! QrCode::size(220)->generate($qrisResult['qrContent']) !!}
                </div>

                <div class="mt-4 space-y-1 rounded-lg bg-gray-50 p-3.5 text-xs font-mono text-gray-600">
                    <p><strong class="text-gray-900">Reference No:</strong> {{ $qrisResult['referenceNo'] ?? '-' }}</p>
                    <p><strong class="text-gray-900">Partner Ref No:</strong> {{ $qrisResult['partnerReferenceNo'] ?? '-' }}</p>
                    <p><strong class="text-gray-900">Nominal:</strong> Rp {{ number_format($qrisResult['amount'] ?? 0, 0, ',', '.') }}</p>
                </div>

                {{-- Query --}}
                <form action="{{ route('doku-test.query') }}" method="POST" class="mt-4">
                    @csrf
                    <input type="hidden" name="referenceNo" value="{{ $qrisResult['referenceNo'] ?? '' }}">
                    <input type="hidden" name="partnerReferenceNo" value="{{ $qrisResult['partnerReferenceNo'] ?? '' }}">
                    <button type="submit" class="w-full rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                        Cek Status Pembayaran
                    </button>
                </form>
                @error('query')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- Hasil Query --}}
        @if (!empty($qrisResult['queryResult']))
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-md">
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-gray-900">Hasil Pembayaran</h3>
                </div>

                @if (($qrisResult['queryResult']['latestTransactionStatus'] ?? null) === '00')
                    <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-center text-sm font-semibold text-emerald-700">
                        Pembayaran Berhasil
                    </div>
                @else
                    <div class="mb-4 rounded-lg bg-yellow-50 p-3 text-center text-sm font-semibold text-yellow-700">
                        {{ $qrisResult['queryResult']['transactionStatusDesc'] ?? 'Menunggu Pembayaran' }}
                    </div>
                @endif

                <div class="space-y-1 rounded-lg bg-gray-50 p-3.5 text-xs font-mono text-gray-600">
                    <p><strong class="text-gray-900">Status:</strong> {{ $qrisResult['queryResult']['latestTransactionStatus'] ?? '-' }}</p>
                    <p><strong class="text-gray-900">Keterangan:</strong> {{ $qrisResult['queryResult']['transactionStatusDesc'] ?? '-' }}</p>
                    <p><strong class="text-gray-900">Nominal:</strong> Rp {{ number_format($qrisResult['queryResult']['amount']['value'] ?? 0, 0, ',', '.') }}</p>
                    @if (!empty($qrisResult['queryResult']['paidTime']))
                        <p><strong class="text-gray-900">Paid Time:</strong> {{ $qrisResult['queryResult']['paidTime'] }}</p>
                    @endif
                </div>
            </div>
        @endif

    </div>
</body>
</html>