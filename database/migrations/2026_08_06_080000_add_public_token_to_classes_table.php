<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Tautan formulir mandiri tidak lagi memakai id kelas yang berurutan.
 *
 * Halaman /isi-biodata/{kelas} dan /refleksi-karakter/{kelas} sengaja terbuka
 * tanpa login — orang tua dan siswa harus bisa membukanya dari tautan WhatsApp
 * tanpa punya akun. Selama ini alamatnya memakai id kelas, sebuah bilangan
 * berurutan, sehingga siapa pun bisa menaikkan angkanya satu per satu dan
 * memanen daftar siswa kelas mana pun di seluruh Indonesia: nama lengkap
 * beserta NIS, semuanya data anak di bawah umur. Nomor id yang sama juga
 * menjadi kunci untuk mengirim biodata ke kelas yang bukan miliknya.
 *
 * Token acak 32 karakter menggantikannya. Tebakannya sia-sia, sementara sifat
 * "cukup dibagikan sekali lalu dipakai berkali-kali" tetap terjaga karena token
 * melekat pada kelas dan tidak berubah sendiri.
 *
 * Token TIDAK dibuat lewat DB::raw/uuid() milik mesin basis data: penamaannya
 * berbeda antara MySQL (produksi) dan SQLite (test suite), dan migrasi yang
 * hanya jalan di salah satunya tidak akan pernah teruji sebelum dipakai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // Nullable dulu: kolom unik yang langsung NOT NULL akan menolak
            // seluruh baris lama yang belum sempat diisi.
            $table->char('public_token', 32)->nullable()->unique()->after('id');
        });

        // Kelas yang sudah ada ikut mendapat token, termasuk yang soft-deleted:
        // kelas terarsip bisa dipulihkan, dan tautannya harus tetap bekerja.
        DB::table('classes')
            ->whereNull('public_token')
            ->orderBy('id')
            ->chunkById(500, function ($baris) {
                foreach ($baris as $kelas) {
                    DB::table('classes')
                        ->where('id', $kelas->id)
                        ->update(['public_token' => Str::random(32)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // Indeks unik dilepas lebih dulu; sebagian versi MySQL menolak
            // menghapus kolom yang masih menopang sebuah indeks.
            $table->dropUnique('classes_public_token_unique');
            $table->dropColumn('public_token');
        });
    }
};
