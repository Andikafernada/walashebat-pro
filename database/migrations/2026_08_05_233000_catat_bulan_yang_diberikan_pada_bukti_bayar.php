<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mencatat berapa bulan yang benar-benar diberikan saat sebuah pembayaran
 * disetujui.
 *
 * Verifikasi dilakukan manual: admin melihat transfer DANA yang masuk lalu
 * menentukan sendiri jumlah bulannya, yang bisa berbeda dari paket yang dipilih
 * guru di formulir (mis. transfer 57rb untuk tiga bulan padahal formulirnya
 * "bulanan"). Tanpa kolom ini tidak ada jejak sama sekali tentang keputusan itu
 * — dan ketika guru bertanya "kenapa masa saya cuma segini", tidak ada yang
 * bisa memeriksa selain menebak dari selisih tanggal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            // Nullable: baris lama disetujui sebelum kolom ini ada, dan menebak
            // nilainya sekarang justru akan mengarang jejak audit.
            $table->unsignedSmallInteger('granted_months')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->dropColumn('granted_months');
        });
    }
};
