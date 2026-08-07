<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tambahkan kolom user_id ke tabel yang menggunakan trait BelongsToTenant
     * tapi belum memiliki kolom tersebut.
     */
    public function up(): void
    {
        // character_records
        if (!Schema::hasColumn('character_records', 'user_id')) {
            Schema::table('character_records', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->index('user_id');
            });
        }

        // character_reflections
        if (!Schema::hasColumn('character_reflections', 'user_id')) {
            Schema::table('character_reflections', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->index('user_id');
            });
        }

        // student_badges
        if (!Schema::hasColumn('student_badges', 'user_id')) {
            Schema::table('student_badges', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->index('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('character_records', 'user_id')) {
            Schema::table('character_records', function (Blueprint $table) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasColumn('character_reflections', 'user_id')) {
            Schema::table('character_reflections', function (Blueprint $table) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasColumn('student_badges', 'user_id')) {
            Schema::table('student_badges', function (Blueprint $table) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
