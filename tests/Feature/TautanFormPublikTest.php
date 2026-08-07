<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\CharacterDimensionProvisioner;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Formulir biodata & refleksi mandiri terbuka tanpa login — memang harus,
 * karena orang tua membukanya dari tautan WhatsApp tanpa punya akun.
 *
 * Karena terbuka, alamatnya sendiri yang menjadi kuncinya. Dulu alamat itu
 * memakai id kelas, sebuah bilangan berurutan: menaikkannya satu per satu
 * memanen nama lengkap dan NIS seluruh siswa kelas mana pun di seluruh
 * Indonesia — data anak di bawah umur — dan memberi jalan menulis biodata ke
 * kelas milik orang lain.
 *
 * Berkas ini menjaga agar alamat berbasis id itu tidak pernah kembali.
 */
class TautanFormPublikTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->user = User::factory()->create();
        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);
    }

    // -- Alamat tidak boleh bisa ditebak ------------------------------------

    public function test_tautan_publik_tidak_memuat_id_kelas(): void
    {
        $tautan = route('public.biodata.show', $this->class->tokenPublik());

        // Dibandingkan sebagai SEGMEN terakhir, bukan sebagai potongan teks.
        // Pencocokan substring di sini pernah gagal secara acak: bila id kelas
        // kebetulan 1 dan token acaknya diawali "1", maka "/isi-biodata/1"
        // memang muncul di dalam alamat yang justru sudah benar.
        $segmen = basename(parse_url($tautan, PHP_URL_PATH));

        $this->assertSame($this->class->public_token, $segmen);
        $this->assertNotSame(
            (string) $this->class->id,
            $segmen,
            'Tautan publik tidak boleh memakai id kelas yang berurutan'
        );
    }

    public function test_membuka_lewat_id_kelas_ditolak(): void
    {
        $this->get('/isi-biodata/'.$this->class->id)->assertNotFound();
        $this->get('/refleksi-karakter/'.$this->class->id)->assertNotFound();
    }

    public function test_token_yang_salah_ditolak(): void
    {
        $this->get('/isi-biodata/'.str_repeat('a', 32))->assertNotFound();
    }

    /**
     * Inti kebocorannya: daftar siswa. Kalau alamat tebakan mana pun sampai
     * membalas 200, nama dan NIS satu kelas ikut terbawa.
     */
    public function test_daftar_siswa_tidak_bocor_lewat_alamat_tebakan(): void
    {
        Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'name' => 'Ahmad Fauzi',
            'nis' => '2025001',
        ]);

        foreach ([$this->class->id, 1, 2, 611] as $tebakan) {
            $balasan = $this->get('/isi-biodata/'.$tebakan);

            $balasan->assertNotFound();
            $this->assertStringNotContainsString('Ahmad Fauzi', $balasan->getContent());
            $this->assertStringNotContainsString('2025001', $balasan->getContent());
        }
    }

    public function test_menulis_biodata_lewat_id_kelas_ditolak(): void
    {
        $this->post('/isi-biodata/'.$this->class->id, [
            'name' => 'Penyusup',
            'gender' => 'L',
            'parent_phone' => '081234567890',
        ])->assertNotFound();

        $this->assertSame(0, Student::withoutTenant()->where('class_id', $this->class->id)->count());
    }

    // -- Jalur yang sah tetap bekerja ---------------------------------------

    public function test_token_yang_benar_tetap_bisa_dibuka_orang_tua(): void
    {
        Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'name' => 'Ahmad Fauzi',
        ]);

        $this->get(route('public.biodata.show', $this->class->tokenPublik()))
            ->assertOk()
            ->assertSee('Ahmad Fauzi');
    }

    public function test_form_refleksi_juga_memakai_token(): void
    {
        (new CharacterDimensionProvisioner)->provisionFor($this->user->id);

        $this->get(route('public.reflection.show', $this->class->tokenPublik()))->assertOk();
    }

    public function test_biodata_masih_bisa_dikirim_lewat_token(): void
    {
        $this->post(route('public.biodata.store', $this->class->tokenPublik()), [
            'name' => 'Siti Aminah',
            'gender' => 'P',
            'parent_phone' => '081234567890',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Student::withoutTenant()->where('class_id', $this->class->id)->count());
    }

    // -- Token itu sendiri ---------------------------------------------------

    public function test_setiap_kelas_baru_mendapat_token_yang_berbeda(): void
    {
        $lain = Classroom::factory()->create(['user_id' => $this->user->id]);

        $this->assertNotEmpty($this->class->public_token);
        $this->assertNotEmpty($lain->public_token);
        $this->assertNotSame($this->class->public_token, $lain->public_token);
        $this->assertSame(32, strlen($lain->public_token));
    }

    /**
     * Token tidak boleh bisa dititipkan lewat request: kalau bisa, penyerang
     * cukup membuat kelas dengan token milik kelas lain.
     */
    public function test_token_tidak_bisa_diisi_lewat_mass_assignment(): void
    {
        $kelas = Classroom::create([
            'user_id' => $this->user->id,
            'name' => 'XII TKJ A',
            'public_token' => 'token-titipan-penyerang',
        ]);

        $this->assertNotSame('token-titipan-penyerang', $kelas->public_token);
    }

    /**
     * Kelas yang tersimpan sebelum kolom ini ada — atau lewat insert massal
     * yang melewati event creating — tidak boleh membuat halaman wali kelas
     * jatuh. Kekurangannya ditambal saat tautannya pertama kali dibutuhkan.
     */
    public function test_kelas_tanpa_token_disembuhkan_saat_dibutuhkan(): void
    {
        DB::table('classes')->where('id', $this->class->id)->update(['public_token' => null]);
        $kelas = Classroom::withoutTenant()->findOrFail($this->class->id);

        $token = $kelas->tokenPublik();

        $this->assertSame(32, strlen($token));
        $this->assertSame($token, $kelas->fresh()->public_token, 'Token hasil tambalan harus ikut tersimpan');
    }
}
