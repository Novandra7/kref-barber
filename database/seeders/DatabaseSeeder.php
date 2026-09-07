<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Plain create (no factory/faker) so this seeder also works in production,
        // where fakerphp/faker is not installed (composer install --no-dev).
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'kayla',
                'password' => Hash::make('password'),
            ]
        );

        $this->call([
            ServiceSeeder::class,
            BarberSeeder::class,
            // ScheduleSeeder::class,
        ]);
    }
}
