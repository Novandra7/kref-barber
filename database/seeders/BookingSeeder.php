<?php

namespace Database\Seeders;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $services = Service::all();
        $admin = User::first();

        // Mengambil jadwal yang ada dan mengelompokkannya
        $schedules = Schedule::where('is_available', true)->with('barber')->get();

        if ($schedules->isEmpty() || $services->isEmpty()) {
            return;
        }

        // Ambil 10 slot jadwal untuk dijadikan sampel transaksi booking
        $selectedSchedules = $schedules->shuffle()->take(10);

        foreach ($selectedSchedules as $index => $schedule) {
            // Pilih 1-2 layanan secara acak
            $selectedServices = $services->random(rand(1, 2));
            $totalAmount = $selectedServices->sum('price');

            // 1. Buat Booking
            $booking = Booking::create([
                'schedule_id'  => $schedule->id,
                'barber_id'    => $schedule->barber_id,
                'name'         => 'Customer ' . ($index + 1),
                'phone'        => '0812345678' . $index,
                'scheduled_at' => $schedule->date . ' ' . $schedule->slot_time,
                'total_amount' => $totalAmount,
                'status'       => match ($index % 4) {
                    0       => 'completed',
                    1       => 'confirmed',
                    2       => 'pending',
                    default => 'cancelled',
                },
            ]);

            // Update status ketersediaan jadwal jika booking tidak dibatalkan
            if ($booking->status !== 'cancelled') {
                $schedule->update(['is_available' => false]);
            }

            // 2. Buat Booking Items
            foreach ($selectedServices as $service) {
                BookingItem::create([
                    'booking_id'            => $booking->id,
                    'service_id'            => $service->id,
                    'service_name_snapshot' => $service->name,
                    'price_snapshot'        => $service->price,
                    'qty'                   => 1,
                ]);
            }

            // 3. Buat Payment terkait
            $this->createPaymentForBooking($booking, $totalAmount, $index, $admin?->id);
        }
    }

    private function createPaymentForBooking(Booking $booking, int $amount, int $index, ?int $adminId): void
    {
        // Variasi skenario pembayaran berdasarkan indeks
        $scenario = $index % 5;

        match ($scenario) {
            // Skenario 0: QRIS DOKU Lunas (Paid)
            0 => Payment::create([
                'booking_id'           => $booking->id,
                'amount'               => $amount,
                'method'               => 'qris_doku',
                'provider'             => 'doku',
                'payment_source'       => 'GOPAY',
                'purpose'              => 'full_payment',
                'status'               => 'paid',
                'partner_reference_no' => 'INV-KREF-' . now()->timestamp . '-' . $booking->id,
                'doku_reference_no'    => 'DOKU-REF-' . Str::upper(Str::random(10)),
                'payment_url'          => 'https://checkout.doku.com/qris/' . Str::random(15),
                'qr_content'           => '00020101021226680014ID.LINKAJA.WWW01189360091100210087035204581253033605802ID5911KREF BARBER6009SAMARINDA61057511162070703A0163041A2F',
                'expires_at'           => now()->addMinutes(30),
                'provider_payload'     => ['response_code' => '0000', 'message' => 'SUCCESS'],
            ]),

            // Skenario 1: Cash / Tunai di Tempat (Walk-In / Pelunasan)
            1 => Payment::create([
                'booking_id'           => $booking->id,
                'amount'               => $amount,
                'method'               => 'cash',
                'provider'             => 'manual',
                'payment_source'       => 'Kasir Store',
                'purpose'              => 'walk_in',
                'status'               => 'paid',
                'partner_reference_no' => 'CASH-' . now()->timestamp . '-' . $booking->id,
                'recorded_by'          => $adminId,
            ]),

            // Skenario 2: QRIS DOKU Masih Pending (Menunggu Pembayaran)
            2 => Payment::create([
                'booking_id'           => $booking->id,
                'amount'               => $amount,
                'method'               => 'qris_doku',
                'provider'             => 'doku',
                'purpose'              => 'dp',
                'status'               => 'pending',
                'partner_reference_no' => 'INV-KREF-' . now()->timestamp . '-' . $booking->id,
                'payment_url'          => 'https://checkout.doku.com/qris/' . Str::random(15),
                'qr_content'           => '00020101021226680014ID.LINKAJA.WWW01189360091100210087035204581253033605802ID5911KREF BARBER6009SAMARINDA61057511162070703A0163041A2F',
                'expires_at'           => now()->addHour(),
            ]),

            // Skenario 3: Transaksi Kadaluarsa (Expired)
            3 => Payment::create([
                'booking_id'           => $booking->id,
                'amount'               => $amount,
                'method'               => 'qris_doku',
                'provider'             => 'doku',
                'purpose'              => 'full_payment',
                'status'               => 'expired',
                'partner_reference_no' => 'INV-KREF-' . now()->timestamp . '-' . $booking->id,
                'expires_at'           => now()->subHour(),
            ]),

            // Skenario 4: Pembayaran Di-refund
            default => Payment::create([
                'booking_id'           => $booking->id,
                'amount'               => $amount,
                'method'               => 'qris_doku',
                'provider'             => 'doku',
                'payment_source'       => 'OVO',
                'purpose'              => 'full_payment',
                'status'               => 'refunded',
                'partner_reference_no' => 'INV-KREF-' . now()->timestamp . '-' . $booking->id,
                'doku_reference_no'    => 'DOKU-REF-' . Str::upper(Str::random(10)),
                'provider_payload'     => ['refund_id' => 'RFD-' . Str::random(8), 'reason' => 'Customer Request'],
            ]),
        };
    }
}