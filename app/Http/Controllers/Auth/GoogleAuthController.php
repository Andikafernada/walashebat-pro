<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect pengguna ke halaman login Google / Akun Belajar.id.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Tangani callback dari Google OAuth.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('login')
                ->with('error', 'Gagal masuk dengan Akun Google. Silakan coba kembali atau gunakan email dan kata sandi.');
        }

        $email = strtolower(trim($googleUser->getEmail()));
        $name = $googleUser->getName() ?: 'Bapak/Ibu Guru';
        $googleId = $googleUser->getId();

        // Cari atau buat akun pengguna
        $user = User::where('email', $email)->first();

        if ($user) {
            // Pengguna lama: update google_id dan pastikan email terverifikasi
            $user->forceFill([
                'google_id' => $googleId,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
        } else {
            // Pengguna baru: daftarkan secara instan
            $user = new User();
            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'password' => bcrypt(Str::random(32)),
                'whatsapp_number' => null,
                'role' => User::ROLE_TEACHER ?? 'teacher',
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
            $user->save();
        }

        // Auto login
        Auth::login($user, remember: true);
        request()->session()->regenerate();

        $pesan = str_ends_with($email, 'belajar.id')
            ? "Selamat datang, {$user->name}! Akun Belajar.id Anda berhasil terhubung 🎖️"
            : "Selamat datang kembali, {$user->name}!";

        return redirect()->route('dashboard')->with('success', $pesan);
    }
}
