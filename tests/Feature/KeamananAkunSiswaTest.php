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
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

/**
 * Pengerasan titik masuk kredensial siswa.
 *
 * Portal siswa sempat tidak dapat diakses sama sekali (model Student belum
 * memenuhi kontrak Authenticatable), sehingga permukaan ini tidak pernah
 * melewati pengerasan yang sudah diterapkan pada sisi wali kelas. Test ini
 * mengunci ketiga celah yang tersingkap begitu portal itu hidup: brute force,
 * penyisiran NIS, dan OTP yang tidak pernah terkirim.
 */
class KeamananAkunSiswaTest extends TestCase
{
    use RefreshDatabase;

    private Student $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        RateLimiter::clear('student-otp-verify|12345|127.0.0.1');

        $user = User::factory()->create();
        $class = Classroom::factory()->create(['user_id' => $user->id]);
        $this->siswa = Student::factory()->create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'nis' => '12345',
            'parent_phone' => '81234567890',
            'password' => Hash::make('rahasia123'),
            'is_active' => true,
        ]);
    }

    /** OTP harus benar-benar dikirim lewat channel, bukan ditulis ke log. */
    public function test_otp_siswa_terkirim_lewat_notification_channel(): void
    {
        $mock = Mockery::mock(NotificationChannel::class);
        $mock->shouldReceive('send')
            ->once()
            ->withArgs(function ($to, $message) {
                // Nomor sudah dinormalisasi model ke format 62..., dan pesan
                // memuat kode 6 digit.
                return $to === '6281234567890'
                    && (bool) preg_match('/\*\d{6}\*/', $message);
            })
            ->andReturn(true);
        $this->app->instance(NotificationChannel::class, $mock);

        $this->post(route('student.password.email'), ['nis' => '12345'])
            ->assertRedirect(route('student.otp.verify', ['nis' => '12345']));

        // Kuncinya per SISWA, bukan per NIS: NIS unik per sekolah, sehingga
        // kunci ber-NIS membuat dua siswa saling menimpa OTP.
        $this->assertNotNull(
            Cache::get('student_otp_'.$this->siswa->id),
            'OTP wajib tersimpan di cache dengan kunci per siswa'
        );
    }

    /** NIS yang tidak ada harus dijawab sama persis dengan NIS yang ada. */
    public function test_nis_tidak_dapat_disisir(): void
    {
        $mock = Mockery::mock(NotificationChannel::class);
        $mock->shouldReceive('send')->andReturn(true);
        $this->app->instance(NotificationChannel::class, $mock);

        $ada = $this->post(route('student.password.email'), ['nis' => '12345']);
        $tidakAda = $this->post(route('student.password.email'), ['nis' => '99999']);

        $this->assertSame($ada->getStatusCode(), $tidakAda->getStatusCode());
        $this->assertSame(
            session()->get('status'),
            $tidakAda->getSession()->get('status'),
            'Pesan untuk NIS ada dan tidak ada harus identik'
        );
        $tidakAda->assertSessionHasNoErrors();
    }

    /** Kode OTP tidak boleh bisa ditebak tanpa batas. */
    public function test_percobaan_otp_dibatasi(): void
    {
        Cache::put('student_otp_'.$this->siswa->id, ['otp' => Hash::make('111111'), 'nis' => '12345'], now()->addMinutes(15));

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('student.otp.submit', ['nis' => '12345']), [
                'otp' => '000000',
                'password' => 'KataSandiBaru9',
                'password_confirmation' => 'KataSandiBaru9',
            ])->assertSessionHasErrors('otp');
        }

        // Percobaan ke-6 ditolak karena batas, bukan karena kode salah —
        // dan kode yang BENAR pun ikut ditolak selama masa penguncian.
        $this->post(route('student.otp.submit', ['nis' => '12345']), [
            'otp' => '111111',
            'password' => 'KataSandiBaru9',
            'password_confirmation' => 'KataSandiBaru9',
        ])->assertSessionHasErrors('otp');

        $this->assertTrue(
            Hash::check('rahasia123', $this->siswa->fresh()->password),
            'Kata sandi tidak boleh berubah saat penguncian aktif'
        );
    }

    /** OTP yang benar tetap harus bisa dipakai dan hanya sekali. */
    public function test_otp_benar_mereset_kata_sandi_dan_sekali_pakai(): void
    {
        Cache::put('student_otp_'.$this->siswa->id, ['otp' => Hash::make('111111'), 'nis' => '12345'], now()->addMinutes(15));

        $this->post(route('student.otp.submit', ['nis' => '12345']), [
            'otp' => '111111',
            'password' => 'KataSandiBaru9',
            'password_confirmation' => 'KataSandiBaru9',
        ])->assertRedirect(route('student.login'));

        $this->assertTrue(Hash::check('KataSandiBaru9', $this->siswa->fresh()->password));
        $this->assertNull(Cache::get('student_otp_'.$this->siswa->id), 'OTP wajib hangus setelah dipakai');
    }

    /**
     * Siswa yang wajib ganti kata sandi harus benar-benar BISA menggantinya.
     *
     * must_change_password bernilai true secara bawaan, jadi kegagalan di sini
     * membuat setiap akun siswa baru tidak dapat dipakai sama sekali.
     */
    public function test_siswa_wajib_ganti_sandi_bisa_menyelesaikannya(): void
    {
        $this->siswa->update(['must_change_password' => true]);

        $this->actingAs($this->siswa, 'student')
            ->post(route('student.password.update'), [
                'current_password' => 'rahasia123',
                'password' => 'KataSandiBaru9',
                'password_confirmation' => 'KataSandiBaru9',
            ])
            ->assertRedirect(route('student.dashboard'));

        $segar = $this->siswa->fresh();
        $this->assertTrue(Hash::check('KataSandiBaru9', $segar->password));
        $this->assertFalse((bool) $segar->must_change_password, 'Penanda wajib ganti harus dilepas');
    }

    /** Kata sandi tidak boleh bisa diganti tanpa mengetahui yang lama. */
    public function test_ganti_sandi_wajib_menyertakan_sandi_lama(): void
    {
        $this->siswa->update(['must_change_password' => false]);

        $this->actingAs($this->siswa, 'student')
            ->post(route('student.password.update'), [
                'password' => 'KataSandiBaru9',
                'password_confirmation' => 'KataSandiBaru9',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(
            Hash::check('rahasia123', $this->siswa->fresh()->password),
            'Kata sandi tidak boleh berubah tanpa current_password'
        );
    }

    /** Kata sandi lama yang salah juga harus ditolak. */
    public function test_sandi_lama_salah_ditolak(): void
    {
        $this->siswa->update(['must_change_password' => false]);

        $this->actingAs($this->siswa, 'student')
            ->post(route('student.password.update'), [
                'current_password' => 'tebakan-salah',
                'password' => 'KataSandiBaru9',
                'password_confirmation' => 'KataSandiBaru9',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('rahasia123', $this->siswa->fresh()->password));
    }

    /** Titik masuk kredensial wajib punya throttle di tingkat route. */
    public function test_route_kredensial_siswa_ber_throttle(): void
    {
        $wajib = [
            'student/login' => 'throttle:10,1',
            'student/lupa-password' => 'throttle:5,1',
            'student/otp/{nis}' => 'throttle:10,1',
        ];

        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
            if (! in_array('POST', $route->methods(), true)) {
                continue;
            }
            if (! isset($wajib[$route->uri()])) {
                continue;
            }

            $this->assertContains(
                $wajib[$route->uri()],
                $route->gatherMiddleware(),
                "Route {$route->uri()} wajib memakai {$wajib[$route->uri()]}"
            );
            unset($wajib[$route->uri()]);
        }

        $this->assertSame([], $wajib, 'Ada route kredensial siswa yang tidak diperiksa');
    }
}
