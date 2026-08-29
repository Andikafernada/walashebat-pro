<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laporan izin/sakit dari orang tua lewat formulir publik (bukan chat bebas).
 *
 * Ditaruh sebagai catatan TERPISAH dari `attendances`, bukan langsung menulis
 * ke sana: attendances wajib berinduk pada attendance_session (satu sesi
 * absensi hari itu), dan sesi itu belum tentu sudah dibuat saat orang tua
 * melapor pagi-pagi. Petugas piket yang membuka roster nanti akan MELIHAT
 * laporan ini sebagai catatan di sebelah nama siswa — bukan status yang
 * sudah tercentang otomatis. Roster ini sengaja tidak punya nilai bawaan
 * per baris (lihat komentar di roster.blade.php); menandai otomatis dari
 * laporan yang belum tentu benar akan melanggar prinsip itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_excuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('jenis', ['izin', 'sakit']);
            $table->text('keterangan')->nullable();
            $table->string('attachment_path')->nullable();
            $table->boolean('parent_phone_verified')->default(false);
            $table->timestamps();

            // Dipakai roster untuk mencari laporan pada tanggal sesi itu.
            $table->index(['class_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_excuses');
    }
};
