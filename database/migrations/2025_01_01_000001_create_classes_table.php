<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            // Pemilik/tenant utama (wali kelas pembuat). Kunci multi-tenancy.
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('name');                        // cth: "XII RPL 1"
            $table->string('academic_year', 20)->nullable(); // cth: "2025/2026"
            $table->string('major', 100)->nullable();      // jurusan: RPL, TKJ, dst
            // Nomor WhatsApp wali kelas: SATU-SATUNYA penerima magic link absensi.
            $table->string('homeroom_wa', 20)->nullable();
            // Sakelar absensi otomatis untuk kelas ini. Jam pemicunya diturunkan
            // dari jadwal pelajaran hari itu, bukan jam tetap — mendukung
            // sekolah yang memakai sistem blok.
            $table->boolean('auto_attendance')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // hapus kelas bisa dibatalkan

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
