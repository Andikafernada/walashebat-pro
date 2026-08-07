<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom penghubung transaksi kas ke siswa pembayarnya.
 *
 * Formulir kas sudah lama punya pilihan "Siswa Pembayar" dan daftarnya sudah
 * menyiapkan tempat untuk menampilkan namanya, tetapi kolomnya tidak pernah
 * ada — sehingga pilihan guru dibuang diam-diam pada setiap penyimpanan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_books', function (Blueprint $table) {
            // Nullable: banyak transaksi memang bersifat umum (beli spidol,
            // sumbangan kelas) dan tidak berkaitan dengan siswa tertentu.
            $table->foreignId('student_id')->nullable()->after('class_id');

            /*
             * nullOnDelete, BUKAN cascadeOnDelete.
             *
             * Uang yang pernah masuk tetap pernah masuk. Menghapus barisnya
             * saat siswa dihapus akan membuat seluruh balance_after sesudahnya
             * meleset tanpa ada yang menyadari.
             */
            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();

            $table->index(['class_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cash_books', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropIndex(['class_id', 'student_id']);
            $table->dropColumn('student_id');
        });
    }
};
