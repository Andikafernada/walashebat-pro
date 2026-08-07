<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aturan produk: setiap pendaftar mendapat tiga bulan gratis, sesudahnya semua
 * pengiriman OTOMATIS ke WhatsApp berhenti.
 *
 * Yang berhenti hanya otomasi. Pesan transaksional — OTP reset kata sandi,
 * kredensial login siswa, konfirmasi pembayaran — harus tetap jalan. Kalau
 * ikut diputus, wali kelas yang lupa kata sandinya terkunci dari akunnya
 * sendiri, dan pembayaran yang sudah disetujui tidak pernah sampai kabarnya
 * ke orang yang membayar.
 */
class GerbangLanggananTest extends TestCase
{
    use RefreshDatabase;

    /** Saluran palsu yang mencatat apa saja yang benar-benar dikirim. */
    private function salurannPerekam(): object
    {
        $rekam = new class implements NotificationChannel
        {
            public array $terkirim = [];

            public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
            {
                $this->terkirim[] = ['to' => $to, 'type' => $meta['type'] ?? null];

                return true;
            }
        };

        $this->app->instance(NotificationChannel::class, $rekam);

        return $rekam;
    }

    public function test_pendaftar_baru_dapat_tiga_bulan_gratis(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Ibu Sari',
            'email' => 'sari@contoh.test',
            'whatsapp_number' => '81234567890',
            'password' => 'RahasiaKuat123',
            'password_confirmation' => 'RahasiaKuat123',
        ]);

        $pengguna = User::where('email', 'sari@contoh.test')->firstOrFail();

        $this->assertTrue($pengguna->otomasiWhatsAppAktif());
        $this->assertEqualsWithDelta(
            now()->addMonths(3)->timestamp,
            $pengguna->subscription_ends_at->timestamp,
            60,
            'Masa gratis pendaftar baru harus tepat tiga bulan.'
        );
    }

    public function test_registrasi_tidak_bisa_menyelundupkan_peran_admin(): void
    {
        // 'role' tidak ada di $fillable; permintaan yang menitipkannya harus
        // tetap menghasilkan akun guru biasa.
        $this->post(route('register.store'), [
            'name' => 'Penyusup',
            'email' => 'penyusup@contoh.test',
            'whatsapp_number' => '81299998888',
            'password' => 'RahasiaKuat123',
            'password_confirmation' => 'RahasiaKuat123',
            'role' => User::ROLE_ADMIN,
            'subscription_ends_at' => now()->addYears(10),
        ]);

        $pengguna = User::where('email', 'penyusup@contoh.test')->firstOrFail();

        $this->assertSame(User::ROLE_TEACHER, $pengguna->role);
        $this->assertTrue($pengguna->subscription_ends_at->lessThan(now()->addMonths(4)));
    }

    public function test_otomasi_berhenti_setelah_masa_gratis_habis(): void
    {
        $rekam = $this->salurannPerekam();
        $guru = User::factory()->kedaluwarsa()->create();

        (new SendWhatsAppMessage(
            to: '6281234567890',
            message: 'Link absensi hari ini',
            userId: $guru->id,
            meta: ['type' => 'attendance_magic_link'],
        ))->handle(app(NotificationChannel::class));

        $this->assertSame([], $rekam->terkirim, 'Otomasi tidak boleh terkirim setelah masa habis.');
    }

    public function test_otomasi_tetap_jalan_selama_masa_masih_berlaku(): void
    {
        $rekam = $this->salurannPerekam();
        $guru = User::factory()->create();

        (new SendWhatsAppMessage(
            to: '6281234567890',
            message: 'Link absensi hari ini',
            userId: $guru->id,
            meta: ['type' => 'attendance_magic_link'],
        ))->handle(app(NotificationChannel::class));

        $this->assertCount(1, $rekam->terkirim);
    }

    /**
     * Masa aktif bisa habis SETELAH pekerjaan mengantre tetapi SEBELUM pekerja
     * menjalankannya. Karena itu gerbangnya ada di dalam job, bukan hanya di
     * tempat dispatch.
     */
    public function test_masa_yang_habis_saat_pesan_mengantre_tetap_tertahan(): void
    {
        $rekam = $this->salurannPerekam();
        $guru = User::factory()->create();

        $pekerjaan = new SendWhatsAppMessage(
            to: '6281234567890',
            message: 'Rekap absensi',
            userId: $guru->id,
            meta: ['type' => 'parent_recap'],
        );

        // Masa berakhir selagi pekerjaan menunggu di antrean.
        $guru->forceFill(['subscription_ends_at' => now()->subMinute()])->save();

        $pekerjaan->handle(app(NotificationChannel::class));

        $this->assertSame([], $rekam->terkirim);
    }

    public function test_otp_reset_kata_sandi_tetap_terkirim_walau_masa_habis(): void
    {
        $rekam = $this->salurannPerekam();
        $guru = User::factory()->kedaluwarsa()->create([
            'whatsapp_number' => '6281234567890',
        ]);

        $this->post(route('password.otp.send'), ['email' => $guru->email]);

        $this->assertCount(
            1,
            $rekam->terkirim,
            'Memutus OTP akan mengunci wali kelas dari akunnya sendiri.'
        );
    }

    public function test_pembayaran_disetujui_memulihkan_otomasi(): void
    {
        $guru = User::factory()->kedaluwarsa()->create();
        $this->assertFalse($guru->otomasiWhatsAppAktif());

        // Meniru yang dilakukan AdminSubscriptionController::approve().
        $guru->forceFill([
            'subscription_tier' => User::TIER_PRO,
            'subscription_ends_at' => now()->addMonth(),
        ])->save();

        $this->assertTrue($guru->fresh()->otomasiWhatsAppAktif());
    }

    public function test_admin_tidak_pernah_terkunci_oleh_langganan(): void
    {
        // Admin memproses persetujuan pembayaran; kalau ia ikut terkunci,
        // tidak ada yang bisa memulihkan langganan siapa pun.
        $admin = User::factory()->kedaluwarsa()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($admin->otomasiWhatsAppAktif());
    }

    public function test_sesi_absensi_ditandai_dilewati_bukan_gagal(): void
    {
        $this->salurannPerekam();
        $guru = User::factory()->kedaluwarsa()->create();
        $kelas = Classroom::factory()->create(['user_id' => $guru->id]);

        $sesi = AttendanceSession::create([
            'user_id' => $guru->id,
            'class_id' => $kelas->id,
            'title' => 'Absensi Pagi',
            'session_date' => today(),
            'token' => 'token-uji-'.uniqid(),
            'pin_hash' => bcrypt('123456'),
            'expires_at' => now()->addHour(),
            'status' => 'open',
            'delivery_status' => 'pending',
        ]);

        (new SendWhatsAppMessage(
            to: '6281234567890',
            message: 'Link absensi',
            userId: $guru->id,
            meta: ['type' => 'attendance_magic_link'],
            attendanceSessionId: $sesi->id,
        ))->handle(app(NotificationChannel::class));

        $sesi->refresh();

        // 'failed' akan mengirim wali kelas mengejar kerusakan yang tidak ada.
        $this->assertSame('skipped', $sesi->delivery_status);
        $this->assertStringContainsString('Masa otomasi', $sesi->delivery_error);
    }
}
