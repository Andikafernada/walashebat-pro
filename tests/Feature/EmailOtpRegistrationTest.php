<?php

namespace Tests\Feature;

use App\Mail\RegistrationOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailOtpRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrasi_mengirimkan_otp_dan_mengarahkan_ke_halaman_verifikasi(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Pak Guru Budi',
            'email' => 'budi.santoso@gmail.com',
            'whatsapp_number' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('/register/verify-otp/', $response->headers->get('Location'));

        // Pastikan email OTP dikirimkan
        Mail::assertSent(RegistrationOtpMail::class, function ($mail) {
            return $mail->hasTo('budi.santoso@gmail.com');
        });

        // User belum dibuat di database sebelum verifikasi OTP
        $this->assertDatabaseMissing('users', ['email' => 'budi.santoso@gmail.com']);
    }

    public function test_kode_otp_salah_ditolak(): void
    {
        $token = 'test_token_12345';
        Cache::put("reg_payload_{$token}", [
            'name' => 'Pak Guru Budi',
            'email' => 'budi.santoso@gmail.com',
            'whatsapp_number' => '081234567890',
            'password_hash' => bcrypt('rahasia123'),
        ], 600);
        Cache::put("reg_otp_{$token}", '123456', 600);

        $response = $this->post(route('register.otp.verify', $token), [
            'otp' => '999999', // Salah
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertFalse(Auth::check());
        $this->assertDatabaseMissing('users', ['email' => 'budi.santoso@gmail.com']);
    }

    public function test_kode_otp_benar_membuat_user_terverifikasi_dan_auto_login(): void
    {
        $token = 'test_token_valid';
        Cache::put("reg_payload_{$token}", [
            'name' => 'Pak Guru Budi',
            'email' => 'budi.santoso@gmail.com',
            'whatsapp_number' => '081234567890',
            'password_hash' => bcrypt('rahasia123'),
        ], 600);
        Cache::put("reg_otp_{$token}", '852963', 600);

        $response = $this->post(route('register.otp.verify', $token), [
            'otp' => '852963', // Benar
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());

        $user = User::where('email', 'budi.santoso@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertEquals('Pak Guru Budi', $user->name);
    }
}
