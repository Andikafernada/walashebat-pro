<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Halaman /whatsapp pernah memuat ulang tanpa henti.
 *
 * Penyebabnya: init() memanggil poll() seketika, dan poll() memanggil
 * window.location.reload() begitu status 'connected'. Untuk guru yang sesinya
 * memang sudah tersambung, keduanya bertemu di setiap pemuatan halaman →
 * lingkaran tak berujung. Bug ini baru muncul setelah komunikasi ke gateway
 * pulih; sebelumnya status selalu 'disconnected' sehingga cabangnya tak pernah
 * tercapai.
 */
class HalamanWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::fake(fn () => Http::response(['status' => 'connected', 'groups' => []], 200));
    }

    private function guru(string $status): User
    {
        return User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'wa_session_status' => $status,
        ]);
    }

    public function test_halaman_terbuka_untuk_guru_yang_sudah_tersambung(): void
    {
        $this->actingAs($this->guru('connected'))
            ->get(route('whatsapp.index'))
            ->assertOk();
    }

    public function test_guru_tersambung_tidak_memulai_polling_yang_memicu_muat_ulang(): void
    {
        $html = $this->actingAs($this->guru('connected'))
            ->get(route('whatsapp.index'))->assertOk()->getContent();

        // Komponen tahu sesi sudah tersambung sejak halaman dibuka…
        $this->assertStringContainsString('connected: true', $html);

        // …dan init() berhenti sebelum sempat memanggil poll(), yang di dalamnya
        // ada window.location.reload().
        $this->assertStringContainsString(
            "if (this.status === 'connected') return;",
            $html,
            'Penjaga anti-loop di init() hilang — halaman akan memuat ulang tanpa henti'
        );
    }

    public function test_guru_belum_tersambung_tetap_mendapat_polling(): void
    {
        $html = $this->actingAs($this->guru('disconnected'))
            ->get(route('whatsapp.index'))->assertOk()->getContent();

        $this->assertStringContainsString('connected: false', $html);

        // Polling tetap dibutuhkan untuk mendeteksi QR yang baru dipindai.
        $this->assertStringContainsString('setInterval(() => this.poll(), 2500)', $html);
    }

    public function test_muat_ulang_hanya_dipakai_sekali_pada_peralihan(): void
    {
        $html = $this->actingAs($this->guru('disconnected'))
            ->get(route('whatsapp.index'))->assertOk()->getContent();

        // Komentar JS ikut terkirim ke browser dan bisa menyebut nama fungsinya,
        // jadi buang dulu agar yang dihitung benar-benar pemanggilan.
        $tanpaKomentar = preg_replace('#/\*.*?\*/|//[^\n]*#s', '', $html);

        // Satu-satunya reload yang boleh ada: setelah QR berhasil dipindai.
        $this->assertSame(
            1,
            substr_count($tanpaKomentar, 'window.location.reload()'),
            'Muat ulang otomatis hanya boleh ada di jalur peralihan sesudah pemindaian QR'
        );
    }
}
