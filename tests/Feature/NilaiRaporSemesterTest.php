<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nilai tengah semester (PTS) dan akhir semester (PAS), semester 1 dan 2.
 *
 * Ini kebutuhan WALI KELAS, bukan guru mapel. Sebelumnya `assessments` hanya
 * mengenal nilai harian per Capaian Pembelajaran — konsep guru mapel — dan
 * wali kelas tidak punya tempat mencatat nilai rapor.
 *
 * Yang paling menentukan di sini adalah semester disimpan, bukan disimpulkan
 * dari tanggal: nilai akhir semester 1 lazim baru dimasukkan pada Januari,
 * yang menurut kalender sudah semester 2.
 */
class NilaiRaporSemesterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Kelas PERWALIAN: tidak punya daftar mapel sama sekali.
        $this->kelas = Classroom::factory()->create([
            'user_id' => $this->user->id,
            'jenis' => Classroom::JENIS_PERWALIAN,
            'mapel' => [],
        ]);
    }

    private function siswa(string $nama): Student
    {
        return Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->kelas->id,
            'name' => $nama,
            'is_active' => true,
        ]);
    }

    /** @param array<string, mixed> $ganti */
    private function payload(array $ganti = []): array
    {
        return array_merge([
            'jenis' => Assessment::JENIS_PAS,
            'semester' => 1,
            'mapel' => 'Matematika',
            'assessment_date' => today()->toDateString(),
        ], $ganti);
    }

    // -- Menyimpan ----------------------------------------------------------

    public function test_wali_kelas_bisa_menyimpan_nilai_pas(): void
    {
        $andi = $this->siswa('Andi');

        $this->post(route('classes.nilai.store', $this->kelas), $this->payload([
            'nilai' => [$andi->id => 88],
        ]))->assertRedirect();

        $penilaian = Assessment::withoutTenant()->firstOrFail();

        $this->assertSame(Assessment::JENIS_PAS, $penilaian->jenis);
        $this->assertSame(1, $penilaian->semester);
        $this->assertSame('Matematika', $penilaian->mapel);
        $this->assertSame(88, $penilaian->scores()->where('student_id', $andi->id)->value('nilai'));
    }

    public function test_capaian_pembelajaran_tidak_wajib_pada_pts_dan_pas(): void
    {
        /*
         * PTS dan PAS menilai satu semester penuh, bukan satu capaian.
         * Mewajibkannya memaksa wali kelas mengarang isian yang tidak berarti
         * apa-apa hanya supaya formulirnya mau tersimpan.
         */
        $this->post(route('classes.nilai.store', $this->kelas), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertNull(Assessment::withoutTenant()->firstOrFail()->capaian_pembelajaran);
    }

    public function test_mapel_wajib_pada_pts_dan_pas(): void
    {
        /*
         * Leger nilai disusun per mapel. Penilaian rapor tanpa nama mapel
         * menumpuk pada satu kolom tanpa nama dan rekapnya kehilangan gunanya.
         */
        $this->post(route('classes.nilai.store', $this->kelas), $this->payload(['mapel' => null]))
            ->assertSessionHasErrors('mapel');
    }

    public function test_mapel_tetap_boleh_kosong_pada_nilai_harian(): void
    {
        // Kelas perwalian memang tidak selalu punya mapel.
        $this->post(route('classes.nilai.store', $this->kelas), $this->payload([
            'jenis' => Assessment::JENIS_HARIAN,
            'mapel' => null,
            'capaian_pembelajaran' => 'Gotong royong',
        ]))->assertSessionHasNoErrors();

        $this->assertNull(Assessment::withoutTenant()->firstOrFail()->mapel);
    }

    public function test_semester_wajib_dan_hanya_1_atau_2(): void
    {
        $this->post(route('classes.nilai.store', $this->kelas), $this->payload(['semester' => 3]))
            ->assertSessionHasErrors('semester');

        $this->post(route('classes.nilai.store', $this->kelas), $this->payload(['semester' => null]))
            ->assertSessionHasErrors('semester');
    }

    public function test_jenis_asing_ditolak(): void
    {
        $this->post(route('classes.nilai.store', $this->kelas), $this->payload(['jenis' => 'ujian-nasional']))
            ->assertSessionHasErrors('jenis');
    }

    public function test_semester_mengikuti_pilihan_bukan_tanggal(): void
    {
        /*
         * Inti perbaikannya. Nilai akhir semester 1 dimasukkan pada Januari —
         * kalender bilang semester 2, tetapi nilainya milik semester 1.
         * Menyimpulkan semester dari tanggal akan memindahkannya ke semester
         * yang salah, diam-diam, dan baru ketahuan saat rapor tidak cocok.
         */
        $this->post(route('classes.nilai.store', $this->kelas), $this->payload([
            'semester' => 1,
            'assessment_date' => '2027-01-15',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, Assessment::withoutTenant()->firstOrFail()->semester);
    }

    // -- Leger nilai --------------------------------------------------------

    /** Satu penilaian PAS untuk satu mapel, dengan nilai beberapa siswa. */
    private function buatPas(string $mapel, int $semester, array $nilaiPerSiswa): void
    {
        $this->post(route('classes.nilai.store', $this->kelas), $this->payload([
            'mapel' => $mapel,
            'semester' => $semester,
            'nilai' => $nilaiPerSiswa,
        ]))->assertSessionHasNoErrors();
    }

    public function test_leger_menyusun_baris_siswa_dan_kolom_mapel(): void
    {
        $andi = $this->siswa('Andi');
        $budi = $this->siswa('Budi');

        $this->buatPas('Matematika', 1, [$andi->id => 90, $budi->id => 70]);
        $this->buatPas('Bahasa Indonesia', 1, [$andi->id => 80, $budi->id => 60]);

        $this->get(route('classes.nilai.rekap', [$this->kelas, 'jenis' => 'pas', 'semester' => 1]))
            ->assertOk()
            ->assertSee('Matematika')
            ->assertSee('Bahasa Indonesia')
            ->assertSee('Andi')
            ->assertSee('Budi')
            ->assertSee('90')
            ->assertSee('60');
    }

    public function test_leger_semester_lain_tidak_ikut_terbawa(): void
    {
        $andi = $this->siswa('Andi');

        $this->buatPas('Matematika', 1, [$andi->id => 90]);
        $this->buatPas('Prakarya', 2, [$andi->id => 65]);

        $this->get(route('classes.nilai.rekap', [$this->kelas, 'jenis' => 'pas', 'semester' => 1]))
            ->assertOk()
            ->assertSee('Matematika')
            ->assertDontSee('Prakarya');
    }

    public function test_leger_memisahkan_pts_dari_pas(): void
    {
        $andi = $this->siswa('Andi');

        $this->buatPas('Matematika', 1, [$andi->id => 90]);

        $this->post(route('classes.nilai.store', $this->kelas), $this->payload([
            'jenis' => Assessment::JENIS_PTS,
            'mapel' => 'Sejarah',
            'semester' => 1,
            'nilai' => [$andi->id => 75],
        ]))->assertSessionHasNoErrors();

        $this->get(route('classes.nilai.rekap', [$this->kelas, 'jenis' => 'pts', 'semester' => 1]))
            ->assertOk()
            ->assertSee('Sejarah')
            ->assertDontSee('Matematika');
    }

    public function test_nilai_harian_tidak_pernah_masuk_leger(): void
    {
        /*
         * Leger adalah bahan rapor. Nilai harian jumlahnya banyak dan tidak
         * setara PTS/PAS; ikut menariknya ke sini akan mengubah rata-rata
         * rapor menjadi angka yang tidak dimaksudkan siapa pun.
         */
        $andi = $this->siswa('Andi');

        $this->post(route('classes.nilai.store', $this->kelas), $this->payload([
            'jenis' => Assessment::JENIS_HARIAN,
            'mapel' => 'Penjaskes',
            'capaian_pembelajaran' => 'Lompat jauh',
            'semester' => 1,
            'nilai' => [$andi->id => 100],
        ]))->assertSessionHasNoErrors();

        $this->get(route('classes.nilai.rekap', [$this->kelas, 'jenis' => 'pas', 'semester' => 1]))
            ->assertOk()
            ->assertDontSee('Penjaskes');
    }

    public function test_siswa_tanpa_nilai_ditandai_belum_dinilai_bukan_nol(): void
    {
        $andi = $this->siswa('Andi');
        $budi = $this->siswa('Budi');

        // Hanya Andi yang dinilai; Budi sedang sakit saat ujian.
        $this->buatPas('Matematika', 1, [$andi->id => 90]);

        $html = $this->get(route('classes.nilai.rekap', [$this->kelas, 'jenis' => 'pas', 'semester' => 1]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Budi', $html);
        $this->assertStringNotContainsString('>0<', $html,
            'Belum dinilai tidak boleh tampil sebagai nol — wali kelas akan mengira anaknya dapat nol');
    }

    public function test_rekap_hanya_menjangkau_kelas_sendiri(): void
    {
        $lain = User::factory()->create();
        $kelasLain = Classroom::factory()->create([
            'user_id' => $lain->id,
            'jenis' => Classroom::JENIS_PERWALIAN,
        ]);

        $this->get(route('classes.nilai.rekap', $kelasLain))->assertNotFound();
    }
}
