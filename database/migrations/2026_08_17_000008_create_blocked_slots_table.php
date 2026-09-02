<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('slot_time');
            $table->string('reason')->nullable();
            $table->timestamps();

            // Memastikan tidak ada duplikasi pemblokiran slot jam yang sama
            $table->unique(['barber_id', 'date', 'slot_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_slots');
    }
};