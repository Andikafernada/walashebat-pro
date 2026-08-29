<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationOtpMail;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EmailOtpVerificationController extends Controller
{
    /**
     * Tampilkan formulir input 6 digit kode OTP.
     */
    public function show(string $token): View|RedirectResponse
    {
        $payload = Cache::get("reg_payload_{$token}");

        if (! $payload) {
            return redirect()->route('register')
                ->with('error', 'Sesi pendaftaran telah kedaluwarsa. Silakan isi kembali formulir pendaftaran.');
        }

        // Mask email untuk keamanan (cth: an****@gmail.com)
        $email = $payload['email'];
        $parts = explode('@', $email);
        $namePart = $parts[0];
        $domain = $parts[1] ?? '';
        $maskedName = substr($namePart, 0, 2) . str_repeat('*', max(2, strlen($namePart) - 2));
        $maskedEmail = "{$maskedName}@{$domain}";

        return view('auth.verify_otp', [
            'token' => $token,
            'maskedEmail' => $maskedEmail,
            'name' => $payload['name'],
        ]);
    }

    /**
     * Verifikasi kode OTP yang dimasukkan pengguna.
     */
    public function verify(Request $request, string $token): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'min:6', 'max:6'],
        ], [
            'otp.required' => 'Mohon masukkan 6 digit kode verifikasi.',
            'otp.min' => 'Kode verifikasi harus terdiri dari 6 digit angka.',
            'otp.max' => 'Kode verifikasi harus terdiri dari 6 digit angka.',
        ]);

        $payload = Cache::get("reg_payload_{$token}");
        $storedOtp = Cache::get("reg_otp_{$token}");

        if (! $payload || ! $storedOtp) {
            return redirect()->route('register')
                ->with('error', 'Kode verifikasi telah kedaluwarsa. Silakan daftar ulang.');
        }

        $inputOtp = trim((string) $request->otp);

        // Verifikasi kecocokan OTP
        if ($inputOtp !== (string) $storedOtp) {
            $attempts = (int) Cache::get("reg_attempts_{$token}", 0) + 1;
            Cache::put("reg_attempts_{$token}", $attempts, 600);

            if ($attempts >= 5) {
                Cache::forget("reg_payload_{$token}");
                Cache::forget("reg_otp_{$token}");
                Cache::forget("reg_attempts_{$token}");

                return redirect()->route('register')
                    ->with('error', 'Terlalu banyak percobaan kode yang salah. Silakan mulai pendaftaran baru.');
            }

            return back()->withErrors(['otp' => 'Kode verifikasi salah. Periksa kembali kotak masuk atau spam email Anda.'])
                ->withInput();
        }

        // Cek kembali jika email sudah terdaftar saat menunggu verifikasi
        if (User::where('email', $payload['email'])->exists()) {
            Cache::forget("reg_payload_{$token}");
            Cache::forget("reg_otp_{$token}");

            return redirect()->route('login')
                ->with('warning', 'Alamat email ini sudah terdaftar. Silakan masuk dengan kata sandi Anda.');
        }

        // Buat Akun Guru Resmi Terverifikasi
        $user = new User();
        $user->forceFill([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => $payload['password_hash'],
            'whatsapp_number' => Phone::normalize($payload['whatsapp_number']),
            'role' => User::ROLE_TEACHER ?? 'teacher',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->save();

        // Bersihkan cache pendaftaran
        Cache::forget("reg_payload_{$token}");
        Cache::forget("reg_otp_{$token}");
        Cache::forget("reg_attempts_{$token}");
        Cache::forget("reg_cooldown_{$token}");

        // Auto Login
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Selamat datang, ' . $user->name . '! Akun dan email resmi Anda berhasil diverifikasi.');
    }

    /**
     * Kirim ulang kode OTP baru ke email pengguna.
     */
    public function resend(Request $request, string $token): RedirectResponse
    {
        $payload = Cache::get("reg_payload_{$token}");

        if (! $payload) {
            return redirect()->route('register')
                ->with('error', 'Sesi verifikasi telah kedaluwarsa. Silakan daftar ulang.');
        }

        // Cek Cooldown 60 Detik Anti-Spam
        if (Cache::has("reg_cooldown_{$token}")) {
            return back()->with('warning', 'Mohon tunggu 60 detik sebelum meminta kode baru.');
        }

        // Buat OTP 6 Digit Baru
        $newOtp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put("reg_otp_{$token}", $newOtp, 600); // 10 menit
        Cache::put("reg_cooldown_{$token}", true, 60); // 60 detik cooldown

        try {
            Mail::to($payload['email'])->send(new RegistrationOtpMail(
                name: $payload['name'],
                otp: $newOtp,
                expiryMinutes: 10
            ));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Kode verifikasi baru telah dikirimkan ke email Anda.');
    }
}
