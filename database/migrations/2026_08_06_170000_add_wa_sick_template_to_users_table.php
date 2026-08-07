<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Variasi balasan tambahan milik wali kelas, terpisah untuk izin dan sakit.
 *
 * Sebelumnya hanya ada `wa_permission_template` — satu kolom untuk dua
 * keadaan yang berbeda, dan lebih buruk lagi: kolom itu ditulis ke basis data
 * lalu TIDAK PERNAH dibaca siapa pun. Gateway tidak mengenal kata "template"
 * sama sekali. Wali kelas menyunting balasannya, menekan Simpan, melihat
 * "berhasil disimpan", dan tidak ada yang berubah.
 *
 * Sekarang kolomnya benar-benar dipakai, dan dipisah karena "semoga lekas
 * sembuh" tidak pantas dikirim untuk kabar izin acara keluarga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('wa_sick_template')->nullable()->after('wa_permission_template');
        });

        /*
         * Isi lama `wa_permission_template` sengaja TIDAK disalin ke sini.
         *
         * Teks bawaannya menyatakan "telah tercatat dalam sistem absensi" —
         * janji yang tidak pernah benar, karena balasan otomatis memang tidak
         * menyentuh absensi sama sekali. Membawanya serta berarti melestarikan
         * kalimat keliru itu ke kolom baru.
         */
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wa_sick_template');
        });
    }
};
