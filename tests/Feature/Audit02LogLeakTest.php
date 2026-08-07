<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use App\Support\Notifications\LogChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Apa yang benar-benar tertulis ke berkas log pada LOG_LEVEL=error.
 *
 * Berkas log adalah tempat rahasia bocor tanpa siapa pun berniat membocorkannya:
 * ia dibaca saat panik, disalin ke percakapan dukungan, dan ikut terbawa ke
 * dalam cadangan. Yang paling penting dijaga di sini adalah PIN absensi —
 * siapa pun yang memegangnya bisa mengisi kehadiran satu kelas.
 */
class Audit02LogLeakTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = storage_path('logs/audit02-probe.log');
        @unlink($this->path);

        // Tiru produksi: stack -> single, level error.
        config([
            'logging.default' => 'audit02',
            'logging.channels.audit02' => [
                'driver' => 'single',
                'path' => $this->path,
                'level' => 'error',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        parent::tearDown();
    }

    private function isi(): string
    {
        return is_file($this->path) ? file_get_contents($this->path) : '';
    }

    /**
     * LogChannel menulis di level info, jadi pada LOG_LEVEL=error seluruh
     * isinya — termasuk PIN — tidak pernah sampai ke berkas.
     */
    public function test_logchannel_tidak_menulis_apa_pun_pada_level_error(): void
    {
        (new LogChannel)->send(
            '6285700000001',
            "PIN Harian: *987654*\nhttps://walas.my.id/a/xyz",
            ['type' => 'attendance_magic_link'],
            '6281234567890',
        );

        $isi = $this->isi();

        $this->assertSame('', $isi);
        $this->assertStringNotContainsString('987654', $isi, 'PIN tidak boleh sampai ke berkas log');
    }

    /**
     * PIN tidak boleh ikut tercatat saat job menyerah.
     *
     * Ini invarian yang sesungguhnya penting, dan ia berlaku: failed() mencatat
     * konteks pengiriman tetapi bukan isi pesannya.
     *
     * CATATAN CACAT (tidak dinyatakan sebagai harapan): kedua nomor WhatsApp —
     * tujuan dan pengirim — memang tertulis apa adanya. Itu data pribadi yang
     * mengendap di berkas yang berumur panjang. Direkam di sini supaya
     * penyamarannya nanti terlihat sebagai perubahan yang disengaja.
     */
    public function test_job_yang_menyerah_tidak_mencatat_pin(): void
    {
        $job = new SendWhatsAppMessage(
            to: '6285700000001',
            message: 'PIN Harian: *987654*',
            userId: User::factory()->create()->id,
            meta: ['type' => 'attendance_magic_link', 'session_id' => 7, 'class_id' => 3],
            attendanceSessionId: null,
            from: '6281234567890',
        );

        $job->failed(new \RuntimeException('Gateway WhatsApp menolak pesan.'));

        $isi = $this->isi();

        $this->assertStringNotContainsString('987654', $isi, 'PIN tidak boleh ikut tercatat');
        $this->assertStringContainsString('Pesan gagal setelah semua percobaan', $isi,
            'kegagalannya sendiri tetap harus meninggalkan jejak');

        // Perilaku saat ini, bukan cita-cita:
        $this->assertStringContainsString('6285700000001', $isi,
            'nomor tujuan masih tercatat apa adanya — lihat CATATAN CACAT di docblock');
    }

    /**
     * driver=n8n tanpa webhook_url diam-diam jatuh ke LogChannel, dan
     * LogChannel selalu menjawab "berhasil".
     *
     * Artinya satu kolom .env yang lupa diisi membuat seluruh aplikasi yakin
     * pesannya terkirim, sementara tidak satu pun benar-benar keluar — dan
     * tidak ada yang tampak rusak sampai ada yang bertanya kenapa tautannya
     * tidak pernah datang.
     */
    public function test_konfigurasi_gateway_tak_lengkap_jatuh_ke_logchannel(): void
    {
        config([
            'walikelas.whatsapp.driver' => 'n8n',
            'walikelas.whatsapp.n8n.webhook_url' => null,
        ]);

        $ch = $this->app->make(NotificationChannel::class);

        $this->assertInstanceOf(LogChannel::class, $ch);
        $this->assertTrue(
            $ch->send('6285700000001', 'PIN Harian: *987654*'),
            'perilaku saat ini: melaporkan sukses tanpa mengirim apa pun',
        );
    }
}
