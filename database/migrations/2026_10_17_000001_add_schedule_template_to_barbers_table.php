<?php

use App\Models\Barber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('barbers', function (Blueprint $table) {
            $table->json('schedule_template')->nullable()->after('is_active');
        });

        // Seed initial default schedule templates for existing barbers
        $rizalTemplate = [
            0 => ['14:00', '14:50', '15:40', '16:30', '18:10', '19:00', '19:50', '20:40', '21:30'],
            1 => ['14:00', '14:50', '15:40', '16:30', '18:10', '19:00', '19:50', '20:40', '21:30'],
            2 => ['14:00', '14:50', '15:40', '16:30', '18:10', '19:00', '19:50', '20:40', '21:30'],
            3 => ['14:00', '14:50', '15:40', '16:30', '18:10', '19:00', '19:50', '20:40', '21:30'],
            4 => ['10:00', '10:50', '13:20', '14:00', '14:50', '15:40', '16:30', '17:20', '18:10', '19:00'],
            5 => ['10:00', '10:50', '11:40', '12:30', '13:20', '14:10', '15:00', '15:50', '16:40', '17:30'],
            6 => ['10:00', '10:50', '11:40', '12:30', '13:20', '14:10', '15:00', '15:50', '16:40', '17:30'],
        ];

        $sogiTemplate = [
            0 => ['10:00', '10:40', '11:20', '12:00', '12:40', '13:20', '14:00', '14:40', '16:20', '17:00', '17:40'],
            1 => ['10:00', '10:40', '11:20', '12:00', '12:40', '13:20', '14:00', '14:40', '16:20', '17:00', '17:40'],
            2 => ['10:00', '10:40', '11:20', '12:00', '12:40', '13:20', '14:00', '14:40', '16:20', '17:00', '17:40'],
            3 => ['10:00', '10:40', '11:20', '12:00', '12:40', '13:20', '14:00', '14:40', '16:20', '17:00', '17:40'],
            4 => ['14:00', '14:40', '15:20', '17:00', '17:40', '18:20', '19:00', '19:40', '20:20', '21:00', '21:40'],
            5 => ['14:00', '14:40', '15:20', '17:00', '17:40', '18:20', '19:00', '19:40', '20:20', '21:00', '21:40'],
            6 => ['14:00', '14:40', '15:20', '17:00', '17:40', '18:20', '19:00', '19:40', '20:20', '21:00', '21:40'],
        ];

        $rizal = Barber::where('id', 1)->orWhere('name', 'like', '%Rizal%')->first();
        if ($rizal) {
            $rizal->update(['schedule_template' => $rizalTemplate]);
        }

        $sogi = Barber::where('id', 2)->orWhere('name', 'like', '%Sogi%')->first();
        if ($sogi) {
            $sogi->update(['schedule_template' => $sogiTemplate]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barbers', function (Blueprint $table) {
            $table->dropColumn('schedule_template');
        });
    }
};
