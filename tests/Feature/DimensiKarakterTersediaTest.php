<?php

namespace Tests\Feature;

use App\Models\CharacterDimension;
use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\CharacterDimensionProvisioner;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Seluruh fitur Jurnal & Portofolio Karakter berdiri di atas daftar dimensi
 * milik wali kelas. Dulu daftar itu tidak pernah terisi: penyemaiannya
 * menyisipkan enam baris tanpa user_id, kolom `code` dikunci unik se-aplikasi
 * sehingga hanya satu wali kelas yang bisa punya dimensi bernama sama, dan
 * kolom `icon` terlalu sempit untuk menampung path SVG-nya. Akibatnya di semua
 * halaman: pilihan dimensi kosong, formulir tidak bisa dikirim sama sekali.
 */
class DimensiKarakterTersediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_wali_kelas_baru_langsung_punya_dimensi_karakter(): void
    {
        $this->post(route('register'), [
            'name' => 'Bu Rina',
            'email' => 'rina@sekolah.test',
            'whatsapp_number' => '81234567890',
            'password' => 'rahasia12345',
            'password_confirmation' => 'rahasia12345',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'rina@sekolah.test')->firstOrFail();

        $this->assertSame(
            6,
            CharacterDimension::forOwner($user->id)->active()->count(),
            'Wali kelas baru harus langsung punya enam dimensi Profil Pelajar Pancasila'
        );
    }

    /** Kode dimensi unik per wali kelas, bukan se-aplikasi. */
    public function test_banyak_wali_kelas_bisa_punya_dimensi_dengan_kode_sama(): void
    {
        $provisioner = new CharacterDimensionProvisioner;

        $satu = User::factory()->create();
        $dua = User::factory()->create();

        $provisioner->provisionFor($satu->id);
        $provisioner->provisionFor($dua->id);

        $this->assertSame(6, CharacterDimension::forOwner($satu->id)->count());
        $this->assertSame(6, CharacterDimension::forOwner($dua->id)->count());
    }

    /** Path SVG dimensi panjang; kolomnya harus sanggup menampung utuh. */
    public function test_ikon_dimensi_tersimpan_utuh(): void
    {
        $user = User::factory()->create();
        (new CharacterDimensionProvisioner)->provisionFor($user->id);

        foreach (CharacterDimension::forOwner($user->id)->get() as $dimensi) {
            $bawaan = collect(CharacterDimensionProvisioner::defaults())
                ->firstWhere('code', $dimensi->code);

            $this->assertSame($bawaan['icon'], $dimensi->icon, 'Ikon '.$dimensi->code.' terpotong');
        }
    }

    /** Dipanggil ulang tidak menggandakan, dan tidak menimpa penyesuaian. */
    public function test_penyiapan_ulang_tidak_menggandakan_atau_menimpa(): void
    {
        $provisioner = new CharacterDimensionProvisioner;
        $user = User::factory()->create();

        $provisioner->provisionFor($user->id);
        CharacterDimension::forOwner($user->id)->where('code', 'mandiri')
            ->update(['name' => 'Mandiri & Bertanggung Jawab']);

        $this->assertSame(0, $provisioner->provisionFor($user->id));
        $this->assertSame(6, CharacterDimension::forOwner($user->id)->count());
        $this->assertSame(
            'Mandiri & Bertanggung Jawab',
            CharacterDimension::forOwner($user->id)->where('code', 'mandiri')->value('name')
        );
    }

    // -- Ujung ke ujung: formulir refleksi publik ---------------------------

    public function test_formulir_refleksi_publik_menampilkan_dimensi_dan_bisa_dikirim(): void
    {
        $user = User::factory()->create();
        (new CharacterDimensionProvisioner)->provisionFor($user->id);

        $class = Classroom::factory()->create(['user_id' => $user->id]);
        $student = Student::factory()->create([
            'user_id' => $user->id, 'class_id' => $class->id, 'is_active' => true,
        ]);
        $dimensi = CharacterDimension::forOwner($user->id)->active()->first();

        $this->get(route('public.reflection.show', $class))
            ->assertOk()
            ->assertSee('Bergotong Royong')
            ->assertSee('-- Pilih Dimensi Karakter --');

        $this->post(route('public.reflection.store', $class), [
            'student_id' => $student->id,
            'character_dimension_id' => $dimensi->id,
            'self_rating' => 4,
            'what_went_well' => 'Rajin piket kelas.',
            'what_to_improve' => 'Kurangi mengobrol saat pelajaran.',
            'action_plan' => 'Duduk di barisan depan.',
            // Seluruh isian formulir publik ini wajib sejak refleksi separuh
            // kosong dianggap tidak terpakai.
            'pesan_ortu' => 'Terima kasih Ayah Ibu sudah sabar menemani.',
            'kesan_teman' => 'Kata Budi aku ramah tapi gampang tersinggung.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, CharacterReflection::withoutTenant()->where('class_id', $class->id)->count());
    }

    /**
     * Bila dimensi memang belum ada, siswa diberi tahu — bukan dibiarkan
     * menatap isian kosong yang tidak bisa dikirim tanpa penjelasan.
     */
    public function test_dimensi_kosong_dijelaskan_bukan_dibiarkan_buntu(): void
    {
        $user = User::factory()->create();
        $class = Classroom::factory()->create(['user_id' => $user->id]);
        Student::factory()->create([
            'user_id' => $user->id, 'class_id' => $class->id, 'is_active' => true,
        ]);

        $this->get(route('public.reflection.show', $class))
            ->assertOk()
            ->assertSee('belum disiapkan wali kelas');
    }

    /** Daftar dimensi satu sekolah tidak boleh tampil di sekolah lain. */
    public function test_dimensi_sekolah_lain_tidak_ikut_tampil(): void
    {
        $provisioner = new CharacterDimensionProvisioner;

        $sekolahLain = User::factory()->create();
        $provisioner->provisionFor($sekolahLain->id);

        $user = User::factory()->create();
        $provisioner->provisionFor($user->id);
        $class = Classroom::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('classes.character-portfolio.index', $class))
            ->assertOk();

        $this->assertSame(
            6,
            CharacterDimension::forOwner($user->id)->active()->count(),
            'Hanya dimensi milik sendiri yang boleh terhitung'
        );
    }
}
