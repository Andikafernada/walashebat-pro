<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Contracts\WhatsAppSessionManager;
use App\Support\Notifications\N8nSessionManager;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tombol "Cek status" menjawab pertanyaan yang tidak bisa dijawab oleh
 * pengaturan tersimpan: apakah balasan otomatis AKAN benar-benar jalan.
 *
 * Sengaja tidak mengirim pesan apa pun — berbeda dari uji kirim — supaya aman
 * ditekan berkali-kali tanpa mengganggu grup orang tua.
 */
class CekBalasanOtomatisTest extends TestCase
{
    use RefreshDatabase;

    private const GRUP = '120363321166050533@g.us';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Cache::flush();

        $this->user = User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'wa_session_status' => 'connected',
        ]);
        $this->actingAs($this->user);

        $this->app->bind(
            WhatsAppSessionManager::class,
            fn () => new N8nSessionManager('http://127.0.0.1:3000/session', 'rahasia'),
        );
    }

    private function gatewayMenjawab(array $isi, int $status = 200): void
    {
        Http::fake(fn () => Http::response($isi, $status));
    }

    private function syaratLengkap(array $timpa = []): array
    {
        return [
            'siap' => true,
            'syarat' => $timpa + [
                'sesi_tersambung' => true,
                'fitur_menyala' => true,
                'grup_terdaftar' => true,
                'dalam_jam_kerja' => true,
                'kuota_tersisa' => 15,
            ],
            'jam' => '06:00-21:00',
            'kuota_harian' => 15,
            'terpakai_hari_ini' => 0,
        ];
    }

    public function test_grup_siap_dilaporkan_siap(): void
    {
        $this->gatewayMenjawab($this->syaratLengkap());

        $this->postJson(route('whatsapp.autoreply.check'), ['group_id' => self::GRUP])
            ->assertOk()
            ->assertJson(['ok' => true, 'siap' => true])
            ->assertJsonPath('syarat.grup_terdaftar', true)
            ->assertJsonPath('kuota_harian', 15);
    }

    public function test_grup_tidak_terdaftar_dilaporkan_belum_siap(): void
    {
        $hasil = $this->syaratLengkap(['grup_terdaftar' => false]);
        $hasil['siap'] = false;
        $this->gatewayMenjawab($hasil);

        $this->postJson(route('whatsapp.autoreply.check'), ['group_id' => self::GRUP])
            ->assertOk()
            ->assertJson(['ok' => true, 'siap' => false])
            ->assertJsonPath('syarat.grup_terdaftar', false);
    }

    public function test_di_luar_jam_kerja_dilaporkan_belum_siap(): void
    {
        $hasil = $this->syaratLengkap(['dalam_jam_kerja' => false]);
        $hasil['siap'] = false;
        $this->gatewayMenjawab($hasil);

        $this->postJson(route('whatsapp.autoreply.check'), ['group_id' => self::GRUP])
            ->assertOk()
            ->assertJsonPath('siap', false)
            ->assertJsonPath('syarat.dalam_jam_kerja', false);
    }

    public function test_kuota_habis_dilaporkan_belum_siap(): void
    {
        $hasil = $this->syaratLengkap(['kuota_tersisa' => 0]);
        $hasil['siap'] = false;
        $hasil['terpakai_hari_ini'] = 15;
        $this->gatewayMenjawab($hasil);

        $this->postJson(route('whatsapp.autoreply.check'), ['group_id' => self::GRUP])
            ->assertOk()
            ->assertJsonPath('siap', false)
            ->assertJsonPath('syarat.kuota_tersisa', 0)
            ->assertJsonPath('terpakai_hari_ini', 15);
    }

    public function test_gateway_gagal_dilaporkan_bukan_sebagai_belum_siap_biasa(): void
    {
        $this->gatewayMenjawab(['error' => 'rate-overlimit'], 500);

        $this->postJson(route('whatsapp.autoreply.check'), ['group_id' => self::GRUP])
            ->assertStatus(503)
            ->assertJson(['ok' => false, 'siap' => false]);
    }

    public function test_nomor_belum_tersambung_ditolak(): void
    {
        $this->user->update(['wa_session_status' => 'disconnected']);

        $this->postJson(route('whatsapp.autoreply.check'), ['group_id' => self::GRUP])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'siap' => false]);
    }

    /** Sejalan dengan penjagaan pada penyimpanan: hanya JID grup. */
    public function test_menolak_jid_yang_bukan_grup(): void
    {
        $this->gatewayMenjawab($this->syaratLengkap());

        $this->postJson(route('whatsapp.autoreply.check'), ['group_id' => '628123456789@s.whatsapp.net'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('group_id');
    }

    /** Tidak boleh ada satu pun pesan terkirim akibat pemeriksaan. */
    public function test_pemeriksaan_tidak_mengirim_pesan(): void
    {
        $this->gatewayMenjawab($this->syaratLengkap());

        $this->postJson(route('whatsapp.autoreply.check'), ['group_id' => self::GRUP])->assertOk();

        foreach (Http::recorded() as [$permintaan, $balasan]) {
            $isi = json_decode($permintaan->body(), true);
            $this->assertSame(
                'autoreply-check',
                $isi['action'] ?? null,
                'Pemeriksaan hanya boleh memanggil aksi diagnosa, bukan pengiriman'
            );
        }
    }

    public function test_tombol_cek_tampil_di_halaman(): void
    {
        Http::fake(fn () => Http::response(['enabled' => true, 'groups' => [self::GRUP], 'jam' => '06:00-21:00'], 200));

        $html = $this->get(route('whatsapp.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Cek status', $html);
        $this->assertStringContainsString('periksa(g.id)', $html);
    }
}
