<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nilai harian guru mapel, ditata per Capaian Pembelajaran.
 *
 * Bentuknya sengaja meniru pasangan attendance_sessions + attendances yang
 * sudah terbukti dipakai: satu baris "penilaian" mewakili satu kesempatan
 * menilai, lalu nilainya diisikan untuk tiap siswa. Guru membuka satu kali,
 * mengisi sekelas, selesai — alur yang sama persis dengan mengisi presensi,
 * sehingga tidak ada kebiasaan baru yang harus dipelajari.
 *
 * Ini lapisan SEBELUM e-Rapor, bukan penggantinya: tempat mencatat nilai
 * sehari-hari yang nantinya direkap, bukan buku nilai rapor lengkap dengan
 * bobot dan agregasi yang aturannya berbeda tiap sekolah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();

            // Denormalisasi user_id ke seluruh tabel domain — pola yang sama
            // dipakai tabel lain agar TenantScope seragam dan cepat.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();

            /*
             * Mapel boleh kosong: kelas perwalian tidak punya mapel. Pada kelas
             * ajar yang mengampu dua mapel, kolom inilah yang menjaga nilai
             * keduanya tidak tercampur menjadi satu rata-rata yang tak berarti
             * bagi kedua-duanya.
             */
            $table->string('mapel', 100)->nullable();

            /*
             * Capaian Pembelajaran, istilah Kurikulum Merdeka. Disimpan sebagai
             * teks, bukan relasi ke daftar baku: rumusan CP berbeda tiap mapel,
             * tiap fase, dan tiap sekolah menuliskannya dengan caranya sendiri.
             * Memaksanya memilih dari daftar hanya akan membuat guru menulis
             * "Lainnya" untuk hampir semua penilaian.
             */
            $table->string('capaian_pembelajaran', 255);

            $table->date('assessment_date');
            $table->timestamps();

            $table->index(['user_id', 'class_id', 'assessment_date']);
            $table->index(['class_id', 'mapel']);
        });

        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            /*
             * NULLABLE, dan itu keputusan yang menentukan.
             *
             * Kosong berarti "belum dinilai" — siswa yang sakit saat ulangan,
             * atau yang penilaiannya belum sempat diisi. Nol berarti "dinilai,
             * hasilnya nol". Menyamakan keduanya membuat rata-rata kelas
             * anjlok oleh siswa yang sebenarnya belum diuji, dan guru mengejar
             * remedial untuk anak yang tidak perlu.
             */
            $table->unsignedTinyInteger('nilai')->nullable();

            $table->string('catatan', 200)->nullable();
            $table->timestamps();

            // Satu siswa satu nilai per penilaian.
            $table->unique(['assessment_id', 'student_id']);
            $table->index(['user_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_scores');
        Schema::dropIfExists('assessments');
    }
};
