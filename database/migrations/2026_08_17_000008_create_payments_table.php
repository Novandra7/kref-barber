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
            
            // Metode dan Provider
            $table->enum('method', ['qris_doku', 'qris_static', 'cash']);
            $table->string('provider')->default('manual'); // 'doku' atau 'manual'
            
            // Tujuan Pembayaran & Status
            $table->enum('purpose', ['dp', 'full_payment', 'pelunasan', 'walk_in']);
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'cancelled', 'refunded'])->default('pending');
            
            // DOKU Specific Identifiers (Sesuai Spesifikasi DOKU SNAP / API)
            $table->string('partner_reference_no')->unique()->nullable(); // Unique Reference No dari Merchant ke DOKU
            $table->string('doku_reference_no')->nullable()->index();    // Reference / Transaction ID balasan dari DOKU
            
            // Response QRIS DOKU
            $table->text('payment_url')->nullable();                     // URL Halaman Pembayaran / Checkout DOKU
            $table->text('qr_content')->nullable();                      // QRIS String / QR Raw Content dari DOKU
            $table->timestamp('expires_at')->nullable();
            
            // Webhook Response Log & Admin Audit
            $table->json('provider_payload')->nullable();                // Log JSON notification/webhook callback dari DOKU
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};