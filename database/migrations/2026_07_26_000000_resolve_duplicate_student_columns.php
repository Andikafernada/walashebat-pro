<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyatukan kolom kembar pada tabel students.
 *
 * Migrasi 2026_07_25_094914 menambahkan jenis_kelamin, alamat, no_hp_siswa, dan
 * no_hp_ortu padahal gender, address, phone, dan parent_phone sudah ada sejak
 * migrasi awal. Dua kolom untuk satu fakta yang sama punya akibat nyata:
 * notifikasi absensi membaca parent_phone, sedangkan import Excel menulis ke
 * no_hp_ortu, sehingga orang tua tidak pernah menerima pesan.
 *
 * Kolom Inggris dipilih sebagai kanonik karena SUDAH dipakai di seluruh aplikasi
 * (view, StudentRequest, mutator normalisasi Phone, ClassApiController, service
 * absensi, factory, seeder, dan tes). Mengganti nama ke bahasa Indonesia berarti
 * menyentuh semua titik itu sekaligus memindahkan data produksi — risiko besar
 * tanpa manfaat fungsional. Kolom baru yang memang belum ada (tempat_lahir,
 * agama, nama_ayah, dan seterusnya) tetap memakai nama Indonesia.
 *
 * Konsekuensinya penamaan jadi campur. Itu disengaja: konsistensi kosmetik tidak
 * sebanding dengan risiko menyentuh kolom yang dipakai fitur pengiriman pesan.
 */
return new class extends Migration
{
    /** duplikat => kanonik */
    private array $pairs = [
        'jenis_kelamin' => 'gender',
        'alamat' => 'address',
        'no_hp_siswa' => 'phone',
        'no_hp_ortu' => 'parent_phone',
    ];

    public function up(): void
    {
        // Selamatkan data yang mungkin sudah masuk ke kolom duplikat lebih dulu.
        // Kolom kanonik selalu menang: penyalinan hanya terjadi bila kanonik kosong.
        foreach ($this->pairs as $duplikat => $kanonik) {
            if (! Schema::hasColumn('students', $duplikat) || ! Schema::hasColumn('students', $kanonik)) {
                continue;
            }

            DB::table('students')
                ->whereNotNull($duplikat)
                ->where($duplikat, '!=', '')
                ->where(fn ($q) => $q->whereNull($kanonik)->orWhere($kanonik, ''))
                ->update([$kanonik => DB::raw($duplikat)]);
        }

        $drop = array_values(array_filter(
            array_keys($this->pairs),
            fn (string $column) => Schema::hasColumn('students', $column)
        ));

        if ($drop === []) {
            return;
        }

        Schema::table('students', function (Blueprint $table) use ($drop) {
            $table->dropColumn($drop);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'jenis_kelamin')) {
                $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            }
            if (! Schema::hasColumn('students', 'alamat')) {
                $table->text('alamat')->nullable();
            }
            if (! Schema::hasColumn('students', 'no_hp_siswa')) {
                $table->string('no_hp_siswa', 20)->nullable();
            }
            if (! Schema::hasColumn('students', 'no_hp_ortu')) {
                $table->string('no_hp_ortu', 20)->nullable();
            }
        });
    }
};
