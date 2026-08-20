<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Notifications\N8nSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Aksi yang belum punya cabang di n8n tidak boleh menjatuhkan circuit breaker
 * milik aksi lain.
 *
 * Dulu bisa, dan gejalanya sulit ditebak: halaman /whatsapp memanggil groups()
 * dan autoreplyStatus() sendiri saat dibuka. Bila n8n belum punya cabang untuk
 * aksi-aksi itu, tiga kali membuka halaman sudah cukup untuk membuka circuit
 * 'whatsapp-session' — dan sejak saat itu menautkan nomor WhatsApp gagal dengan
 * pesan "Gateway sedang tidak tersedia", padahal gateway sehat walafiat dan
 * pair/status tidak pernah sekali pun gagal.
 *
 * Circuit tetap satu untuk semua aksi; yang berubah, 404/501 tidak lagi
 * dihitung sebagai kegagalan gateway.
 */
class Audit02SessionCircuitTest extends TestCase
{
    use RefreshDatabase;

    /** Gateway yang hanya mengenal pair/status/disconnect, seperti n8n polos. */
    private function gatewayTanpaCabangGroups(): void
    {
        Http::fake(function ($request) {
            $aksi = json_decode($request->body(), true)['action'] ?? '';

            if (in_array($aksi, ['pair', 'status', 'disconnect'], true)) {
                return Http::response(['status' => 'connected', 'qr' => null], 200);
            }

            // n8n menjawab 404 untuk aksi yang tidak punya cabang.
            return Http::response(['message' => 'no branch matched'], 404);
        });
    }

    public function test_aksi_tak_dikenal_tidak_menjatuhkan_circuit_bersama(): void
    {
        $user = User::factory()->create(['whatsapp_number' => '6281234567890']);
        $this->gatewayTanpaCabangGroups();

        $m = new N8nSessionManager('http://127.0.0.1:3000/session', 'rahasia');

        // Keadaan awal sehat.
        $this->assertSame('connected', $m->status($user)['status']);
        $this->assertSame('closed', $m->getCircuitStatus()['state']);

        // Sepuluh panggilan groups() — jauh di atas ambang 3, persis pola
        // halaman /whatsapp yang dibuka berkali-kali sepanjang hari.
        foreach (range(1, 10) as $ke) {
            $m->groups($user);
        }

        $this->assertSame('closed', $m->getCircuitStatus()['state'],
            '404 tanpa cabang bukan bukti gateway rusak, jadi jangan dihitung');
        $this->assertSame(0, $m->getCircuitStatus()['failures']);
        $this->assertTrue($m->isHealthy());

        // Yang penting bagi wali kelas: menautkan nomor tetap bisa dilakukan.
        $this->assertSame('connected', $m->startPairing($user)['status'],
            'pair sehat dan cabangnya ada, jadi tidak boleh ikut terblokir');
        $this->assertNull($m->status($user)['error']);
    }

    /**
     * Aksi tak dikenal harus dilaporkan sebagai fitur yang belum ada, bukan
     * sebagai gateway rusak — supaya wali kelas tidak menunggu pemulihan yang
     * tidak akan pernah datang, dan tidak mengira daftar grupnya lenyap.
     */
    public function test_aksi_tak_dikenal_dilaporkan_sebagai_belum_tersedia(): void
    {
        $user = User::factory()->create(['whatsapp_number' => '6281234567890']);
        $this->gatewayTanpaCabangGroups();

        $m = new N8nSessionManager('http://127.0.0.1:3000/session', 'rahasia');

        $hasil = $m->groupsResult($user);

        $this->assertFalse($hasil['ok']);
        $this->assertSame([], $hasil['groups']);
        $this->assertSame('Fitur ini belum tersedia di gateway WhatsApp.', $hasil['error']);
    }

    /**
     * Penjagaan arah sebaliknya: menyaring 404 tidak boleh membuat circuit jadi
     * tuli. Gateway yang benar-benar rusak (5xx) tetap harus menjatuhkannya,
     * termasuk saat kerusakannya muncul di aksi yang jarang dipakai.
     */
    public function test_gateway_rusak_tetap_menjatuhkan_circuit(): void
    {
        $user = User::factory()->create(['whatsapp_number' => '6281234567890']);

        Http::fake(fn () => Http::response(['message' => 'workflow error'], 500));

        $m = new N8nSessionManager('http://127.0.0.1:3000/session', 'rahasia');

        foreach (range(1, 3) as $ke) {
            $m->groups($user);
        }

        $this->assertSame('open', $m->getCircuitStatus()['state']);
        $this->assertFalse($m->isHealthy());
        $this->assertSame('Gateway sedang tidak tersedia.', $m->status($user)['error']);
    }

    /**
     * 404 juga tidak boleh menghapus jejak kegagalan yang sudah terkumpul.
     * Kalau ia dianggap "berhasil", gateway yang setengah rusak tidak akan
     * pernah menjatuhkan circuit selama halaman terus memanggil aksi tak
     * dikenal di sela-selanya.
     */
    public function test_aksi_tak_dikenal_tidak_menghapus_hitungan_kegagalan(): void
    {
        $user = User::factory()->create(['whatsapp_number' => '6281234567890']);

        Http::fake(function ($request) {
            $aksi = json_decode($request->body(), true)['action'] ?? '';

            return $aksi === 'groups'
                ? Http::response(['message' => 'no branch matched'], 404)
                : Http::response(['message' => 'workflow error'], 500);
        });

        $m = new N8nSessionManager('http://127.0.0.1:3000/session', 'rahasia');

        // Dua kegagalan sungguhan diselingi aksi tak dikenal.
        $m->status($user);
        $m->groups($user);
        $m->status($user);
        $this->assertSame(2, $m->getCircuitStatus()['failures']);
        $this->assertSame('closed', $m->getCircuitStatus()['state']);

        // Kegagalan ketiga tetap menjatuhkan circuit tepat pada waktunya.
        $m->status($user);
        $this->assertSame('open', $m->getCircuitStatus()['state']);
    }

    /** Pembanding: gateway yang lengkap tidak pernah menjatuhkan circuit. */
    public function test_gateway_dengan_cabang_lengkap_tidak_menjatuhkan_circuit(): void
    {
        $user = User::factory()->create(['whatsapp_number' => '6281234567890']);

        Http::fake(fn () => Http::response(['status' => 'connected', 'qr' => null, 'groups' => []], 200));

        $m = new N8nSessionManager('http://127.0.0.1:3000/session', 'rahasia');

        foreach (range(1, 5) as $ke) {
            $m->groups($user);
        }

        $this->assertSame('closed', $m->getCircuitStatus()['state']);
        $this->assertTrue($m->isHealthy());
        $this->assertSame('connected', $m->status($user)['status']);
    }
}
