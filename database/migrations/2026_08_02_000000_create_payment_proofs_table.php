<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bukti pembayaran langganan PRO yang diunggah wali kelas, lalu diverifikasi
 * admin secara manual.
 *
 * Tabel ini sempat hanya ada di database produksi tanpa migrasi pasangannya —
 * pemasangan dari nol menghasilkan aplikasi yang kehilangan seluruh alur
 * langganan, dan berkas ini menutup celah itu. Bentuknya mengikuti persis
 * skema yang sudah berjalan supaya lingkungan lama dan baru tidak berbeda.
 *
 * Kolom status ditulis admin lewat proses persetujuan, bukan lewat mass
 * assignment dari formulir pengunggahan — karena itu default-nya 'pending'.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_proofs')) {
            return;
        }

        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan_type')->default('monthly');
            $table->integer('amount')->default(19000);
            $table->string('proof_image');
            $table->string('bank_name')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('status')->default('pending');
            $table->string('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};
