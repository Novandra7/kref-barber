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

            // Relasi
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Asal booking (PRD Bagian 9A.6 / 20)
            $table->enum('source', ['online', 'walk_in']);

            // PRD Bagian 20: payment_type wajib mencakup 'pay_later' untuk walk-in,
            // karena walk-in tidak memilih metode pembayaran saat booking dibuat (Bagian 9A.4).
            $table->enum('payment_type', ['dp', 'full', 'pay_later']);

            // PRD Bagian 12: siklus status booking lengkap.
            // 'waiting_payment', 'paid', 'failed' ditambahkan agar sesuai state machine di Bagian 12.
            $table->enum('status', [
                'pending',
                'waiting_payment',
                'paid',
                'confirmed',
                'completed',
                'cancelled',
                'expired',
                'failed',
            ])->default('pending');

            // PRD Bagian 9C.2: derived dari outstanding_amount vs total_amount,
            // di-cache di kolom ini untuk kemudahan query (di-update tiap ada perubahan item/payment).
            $table->enum('payment_status', ['unpaid', 'partial', 'paid_full'])->default('unpaid');

            // PRD Bagian 20: data walk-in customer disimpan langsung di bookings, tanpa relasi ke users.
            // Nullable di level DB (kosong untuk booking online); validasi "nama wajib untuk walk-in"
            // ditegakkan di application layer (lihat PRD Bagian 19 edge case).
            $table->string('walk_in_customer_name')->nullable();
            $table->string('walk_in_customer_phone')->nullable();

            // PRD Bagian 9C.2 & 20: total_amount dinamis (service + product aktif),
            // outstanding_amount = total_amount - sum(payments sukses).
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->unsignedBigInteger('outstanding_amount')->default(0);

            // Waktu mulai booking (dipakai availability engine, Bagian 10).
            $table->timestamp('scheduled_at')->nullable();

            // Tambahan: waktu selesai booking, dihitung dari total durasi service items.
            // Tidak disebutkan literal di PRD, tapi diperlukan agar pengecekan overlap slot
            // (Bagian 10 & 11) tidak hanya mengandalkan satu titik waktu.
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();

            // Index untuk query yang sering dipakai: availability check, dashboard filter (Bagian 16),
            // dan double booking prevention (Bagian 11).
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