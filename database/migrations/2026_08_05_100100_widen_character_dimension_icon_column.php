<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom `icon` menyimpan data path SVG, bukan nama kelas ikon.
 *
 * Lebarnya 100 karakter, sedangkan path SVG yang dipakai seluruh tampilan
 * karakter panjangnya 150-400 karakter. Akibatnya penyisipan dimensi bawaan
 * selalu gagal dengan "Data too long for column 'icon'" — inilah sebab tabel
 * dimensi tidak pernah terisi dan halaman Jurnal & Portofolio Karakter tampil
 * tanpa pilihan apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('character_dimensions', function (Blueprint $table) {
            $table->text('icon')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('character_dimensions', function (Blueprint $table) {
            $table->string('icon', 100)->nullable()->change();
        });
    }
};
