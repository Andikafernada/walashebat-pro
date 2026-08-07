<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

/**
 * NIS unik PER SEKOLAH, bukan se-aplikasi — dan di lapangan sering hanya nomor
 * urut pendek ("36"), sehingga dua sekolah punya siswa ber-NIS sama adalah
 * keadaan biasa.
 *
 * Versi lama menyelesaikannya dengan first(): siswa yang id-nya lebih besar
 * tidak pernah bisa masuk memakai kata sandinya sendiri, dan bila kata sandinya
 * kebetulan sama dengan siswa satunya, ia masuk ke akun sekolah lain. Jalur
 * OTP punya cacat yang sama karena kunci cache-nya memakai NIS.
 */
class LoginSiswaNisBentrokTest extends TestCase
{
    use RefreshDatabase;

    private Student $siswaA;

    private Student $siswaB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->siswaA = $this->buatSiswa('SEKOLAH A', 'SiswaSatu', '628111111111');
        $this->siswaB = $this->buatSiswa('SEKOLAH B', 'SiswaDua', '628222222222');
    }

    private function buatSiswa(string $namaSekolah, string $sandi, string $hpOrtu): Student
    {
        $guru = User::factory()->create();
        $kelas = Classroom::factory()->create(['user_id' => $guru->id]);

        return Student::factory()->create([
            'user_id' => $guru->id,
            'class_id' => $kelas->id,
            'name' => $namaSekolah,
            'nis' => '36',              // NIS yang sama persis di kedua sekolah
            'parent_phone' => $hpOrtu,
            'password' => Hash::make($sandi),
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    /** Keduanya harus bisa masuk ke akunnya masing-masing. */
    public function test_kedua_siswa_ber_nis_sama_bisa_masuk_ke_akunnya_sendiri(): void
    {
        $this->post('/student/login', ['nis' => '36', 'password' => 'SiswaSatu']);
        $this->assertSame($this->siswaA->id, auth('student')->id(), 'Siswa A gagal masuk dengan sandinya sendiri');

        auth('student')->logout();

        $this->post('/student/login', ['nis' => '36', 'password' => 'SiswaDua']);
        $this->assertSame($this->siswaB->id, auth('student')->id(), 'Siswa B gagal masuk dengan sandinya sendiri');
    }

    public function test_kata_sandi_salah_tetap_ditolak(): void
    {
        $this->post('/student/login', ['nis' => '36', 'password' => 'BukanSandiSiapaPun'])
            ->assertSessionHasErrors('nis');

        $this->assertNull(auth('student')->id());
    }

    /**
     * Bila NIS DAN kata sandinya sama persis, tidak ada cara menentukan yang
     * mana — dan menebak berarti membuka data sekolah orang lain.
     */
    public function test_nis_dan_sandi_kembar_ditolak_bukan_ditebak(): void
    {
        $this->siswaB->update(['password' => Hash::make('SiswaSatu')]);

        $this->post('/student/login', ['nis' => '36', 'password' => 'SiswaSatu'])
            ->assertSessionHasErrors('nis');

        $this->assertNull(auth('student')->id(), 'Tidak boleh menebak salah satu akun');
        $this->assertStringContainsString('lebih dari satu kelas', session('errors')->first('nis'));
    }

    // -- Reset kata sandi lewat OTP ----------------------------------------

    /** Masing-masing orang tua menerima kode sendiri, bukan hanya satu siswa. */
    public function test_otp_dikirim_ke_kedua_orang_tua(): void
    {
        $tujuan = [];

        $mock = Mockery::mock(NotificationChannel::class);
        $mock->shouldReceive('send')->twice()
            ->withArgs(function ($to, $pesan) use (&$tujuan) {
                $tujuan[] = $to;

                return (bool) preg_match('/\*\d{6}\*/', $pesan);
            })->andReturn(true);
        $this->app->instance(NotificationChannel::class, $mock);

        $this->post(route('student.password.email'), ['nis' => '36'])->assertRedirect();

        $this->assertEqualsCanonicalizing(['628111111111', '628222222222'], $tujuan);
        $this->assertNotNull(Cache::get('student_otp_'.$this->siswaA->id));
        $this->assertNotNull(Cache::get('student_otp_'.$this->siswaB->id));
    }

    /** Kode yang cocok menentukan siswa mana yang kata sandinya direset. */
    public function test_otp_mereset_siswa_pemilik_kodenya(): void
    {
        Cache::put('student_otp_'.$this->siswaB->id, ['otp' => Hash::make('654321'), 'nis' => '36'], now()->addMinutes(15));

        $this->post(route('student.otp.submit', ['nis' => '36']), [
            'otp' => '654321',
            'password' => 'SandiBaruB9',
            'password_confirmation' => 'SandiBaruB9',
        ])->assertRedirect(route('student.login'));

        $this->assertTrue(
            Hash::check('SandiBaruB9', $this->siswaB->fresh()->password),
            'Kata sandi siswa B harus berubah'
        );
        $this->assertTrue(
            Hash::check('SiswaSatu', $this->siswaA->fresh()->password),
            'Kata sandi siswa A tidak boleh ikut tersentuh'
        );
    }
}
