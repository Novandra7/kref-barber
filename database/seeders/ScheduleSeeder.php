<?php

namespace Database\Seeders;

use App\Models\Barber;
use App\Models\BlockedSlot;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barbers = Barber::take(5)->get();
        
        // Jam slot lengkap
        $morningSlots = ['09:00:00', '10:00:00', '11:00:00'];
        $afternoonSlots = ['13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00'];
        $fullSlots = array_merge($morningSlots, $afternoonSlots);

        // Generate untuk 3 hari ke depan (mulai hari ini)
        for ($i = 0; $i < 3; $i++) {
            $currentDate = now()->addDays($i)->toDateString();

            foreach ($barbers as $index => $barber) {
                // Pola variasi berdasarkan ID / Index Barber & Hari
                $slotsToInsert = [];
                
                switch ($index % 5) {
                    case 0: 
                        // Barber 1: Shift Penuh
                        $slotsToInsert = $fullSlots;
                        break;

                    case 1: 
                        // Barber 2: Shift Pagi saja
                        $slotsToInsert = $morningSlots;
                        break;

                    case 2: 
                        // Barber 3: Shift Siang/Sore saja
                        $slotsToInsert = $afternoonSlots;
                        break;

                    case 3: 
                        // Barber 4: Libur di hari ke-2 (index i = 1)
                        if ($i !== 1) {
                            $slotsToInsert = $fullSlots;
                        }
                        break;

                    case 4: 
                        // Barber 5: Slot acak / parsial
                        $slotsToInsert = ['10:00:00', '11:00:00', '14:00:00', '15:00:00'];
                        break;
                }

                // Insert slot ke database
                foreach ($slotsToInsert as $timeKey => $time) {
                    // Simulasi: Jam 10:00 & 14:00 di-set tidak tersedia
                    $isAvailable = !in_array($time, ['10:00:00', '14:00:00']);

                    // 1. Buat / Ambil Jadwal Schedule
                    Schedule::firstOrCreate([
                        'barber_id' => $barber->id,
                        'date' => $currentDate,
                        'slot_time' => $time,
                    ], [
                        'is_available' => $isAvailable,
                    ]);

                    // 2. Jika is_available = false, buatkan record di blocked_slots secara terikat
                    if (!$isAvailable) {
                        BlockedSlot::firstOrCreate([
                            'barber_id' => $barber->id,
                            'date' => $currentDate,
                            'slot_time' => $time,
                        ], [
                            'reason' => 'Jam Istirahat / Diblokir Admin',
                        ]);
                    }
                }
            }
        }
    }
}