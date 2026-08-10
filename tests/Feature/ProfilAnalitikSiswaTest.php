<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\CharacterDimension;
use App\Models\CharacterRecord;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentProfileBuilder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Profil siswa yang menyatukan absensi, NILAI, dan KARAKTER P5.
 *
 * Sebelumnya ketiganya tersebar di tiga halaman: absensi di profil siswa,
 * nilai di menu Nilai, karakter di menu Karakter. Untuk menjawab satu
 * pertanyaan sederhana — "anak ini bagaimana perkembangannya?" — wali kelas
 * harus membuka tiga tempat dan menggabungkannya di kepala, justru pada saat
 * orang tua sedang menunggu jawaban.
 *
 * Yang paling menentukan di sini: nilai rapor disaring per SEMESTER, bukan
 * per rentang tanggal periode.
 */
class ProfilAnalitikSiswaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    private Student $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create();
        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);
        $this->siswa = Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'name' => 'Fitriani Salsabila',
        ]);

        $this->actingAs($this->user);
    }

    private function nilai(string $jenis, int $semester, string $mapel, int $angka, ?string $tanggal = null): void
    {
        $penilaian = Assessment::create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'jenis' => $jenis,
            'semester' => $semester,
            'mapel' => $mapel,
            'capaian_pembelajaran' => $jenis === Assessment::JENIS_HARIAN ? 'CP uji' : null,
            'assessment_date' => $tanggal ?? today()->toDateString(),
        ]);

        AssessmentScore::create([
            'user_id' => $this->user->id,
            'assessment_id' => $penilaian->id,
            'student_id' => $this->siswa->id,
            'nilai' => $angka,
        ]);
    }

    private function catatanKarakter(string $dimensi, string $tipe, int $skor): void
    {
        $d = CharacterDimension::create([
            'user_id' => $this->user->id,
            // `code` wajib pada tabelnya; diturunkan dari nama agar tiap
            // dimensi uji punya kode sendiri tanpa perlu ditulis dua kali.
            'code' => str($dimensi)->slug('_')->limit(20, ''),
            'name' => $dimensi,
            'is_active' => true,
        ]);

        CharacterRecord::create([
            'user_id' => $this->user->id,
            'student_id' => $this->siswa->id,
            'class_id' => $this->class->id,
            'character_dimension_id' => $d->id,
            'type' => $tipe,
            'score' => $skor,
            'title' => 'Catatan '.$dimensi,
            'record_date' => today()->toDateString(),
        ]);
    }

    /** @return array<string, mixed> */
    private function profil(int $semester = 1): array
    {
        return app(StudentProfileBuilder::class)->build(
            $this->siswa,
            today()->startOfMonth(),
            today()->endOfMonth(),
            $semester,
        );
    }

    // -- Nilai --------------------------------------------------------------

    public function test_nilai_rapor_dikelompokkan_per_mapel(): void
    {
        $this->nilai(Assessment::JENIS_PTS, 1, 'Matematika', 80);
        $this->nilai(Assessment::JENIS_PAS, 1, 'Matematika', 90);

        $rapor = $this->profil()['nilai']['rapor'];

        $this->assertCount(1, $rapor, 'PTS dan PAS mapel yang sama harus jadi satu baris');
        $this->assertSame('Matematika', $rapor[0]['mapel']);
        $this->assertSame(80.0, $rapor[0]['pts']);
        $this->assertSame(90.0, $rapor[0]['pas']);
    }

    public function test_nilai_rapor_disaring_per_semester_bukan_per_tanggal(): void
    {
        /*
         * Inti perbaikannya. Nilai PAS semester 1 dimasukkan pada Januari —
         * di luar rentang tanggal periode yang sedang dilihat. Kalau
         * penyaringannya memakai tanggal, nilai ini hilang dari profil dan
         * dari berkas yang diserahkan ke orang tua.
         */
        $this->nilai(Assessment::JENIS_PAS, 1, 'Matematika', 88, today()->addYear()->toDateString());

        $rapor = $this->profil(1)['nilai']['rapor'];

        $this->assertCount(1, $rapor);
        $this->assertSame(88.0, $rapor[0]['pas']);
    }

    public function test_nilai_semester_lain_tidak_ikut(): void
    {
        $this->nilai(Assessment::JENIS_PAS, 1, 'Matematika', 88);
        $this->nilai(Assessment::JENIS_PAS, 2, 'Prakarya', 70);

        $rapor = $this->profil(1)['nilai']['rapor'];

        $this->assertCount(1, $rapor);
        $this->assertSame('Matematika', $rapor[0]['mapel']);
    }

    public function test_nilai_harian_dipisah_dari_rapor(): void
    {
        /*
         * Nilai harian jumlahnya banyak dan bergerak; PTS/PAS sedikit dan
         * menjadi bahan keputusan. Meleburnya menjadi satu rata-rata
         * menghasilkan angka yang tidak dimaksudkan siapa pun.
         */
        $this->nilai(Assessment::JENIS_PAS, 1, 'Matematika', 90);
        $this->nilai(Assessment::JENIS_HARIAN, 1, 'Matematika', 60);
        $this->nilai(Assessment::JENIS_HARIAN, 1, 'Matematika', 70);

        $nilai = $this->profil(1)['nilai'];

        $this->assertSame(90.0, $nilai['rapor'][0]['pas']);
        $this->assertNull($nilai['rapor'][0]['pts'], 'PTS belum ada, jangan diisi dari nilai harian');
        $this->assertSame(65.0, $nilai['harian'][0]['rata']);
        $this->assertSame(2, $nilai['harian'][0]['jumlah']);
        $this->assertSame(90.0, $nilai['rata_rapor'], 'Rata-rata rapor tidak boleh tercampur nilai harian');
    }

    public function test_belum_dinilai_tetap_null_bukan_nol(): void
    {
        $this->nilai(Assessment::JENIS_PTS, 1, 'Matematika', 80);

        $rapor = $this->profil(1)['nilai']['rapor'];

        $this->assertNull($rapor[0]['pas'], 'Belum ada PAS berarti null, bukan 0');
    }

    public function test_tanpa_nilai_ditandai_tidak_ada(): void
    {
        $this->assertFalse($this->profil()['nilai']['ada']);
        $this->assertNull($this->profil()['nilai']['rata_rapor']);
    }

    // -- Karakter P5 --------------------------------------------------------

    public function test_karakter_dikelompokkan_per_dimensi(): void
    {
        $this->catatanKarakter('Gotong Royong', CharacterRecord::TYPE_POSITIVE, 5);
        $this->catatanKarakter('Kemandirian', CharacterRecord::TYPE_NEGATIVE, -5);

        $karakter = $this->profil()['karakter'];

        $this->assertCount(2, $karakter['dimensi']);
        $this->assertSame('Gotong Royong', $karakter['dimensi'][0]['dimensi']);
        $this->assertSame(1, $karakter['dimensi'][0]['positif']);
        $this->assertSame(1, $karakter['dimensi'][1]['negatif']);
        $this->assertSame(1, $karakter['total']['positif']);
        $this->assertSame(1, $karakter['total']['negatif']);
    }

    public function test_prestasi_dihitung_sebagai_positif(): void
    {
        $this->catatanKarakter('Kreativitas', CharacterRecord::TYPE_ACHIEVEMENT, 10);

        $this->assertSame(1, $this->profil()['karakter']['total']['positif']);
    }

    public function test_pengamatan_tidak_dihitung_positif_maupun_negatif(): void
    {
        // Catatan pengamatan bersifat netral: dicatat, tidak berpoin. Ikut
        // menghitungnya sebagai positif akan membuat anak terlihat lebih baik
        // daripada yang sebenarnya tercatat.
        $this->catatanKarakter('Bernalar Kritis', CharacterRecord::TYPE_OBSERVATION, 0);

        $total = $this->profil()['karakter']['total'];

        $this->assertSame(0, $total['positif']);
        $this->assertSame(0, $total['negatif']);
        $this->assertSame(1, $total['lainnya']);
    }

    // -- Halaman & PDF ------------------------------------------------------

    public function test_halaman_profil_menampilkan_nilai_dan_karakter(): void
    {
        $this->nilai(Assessment::JENIS_PAS, 1, 'Matematika', 88);
        $this->catatanKarakter('Gotong Royong', CharacterRecord::TYPE_POSITIVE, 5);

        $this->get(route('classes.students.show', [$this->class, $this->siswa]).'?mode=semester&sub_periode=1_penuh')
            ->assertOk()
            ->assertSee('Nilai')
            ->assertSee('Matematika')
            ->assertSee('88')
            ->assertSee('Karakter P5')
            ->assertSee('Gotong Royong');
    }

    public function test_pdf_profil_tetap_terbentuk_dengan_seksi_baru(): void
    {
        $this->nilai(Assessment::JENIS_PAS, 1, 'Matematika', 88);
        $this->catatanKarakter('Gotong Royong', CharacterRecord::TYPE_POSITIVE, 5);

        $res = $this->get(route('classes.students.pdf', [$this->class, $this->siswa]).'?mode=semester&sub_periode=1_penuh');

        $res->assertOk();
        // download() dari dompdf membalas Response biasa, bukan streamed.
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_profil_siswa_kelas_lain_tetap_tertutup(): void
    {
        $lain = User::factory()->create();
        $kelasLain = Classroom::factory()->create(['user_id' => $lain->id]);
        $siswaLain = Student::factory()->create([
            'user_id' => $lain->id,
            'class_id' => $kelasLain->id,
        ]);

        $this->get(route('classes.students.show', [$kelasLain, $siswaLain]))->assertNotFound();
    }
}
