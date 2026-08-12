<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Poin kerajinan: akumulasi kehadiran, terpisah dari discipline_points yang
 * digerakkan pelanggaran. Sumber kebenarannya tabel attendances; kolom ini
 * hanya cache yang dihitung ulang oleh App\Support\PoinKerajinan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->integer('diligence_points')->default(0)->after('discipline_points');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('diligence_points');
        });
    }
};
