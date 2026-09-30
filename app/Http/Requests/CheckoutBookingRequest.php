<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutBookingRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan untuk membuat request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_type'                  => ['required', 'in:DP,Full'],
            'guests'                        => ['required', 'array', 'min:1', 'max:5'],
            'guests.0.phone'                => ['required', 'regex:/^(08|8|628)[0-9]{7,13}$/'],
            'guests.*.name'                 => ['required', 'string', 'min:2', 'max:100'],
            'guests.*.phone'                => ['nullable', 'regex:/^(08|8|628)[0-9]{7,13}$/'],
            'guests.*.barber'               => ['required'],
            'guests.*.date'                 => ['required', 'date_format:Y-m-d'],
            'guests.*.time'                 => ['required', 'date_format:H:i'],
            'guests.*.notes'                => ['nullable', 'string', 'max:500'],
            'guests.*.selectedHaircut'      => ['nullable', 'string', 'max:100'],
            'guests.*.selectedChemical'     => ['nullable', 'string', 'max:100'],
            'guests.*.selectedTreatments'   => ['nullable', 'array', 'max:10'],
            'guests.*.selectedTreatments.*' => ['string', 'max:100'],
        ];
    }

    /**
     * Dapatkan pesan kesalahan kustom untuk validator.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'guests.max'           => 'Maksimal pemesanan adalah 5 tamu dalam satu transaksi.',
            'guests.0.phone.regex' => 'Nomor telepon pemesan utama harus berupa nomor WhatsApp Indonesia yang valid.',
            'guests.*.phone.regex' => 'Nomor telepon harus berupa nomor WhatsApp Indonesia yang valid.',
        ];
    }
}
