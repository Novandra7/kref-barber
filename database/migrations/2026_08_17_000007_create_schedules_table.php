<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('slot_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            // Memastikan 1 barber tidak punya 2 slot di jam dan tanggal yang sama
            $table->unique(['barber_id', 'date', 'slot_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};