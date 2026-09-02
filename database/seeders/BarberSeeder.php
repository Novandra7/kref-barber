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
            ],
            [
                'name' => 'Candra Wijaya',
                'role' => 'senior',
                'photo' => null,
                'phone' => '081234567892',
                'is_active' => true,
            ],
            [
                'name' => 'Dedi Kurniawan',
                'role' => 'junior',
                'photo' => null,
                'phone' => '081234567893',
                'is_active' => true,
            ],
            [
                'name' => 'Eko Ramadhan',
                'role' => 'junior',
                'photo' => null,
                'phone' => '081234567894',
                'is_active' => false,
            ],
        ];

        foreach ($barbers as $barber) {
            Barber::create($barber);
        }
    }
}