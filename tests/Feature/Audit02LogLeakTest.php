<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use App\Support\Notifications\LogChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit: apa yang benar-benar tertulis ke berkas log pada LOG_LEVEL=error?
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

    /** LogChannel (driver=log) menulis PIN? Level info -> harus dibuang. */
    public function test_audit_logchannel_pada_level_error(): void
    {
        (new LogChannel)->send('6285700000001', "PIN Harian: *987654*\nhttps://walas.my.id/a/xyz", ['type' => 'attendance_magic_link'], '6281234567890');

        $isi = $this->isi();
        fwrite(STDERR, 'LOGLEAK LogChannel panjang-berkas='.strlen($isi)
            .' memuat-PIN='.var_export(str_contains($isi, '987654'), true)
            .' memuat-nomor='.var_export(str_contains($isi, '6285700000001'), true)."\n");
        $this->assertTrue(true);
    }

    /** SendWhatsAppMessage::failed() menulis Log::error -> LOLOS filter level. */
    public function test_audit_job_failed_menulis_nomor_ke_log(): void
    {
        $job = new SendWhatsAppMessage(
            to: '6285700000001',
            message: "PIN Harian: *987654*",
            userId: User::factory()->create()->id,
            meta: ['type' => 'attendance_magic_link', 'session_id' => 7, 'class_id' => 3],
            attendanceSessionId: null,
            from: '6281234567890',
        );

        $job->failed(new \RuntimeException('Gateway WhatsApp menolak pesan.'));

        $isi = $this->isi();
        fwrite(STDERR, "LOGLEAK --- isi berkas log setelah failed() ---\n".$isi."--- akhir ---\n");
        fwrite(STDERR, 'LOGLEAK memuat-nomor-tujuan='.var_export(str_contains($isi, '6285700000001'), true)
            .' memuat-nomor-guru='.var_export(str_contains($isi, '6281234567890'), true)
            .' memuat-PIN='.var_export(str_contains($isi, '987654'), true)."\n");
        $this->assertTrue(true);
    }

    /** Fallback diam-diam: driver n8n tapi webhook_url kosong -> LogChannel (selalu true). */
    public function test_audit_fallback_logchannel_melaporkan_sukses_palsu(): void
    {
        config([
            'walikelas.whatsapp.driver' => 'n8n',
            'walikelas.whatsapp.n8n.webhook_url' => null,
        ]);

        $ch = $this->app->make(NotificationChannel::class);
        $hasil = $ch->send('6285700000001', 'PIN Harian: *987654*');

        fwrite(STDERR, 'FALLBACK kelas='.get_class($ch).' hasil-send='.var_export($hasil, true)
            ." (tidak ada pesan yang benar-benar terkirim)\n");
        $this->assertTrue(true);
    }
}
