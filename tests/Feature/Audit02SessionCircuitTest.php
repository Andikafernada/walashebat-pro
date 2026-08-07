<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Notifications\N8nSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Aksi gateway yang belum ada cabangnya di n8n ikut menjatuhkan circuit
 * breaker yang SAMA dengan pair/status.
 *
 * Akibatnya nyata dan sulit ditebak dari gejalanya: halaman /whatsapp memanggil
 * groups() dan autoreplyStatus() sendiri saat dibuka. Bila n8n belum punya
 * cabang untuk aksi-aksi itu, tiga kali membuka halaman sudah cukup untuk
 * membuka circuit 'whatsapp-session' — dan sejak saat itu menautkan nomor
 * WhatsApp gagal dengan pesan "Gateway sedang tidak tersedia", padahal gateway
 * sehat walafiat dan pair/status tidak pernah sekali pun gagal.
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

    /**
     * CATATAN CACAT: ini merekam perilaku yang ADA, bukan yang diinginkan.
     *
     * Bila nanti circuit dipisah per aksi — atau aksi yang tidak dikenal
     * berhenti dihitung sebagai kegagalan gateway — test ini yang harus
     * diperbarui. Kegagalannya saat itu berarti perbaikan, bukan regresi.
     */
    public function test_aksi_tak_dikenal_menjatuhkan_circuit_bersama(): void
    {
        $user = User::factory()->create(['whatsapp_number' => '6281234567890']);
        $this->gatewayTanpaCabangGroups();

        $m = new N8nSessionManager('http://127.0.0.1:3000/session', 'rahasia');

        // Keadaan awal sehat.
        $this->assertSame('connected', $m->status($user)['status']);
        $this->assertSame('closed', $m->getCircuitStatus()['state']);

        // Tiga panggilan groups() — persis yang dilakukan halaman /whatsapp.
        foreach (range(1, 3) as $ke) {
            $m->groups($user);
        }

        $this->assertSame('open', $m->getCircuitStatus()['state']);
        $this->assertFalse($m->isHealthy());

        // Dan sejak itu aksi yang tidak pernah gagal pun ikut terblokir.
        $this->assertSame('disconnected', $m->startPairing($user)['status'],
            'pair ikut mati padahal cabangnya ada dan sehat');
        $this->assertSame('Gateway sedang tidak tersedia.', $m->status($user)['error']);
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
