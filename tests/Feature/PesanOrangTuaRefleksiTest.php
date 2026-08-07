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
 * Refleksi karakter mandiri kini memuat pesan siswa untuk orang tuanya.
 *
 * Disimpan terpisah dari what_went_well / what_to_improve / action_plan karena
 * sasarannya berbeda: tiga isian itu untuk diri sendiri dan wali kelas, yang
 * ini untuk orang tua.
 */
class PesanOrangTuaRefleksiTest extends TestCase
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
        ]);
    }

    public function test_pesan_untuk_orang_tua_tersimpan(): void
    {
        $pesan = 'Terima kasih Ayah Ibu, aku janji lebih rajin belajar.';

        $this->kirim(['pesan_ortu' => $pesan])->assertSessionHasNoErrors();

        $this->assertSame($pesan, CharacterReflection::withoutTenant()->sole()->pesan_ortu);
    }

    public function test_pesan_boleh_dikosongkan(): void
    {
        $this->kirim()->assertSessionHasNoErrors();

        $this->assertNull(CharacterReflection::withoutTenant()->sole()->pesan_ortu);
    }

    public function test_pesan_terlalu_panjang_ditolak(): void
    {
        $this->kirim(['pesan_ortu' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('pesan_ortu');

        $this->assertSame(0, CharacterReflection::withoutTenant()->count());
    }

    public function test_form_menyediakan_kolom_pesan(): void
    {
        $html = $this->get(route('public.reflection.show', $this->class))->assertOk()->getContent();

        $this->assertStringContainsString('name="pesan_ortu"', $html);
        $this->assertStringContainsString('Pesan untuk Orang Tua', $html);
    }

    public function test_pesan_tampil_di_portofolio_wali_kelas(): void
    {
        $pesan = 'Maaf ya Bu, kemarin aku bandel.';
        $this->kirim(['pesan_ortu' => $pesan]);

        $this->actingAs($this->user)
            ->get(route('classes.character-portfolio.student', [$this->class, $this->siswa]))
            ->assertOk()
            ->assertSee('Pesan untuk Orang Tua')
            ->assertSee($pesan);
    }

    // -- Bug lain yang ikut ketahuan di jalur ini ---------------------------

    /**
     * period harus memakai konstanta, bukan teks bebas seperti "Agustus 2026".
     * Nilai bebas membuat refleksi dari jalur publik tidak pernah cocok pada
     * penyaringan mana pun di sisa aplikasi.
     */
    public function test_period_memakai_konstanta_yang_dikenal(): void
    {
        $this->kirim()->assertSessionHasNoErrors();

        $this->assertSame(
            CharacterReflection::PERIOD_MONTHLY,
            CharacterReflection::withoutTenant()->sole()->period
        );
    }

    public function test_siswa_kelas_lain_ditolak(): void
    {
        $kelasLain = Classroom::factory()->create(['user_id' => $this->user->id]);
        $asing = Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $kelasLain->id,
        ]);

        $this->kirim(['student_id' => $asing->id])->assertSessionHasErrors('student_id');

        $this->assertSame(0, CharacterReflection::withoutTenant()->count());
    }

    public function test_dimensi_sekolah_lain_ditolak(): void
    {
        $guruLain = User::factory()->create();
        $dimensiAsing = CharacterDimension::create([
            'user_id' => $guruLain->id, 'code' => 'lain', 'name' => 'Dimensi Sekolah Lain',
            'is_active' => true, 'sort_order' => 1,
        ]);

        $this->kirim(['character_dimension_id' => $dimensiAsing->id])
            ->assertSessionHasErrors('character_dimension_id');

        $this->assertSame(0, CharacterReflection::withoutTenant()->count());
    }

    public function test_daftar_dimensi_hanya_milik_sekolah_ini(): void
    {
        $guruLain = User::factory()->create();
        CharacterDimension::create([
            'user_id' => $guruLain->id, 'code' => 'asing', 'name' => 'Dimensi Sekolah Lain',
            'is_active' => true, 'sort_order' => 1,
        ]);

        $this->get(route('public.reflection.show', $this->class))
            ->assertOk()
            ->assertSee('Mandiri')
            ->assertDontSee('Dimensi Sekolah Lain');
    }

    public function test_kegagalan_validasi_terlihat_oleh_siswa(): void
    {
        $this->kirim(['what_went_well' => '']);

        $this->get(route('public.reflection.show', $this->class))
            ->assertOk()
            ->assertSee('Refleksi belum bisa dikirim');
    }
}
