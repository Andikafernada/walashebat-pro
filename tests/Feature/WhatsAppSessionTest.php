<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_tidak_bisa_menautkan_sebelum_nomor_diisi(): void
    {
        $user = User::factory()->create(['whatsapp_number' => null]);

        $this->actingAs($user)
            ->post(route('whatsapp.pair'))
            ->assertSessionHasErrors('whatsapp');
    }

    public function test_webhook_menolak_secret_yang_salah(): void
    {
        config(['walikelas.whatsapp.n8n.secret' => 'rahasia-benar']);

        $this->postJson(route('whatsapp.webhook'), [
            'session_id' => 'guru-1',
            'status' => 'connected',
        ], ['X-Webhook-Secret' => 'salah'])->assertForbidden();
    }

    public function test_webhook_memperbarui_status_sesi(): void
    {
        config(['walikelas.whatsapp.n8n.secret' => 'rahasia-benar']);

        $user = User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'wa_session_id' => 'guru-99',
            'wa_session_status' => 'pairing',
        ]);

        $this->postJson(route('whatsapp.webhook'), [
            'session_id' => 'guru-99',
            'status' => 'connected',
        ], ['X-Webhook-Secret' => 'rahasia-benar'])->assertOk();

        $this->assertTrue($user->fresh()->whatsappConnected());
    }

    public function test_sesi_putus_membuat_status_tidak_tersambung(): void
    {
        config(['walikelas.whatsapp.n8n.secret' => 'rahasia-benar']);

        $user = User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'wa_session_id' => 'guru-7',
            'wa_session_status' => 'connected',
            'wa_connected_at' => now(),
        ]);

        $this->postJson(route('whatsapp.webhook'), [
            'session_id' => 'guru-7',
            'status' => 'disconnected',
            'error' => 'Perangkat tertaut dihapus.',
        ], ['X-Webhook-Secret' => 'rahasia-benar'])->assertOk();

        $this->assertFalse($user->fresh()->whatsappConnected());
    }
}
