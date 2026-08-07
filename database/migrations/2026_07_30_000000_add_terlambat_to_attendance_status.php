<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan status "terlambat" ke absensi.
 *
 * Keterlambatan adalah porsi besar pekerjaan wali kelas, tapi sampai sekarang
 * tidak punya tempat: petugas terpaksa menandainya "hadir" (keterlambatannya
 * hilang sama sekali) atau "alfa" (tidak adil — anaknya masuk). Keduanya
 * membuat data yang paling sering ditanya BK justru yang paling tidak akurat.
 *
 * Kolomnya enum, jadi daftar nilainya harus diubah di level database. MySQL
 * bisa memodifikasi enum di tempat; SQLite tidak mengenal enum sama sekali dan
 * menyimpannya sebagai varchar dengan CHECK constraint, sehingga tabelnya perlu
 * disusun ulang. Karena itu perlakuannya dibedakan per driver.
 */
return new class extends Migration
{
    private const STATUS_BARU = ['hadir', 'terlambat', 'sakit', 'izin', 'alfa'];

    private const STATUS_LAMA = ['hadir', 'sakit', 'izin', 'alfa'];

    public function up(): void
    {
        $this->ubahEnum(self::STATUS_BARU);
    }

    public function down(): void
    {
        /*
         * Turun berarti menghapus nilai yang mungkin sudah dipakai. Baris
         * "terlambat" dijadikan "hadir" — bukan "alfa" — karena siswanya
         * memang hadir; keterlambatannya yang hilang, bukan kehadirannya.
         */
        DB::table('attendances')->where('status', 'terlambat')->update(['status' => 'hadir']);

        $this->ubahEnum(self::STATUS_LAMA);
    }

    /** @param array<int, string> $nilai */
    private function ubahEnum(array $nilai): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $daftar = implode(', ', array_map(fn ($v) => "'".$v."'", $nilai));

            DB::statement(
                "ALTER TABLE attendances MODIFY status ENUM({$daftar}) NOT NULL DEFAULT 'alfa'"
            );

            return;
        }

        // SQLite dan lainnya: change() akan menyusun ulang tabel berikut
        // CHECK constraint-nya. Data yang ada ikut terbawa.
        Schema::table('attendances', function (Blueprint $table) use ($nilai) {
            $table->enum('status', $nilai)->default('alfa')->change();
        });
    }
};
