<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Empat kolom yang sudah lama dipakai kode (halaman Integrasi WhatsApp,
 * KirimPengingatSpp, dst) tetapi tidak pernah lahir dari migration -- di
 * produksi kolomnya ADA (ditambahkan manual di suatu titik), tapi database
 * yang dibangun ulang dari migration (termasuk sqlite :memory: yang dipakai
 * test) tidak akan pernah memilikinya. Ditemukan saat test menulis ke
 * wa_permission_template dan gagal dengan "no such column" -- membaca
 * kolom yang tidak ada diam-diam mengembalikan null lewat Eloquent, jadi
 * celah ini lolos dari setiap test yang hanya MEMBACA kolom ini.
 *
 * hasColumn() menjaga migration ini aman dijalankan di produksi (no-op,
 * kolomnya sudah ada) maupun di lingkungan baru (kolomnya baru dibuat).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'wa_permission_template')) {
                $table->text('wa_permission_template')->nullable();
            }
            if (! Schema::hasColumn('users', 'wa_magic_link_template')) {
                $table->text('wa_magic_link_template')->nullable();
            }
            if (! Schema::hasColumn('users', 'wa_permission_keywords')) {
                $table->text('wa_permission_keywords')->nullable();
            }
            if (! Schema::hasColumn('users', 'wa_sick_keywords')) {
                $table->text('wa_sick_keywords')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'wa_permission_template', 'wa_magic_link_template',
                'wa_permission_keywords', 'wa_sick_keywords',
            ]);
        });
    }
};
