<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('services')->insert([
            // Haircut
            [
                'name' => 'Regular Haircut',
                'category' => 'Haircut',
                'price' => 70000,
                'is_active' => true,
            ],
            [
                'name' => 'Haircut by Rizal',
                'category' => 'Haircut',
                'price' => 80000,
                'is_active' => true,
            ],
            [
                'name' => 'Long Trim',
                'category' => 'Haircut',
                'price' => 80000,
                'is_active' => true,
            ],

            // Chemicals
            [
                'name' => 'Design Perm',
                'category' => 'Chemicals',
                'price' => 300000,
                'is_active' => true,
            ],
            [
                'name' => 'Root Lift',
                'category' => 'Chemicals',
                'price' => 100000,
                'is_active' => true,
            ],
            [
                'name' => 'Perming',
                'category' => 'Chemicals',
                'price' => 250000,
                'is_active' => true,
            ],
            [
                'name' => 'Down Perm',
                'category' => 'Chemicals',
                'price' => 150000,
                'is_active' => true,
            ],
            [
                'name' => 'Fashion Color',
                'category' => 'Chemicals',
                'price' => 350000,
                'is_active' => true,
            ],
            [
                'name' => 'Highlight',
                'category' => 'Chemicals',
                'price' => 250000,
                'is_active' => true,
            ],
            [
                'name' => 'Toning',
                'category' => 'Chemicals',
                'price' => 130000,
                'is_active' => true,
            ],

            // Treatment
            [
                'name' => 'Hairwash',
                'category' => 'Treatment',
                'price' => 50000,
                'is_active' => true,
            ],
            [
                'name' => 'Styling',
                'category' => 'Treatment',
                'price' => 50000,
                'is_active' => true,
            ],
            [
                'name' => 'Face Mask',
                'category' => 'Treatment',
                'price' => 50000,
                'is_active' => true,
            ],
            [
                'name' => 'Hair Mask',
                'category' => 'Treatment',
                'price' => 50000,
                'is_active' => true,
            ],
            [
                'name' => 'Creambath',
                'category' => 'Treatment',
                'price' => 50000,
                'is_active' => true,
            ],
            [
                'name' => 'Scalp Scrub',
                'category' => 'Treatment',
                'price' => 100000,
                'is_active' => true,
            ],
        ]);
    }
}