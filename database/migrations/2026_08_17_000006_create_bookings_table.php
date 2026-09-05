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

            // Relasi Slot Waktu ke Table Schedules
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();

            // Identitas Pelanggan (Online Guest maupun Walk-in)
            $table->string('name');
            $table->string('phone');
            $table->text('description')->nullable();

            // Relasi Tambahan
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Tipe & Status Transaction
            $table->enum('source', ['online', 'walk_in']);
            $table->enum('payment_type', ['dp', 'full']);
            $table->enum('status', [
                'pending',      // Menunggu konfirmasi admin / pembayaran awal
                'confirmed',    // Jadwal sudah disetujui & slot terkunci
                'in_progress',  // Customer sedang dicukur
                'completed',    // Layanan selesai
                'cancelled',    // Dibatalkan (oleh admin atau customer)
            ])->default('pending');

            // Status Finansial / Pembayaran
            $table->enum('payment_status', [
                'unpaid',       // Belum ada pembayaran
                'partial',      // DP (Down Payment) sudah diterima
                'paid_full',    // Lunas 100%
                'failed',       // Gagal bayar via gateway
                'expired',      // Batas waktu pembayaran habis
            ])->default('unpaid');

            // Financials
            $table->unsignedBigInteger('total_amount')->default(0); // Total biaya layanan
            $table->unsignedBigInteger('outstanding_amount')->default(0); // Sisa tagihan yang harus dibayar (jika ada)

            // Waktu Mulai & Selesai Booking
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();

            // Indexing
            $table->index(['barber_id', 'scheduled_at']);
            $table->index('status');
            $table->index('payment_status');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};