<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        RateLimiter::clear('test@walas.my.id|127.0.0.1');
    }

    public function test_login_dikunci_setelah_lima_percobaan_gagal(): void
    {
        User::factory()->create([
            'email' => 'test@walas.my.id',
            'password' => Hash::make('kata-sandi-benar'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => 'test@walas.my.id',
                'password' => 'salah',
            ]);
        }

        // Percobaan ke-6 ditolak walau kata sandinya benar.
        $this->post(route('login'), [
            'email' => 'test@walas.my.id',
            'password' => 'kata-sandi-benar',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_bisa_mengakses_dashboard(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }
}
