<?php

namespace Tests\Feature;

use App\Models\CharacterDimension;
use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Refleksi karakter mandiri kini menanyakan kesan teman sebaya.
 *
 * Tiga isian lamanya seluruhnya penilaian diri sendiri, dan penilaian diri
 * sendiri punya titik buta yang sama pada setiap anak.
 */
class KesanTemanRefleksiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    private Student $siswa;

    private CharacterDimension $dimensi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create();
        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);
        $this->siswa = Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id, 'is_active' => true,
        ]);
        $this->dimensi = CharacterDimension::create([
            'user_id' => $this->user->id, 'code' => 'mandiri', 'name' => 'Mandiri',
            'is_active' => true, 'sort_order' => 1,
        ]);
    }

    private function kirim(array $timpa = []): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('public.reflection.store', $this->class), $timpa + [
            'student_id' => $this->siswa->id,
            'character_dimension_id' => $this->dimensi->id,
            'self_rating' => 4,
            'what_went_well' => 'Saya piket tanpa disuruh',
            'what_to_improve' => 'Masih sering menunda PR',
            'action_plan' => 'Mengerjakan PR sebelum bermain',
            // Keduanya wajib sejak formulir publik tidak lagi menerima isian
            // kosong; tanpa ini setiap test di berkas ini gagal validasi.
            'pesan_ortu' => 'Terima kasih Ayah Ibu sudah sabar menemani.',
            'kesan_teman' => 'Kata Rina aku ramah tapi suka menunda.',
        ]);
    }

    public function test_kesan_teman_tersimpan(): void
    {
        $kesan = 'Kata Rina aku asyik tapi suka memotong pembicaraan.';

        $this->kirim(['kesan_teman' => $kesan])->assertSessionHasNoErrors();

        $this->assertSame($kesan, CharacterReflection::withoutTenant()->sole()->kesan_teman);
    }

    /**
     * Wajib diisi. Anak yang belum sempat bertanya tetap harus bisa lewat, dan
     * itu diselesaikan di teks pertanyaannya ("kalau belum sempat bertanya,
     * tulis perkiraanmu"), bukan dengan menyisakan kolomnya boleh kosong.
     */
    public function test_wajib_diisi(): void
    {
        $this->kirim(['kesan_teman' => ''])->assertSessionHasErrors('kesan_teman');

        $this->assertSame(0, CharacterReflection::withoutTenant()->count());
    }

    /** `required` sendirian meloloskan "-", dan itu sama kosongnya. */
    public function test_sekadar_tanda_hubung_ditolak(): void
    {
        $this->kirim(['kesan_teman' => '-'])->assertSessionHasErrors('kesan_teman');
    }

    public function test_panjangnya_dibatasi(): void
    {
        $this->kirim(['kesan_teman' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('kesan_teman');
    }

    public function test_kolomnya_dirender_di_formulir_publik(): void
    {
        $html = $this->get(route('public.reflection.show', $this->class))->assertOk()->getContent();

        $this->assertStringContainsString('name="kesan_teman"', $html);
    }

    /**
     * Tersimpan tetapi tidak pernah terbaca sama saja dengan tidak ditanyakan:
     * kolom ini gunanya justru dibandingkan dengan tiga isian di atasnya.
     */
    public function test_terbaca_wali_kelas_di_portofolio(): void
    {
        $kesan = 'Kata Budi aku gampang marah kalau kalah main bola.';

        $this->kirim(['kesan_teman' => $kesan]);

        $this->actingAs($this->user)
            ->get(route('classes.character-portfolio.student', [$this->class, $this->siswa]))
            ->assertOk()
            ->assertSee($kesan);
    }
}
