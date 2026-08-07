<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bobot poin per dimensi karakter.
 *
 * `positive_score` sudah dipanggil di Student\PortfolioController sejak awal
 * (`$dimension?->positive_score ?? 5`) tetapi kolomnya tidak pernah ada:
 * pemanggilan itu selalu null dan setiap pencapaian bernilai 5 poin, tanpa cara
 * apa pun bagi wali kelas untuk membedakan bobot antar dimensi.
 *
 * Nilai bawaannya sengaja +5 dan -5 — sama persis dengan angka yang selama ini
 * dipakai — supaya poin disiplin siswa yang sudah tercatat tidak berubah makna
 * hanya karena migrasi ini dijalankan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('character_dimensions', function (Blueprint $table) {
            $table->integer('positive_score')->default(5)->after('color');
            $table->integer('negative_score')->default(-5)->after('positive_score');
        });
    }

    public function down(): void
    {
        Schema::table('character_dimensions', function (Blueprint $table) {
            $table->dropColumn(['positive_score', 'negative_score']);
        });
    }
};
