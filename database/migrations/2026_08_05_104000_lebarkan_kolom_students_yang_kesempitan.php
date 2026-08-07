<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyelaraskan lebar kolom dengan aturan validasi yang sudah berlaku.
 *
 * Formulir menerima rt_rw sampai 20 karakter, tetapi kolomnya varchar(10).
 * Siswa yang mengetik format wajar seperti "RT 08 / RW 06" (13 karakter)
 * lolos validasi lalu ditolak MySQL — HTTP 500, dan seluruh isian biodata
 * yang sudah susah payah diketik ikut hilang.
 *
 * Kolom yang dilebarkan, bukan validasinya yang diperketat: formatnya memang
 * lazim ditulis begitu di Indonesia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('rt_rw', 20)->nullable()->change();
            // StudentRequest mengizinkan name sampai 255.
            $table->string('name', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('rt_rw', 10)->nullable()->change();
            $table->string('name', 191)->change();
        });
    }
};
