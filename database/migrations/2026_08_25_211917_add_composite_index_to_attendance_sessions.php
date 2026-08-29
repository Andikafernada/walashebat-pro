<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            // Query yang menyaring schedule_id DAN class_id (scheduler, laporan)
            // menjadi full-table scan tanpa index ini. Indeks komposit
            // meng-cover kedua kolom sekaligus.
            $table->index(['schedule_id', 'class_id'], 'attendance_sessions_sched_class_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropIndex('attendance_sessions_sched_class_index');
        });
    }
};
