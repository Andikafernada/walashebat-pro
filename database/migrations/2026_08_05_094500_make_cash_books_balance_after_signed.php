<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * balance_after harus bisa bernilai negatif.
 *
 * Kolomnya dulu unsigned, sehingga saldo berjalan yang minus terpaksa diklem
 * ke 0 saat disimpan — angka yang tidak pernah benar. Padahal saldo kelas
 * memang bisa minus (guru menalangi dulu), tampilan kas SUDAH menyiapkan
 * warna merah untuknya, dan dua penghitung saldo lain (ExportController serta
 * ClassReportBuilder::bukuKas) menghitungnya tanpa klem. Yang tersimpan justru
 * satu-satunya yang berbohong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_books', function (Blueprint $table) {
            // default(0) WAJIB disebut ulang: change() mengganti definisi kolom
            // seutuhnya, jadi atribut yang tidak ditulis lagi akan hilang.
            $table->bigInteger('balance_after')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cash_books', function (Blueprint $table) {
            $table->unsignedBigInteger('balance_after')->default(0)->change();
        });
    }
};
