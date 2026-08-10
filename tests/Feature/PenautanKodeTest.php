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
 * Penautan WhatsApp lewat KODE, bukan pindai QR.
 *
 * QR mensyaratkan sesuatu yang tidak selalu ada: layar kedua. Wali kelas yang
 * mendaftar langsung dari ponselnya tidak bisa memindai layar ponsel itu
 * dengan ponsel yang sama — sebelum ini ia berhenti di jalan buntu, karena
 * QR adalah satu-satunya jalan yang disediakan.
 *
 * Yang dikunci di sini: metode benar-benar sampai ke gateway, kode sampai ke
 * halaman, dan jalur QR yang lama tidak berubah perilakunya.
 */
class PenautanKodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        Cache::flush();

        // phpunit.xml memakai WHATSAPP_DRIVER=log yang mengikat
        // NullSessionManager; yang diuji di sini implementasi n8n.
        $this->app->bind(
            WhatsAppSessionManager::class,
            fn () => new N8nSessionManager('http://127.0.0.1:3000/session', 'rahasia'),
        );
    }

    private function guru(): User
    {
        return User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'wa_session_id' => null,
            'wa_session_status' => 'disconnected',
        ]);
    }

    /** Payload yang benar-benar dikirim ke gateway pada permintaan terakhir. */
    private function payloadTerakhir(): array
    {
        $rekaman = Http::recorded();

        return json_decode($rekaman[count($rekaman) - 1][0]->body(), true);
    }

    public function test_metode_kode_diteruskan_ke_gateway(): void
    {
        Http::fake(fn () => Http::response([
            'session_id' => 'guru-1',
            'pairing_code' => 'ABCD1234',
            'status' => 'pairing',
        ], 200));

        $this->actingAs($this->guru())
            ->post(route('whatsapp.pair'), ['metode' => 'kode'])
            ->assertRedirect();

        $this->assertSame('kode', $this->payloadTerakhir()['metode'] ?? null,
            'Gateway harus tahu jalur mana yang diminta; tanpa itu ia selalu menerbitkan QR');
    }

    public function test_kode_pairing_sampai_ke_halaman(): void
    {
        Http::fake(fn () => Http::response([
            'session_id' => 'guru-1',
            'pairing_code' => 'ABCD1234',
            'status' => 'pairing',
        ], 200));

        $this->actingAs($this->guru())
            ->post(route('whatsapp.pair'), ['metode' => 'kode'])
            ->assertSessionHas('wa_pairing_code', 'ABCD1234');
    }

    public function test_jalur_qr_tidak_berubah(): void
    {
        Http::fake(fn () => Http::response([
            'session_id' => 'guru-1',
            'qr' => 'data-qr-palsu',
            'status' => 'pairing',
        ], 200));

        $this->actingAs($this->guru())
            ->post(route('whatsapp.pair'), ['metode' => 'qr'])
            ->assertSessionHas('wa_qr', 'data-qr-palsu')
            ->assertSessionMissing('wa_pairing_code');

        $this->assertSame('qr', $this->payloadTerakhir()['metode'] ?? null);
    }

    public function test_metode_tak_dikenal_jatuh_ke_qr(): void
    {
        Http::fake(fn () => Http::response([
            'session_id' => 'guru-1',
            'qr' => 'data-qr-palsu',
            'status' => 'pairing',
        ], 200));

        /*
         * Nilai asing tidak boleh diam-diam mematikan penautan. Jatuh ke QR
         * berarti guru tetap punya jalan, sekalipun bukan yang ia maksud.
         */
        $this->actingAs($this->guru())
            ->post(route('whatsapp.pair'), ['metode' => 'entah-apa'])
            ->assertSessionHas('wa_qr');

        $this->assertSame('qr', $this->payloadTerakhir()['metode'] ?? null);
    }

    public function test_kode_kosong_diberi_tahu_bukan_dibiarkan_diam(): void
    {
        // Gateway menjawab, tapi tanpa kode — mis. WhatsApp sedang menolak
        // koneksi. Halaman yang diam membuat guru mengira aplikasinya rusak.
        Http::fake(fn () => Http::response([
            'session_id' => 'guru-1',
            'pairing_code' => null,
            'status' => 'pairing',
        ], 200));

        $this->actingAs($this->guru())
            ->post(route('whatsapp.pair'), ['metode' => 'kode'])
            ->assertSessionHas('warning')
            ->assertSessionMissing('wa_pairing_code');
    }

    public function test_status_membawa_kode_yang_terbit_belakangan(): void
    {
        /*
         * Kode baru terbit beberapa detik setelah /pair menjawab, karena
         * Baileys harus menyelesaikan jabat tangan lebih dulu. Guru yang
         * menekan tombol tepat sebelum kodenya siap hanya akan melihat
         * halaman diam kalau polling status tidak ikut membawanya.
         */
        Http::fake(fn () => Http::response([
            'status' => 'pairing',
            'pairing_code' => 'ZXY98765',
        ], 200));

        $this->actingAs($this->guru())
            ->getJson(route('whatsapp.status'))
            ->assertOk()
            ->assertJsonPath('pairing_code', 'ZXY98765');
    }

    public function test_nomor_kosong_tetap_ditolak_pada_jalur_kode(): void
    {
        // Penautan lewat kode justru BERTUMPU pada nomor telepon.
        $user = User::factory()->create(['whatsapp_number' => null]);

        $this->actingAs($user)
            ->post(route('whatsapp.pair'), ['metode' => 'kode'])
            ->assertSessionHasErrors('whatsapp');
    }
}
