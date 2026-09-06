<?php

namespace Database\Seeders;

use App\Models\Barber;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $barbers = Barber::take(5)->get();

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

                foreach ($slotsToInsert as $time) {
                    Schedule::firstOrCreate([
                        'barber_id' => $barber->id,
                        'date'      => $currentDate,
                        'slot_time' => $time,
                    ], [
                        'is_available' => true,
                    ]);
                }
            }
        }
    }
}