<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Pendaftar harus membuktikan nomor WhatsApp yang diketiknya benar miliknya.
 *
 * Sebelum ini pendaftaran menerima nomor siapa pun tanpa pembuktian, dan
 * kolom whatsapp_verified ditulis false — jujur, tetapi berarti tidak ada
 * satu pun nomor guru di basis data yang benar-benar terbukti bisa dihubungi.
 */
class VerifikasiNomorSaatDaftarTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{to: string, message: string}> */
    private array $terkirim = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config(['walikelas.verifikasi_nomor_saat_daftar' => true]);
        $this->pasangGateway(berhasil: true);
    }

    private function pasangGateway(bool $berhasil): void
    {
        $uji = $this;
        $this->app->instance(NotificationChannel::class, new class($uji, $berhasil) implements NotificationChannel
        {
            public function __construct(private $uji, private bool $berhasil) {}

            public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
            {
                if ($this->berhasil) {
                    $this->uji->catatTerkirim($to, $message);
                }

                return $this->berhasil;
            }
        });
    }

    public function catatTerkirim(string $to, string $message): void
    {
        $this->terkirim[] = ['to' => $to, 'message' => $message];
    }

    private function kodeTerakhir(): string
    {
        preg_match('/\*(\d{6})\*/', end($this->terkirim)['message'], $m);

        return $m[1];
    }

    /** @return array<string, string> */
    private function isian(): array
    {
        return [
            'name' => 'Bu Sri',
            'email' => 'sri@sekolah.sch.id',
            'whatsapp_number' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ];
    }

    public function test_akun_belum_dibuat_sebelum_kode_dibalas(): void
    {
        $this->post('/register', $this->isian())
            ->assertRedirect(route('register.verifikasi.form'));

        $this->assertDatabaseCount('users', 0);
        $this->assertCount(1, $this->terkirim);
        $this->assertSame('6281234567890', $this->terkirim[0]['to']);
        $this->assertGuest();
    }

    public function test_kode_benar_membuat_akun_yang_kata_sandinya_bisa_dipakai_masuk(): void
    {
        $this->post('/register', $this->isian());

        $this->post('/register/verifikasi', ['otp' => $this->kodeTerakhir()])
            ->assertRedirect(route('dashboard'));

        $user = User::firstOrFail();
        $this->assertTrue((bool) $user->whatsapp_verified);
        $this->assertSame('6281234567890', $user->whatsapp_number);
        $this->assertTrue(Auth::check());

        /*
         * Kata sandi dititipkan ke cache dalam bentuk hash. Bila jalur ini
         * meng-hash-nya sekali lagi, akunnya terbuat dengan sempurna dan
         * pemiliknya tidak akan pernah bisa masuk — kegagalan yang baru
         * ketahuan setelah guru menyerah dan menghubungi admin.
         */
        Auth::logout();
        $this->post('/login', [
            'email' => 'sri@sekolah.sch.id',
            'password' => 'rahasia123',
        ]);
        $this->assertTrue(Auth::check(), 'Kata sandi hasil pendaftaran tidak bisa dipakai masuk.');
    }

    public function test_kode_salah_tidak_membuat_akun(): void
    {
        $this->post('/register', $this->isian());

        $this->post('/register/verifikasi', ['otp' => '000000'])
            ->assertSessionHasErrors('otp');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_lima_kali_salah_menghanguskan_titipan(): void
    {
        $this->post('/register', $this->isian());

        for ($i = 0; $i < 5; $i++) {
            $this->post('/register/verifikasi', ['otp' => '000000']);
        }

        $this->post('/register/verifikasi', ['otp' => $this->kodeTerakhir()])
            ->assertRedirect(route('register'));

        $this->assertDatabaseCount('users', 0);
    }

    public function test_gateway_mati_tetap_membuka_pendaftaran_tanpa_mengaku_terverifikasi(): void
    {
        $this->pasangGateway(berhasil: false);

        $this->post('/register', $this->isian())
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('warning');

        $user = User::firstOrFail();
        $this->assertFalse((bool) $user->whatsapp_verified, 'Tidak ada bukti apa pun, tetapi ditandai terverifikasi.');
        $this->assertCount(0, $this->terkirim);
    }

    /**
     * Penautan yang berhasil adalah bukti kepemilikan yang sama kuatnya:
     * WhatsApp hanya menautkan perangkat setelah pemilik nomor menyetujuinya
     * dari ponselnya sendiri.
     */
    public function test_sesi_tersambung_menandai_nomor_terbukti(): void
    {
        $this->pasangGateway(berhasil: false);
        $this->post('/register', $this->isian());

        $user = User::firstOrFail();
        $this->assertFalse((bool) $user->whatsapp_verified);

        $user->catatStatusSesi('connected');

        $this->assertTrue((bool) $user->fresh()->whatsapp_verified);
    }

    public function test_sesi_putus_tidak_mencabut_bukti_yang_sudah_ada(): void
    {
        $this->post('/register', $this->isian());
        $this->post('/register/verifikasi', ['otp' => $this->kodeTerakhir()]);

        $user = User::firstOrFail();
        $user->catatStatusSesi('disconnected', 'gateway mati');

        $this->assertTrue((bool) $user->fresh()->whatsapp_verified);
    }

    public function test_kirim_ulang_menerbitkan_kode_baru_dan_yang_lama_mati(): void
    {
        $this->post('/register', $this->isian());
        $kodeLama = $this->kodeTerakhir();

        $this->post('/register/kirim-ulang')->assertSessionHasNoErrors();
        $kodeBaru = $this->kodeTerakhir();

        $this->assertNotSame($kodeLama, $kodeBaru);
        $this->post('/register/verifikasi', ['otp' => $kodeLama])->assertSessionHasErrors('otp');
        $this->post('/register/verifikasi', ['otp' => $kodeBaru])->assertRedirect(route('dashboard'));
    }

    public function test_sakelar_mati_mendaftar_langsung_dan_menandai_belum_terverifikasi(): void
    {
        config(['walikelas.verifikasi_nomor_saat_daftar' => false]);

        $this->post('/register', $this->isian())->assertRedirect(route('dashboard'));

        $user = User::firstOrFail();
        $this->assertFalse((bool) $user->whatsapp_verified);
        $this->assertCount(0, $this->terkirim);

        Auth::logout();
        $this->post('/login', ['email' => 'sri@sekolah.sch.id', 'password' => 'rahasia123']);
        $this->assertTrue(Auth::check());
    }

    public function test_halaman_verifikasi_tanpa_titipan_mengembalikan_ke_formulir(): void
    {
        $this->get('/register/verifikasi')->assertRedirect(route('register'));
    }
}
