<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Batas laju pendaftaran adalah satu-satunya rem terhadap banjir akun palsu. */
class BatasPendaftaranTest extends TestCase
{
    use RefreshDatabase;

    public function test_pendaftaran_dibatasi_setelah_20_percobaan(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $ditolak = 0;

        for ($i = 1; $i <= 25; $i++) {
            $r = $this->post('/register', [
                'name' => "Guru $i",
                'email' => "guru$i@sekolah.id",
                'whatsapp_number' => '81234567'.str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ]);
            if ($r->getStatusCode() === 429) { $ditolak++; }
            $this->flushSession();
            auth()->logout();
        }

        $this->assertGreaterThan(0, $ditolak, 'Throttle harus menolak sebagian percobaan');
        $this->assertLessThanOrEqual(20, User::count(), 'Tidak boleh lebih dari 20 akun lolos');
        fwrite(STDERR, "\n[throttle] akun dibuat=".User::count()." ditolak-429=$ditolak\n");
    }
}
