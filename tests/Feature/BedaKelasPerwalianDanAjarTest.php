<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kelas perwalian dan kelas ajar harus terbedakan sejak pandangan pertama.
 *
 * Kebanyakan wali kelas di Indonesia sekaligus mengajar sebagai guru mapel,
 * sehingga daftar kelasnya bercampur: satu kelas yang ia walikelasi di antara
 * beberapa kelas yang hanya ia ajar. Keduanya menuntut pekerjaan yang sangat
 * berbeda — biodata, kas, dan pelanggaran hanya milik yang pertama — tetapi
 * sebelumnya keduanya tampil dengan kartu yang identik.
 *
 * Kekeliruannya mahal dan sunyi: mengirim tautan biodata ke grup orang tua
 * kelas yang wali kelasnya orang lain tidak menimbulkan galat apa pun.
 */
class BedaKelasPerwalianDanAjarTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create();
        $this->actingAs($this->guru);
    }

    private function kelas(string $nama, string $jenis, array $mapel = []): Classroom
    {
        return Classroom::factory()->create([
            'user_id' => $this->guru->id,
            'name' => $nama,
            'jenis' => $jenis,
            'mapel' => $mapel,
        ]);
    }

    // -- Penanda di daftar kelas --------------------------------------------

    public function test_daftar_kelas_menandai_kedua_jenis_secara_berbeda(): void
    {
        $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);
        $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);

        $this->get(route('classes.index'))
            ->assertOk()
            ->assertSee('Wali Kelas')
            ->assertSee('Guru Mapel')
            // Mapel yang diampu adalah identitas kelas ajar; tanpa itu daftarnya
            // hanya berisi nama kelas yang mirip satu sama lain.
            ->assertSee('Informatika');
    }

    public function test_saringan_jenis_benar_benar_menyaring(): void
    {
        $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);
        $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);

        /*
         * Diperiksa dari daftar yang dikirim ke view, bukan dari teks seluruh
         * halaman. Sejak chrome memakai pembatas buku, tombol ganti kelas
         * menyebut SEMUA kelas guru di setiap halaman — memang itu gunanya —
         * sehingga assertDontSee() pada nama kelas selalu gagal walau
         * saringannya bekerja sempurna.
         */
        $nama = fn (?string $jenis) => $this->get(route('classes.index', $jenis ? ['jenis' => $jenis] : []))
            ->assertOk()
            ->viewData('classes')
            ->getCollection()
            ->pluck('name')
            ->all();

        $this->assertSame(['XII RPL'], $nama('ajar'));
        $this->assertSame(['XII TKJ D'], $nama('perwalian'));
    }

    /** Saringan yang tidak pernah berguna hanya menambah beban baca. */
    public function test_saringan_disembunyikan_bila_guru_hanya_punya_satu_jenis(): void
    {
        $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);

        $this->get(route('classes.index'))
            ->assertOk()
            ->assertDontSee('Semua Kelas');
    }

    public function test_saringan_bertahan_saat_pindah_halaman(): void
    {
        $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);

        for ($i = 1; $i <= 13; $i++) {
            $this->kelas("Kelas Ajar {$i}", Classroom::JENIS_AJAR, ['Informatika']);
        }

        $this->get(route('classes.index', ['jenis' => 'ajar']))
            ->assertOk()
            // Tanpa withQueryString(), tautan halaman 2 membuang saringannya dan
            // kelas perwalian muncul kembali tanpa diminta.
            ->assertSee('jenis=ajar', false);
    }

    // -- Isi kartu menyesuaikan jenisnya ------------------------------------

    public function test_kelas_ajar_tidak_menawarkan_form_biodata(): void
    {
        $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);

        $this->get(route('classes.index'))
            ->assertOk()
            // Guru mapel hanya memegang NIS dan nama; biodata adalah urusan
            // wali kelas, dan grup orang tuanya pun bukan miliknya.
            ->assertDontSee('Bagikan Form Mandiri Siswa');
    }

    public function test_kelas_perwalian_tetap_menawarkan_form_biodata(): void
    {
        $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);

        $this->get(route('classes.index'))
            ->assertOk()
            ->assertSee('Bagikan Form Mandiri Siswa');
    }

    public function test_kelas_ajar_menawarkan_jurnal_dan_nilai(): void
    {
        $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);

        $this->get(route('classes.index'))
            ->assertOk()
            ->assertSee('Jurnal Mengajar')
            ->assertSee('Nilai Harian')
            // Absensi kelas ajar manual, bukan magic link.
            ->assertSee('Absen Manual');
    }

    public function test_kelas_ajar_tidak_menampilkan_pelanggaran_di_kartunya(): void
    {
        $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);

        $this->get(route('classes.index'))
            ->assertOk()
            ->assertDontSee('Pelanggaran Siswa');
    }

    // -- Kelas perwalian tersemat di tab "Semua Kelas" ----------------------

    /**
     * Kelas perwalian harus berada di paling atas, bukan mengikuti tanggal buat.
     *
     * Guru yang mengampu banyak kelas mapel hanya punya satu kelas yang benar-
     * benar ia walikelasi, dan justru kelas itulah yang menuntut pekerjaan
     * paling banyak. Diurutkan menurut tanggal buat, kelas itu terdampar di
     * tengah daftar — di sini sengaja dibuat PALING LAMA supaya urutan bawaan
     * meletakkannya di posisi terakhir bila penyematannya tidak bekerja.
     */
    public function test_kelas_perwalian_disematkan_di_urutan_pertama(): void
    {
        $perwalian = $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);
        $perwalian->forceFill(['created_at' => now()->subYear()])->save();

        foreach (['XII RPL', 'XI RPL', 'X RPL'] as $nama) {
            $this->kelas($nama, Classroom::JENIS_AJAR, ['Informatika']);
        }

        $urutan = $this->get(route('classes.index'))
            ->assertOk()
            ->viewData('classes')
            ->pluck('name')
            ->all();

        $this->assertSame('XII TKJ D', $urutan[0], 'kelas perwalian harus tersemat di atas');
    }

    public function test_kartu_perwalian_diberi_penanda_tersemat(): void
    {
        $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);
        $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);

        $this->get(route('classes.index'))
            ->assertOk()
            ->assertSee('Kelas Wali Anda')
            // Animasinya bukan satu-satunya penanda: cincin dan pita tetap
            // terbaca bagi yang mematikan gerak di ponselnya.
            ->assertSee('animate-ping');
    }

    /** Di tab Perwalian semua kartu berjenis sama, jadi penyematan tidak berarti. */
    public function test_penanda_tersemat_tidak_muncul_saat_tab_perwalian(): void
    {
        $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);
        $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);

        $this->get(route('classes.index', ['jenis' => 'perwalian']))
            ->assertOk()
            ->assertDontSee('Kelas Wali Anda');
    }

    /**
     * Penyematan adalah pernyataan pembanding. Pada guru yang seluruh kelasnya
     * perwalian, setiap kartu akan tersemat — dan penanda yang muncul di semua
     * tempat berhenti menandakan apa pun.
     */
    public function test_penanda_tersemat_tidak_muncul_bila_semua_kelas_perwalian(): void
    {
        $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);
        $this->kelas('XII TKJ E', Classroom::JENIS_PERWALIAN);

        $this->get(route('classes.index'))
            ->assertOk()
            ->assertDontSee('Kelas Wali Anda');
    }

    public function test_kartu_kelas_ajar_tidak_ikut_tersemat(): void
    {
        $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);

        $this->get(route('classes.index'))
            ->assertOk()
            ->assertDontSee('Kelas Wali Anda');
    }

    // -- Penanda saat berada DI DALAM kelas ---------------------------------

    public function test_penanda_jenis_tampil_di_dalam_kelas_ajar(): void
    {
        $kelas = $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);

        $this->get(route('classes.show', $kelas))
            ->assertOk()
            ->assertSee('Guru Mapel')
            ->assertSee('Informatika');
    }

    public function test_penanda_jenis_tampil_di_dalam_kelas_perwalian(): void
    {
        $kelas = $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);

        $this->get(route('classes.show', $kelas))
            ->assertOk()
            ->assertSee('Wali Kelas')
            ->assertSee('seluruh administrasi kelas');
    }

    // -- Sebutan peran pemilik kelas ----------------------------------------

    /**
     * Kartu hero halaman ringkasan dulu selalu menulis "Wali Kelas: <nama>".
     *
     * Pada kelas ajar pemiliknya adalah guru mapel itu sendiri, sementara wali
     * kelasnya orang lain yang tidak tercatat di mana pun. Jadi kalimat itu
     * bukan label yang kurang pas melainkan pernyataan yang salah tentang siapa
     * orang tersebut — dan penanda jenis di class-nav tepat di atasnya sudah
     * menulis "Guru Mapel", sehingga satu halaman menyebut dua hal berbeda.
     */
    public function test_kelas_ajar_tidak_menyebut_pemiliknya_wali_kelas(): void
    {
        $kelas = $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);

        $this->get(route('classes.show', $kelas))
            ->assertOk()
            ->assertSee('Guru Mapel:')
            ->assertSee($this->guru->name)
            ->assertDontSee('Wali Kelas:');
    }

    public function test_kelas_perwalian_tetap_menyebut_wali_kelas(): void
    {
        $kelas = $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);

        $this->get(route('classes.show', $kelas))
            ->assertOk()
            ->assertSee('Wali Kelas:')
            ->assertDontSee('Guru Mapel:');
    }

    /** Lembar yang ditandatangani ikut salah menyebut, dan itu tercetak. */
    public function test_rekap_kehadiran_kelas_ajar_ditandatangani_guru_mapel(): void
    {
        $kelas = $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);

        $this->get(route('classes.reports.attendance', $kelas))
            ->assertOk()
            ->assertSee('Guru Mapel')
            ->assertDontSee('Wali Kelas');
    }

    // -- Modul perwalian tidak boleh bocor lewat halaman ringkasan ----------

    /**
     * class-nav menyingkirkan tab Laporan Administrasi dari kelas ajar, tetapi
     * kartu pintasan di halaman ringkasan membukanya kembali — jadi modul yang
     * dianggap sudah tertutup tetap dijangkau dari halaman yang sama.
     */
    public function test_ringkasan_kelas_ajar_tidak_menautkan_laporan_administrasi(): void
    {
        $kelas = $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);

        $this->get(route('classes.show', $kelas))
            ->assertOk()
            ->assertDontSee(route('classes.reports.full', $kelas), false)
            // Yang setara gunanya bagi guru mapel: daftar nilai mapelnya.
            ->assertSee(route('classes.nilai.index', $kelas), false);
    }

    public function test_ringkasan_kelas_perwalian_tetap_menautkan_laporan_administrasi(): void
    {
        $kelas = $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);

        $this->get(route('classes.show', $kelas))
            ->assertOk()
            ->assertSee(route('classes.reports.full', $kelas), false);
    }

    // -- EWS: poin kedisiplinan milik wali kelas ----------------------------

    /**
     * Poin kedisiplinan berasal dari buku poin pelanggaran — modul yang sengaja
     * disembunyikan dari guru mapel. Panel EWS menampilkannya kembali lengkap
     * dengan angkanya, jadi catatan pembinaan yang dipegang wali kelas terbaca
     * oleh setiap guru mapel yang mengajar kelas itu.
     */
    public function test_ews_kelas_ajar_tidak_membocorkan_poin_kedisiplinan(): void
    {
        $kelas = $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);

        Student::factory()->create([
            'user_id' => $this->guru->id,
            'class_id' => $kelas->id,
            'name' => 'Siswa Berpoin Rendah',
            'discipline_points' => 40,
            'is_active' => true,
        ]);

        $this->get(route('classes.show', $kelas))
            ->assertOk()
            ->assertDontSee('poin kedisiplinan')
            ->assertDontSee('Siswa Berpoin Rendah');
    }

    public function test_ews_kelas_perwalian_tetap_memakai_poin_kedisiplinan(): void
    {
        $kelas = $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);

        Student::factory()->create([
            'user_id' => $this->guru->id,
            'class_id' => $kelas->id,
            'name' => 'Siswa Berpoin Rendah',
            'discipline_points' => 40,
            'is_active' => true,
        ]);

        $this->get(route('classes.show', $kelas))
            ->assertOk()
            ->assertSee('Siswa Berpoin Rendah')
            ->assertSee('poin kedisiplinan 40');
    }

    /**
     * Ketidakhadiran dihitung untuk KELAS INI saja.
     *
     * Tanpa penyaringan class_id yang terhitung adalah seluruh absen siswa di
     * kelas guru mana pun, sehingga seorang siswa yang sering bolos di jam
     * pelajaran orang lain muncul sebagai berisiko di sini — dan guru yang
     * membukanya mencari sebab pada pelajaran yang tidak pernah ia tinggalkan.
     */
    public function test_ews_hanya_menghitung_ketidakhadiran_di_kelas_ini(): void
    {
        $kelasIni = $this->kelas('XII RPL', Classroom::JENIS_AJAR, ['Informatika']);
        $kelasLain = $this->kelas('XI RPL', Classroom::JENIS_AJAR, ['Informatika']);

        $siswa = Student::factory()->create([
            'user_id' => $this->guru->id,
            'class_id' => $kelasIni->id,
            'name' => 'Siswa Sering Alfa',
            'is_active' => true,
        ]);

        // Tiga alfa, semuanya di kelas LAIN.
        foreach (range(1, 3) as $ke) {
            $sesi = AttendanceSession::create([
                'user_id' => $this->guru->id,
                'class_id' => $kelasLain->id,
                'session_date' => today()->subDays($ke),
                'sequence' => 1,
                'token' => 'tok'.uniqid(),
                'pin_hash' => bcrypt('123456'),
                'expires_at' => now()->addDay(),
                'status' => 'submitted',
            ]);

            Attendance::create([
                'user_id' => $this->guru->id,
                'attendance_session_id' => $sesi->id,
                'student_id' => $siswa->id,
                'status' => 'alfa',
            ]);
        }

        $this->get(route('classes.show', $kelasIni))
            ->assertOk()
            ->assertDontSee('Siswa Sering Alfa');

        // Sebaliknya: alfa di kelas ini memang harus memunculkannya.
        foreach (range(1, 3) as $ke) {
            $sesi = AttendanceSession::create([
                'user_id' => $this->guru->id,
                'class_id' => $kelasIni->id,
                'session_date' => today()->subDays($ke),
                'sequence' => 1,
                'token' => 'tok'.uniqid(),
                'pin_hash' => bcrypt('123456'),
                'expires_at' => now()->addDay(),
                'status' => 'submitted',
            ]);

            Attendance::create([
                'user_id' => $this->guru->id,
                'attendance_session_id' => $sesi->id,
                'student_id' => $siswa->id,
                'status' => 'alfa',
            ]);
        }

        $this->get(route('classes.show', $kelasIni))
            ->assertOk()
            ->assertSee('Siswa Sering Alfa')
            ->assertSee('3x tidak hadir / 30 hari');
    }
}
