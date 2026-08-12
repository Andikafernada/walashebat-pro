<?php

namespace Tests\Feature;

use App\Models\CharacterDimension;
use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman Portofolio Karakter menghitung DUA sumber, bukan satu.
 *
 * Halaman ini membagikan tautan formulir refleksi, lalu dulu hanya menghitung
 * character_records — catatan pengamatan yang ditulis wali kelas sendiri.
 * Refleksi kiriman siswa masuk ke tabel yang lain sama sekali.
 *
 * Akibatnya di kelas XII TKJ D: 23 siswa sudah mengisi, seluruh angka di
 * halaman tetap 0, dan tidak ada galat apa pun yang bisa menjelaskannya —
 * wali kelas hanya bisa menyimpulkan kiriman siswanya hilang.
 */
class PortofolioKarakterMenghitungRefleksiTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private Classroom $kelas;

    private CharacterDimension $dimensi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create();
        $this->kelas = Classroom::factory()->create(['user_id' => $this->guru->id]);
        $this->dimensi = CharacterDimension::create([
            'user_id' => $this->guru->id, 'code' => 'mandiri', 'name' => 'Mandiri',
            'is_active' => true, 'sort_order' => 1,
        ]);
    }

    private function siswa(string $nama): Student
    {
        return Student::factory()->create([
            'user_id' => $this->guru->id, 'class_id' => $this->kelas->id, 'name' => $nama,
        ]);
    }

    private function refleksi(Student $siswa): void
    {
        (new CharacterReflection)->forceFill([
            'user_id' => $this->guru->id,
            'class_id' => $this->kelas->id,
            'student_id' => $siswa->id,
            'character_dimension_id' => $this->dimensi->id,
            'period' => CharacterReflection::PERIOD_MONTHLY,
            'reflection_date' => today()->toDateString(),
            'self_rating' => 4,
            'what_went_well' => 'Piket tanpa disuruh',
            'what_to_improve' => 'Suka menunda PR',
            'action_plan' => 'Kerjakan PR lebih dulu',
            'status' => 'submitted',
        ])->save();
    }

    public function test_refleksi_siswa_terhitung_walau_guru_belum_mencatat_apa_pun(): void
    {
        $a = $this->siswa('Ahmad');
        $b = $this->siswa('Budi');
        $this->refleksi($a);
        $this->refleksi($b);
        $this->refleksi($b);

        $response = $this->actingAs($this->guru)
            ->get(route('classes.character-portfolio.index', $this->kelas))
            ->assertOk();

        // Tiga refleksi masuk, nol catatan guru — dan angkanya tidak boleh 0.
        $this->assertSame(3, $response->viewData('dimensionStats')[$this->dimensi->id]['refleksi']);
        $this->assertSame(3, $response->viewData('totalRefleksi'));
    }

    /** Yang paling ingin diketahui wali kelas setelah menyebar tautan. */
    public function test_menandai_siswa_yang_belum_mengisi(): void
    {
        $sudah = $this->siswa('Sudah Mengisi');
        $this->siswa('Belum Mengisi');
        $this->refleksi($sudah);

        $response = $this->actingAs($this->guru)
            ->get(route('classes.character-portfolio.index', $this->kelas))
            ->assertOk();

        $perSiswa = $response->viewData('refleksiPerSiswa');

        $this->assertSame(1, (int) $perSiswa[$sudah->id]);
        $this->assertStringContainsString('Belum mengisi', $response->getContent());
    }

    /** Kelas lain tidak boleh ikut terhitung; angkanya jadi bohong ke dua arah. */
    public function test_refleksi_kelas_lain_tidak_ikut(): void
    {
        $lain = Classroom::factory()->create(['user_id' => $this->guru->id]);
        $siswaLain = Student::factory()->create([
            'user_id' => $this->guru->id, 'class_id' => $lain->id,
        ]);

        (new CharacterReflection)->forceFill([
            'user_id' => $this->guru->id,
            'class_id' => $lain->id,
            'student_id' => $siswaLain->id,
            'character_dimension_id' => $this->dimensi->id,
            'period' => CharacterReflection::PERIOD_MONTHLY,
            'reflection_date' => today()->toDateString(),
            'status' => 'submitted',
        ])->save();

        $response = $this->actingAs($this->guru)
            ->get(route('classes.character-portfolio.index', $this->kelas))
            ->assertOk();

        $this->assertSame(0, $response->viewData('totalRefleksi'));
    }
}
