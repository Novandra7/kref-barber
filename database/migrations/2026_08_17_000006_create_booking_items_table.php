<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_items')) {
            return;
        }

        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();

            // PRD Bagian 9B.6 / 20: item_type membedakan pelaporan revenue service vs product.
            $table->enum('item_type', ['service', 'product']);

            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // Qty relevan terutama untuk produk (bisa beli >1 unit), service default 1.
            $table->unsignedInteger('qty')->default(1);

            // Snapshot (PRD Bagian 20): harga/nama tidak berubah walau master data diubah.
            $table->string('service_name_snapshot')->nullable();
            $table->string('product_name_snapshot')->nullable();

            // Disamakan ke unsignedBigInteger agar konsisten dengan total_amount/outstanding_amount
            // di tabel bookings — menghindari mismatch tipe saat dijumlahkan.
            $table->unsignedBigInteger('price_snapshot');

            // PRD Bagian 20: admin yang menambahkan item ini — untuk service awal booking online
            // biasanya sistem/user, untuk product upselling selalu admin (Bagian 9B.2).
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Mendukung query breakdown revenue per booking per tipe item (PRD Bagian 16).
            $table->index(['booking_id', 'item_type']);
        });

        // MySQL membatasi CHECK ini ketika kolom juga dipakai oleh foreign key referential action.
        // Konsistensi item_type vs FK dijaga di validation/service layer.
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};