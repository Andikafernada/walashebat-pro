<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identitas yang dibutuhkan blok tanda tangan pada laporan resmi.
 *
 * Kolom NIP kepala sekolah sudah ada, tapi NIP wali kelas sendiri belum —
 * padahal justru dialah yang menandatangani buku administrasi ini. Tanpa itu,
 * dokumen yang dicetak tidak bisa diserahkan ke pengawas tanpa ditulis tangan
 * lebih dulu.
 *
 * school_city dipakai untuk baris "Bandung, 25 Juli 2026" di atas tanda tangan;
 * tanpa kolom ini kota harus ditebak dari alamat sekolah yang formatnya bebas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'nip')) {
                $table->string('nip', 30)->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'school_city')) {
                $table->string('school_city', 100)->nullable()->after('school_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['nip', 'school_city'] as $kolom) {
                if (Schema::hasColumn('users', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
