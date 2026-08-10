<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membedakan nilai harian dari PTS dan PAS, serta menyandarkannya ke semester.
 *
 * Sampai kini `assessments` hanya mengenal satu bentuk penilaian: nilai harian
 * per Capaian Pembelajaran. Itu konsep guru mapel. Wali kelas membutuhkan yang
 * lain — nilai tengah semester dan akhir semester untuk semester 1 dan 2 —
 * dan tidak punya tempat untuk mencatatnya.
 *
 * MENGAPA SEMESTER DISIMPAN, BUKAN DIHITUNG DARI TANGGAL
 *
 * Semester bisa saja disimpulkan dari assessment_date, dan untuk nilai harian
 * itu benar. Untuk PAS tidak: nilai akhir semester 1 lazim baru selesai
 * dikoreksi dan dimasukkan pada Januari, yang menurut kalender sudah semester
 * 2. Menyimpulkannya dari tanggal akan memindahkan nilai itu ke semester yang
 * salah — diam-diam, dan baru ketahuan saat rapor tidak cocok.
 *
 * Tahun ajaran sengaja TIDAK ditambahkan di sini: satu kelas hidup dalam satu
 * tahun ajaran (classes.academic_year), jadi semester 1 dan 2 di dalam satu
 * kelas sudah tidak ambigu. Menyalinnya ke sini hanya melahirkan kemungkinan
 * dua nilai yang berbeda untuk hal yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            /*
             * String, bukan enum. Menambah jenis penilaian baru pada enum MySQL
             * memerlukan ALTER TABLE yang mengunci seluruh tabel; dengan string
             * ditambah penjagaan di lapisan validasi, penambahan berikutnya
             * tidak menyentuh basis data sama sekali.
             */
            $table->string('jenis', 20)->default('harian')->after('class_id');

            // Nullable supaya baris lama bisa diisi bertahap di bawah, bukan
            // ditolak mentah-mentah oleh kolom NOT NULL tanpa nilai bawaan.
            $table->unsignedTinyInteger('semester')->nullable()->after('jenis');

            /*
             * Capaian Pembelajaran menjadi opsional.
             *
             * Kolomnya lahir NOT NULL ketika nilai harian adalah satu-satunya
             * bentuk penilaian yang ada, dan di sana ia memang selalu terisi.
             * PTS dan PAS menilai satu semester penuh, bukan satu capaian —
             * tanpa perubahan ini penyimpanannya gagal di tingkat basis data,
             * jauh setelah validasi meloloskannya.
             */
            $table->string('capaian_pembelajaran', 255)->nullable()->change();
        });

        /*
         * Baris yang sudah ada semuanya nilai harian — satu-satunya bentuk
         * yang pernah bisa dibuat — jadi bawaan 'harian' sudah tepat dan
         * semesternya aman disimpulkan dari tanggal.
         *
         * Batasnya mengikuti PeriodeLaporan: semester 1 Juli–Desember,
         * semester 2 Januari–Juni. Angka di sini harus sama dengan yang dipakai
         * laporan, kalau tidak rekap nilai dan rekap kehadiran akan bercerita
         * tentang rentang waktu yang berbeda tanpa ada yang menyadarinya.
         *
         * Dihitung di PHP, bukan lewat MONTH() di SQL: fungsi tanggal berbeda
         * antara MySQL dan SQLite, dan test berjalan di SQLite. Aman diproses
         * baris demi baris karena kolom ini baru ada — yang perlu diisi hanya
         * riwayat yang sudah ada, bukan aliran data yang terus tumbuh.
         */
        DB::table('assessments')
            ->select('id', 'assessment_date')
            ->whereNull('semester')
            ->orderBy('id')
            ->chunk(500, function ($baris) {
                foreach ($baris as $b) {
                    $bulan = (int) date('n', strtotime((string) $b->assessment_date));

                    DB::table('assessments')
                        ->where('id', $b->id)
                        ->update(['semester' => $bulan >= 7 ? 1 : 2]);
                }
            });

        Schema::table('assessments', function (Blueprint $table) {
            /*
             * Halaman rekap selalu bertanya dengan bentuk yang sama: satu
             * kelas, satu semester, jenis tertentu. Tanpa indeks ini setiap
             * pembukaan rekap memindai seluruh riwayat penilaian kelas itu.
             */
            $table->index(['class_id', 'semester', 'jenis'], 'assessments_rekap_index');
        });
    }

    public function down(): void
    {
        /*
         * Penilaian PTS/PAS tidak punya Capaian Pembelajaran, sedangkan kolom
         * itu akan kembali NOT NULL di bawah. Diisi penanda lebih dulu supaya
         * pembalikan tidak gagal di tengah jalan dan meninggalkan tabel dalam
         * keadaan setengah berubah.
         */
        DB::table('assessments')
            ->whereNull('capaian_pembelajaran')
            ->update(['capaian_pembelajaran' => '(tanpa capaian pembelajaran)']);

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex('assessments_rekap_index');
            $table->dropColumn(['jenis', 'semester']);
            $table->string('capaian_pembelajaran', 255)->nullable(false)->change();
        });
    }
};
