<?php    
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DokuTestController extends Controller
{
    /**
     * Menampilkan halaman pengujian QRIS
     */
    public function index(): View
    {
        return view('test-payment', [
            'qrisResult' => session('qrisResult'),
        ]);
    }

    /**
     * Hit API Local DOKU QRIS (http://localhost:8000/api/doku/create-qris)
     */
    public function generate(Request $request): RedirectResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1000'],
        ]);

        $amount = (int) $request->input('amount');

        try {
            // Hit API menggunakan HTTP GET dengan query parameters
            $response = Http::acceptJson()
                ->post('http://localhost:8000/api/doku/create-qris', [
                    'amount' => $amount,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return redirect()
                    ->route('doku-test.index')
                    ->with('qrisResult', array_merge($data, ['amount' => $amount]));
            }

            // Ambil detail error jika status code selain 2xx
            $errorBody = $response->json();
            $errorMessage = $errorBody['responseMessage'] 
                ?? $errorBody['message'] 
                ?? $errorBody['error'] 
                ?? 'Terjadi kesalahan pada API DOKU.';

            $detailedError = sprintf(
                'Gagal membuat QRIS (HTTP %d): %s',
                $response->status(),
                is_array($errorMessage) ? json_encode($errorMessage) : $errorMessage
            );

            Log::error('DOKU API Error Response', [
                'status' => $response->status(),
                'body'   => $errorBody ?? $response->body(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['amount' => $detailedError]);

        } catch (\Throwable $e) {
            Log::error('DOKU API Connection Failed: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['amount' => 'Tidak dapat terhubung ke API: ' . $e->getMessage()]);
        }
    }
}