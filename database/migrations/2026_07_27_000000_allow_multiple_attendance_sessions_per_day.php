<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Membuka batas sesi absensi dari SATU menjadi beberapa sesi per kelas per hari.
 *
 * Sebelumnya unique(class_id, session_date) menutup kemungkinan itu di level
 * database. Batasnya tidak dihapus, hanya digeser: kolom `sequence` masuk ke
 * kunci unik sehingga (kelas, tanggal, urutan) tetap tidak bisa kembar.
 *
 * Mengapa bukan sekadar menghapus unique-nya:
 * scheduler berjalan setiap menit. Tanpa penjagaan di level database, dua
 * proses yang berjalan bersamaan bisa membuat dua sesi otomatis untuk kelas
 * yang sama. Karena sesi otomatis SELALU memakai sequence = 1, kunci unik yang
 * baru tetap menghentikan duplikat itu, sementara sesi tambahan yang dibuat
 * manual oleh wali kelas mendapat sequence 2, 3, dan seterusnya.
 *
 * Urutan operasi disengaja: indeks unik BARU dibuat lebih dulu, indeks lama
 * dihapus sesudahnya. Kolom class_id memikul foreign key, dan MySQL menolak
 * menghapus indeks terakhir yang menopang sebuah foreign key (errno 150).
 * Karena kunci baru juga berawal dari class_id, foreign key selalu punya
 * indeks pendukung di sepanjang migrasi ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attendance_sessions', 'sequence')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->unsignedTinyInteger('sequence')->default(1)->after('session_date');
            });
        }

        // Baris yang sudah ada semuanya sesi pertama pada tanggalnya.
        DB::table('attendance_sessions')->update(['sequence' => 1]);

        $indexes = $this->indexNames();

        if (! in_array('attendance_sessions_class_id_session_date_sequence_unique', $indexes, true)) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->unique(['class_id', 'session_date', 'sequence']);
            });
        }

        if (in_array('attendance_sessions_class_id_session_date_unique', $this->indexNames(), true)) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->dropUnique('attendance_sessions_class_id_session_date_unique');
            });
        }
    }

    public function down(): void
    {
        /*
         * Kembali ke satu sesi per hari mustahil dilakukan diam-diam bila sudah
         * ada hari dengan lebih dari satu sesi — indeks uniknya akan gagal
         * dibuat. Sesi tambahan dihapus lebih dulu, dan itu memang menghapus
         * data, jadi rollback ini bukan operasi yang aman dijalankan sembarangan.
         */
        $extra = DB::table('attendance_sessions')->where('sequence', '>', 1)->pluck('id');

        if ($extra->isNotEmpty()) {
            DB::table('attendances')->whereIn('attendance_session_id', $extra)->delete();
            DB::table('attendance_sessions')->whereIn('id', $extra)->delete();
        }

        if (! in_array('attendance_sessions_class_id_session_date_unique', $this->indexNames(), true)) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->unique(['class_id', 'session_date']);
            });
        }

        if (in_array('attendance_sessions_class_id_session_date_sequence_unique', $this->indexNames(), true)) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->dropUnique('attendance_sessions_class_id_session_date_sequence_unique');
            });
        }

        if (Schema::hasColumn('attendance_sessions', 'sequence')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->dropColumn('sequence');
            });
        }
    }

    /** @return array<int, string> */
    private function indexNames(): array
    {
        return array_column(
            Schema::getIndexes('attendance_sessions'),
            'name'
        );
    }
};
