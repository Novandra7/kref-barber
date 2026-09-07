<?php

namespace App\Http\Controllers;

use App\Services\DokuService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DokuTestController extends Controller
{
    public function index(): View
    {
        return view('test-payment', [
            'qrisResult' => session('qrisResult'),
        ]);
    }

    public function generate(
        Request $request,
        DokuService $dokuService
    ): RedirectResponse {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1000'],
        ]);

        $amount = (int) $request->input('amount');

        try {
            $reference = 'TEST-' . now()->format('YmdHis') . '-' . random_int(100, 999);

            $result = $dokuService->createQrisPayment(
                $reference,
                $amount
            );

            $qrisResult = [
                'referenceNo' => $result['referenceNo'] ?? null,
                'partnerReferenceNo' => $result['partnerReferenceNo'] ?? null,
                'qrContent' => $result['qrContent'] ?? null,
                'amount' => $amount,

                // Belum melakukan query
                'queryResult' => null,
            ];

            session()->put('qrisResult', $qrisResult);
            return redirect()->route('doku-test.index');

        } catch (\Throwable $e) {
            Log::error('DOKU test QRIS generation failed.', [
                'amount' => $amount,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'amount' => 'Tidak dapat terhubung ke API: ' . $e->getMessage(),
                ]);
        }
    }

    public function query(Request $request, DokuService $dokuService): RedirectResponse 
    {
        $request->validate([
            'referenceNo' => ['required', 'string'],
            'partnerReferenceNo' => ['required', 'string'],
        ]);

        try {
            $queryResult = $dokuService->queryQrisPayment(
                $request->input('referenceNo'),
                $request->input('partnerReferenceNo')
            );

            /*
             * Ambil data QRIS yang sebelumnya disimpan
             */
            $qrisResult = session()->get('qrisResult', []);

            /*
             * Simpan hasil query ke dalam qrisResult
             */
            $qrisResult = session()->get('qrisResult', []);
            $qrisResult['referenceNo'] ??= $request->input('referenceNo');
            $qrisResult['partnerReferenceNo'] ??= $request->input('partnerReferenceNo');
            $qrisResult['queryResult'] = $queryResult;

            session()->put('qrisResult', $qrisResult);
            return redirect()->route('doku-test.index');

        } catch (\Throwable $e) {
            Log::error('DOKU test QRIS query failed.', [
                'referenceNo' => $request->input('referenceNo'),
                'partnerReferenceNo' => $request->input('partnerReferenceNo'),
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('qrisResult', session()->get('qrisResult', []))
                ->withErrors([
                    'query' => $e->getMessage(),
                ]);
        }
    }
}