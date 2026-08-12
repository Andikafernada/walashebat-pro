<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Umpan balik teman sebaya pada refleksi karakter.
 *
 * Tiga isian yang sudah ada seluruhnya penilaian diri sendiri, dan penilaian
 * diri sendiri punya titik buta yang sama pada setiap anak. Pertanyaan
 * "menurut temanmu, kamu itu seperti apa?" memaksa siswa melihat dirinya dari
 * luar — dan bagi wali kelas, jarak antara jawaban ini dan tiga isian di
 * atasnya sering lebih informatif daripada isi jawabannya sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('character_reflections', function (Blueprint $table) {
            $table->text('kesan_teman')->nullable()->after('pesan_ortu');
        });
    }

    public function down(): void
    {
        Schema::table('character_reflections', function (Blueprint $table) {
            $table->dropColumn('kesan_teman');
        });
    }
};
