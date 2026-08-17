<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->enum('method', ['midtrans', 'cash', 'qris_static']);
            $table->enum('purpose', ['dp', 'full_payment', 'pelunasan', 'walk_in']);
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'cancelled', 'refunded'])->default('pending');
            $table->string('midtrans_transaction_id')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
