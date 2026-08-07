<?php

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat koreksi absensi.
 *
 * Wali kelas HARUS bisa memperbaiki absensi — siswa yang datang membawa surat
 * dokter sehari kemudian adalah kejadian rutin, bukan pengecualian. Tapi pada
 * aplikasi yang seluruh premisnya mencegah absensi asal-asalan, koreksi yang
 * tidak berjejak justru membuka pintu yang tadi ditutup: siapa pun yang
 * memegang akun wali kelas bisa mengubah "alfa" menjadi "hadir" tanpa bekas.
 *
 * Karena itu koreksi dicatat sebagai baris tersendiri, bukan sekadar menimpa
 * kolom status. Tabel terpisah (bukan kolom di attendances) dipilih supaya
 * koreksi kedua, ketiga, dan seterusnya tidak menghapus jejak koreksi
 * sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Attendance::class)->constrained()->cascadeOnDelete();
            // Pelaku koreksi. Tidak nullable: koreksi tanpa penanggung jawab
            // tidak ada gunanya sebagai jejak audit.
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('from_status', 10);
            $table->string('to_status', 10);
            $table->string('reason', 255);
            $table->timestamps();

            $table->index(['attendance_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_revisions');
    }
};
