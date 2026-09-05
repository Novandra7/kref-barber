<?php

namespace Database\Seeders;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID'); // Menggunakan locale Indonesia
        $barbers = Barber::take(5)->get();
        $adminUser = User::first();
        $service = Service::first();

        if ($barbers->isEmpty()) {
            return;
        }

        $morningSlots = ['09:00:00', '10:00:00', '11:00:00'];
        $afternoonSlots = ['13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00'];
        $fullSlots = array_merge($morningSlots, $afternoonSlots);

        for ($i = 0; $i < 3; $i++) {
            $currentDate = now()->addDays($i)->toDateString();

            foreach ($barbers as $index => $barber) {
                $slotsToInsert = match ($index % 5) {
                    0 => $fullSlots,
                    1 => $morningSlots,
                    2 => $afternoonSlots,
                    3 => ($i !== 1) ? $fullSlots : [],
                    4 => ['10:00:00', '11:00:00', '14:00:00', '15:00:00'],
                    default => $fullSlots,
                };

                foreach ($slotsToInsert as $timeKey => $time) {
                    // 1. Buat Schedule Slot terlebih dahulu
                    $schedule = Schedule::firstOrCreate([
                        'barber_id' => $barber->id,
                        'date'      => $currentDate,
                        'slot_time' => $time,
                    ], [
                        'is_available' => true,
                    ]);

                    // 2. Jika slot jam 10:00 atau 14:00, buat data Booking yang berelasi ke schedule_id
                    if (in_array($time, ['10:00:00', '14:00:00'])) {
                        $isWalkIn = ($timeKey % 2 === 0);
                        $scheduledAt = Carbon::parse("{$currentDate} {$time}");
                        $endsAt = (clone $scheduledAt)->addMinutes(45);
                        $price = $service?->price ?? 75000;

                        // Logika Penyesuaian Status & Payment Status:
                        // - Walk-in: Bayar Cash Lunas di tempat (Status: confirmed / completed, Payment: paid_full)
                        // - Online: Bayar DP via App (Status: confirmed, Payment: partial, Sisa Tagihan)
                        if ($isWalkIn) {
                            $paymentType       = 'full';
                            $status            = 'completed';
                            $paymentStatus     = 'paid_full';
                            $outstandingAmount = 0;
                        } else {
                            $paymentType       = 'dp';
                            $status            = 'confirmed';
                            $paymentStatus     = 'partial';
                            $dpAmount          = 40000; // Contoh nilai DP
                            $outstandingAmount = max(0, $price - $dpAmount);
                        }

                        // Create Booking Record menggunakan Faker
                        $booking = Booking::create([
                            'schedule_id'        => $schedule->id,
                            'name'               => $faker->firstName('male'), // Mengenerate nama pria acak
                            'phone'              => $faker->numerify('08##########'), // Contoh output: "081234567890"
                            'description'        => $isWalkIn ? 'Minta potong cepat' : 'Model Rambut Two Block',
                            'barber_id'          => $barber->id,
                            'created_by'         => $isWalkIn ? $adminUser?->id : null,
                            'source'             => $isWalkIn ? 'walk_in' : 'online',
                            'payment_type'       => $paymentType,
                            'status'             => $status,
                            'payment_status'     => $paymentStatus,
                            'total_amount'       => $price,
                            'outstanding_amount' => $outstandingAmount,
                            'scheduled_at'       => $scheduledAt,
                            'ends_at'            => $endsAt,
                        ]);

                        // Create Booking Item
                        BookingItem::create([
                            'booking_id'            => $booking->id,
                            'item_type'             => 'service',
                            'service_id'            => $service?->id,
                            'product_id'            => null,
                            'qty'                   => 1,
                            'service_name_snapshot' => $service?->name ?? 'Gentlemen Haircut & Styling',
                            'product_name_snapshot' => null,
                            'price_snapshot'        => $price,
                            'added_by'              => $adminUser?->id,
                        ]);

                        // Tandai slot pada schedule tidak tersedia
                        $schedule->update(['is_available' => false]);
                    }
                }
            }
        }
    }
}