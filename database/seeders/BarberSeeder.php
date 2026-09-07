<?php

namespace Database\Seeders;

use App\Models\Barber;
use Illuminate\Database\Seeder;

class BarberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barbers = [
            [
                'name' => 'Rizal',
                'role' => 'owner',
                'photo' => null,
                'phone' => '081234567890',
                'is_active' => true,
            ],
            [
                'name' => 'Sogi',
                'role' => 'senior',
                'photo' => null,
                'phone' => '081234567891',
                'is_active' => true,
            ]
        ];

        foreach ($barbers as $barber) {
            Barber::create($barber);
        }
    }
}