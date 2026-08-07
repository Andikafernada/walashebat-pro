<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Notifications\N8nSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Audit: aksi gateway yang tidak diimplementasikan (groups / autoreply-*)
 * menaikkan penghitung kegagalan pada circuit breaker BERSAMA
 * 'whatsapp-session', sehingga ikut memblokir pair/status yang sehat.
 */
class Audit02SessionCircuitTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_aksi_tak_dikenal_menjatuhkan_circuit_bersama(): void
    {
        $user = User::factory()->create(['whatsapp_number' => '6281234567890']);

        // Gateway hidup, tapi hanya mengenal pair/status/disconnect.
        Http::fake(function ($request) {
            $body = json_decode($request->body(), true);
            $action = $body['action'] ?? '';

            if (in_array($action, ['pair', 'status', 'disconnect'], true)) {
                return Http::response(['status' => 'connected', 'qr' => null], 200);
            }

            // n8n mengembalikan 404 untuk aksi yang tidak punya cabang.
            return Http::response(['message' => 'no branch matched'], 404);
        });

        $m = new N8nSessionManager('http://127.0.0.1:3000/session', 'rahasia');

        fwrite(STDERR, 'SESCB status awal ='.json_encode($m->status($user))
            .' circuit='.json_encode($m->getCircuitStatus())."\n");

        // Halaman /whatsapp memanggil autoreplyStatus, dan JS memanggil groups.
        for ($i = 1; $i <= 3; $i++) {
            $g = $m->groups($user);
            fwrite(STDERR, "SESCB groups#{$i} hasil=".json_encode($g)
                .' circuit='.json_encode($m->getCircuitStatus())."\n");
        }

        fwrite(STDERR, 'SESCB isHealthy setelah 3 panggilan groups='
            .var_export($m->isHealthy(), true)."\n");

        // Sekarang aksi yang SEHAT pun ikut diblokir:
        fwrite(STDERR, 'SESCB startPairing setelah circuit open='
            .json_encode($m->startPairing($user))."\n");
        fwrite(STDERR, 'SESCB status setelah circuit open='
            .json_encode($m->status($user))."\n");

        $keluar = 0;
        foreach (Http::recorded() as [$req, $res]) {
            $keluar++;
        }
        fwrite(STDERR, "SESCB total HTTP keluar={$keluar}\n");

        $this->assertTrue(true);
    }
}
