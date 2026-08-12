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
 * Dashboard harus memisahkan kelas perwalian dari kelas ajar.
 *
 * Seorang guru bisa memegang satu kelas perwalian sekaligus tujuh kelas yang
 * hanya ia ajar. Sebelumnya seluruh angka dijumlahkan menjadi satu, dan yang
 * paling merugikan adalah kartu "Biodata Terisi": penyebutnya ikut memuat siswa
 * kelas ajar, yang menurut rancangan TIDAK MUNGKIN punya biodata orang tua
 * karena template impornya sengaja hanya NIS dan nama.
 *
 * Akibatnya kartu itu tidak akan pernah bisa mencapai 100% berapa pun kerja
 * wali kelasnya — pada data produksi ia membaca 43% padahal kelas perwalian
 * sudah 87%. Target yang mustahil dituntaskan berhenti dibaca sebagai target.
 */
class DashboardSaringanJenisTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create();
        $this->actingAs($this->guru);
    }

    private function kelas(string $nama, string $jenis): Classroom
    {
        return Classroom::factory()->create([
            'user_id' => $this->guru->id,
            'name' => $nama,
            'jenis' => $jenis,
            'is_active' => true,
        ]);
    }

    /** @param  array<string, mixed>  $atribut */
    private function siswa(Classroom $kelas, int $jumlah, array $atribut = []): void
    {
        Student::factory()->count($jumlah)->create($atribut + [
            'user_id' => $this->guru->id,
            'class_id' => $kelas->id,
            'is_active' => true,
        ]);
    }

    private function stats(?string $jenis = null): array
    {
        return $this->get(route('dashboard', $jenis ? ['jenis' => $jenis] : []))
            ->assertOk()
            ->viewData('stats');
    }

    // -- Cacat penyebut biodata ---------------------------------------------

    /**
     * Inti perkaranya: siswa kelas ajar tidak boleh masuk penyebut biodata.
     */
    public function test_biodata_dihitung_dari_siswa_perwalian_saja(): void
    {
        $perwalian = $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);
        $this->siswa($perwalian, 3, ['nama_ayah' => 'Bapak Ada']);
        $this->siswa($perwalian, 1, ['nama_ayah' => null]);

        // Kelas ajar: 20 siswa yang memang tidak akan pernah punya nama ayah.
        $this->siswa($this->kelas('XII RPL', Classroom::JENIS_AJAR), 20, ['nama_ayah' => null]);

        $stats = $this->stats();

        // 3 dari 4 siswa perwalian = 75%. Bila kelas ajar ikut terhitung,
        // angkanya menjadi 3 dari 24 = 13%.
        $this->assertSame(75, $stats['biodata_percent']);
        $this->assertSame(4, $stats['siswa_perwalian']);
    }

    /** Penyebutnya ikut ditulis di layar supaya angkanya bisa diperiksa. */
    public function test_kartu_biodata_menyebutkan_penyebutnya(): void
    {
        $perwalian = $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);
        $this->siswa($perwalian, 4);
        $this->siswa($this->kelas('XII RPL', Classroom::JENIS_AJAR), 20);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('dari 4 siswa perwalian');
    }

    /** Total siswa memang penjumlahan keduanya — itu bukan bagian yang salah. */
    public function test_total_siswa_tetap_menjumlahkan_kedua_jenis(): void
    {
        $this->siswa($this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN), 4);
        $this->siswa($this->kelas('XII RPL', Classroom::JENIS_AJAR), 20);

        $this->assertSame(24, $this->stats()['students']);
        $this->assertSame(2, $this->stats()['classes']);
    }

    // -- Saringan ------------------------------------------------------------

    public function test_saringan_perwalian_menyempitkan_seluruh_angka(): void
    {
        $this->siswa($this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN), 4);
        $this->siswa($this->kelas('XII RPL', Classroom::JENIS_AJAR), 20);

        $stats = $this->stats('perwalian');

        $this->assertSame(4, $stats['students']);
        $this->assertSame(1, $stats['classes']);
    }

    public function test_saringan_ajar_menyempitkan_seluruh_angka(): void
    {
        $this->siswa($this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN), 4);
        $this->siswa($this->kelas('XII RPL', Classroom::JENIS_AJAR), 20);

        $stats = $this->stats('ajar');

        $this->assertSame(20, $stats['students']);
        $this->assertSame(1, $stats['classes']);
    }

    public function test_jenis_ngawur_diperlakukan_sebagai_semua(): void
    {
        $this->siswa($this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN), 4);
        $this->siswa($this->kelas('XII RPL', Classroom::JENIS_AJAR), 20);

        $this->assertSame(24, $this->stats('bukan-jenis')['students']);
    }

    public function test_saringan_tampil_hanya_bila_guru_punya_kedua_jenis(): void
    {
        $this->siswa($this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN), 2);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('📚 Guru Mapel');

        $this->siswa($this->kelas('XII RPL', Classroom::JENIS_AJAR), 2);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('📚 Guru Mapel')
            ->assertSee('🏫 Perwalian');
    }

    // -- Kartu khas perwalian disembunyikan di mode ajar --------------------

    public function test_mode_ajar_menyembunyikan_kartu_khas_perwalian(): void
    {
        $this->siswa($this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN), 2);
        $this->siswa($this->kelas('XII RPL', Classroom::JENIS_AJAR), 2);

        $this->get(route('dashboard', ['jenis' => 'ajar']))
            ->assertOk()
            ->assertDontSee('Biodata Terisi')
            ->assertDontSee('Portofolio P5')
            ->assertDontSee('Siswa Perlu Perhatian (EWS)')
            // Yang berlaku di kedua jenis tetap ada.
            ->assertSee('Total Siswa')
            ->assertSee('Presensi Hari Ini')
            ->assertSee('Grafik Tren Kehadiran');
    }

    public function test_mode_perwalian_tetap_menampilkan_kartu_itu(): void
    {
        $this->siswa($this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN), 2);
        $this->siswa($this->kelas('XII RPL', Classroom::JENIS_AJAR), 2);

        $this->get(route('dashboard', ['jenis' => 'perwalian']))
            ->assertOk()
            ->assertSee('Biodata Terisi')
            ->assertSee('Portofolio P5')
            ->assertSee('Siswa Perlu Perhatian (EWS)');
    }

    /** Guru mapel murni tidak punya kelas perwalian sama sekali. */
    public function test_guru_tanpa_kelas_perwalian_tidak_melihat_kartu_perwalian(): void
    {
        $this->siswa($this->kelas('XII RPL', Classroom::JENIS_AJAR), 5);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Biodata Terisi')
            ->assertSee('Total Siswa');
    }

    // -- Grafik tren & EWS ikut saringan ------------------------------------

    /**
     * Grafik tren dulu selalu menggambar SELURUH kelas. Bila kartu di atasnya
     * sudah menyempit sementara grafiknya tidak, satu layar menyajikan dua
     * kebenaran yang berbeda tentang kelas yang sama.
     */
    public function test_grafik_tren_ikut_saringan(): void
    {
        $perwalian = $this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN);
        $ajar = $this->kelas('XII RPL', Classroom::JENIS_AJAR);

        $this->siswa($perwalian, 1);
        $this->siswa($ajar, 1);

        // Kelas perwalian hadir penuh; kelas ajar alfa semua, pada hari yang sama.
        $this->absen($perwalian, 'hadir');
        $this->absen($ajar, 'alfa');

        $hariIni = today()->format('d/m');

        $persen = fn (?string $jenis) => collect(
            $this->get(route('dashboard', $jenis ? ['jenis' => $jenis] : []))->viewData('chartTrend')
        )->firstWhere('tanggal', $hariIni)['persen'];

        $this->assertSame(100, $persen('perwalian'), 'perwalian hadir penuh');
        $this->assertSame(0, $persen('ajar'), 'kelas ajar alfa semua');
        $this->assertSame(50, $persen(null), 'gabungan: satu hadir dari dua');
    }

    /** Poin kedisiplinan milik wali kelas; siswa kelas ajar tidak boleh masuk EWS. */
    public function test_ews_tidak_menghitung_siswa_kelas_ajar(): void
    {
        $this->siswa($this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN), 1);
        $this->siswa($this->kelas('XII RPL', Classroom::JENIS_AJAR), 1, [
            'name' => 'Siswa Mapel Berpoin Rendah',
            'discipline_points' => 40,
        ]);

        $perluPerhatian = $this->get(route('dashboard'))
            ->assertOk()
            ->viewData('perluPerhatian');

        $this->assertCount(0, $perluPerhatian);
    }

    public function test_ews_tetap_menghitung_siswa_perwalian(): void
    {
        $this->siswa($this->kelas('XII TKJ D', Classroom::JENIS_PERWALIAN), 1, [
            'name' => 'Siswa Wali Berpoin Rendah',
            'discipline_points' => 40,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Siswa Wali Berpoin Rendah');
    }

    private function absen(Classroom $kelas, string $status): void
    {
        $sesi = AttendanceSession::create([
            'user_id' => $this->guru->id,
            'class_id' => $kelas->id,
            'session_date' => today(),
            'sequence' => 1,
            'token' => 'tok'.uniqid(),
            'pin_hash' => bcrypt('123456'),
            'expires_at' => now()->addDay(),
            'status' => 'submitted',
        ]);

        foreach ($kelas->students as $s) {
            Attendance::create([
                'user_id' => $this->guru->id,
                'attendance_session_id' => $sesi->id,
                'student_id' => $s->id,
                'status' => $status,
            ]);
        }
    }
}
