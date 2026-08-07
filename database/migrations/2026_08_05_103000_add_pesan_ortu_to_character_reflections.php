<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pesan siswa untuk orang tuanya pada refleksi karakter.
 *
 * Terpisah dari what_went_well / what_to_improve / action_plan: ketiganya
 * ditujukan kepada diri sendiri dan wali kelas, sedangkan yang ini ditujukan
 * kepada orang tua dan pantas ditampilkan tersendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('character_reflections', function (Blueprint $table) {
            $table->text('pesan_ortu')->nullable()->after('action_plan');
        });
    }

    public function down(): void
    {
        Schema::table('character_reflections', function (Blueprint $table) {
            $table->dropColumn('pesan_ortu');
        });
    }
};
