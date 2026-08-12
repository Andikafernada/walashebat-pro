<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengingat iuran bulanan ke grup WhatsApp orang tua.
 *
 * Ditaruh pada `classes`, bukan `users`: grup orang tua sendiri milik kelas
 * (classes.parent_group_wa), dan besaran maupun kesepakatan iuran berbeda antar
 * kelas walau wali kelasnya orang yang sama.
 *
 * Isinya sengaja teks bebas, bukan nominal + daftar penunggak. Menyebut nama
 * anak yang belum bayar di grup yang dibaca seluruh orang tua adalah keputusan
 * yang jauh lebih besar daripada teknisnya — dan bukan itu yang diminta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->boolean('spp_pengingat_aktif')->default(false)->after('parent_group_wa');
            $table->unsignedTinyInteger('spp_pengingat_tanggal')->default(10)->after('spp_pengingat_aktif');
            $table->text('spp_pengingat_teks')->nullable()->after('spp_pengingat_tanggal');

            /*
             * Penjaga kirim-ganda.
             *
             * Penjadwal berjalan tiap hari dan bisa dijalankan ulang manual;
             * tanpa penanda ini, satu hari yang sama bisa mengirim pesan yang
             * sama berkali-kali ke grup orang tua — kesalahan yang tidak bisa
             * ditarik kembali dan langsung terlihat oleh puluhan orang.
             */
            $table->date('spp_pengingat_terkirim_pada')->nullable()->after('spp_pengingat_teks');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn([
                'spp_pengingat_aktif',
                'spp_pengingat_tanggal',
                'spp_pengingat_teks',
                'spp_pengingat_terkirim_pada',
            ]);
        });
    }
};
