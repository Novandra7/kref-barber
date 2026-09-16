<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('refund_no')->unique();
            $table->unsignedInteger('amount');
            $table->enum('type', ['auto_doku', 'manual']);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('reason')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
