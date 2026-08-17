<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('source', ['online', 'walk_in']);
            $table->enum('payment_type', ['dp', 'full', 'pay_later']);
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'expired'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid_full'])->default('unpaid');
            $table->string('walk_in_customer_name')->nullable();
            $table->string('walk_in_customer_phone')->nullable();
            $table->unsignedInteger('total_amount')->default(0);
            $table->unsignedInteger('outstanding_amount')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
