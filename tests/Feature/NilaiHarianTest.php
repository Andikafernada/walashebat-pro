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
 * Nilai harian per Capaian Pembelajaran.
 *
 * Yang paling menentukan di sini bukan penyimpanannya, melainkan perbedaan
 * antara "belum dinilai" dan "dapat nol". Menyamakan keduanya membuat
 * rata-rata kelas anjlok oleh siswa yang sebenarnya belum diuji, dan guru
 * mengejar remedial untuk anak yang tidak memerlukannya.
 */
class NilaiHarianTest extends TestCase
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

        $this->kelas = Classroom::factory()->create([
            'user_id' => $this->user->id,
            'jenis' => Classroom::JENIS_AJAR,
            'mapel' => ['Matematika', 'Informatika'],
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

    // -- Menyimpan ----------------------------------------------------------

    public function test_menyimpan_penilaian_beserta_nilai_sekelas(): void
    {
        $andi = $this->siswa('Andi');
        $budi = $this->siswa('Budi');

        $this->post(route('classes.nilai.store', $this->kelas), [
            'capaian_pembelajaran' => 'Memahami sistem bilangan biner',
            'assessment_date' => today()->toDateString(),
            'mapel' => 'Matematika',
            'nilai' => [$andi->id => 90, $budi->id => 70],
        ])->assertRedirect();

        $penilaian = Assessment::withoutTenant()->firstOrFail();

        $this->assertSame('Memahami sistem bilangan biner', $penilaian->capaian_pembelajaran);
        $this->assertSame('Matematika', $penilaian->mapel);
        $this->assertSame(90, $penilaian->scores()->where('student_id', $andi->id)->first()->nilai);
        $this->assertSame(70, $penilaian->scores()->where('student_id', $budi->id)->first()->nilai);
    }

    // -- Kosong BUKAN nol ---------------------------------------------------

    public function test_nilai_kosong_tersimpan_sebagai_belum_dinilai(): void
    {
        $andi = $this->siswa('Andi');
        $sakit = $this->siswa('Sakit Saat Ulangan');

        $this->post(route('classes.nilai.store', $this->kelas), [
            'capaian_pembelajaran' => 'CP 1',
            'assessment_date' => today()->toDateString(),
            'nilai' => [$andi->id => 80, $sakit->id => ''],
        ])->assertRedirect();

        $penilaian = Assessment::withoutTenant()->firstOrFail();

        $this->assertSame(80, $penilaian->scores()->where('student_id', $andi->id)->first()->nilai);
        $this->assertNull(
            $penilaian->scores()->where('student_id', $sakit->id)->first()->nilai,
            'kolom kosong harus null, bukan 0'
        );
    }

    /** Inti perlindungannya: yang belum dinilai tidak boleh menyeret rata-rata. */
    public function test_rata_rata_mengabaikan_yang_belum_dinilai(): void
    {
        $andi = $this->siswa('Andi');
        $budi = $this->siswa('Budi');
        $sakit = $this->siswa('Sakit');

        $this->post(route('classes.nilai.store', $this->kelas), [
            'capaian_pembelajaran' => 'CP 1',
            'assessment_date' => today()->toDateString(),
            'nilai' => [$andi->id => 90, $budi->id => 80, $sakit->id => ''],
        ]);

        $penilaian = Assessment::withoutTenant()->with('scores')->firstOrFail();

        // 90 dan 80 -> 85. Kalau yang kosong dihitung nol, hasilnya 56,7.
        $this->assertSame(85.0, $penilaian->rataRata());
        $this->assertSame(1, $penilaian->belumDinilai());
    }

    /** Nol yang benar-benar diberikan guru TETAP dihitung. */
    public function test_nilai_nol_tetap_dihitung(): void
    {
        $andi = $this->siswa('Andi');
        $budi = $this->siswa('Budi');

        $this->post(route('classes.nilai.store', $this->kelas), [
            'capaian_pembelajaran' => 'CP 1',
            'assessment_date' => today()->toDateString(),
            'nilai' => [$andi->id => 100, $budi->id => 0],
        ]);

        $penilaian = Assessment::withoutTenant()->with('scores')->firstOrFail();

        $this->assertSame(50.0, $penilaian->rataRata());
        $this->assertSame(0, $penilaian->belumDinilai());
    }

    // -- Validasi -----------------------------------------------------------

    public function test_nilai_di_luar_0_sampai_100_ditolak(): void
    {
        $andi = $this->siswa('Andi');

        $this->post(route('classes.nilai.store', $this->kelas), [
            'capaian_pembelajaran' => 'CP 1',
            'assessment_date' => today()->toDateString(),
            'nilai' => [$andi->id => 150],
        ])->assertSessionHasErrors('nilai.' . $andi->id);

        $this->assertSame(0, Assessment::withoutTenant()->count());
    }

    public function test_capaian_pembelajaran_wajib_diisi(): void
    {
        $this->post(route('classes.nilai.store', $this->kelas), [
            'assessment_date' => today()->toDateString(),
        ])->assertSessionHasErrors('capaian_pembelajaran');
    }

    // -- Keamanan -----------------------------------------------------------

    /** Daftar id datang dari formulir dan tidak boleh dipercaya begitu saja. */
    public function test_nilai_untuk_siswa_kelas_lain_diabaikan(): void
    {
        $andi = $this->siswa('Andi');

        $kelasLain = Classroom::factory()->create(['user_id' => $this->user->id]);
        $siswaLain = Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $kelasLain->id, 'is_active' => true,
        ]);

        $this->post(route('classes.nilai.store', $this->kelas), [
            'capaian_pembelajaran' => 'CP 1',
            'assessment_date' => today()->toDateString(),
            'nilai' => [$andi->id => 90, $siswaLain->id => 100],
        ]);

        $penilaian = Assessment::withoutTenant()->firstOrFail();

        $this->assertSame(1, $penilaian->scores()->count(), 'hanya siswa kelas ini yang boleh dinilai');
        $this->assertNull($penilaian->scores()->where('student_id', $siswaLain->id)->first());
    }

    public function test_penilaian_kelas_lain_tidak_bisa_disunting_lewat_kelas_ini(): void
    {
        $kelasLain = Classroom::factory()->create(['user_id' => $this->user->id]);
        $penilaianLain = $kelasLain->assessments()->create([
            'user_id' => $this->user->id,
            'capaian_pembelajaran' => 'CP milik kelas lain',
            'assessment_date' => today(),
        ]);

        $this->get(route('classes.nilai.edit', [$this->kelas, $penilaianLain]))->assertNotFound();
    }

    // -- Mengubah & menghapus ----------------------------------------------

    public function test_mengubah_nilai_menimpa_yang_lama(): void
    {
        $andi = $this->siswa('Andi');

        $this->post(route('classes.nilai.store', $this->kelas), [
            'capaian_pembelajaran' => 'CP 1',
            'assessment_date' => today()->toDateString(),
            'nilai' => [$andi->id => 60],
        ]);

        $penilaian = Assessment::withoutTenant()->firstOrFail();

        $this->patch(route('classes.nilai.update', [$this->kelas, $penilaian]), [
            'capaian_pembelajaran' => 'CP 1 revisi',
            'assessment_date' => today()->toDateString(),
            'nilai' => [$andi->id => 85],
        ])->assertRedirect();

        $this->assertSame(85, $penilaian->scores()->where('student_id', $andi->id)->first()->nilai);
        $this->assertSame('CP 1 revisi', $penilaian->fresh()->capaian_pembelajaran);
        $this->assertSame(1, $penilaian->scores()->count(), 'mengubah tidak boleh menggandakan baris nilai');
    }

    public function test_menghapus_penilaian_ikut_menghapus_nilainya(): void
    {
        $andi = $this->siswa('Andi');

        $this->post(route('classes.nilai.store', $this->kelas), [
            'capaian_pembelajaran' => 'CP 1',
            'assessment_date' => today()->toDateString(),
            'nilai' => [$andi->id => 60],
        ]);

        $penilaian = Assessment::withoutTenant()->firstOrFail();

        $this->delete(route('classes.nilai.destroy', [$this->kelas, $penilaian]))->assertRedirect();

        $this->assertSame(0, Assessment::withoutTenant()->count());
        $this->assertSame(0, \DB::table('assessment_scores')->count());
    }

    // -- Pemisahan mapel & menu --------------------------------------------

    public function test_daftar_tersaring_per_mapel(): void
    {
        $this->kelas->assessments()->create([
            'user_id' => $this->user->id, 'mapel' => 'Matematika',
            'capaian_pembelajaran' => 'CP Matematika', 'assessment_date' => today(),
        ]);
        $this->kelas->assessments()->create([
            'user_id' => $this->user->id, 'mapel' => 'Informatika',
            'capaian_pembelajaran' => 'CP Informatika', 'assessment_date' => today(),
        ]);

        $this->get(route('classes.nilai.index', [$this->kelas, 'mapel' => 'Matematika']))
            ->assertOk()
            ->assertSee('CP Matematika')
            ->assertDontSee('CP Informatika');
    }

    public function test_menu_nilai_muncul_di_kelas_ajar(): void
    {
        $this->get(route('classes.nilai.index', $this->kelas))
            ->assertOk()
            ->assertSee('Nilai Harian');
    }
}
