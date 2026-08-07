<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kode dimensi unik PER WALI KELAS, bukan unik se-aplikasi.
 *
 * Tabelnya dibuat sebelum kolom user_id ada, jadi `code` dikunci unik secara
 * global. Setelah tabel ini dijadikan milik masing-masing wali kelas, kunci itu
 * berarti hanya SATU wali kelas di seluruh aplikasi yang boleh punya dimensi
 * "mandiri" — wali kelas kedua dan seterusnya gagal disiapkan, tabelnya
 * ditinggal kosong, dan seluruh halaman Jurnal & Portofolio Karakter ikut
 * kosong: daftar dimensinya nihil sehingga formulir tidak bisa dikirim sama
 * sekali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('character_dimensions', function (Blueprint $table) {
            $table->dropUnique('character_dimensions_code_unique');
            $table->unique(['user_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('character_dimensions', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'code']);
            $table->unique('code');
        });
    }
};
