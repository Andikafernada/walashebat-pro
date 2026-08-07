<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $this->addIfMissing($table, 'nisn', fn () => $table->string('nisn', 20)->nullable()->after('nis'));
            $this->addIfMissing($table, 'jenis_kelamin', fn () => $table->enum('jenis_kelamin', ['L', 'P'])->nullable());
            $this->addIfMissing($table, 'tempat_lahir', fn () => $table->string('tempat_lahir')->nullable());
            $this->addIfMissing($table, 'tanggal_lahir', fn () => $table->date('tanggal_lahir')->nullable());
            $this->addIfMissing($table, 'agama', fn () => $table->enum('agama', [
                'Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya',
            ])->nullable());
            $this->addIfMissing($table, 'anak_ke', fn () => $table->unsignedTinyInteger('anak_ke')->nullable());
            $this->addIfMissing($table, 'jumlah_saudara', fn () => $table->unsignedTinyInteger('jumlah_saudara')->nullable());
            $this->addIfMissing($table, 'golongan_darah', fn () => $table->enum('golongan_darah', ['A', 'B', 'AB', 'O', 'Tidak Tahu'])->nullable());
            $this->addIfMissing($table, 'tinggi_badan_cm', fn () => $table->unsignedSmallInteger('tinggi_badan_cm')->nullable());
            $this->addIfMissing($table, 'berat_badan_kg', fn () => $table->unsignedSmallInteger('berat_badan_kg')->nullable());

            $this->addIfMissing($table, 'alamat', fn () => $table->text('alamat')->nullable());
            $this->addIfMissing($table, 'rt_rw', fn () => $table->string('rt_rw', 10)->nullable());
            $this->addIfMissing($table, 'kelurahan', fn () => $table->string('kelurahan')->nullable());
            $this->addIfMissing($table, 'kecamatan', fn () => $table->string('kecamatan')->nullable());
            $this->addIfMissing($table, 'no_hp_siswa', fn () => $table->string('no_hp_siswa', 20)->nullable());
            $this->addIfMissing($table, 'no_hp_ortu', fn () => $table->string('no_hp_ortu', 20)->nullable());
            $this->addIfMissing($table, 'jarak_rumah_km', fn () => $table->float('jarak_rumah_km')->nullable());
            $this->addIfMissing($table, 'moda_transportasi', fn () => $table->string('moda_transportasi')->nullable());

            $this->addIfMissing($table, 'nama_ayah', fn () => $table->string('nama_ayah')->nullable());
            $this->addIfMissing($table, 'pekerjaan_ayah', fn () => $table->string('pekerjaan_ayah')->nullable());
            $this->addIfMissing($table, 'nama_ibu', fn () => $table->string('nama_ibu')->nullable());
            $this->addIfMissing($table, 'pekerjaan_ibu', fn () => $table->string('pekerjaan_ibu')->nullable());
            $this->addIfMissing($table, 'nama_wali', fn () => $table->string('nama_wali')->nullable());
            $this->addIfMissing($table, 'pekerjaan_wali', fn () => $table->string('pekerjaan_wali')->nullable());
            $this->addIfMissing($table, 'alamat_ortu', fn () => $table->text('alamat_ortu')->nullable());

            $this->addIfMissing($table, 'asal_sekolah', fn () => $table->string('asal_sekolah')->nullable());
            $this->addIfMissing($table, 'tahun_masuk', fn () => $table->year('tahun_masuk')->nullable());
            $this->addIfMissing($table, 'kompetensi_keahlian', fn () => $table->string('kompetensi_keahlian')->nullable());

            $this->addIfMissing($table, 'foto_path', fn () => $table->string('foto_path')->nullable());
            $this->addIfMissing($table, 'hobi', fn () => $table->string('hobi')->nullable());
            $this->addIfMissing($table, 'cita_cita', fn () => $table->string('cita_cita')->nullable());
            $this->addIfMissing($table, 'penerima_kip', fn () => $table->boolean('penerima_kip')->default(false));
            $this->addIfMissing($table, 'penerima_pkh', fn () => $table->boolean('penerima_pkh')->default(false));
        });
    }

    protected function addIfMissing(Blueprint $table, string $column, \Closure $callback): void
    {
        if (! Schema::hasColumn($table->getTable(), $column)) {
            $callback();
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            foreach ([
                'nisn', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama',
                'anak_ke', 'jumlah_saudara', 'golongan_darah', 'tinggi_badan_cm', 'berat_badan_kg',
                'alamat', 'rt_rw', 'kelurahan', 'kecamatan', 'no_hp_siswa', 'no_hp_ortu',
                'jarak_rumah_km', 'moda_transportasi',
                'nama_ayah', 'pekerjaan_ayah', 'nama_ibu', 'pekerjaan_ibu', 'nama_wali', 'pekerjaan_wali', 'alamat_ortu',
                'asal_sekolah', 'tahun_masuk', 'kompetensi_keahlian',
                'foto_path', 'hobi', 'cita_cita', 'penerima_kip', 'penerima_pkh',
            ] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
