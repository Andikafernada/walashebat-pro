<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kalender libur. Dipakai scheduler untuk MENAHAN pembuatan sesi absensi
     * otomatis, sehingga tidak ada blast WhatsApp saat libur nasional, libur
     * semester, atau pekan ujian.
     *
     * user_id NULL = libur global (nasional) yang berlaku untuk semua tenant.
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable()->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('description');
            $table->timestamps();

            $table->index(['user_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
