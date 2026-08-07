<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membedakan kelas PERWALIAN dari kelas AJAR.
 *
 * Sebagian besar wali kelas di Indonesia juga guru mapel: satu kelas
 * perwalian, tetapi mengajar mapelnya di beberapa kelas lain. Selama ini
 * aplikasi hanya melayani yang pertama — kelas kedua dan seterusnya tidak
 * punya tempat sama sekali.
 *
 * Membuatnya sebagai kelas biasa keliru: guru akan ditawari buku kas, struktur
 * organisasi, laporan administrasi, dan pembagian biodata ke grup orang tua
 * untuk kelas yang wali kelasnya ORANG LAIN. Kalau biodata dan nomor orang tua
 * ikut diisi di sana, lahir salinan kedua yang akan menyimpang dari data wali
 * kelas aslinya.
 *
 * Yang lebih berbahaya: menyalakan absensi otomatis di kelas ajar akan
 * mengirim magic link + PIN ke grup orang tua yang bukan miliknya.
 *
 * Semua kelas lama otomatis menjadi 'perwalian', jadi tidak ada satu pun
 * perilaku yang berubah bagi pengguna yang sudah berjalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->enum('jenis', ['perwalian', 'ajar'])
                ->default('perwalian')
                ->after('name');

            /*
             * Mapel yang diampu guru di kelas itu — bisa lebih dari satu.
             *
             * Disimpan sebagai daftar (JSON dalam kolom teks) alih-alih dua
             * kolom terpisah: hari ini maksimal dua, tetapi jumlahnya keputusan
             * jadwal sekolah yang bisa berubah tiap semester, dan skema yang
             * memaku angkanya akan menghalangi tanpa alasan.
             *
             * Tipe `text` + cast array, bukan tipe `json` bawaan: rangkaian tes
             * berjalan di SQLite sedangkan produksi MariaDB, dan `text`
             * berperilaku sama di keduanya.
             */
            $table->text('mapel')->nullable()->after('major');
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            /*
             * Mapel PADA SESI, bukan pada kelas.
             *
             * Satu guru bisa mengampu dua mapel di kelas yang sama, sehingga
             * penandanya harus melekat pada tiap pertemuan — kalau tidak,
             * presensi dan rekap kedua mapel itu tercampur jadi satu.
             *
             * Kosong berarti presensi harian wali kelas seperti sebelumnya.
             */
            $table->string('mapel', 100)->nullable()->after('title');

            /*
             * Materi yang diajarkan pada pertemuan itu — bekal jurnal mengajar.
             *
             * Disertakan sejak sekarang meski laporan jurnalnya menyusul,
             * karena jurnal TIDAK BISA diisi mundur: tidak ada yang mengingat
             * materi tanggal 12 Agustus pada bulan berikutnya. Kolomnya boleh
             * kosong, jadi tidak memaksa siapa pun.
             */
            $table->string('materi', 500)->nullable()->after('mapel');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['jenis', 'mapel']);
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropColumn(['mapel', 'materi']);
        });
    }
};
