<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Reset kata sandi lewat OTP WhatsApp.
 *
 * Dipilih ketimbang tautan email karena guru di Indonesia jauh lebih sering
 * membuka WhatsApp daripada email, dan kanal WhatsApp sudah tersedia.
 *
 * Keamanan:
 * - OTP disimpan sebagai HASH di tabel password_reset_tokens (bukan plaintext).
 * - Berlaku terbatas (config walikelas.otp_ttl_minutes).
 * - Rate limit per email+IP untuk permintaan maupun percobaan verifikasi.
 * - Respons permintaan OTP selalu sama, tidak membocorkan email mana yang terdaftar.
 */
class PasswordResetController extends Controller
{
    public function showRequestForm(): View
    {
        return view('auth.forgot');
    }

    public function sendOtp(Request $request, NotificationChannel $notifier): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        /*
         * Dua batas, bukan satu. Batas per-IP menahan satu penyerang mengirim
         * banyak permintaan; batas per-alamat-email menahan penyerang yang
         * berganti-ganti IP untuk membanjiri WhatsApp satu guru dengan OTP —
         * mengganggu korban sekaligus menghabiskan kuota kirim kita.
         */
        $kunciIp = 'otp-request|'.$request->ip();
        $kunciEmail = 'otp-request-email|'.mb_strtolower($request->input('email'));

        if (RateLimiter::tooManyAttempts($kunciIp, 5) || RateLimiter::tooManyAttempts($kunciEmail, 3)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak permintaan. Coba lagi beberapa saat.',
            ]);
        }
        RateLimiter::hit($kunciIp, 300);
        RateLimiter::hit($kunciEmail, 900);

        $user = User::where('email', $request->input('email'))->first();

        // Hanya kirim bila akun ada, aktif, dan punya nomor WhatsApp.
        if ($user && $user->is_active && $user->whatsapp_number) {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($otp), 'created_at' => now()]
            );

            $ttl = (int) config('walikelas.otp_ttl_minutes', 15);

            $notifier->send($user->whatsapp_number, implode("\n", [
                '*WaliKelas Pro - Reset Kata Sandi*',
                '',
                'Kode OTP Anda: *'.$otp.'*',
                'Berlaku '.$ttl.' menit.',
                '',
                'Abaikan pesan ini bila Anda tidak meminta reset kata sandi.',
                'Jangan bagikan kode ini kepada siapa pun.',
            ]), ['type' => 'password_reset_otp', 'user_id' => $user->id]);
        }

        // Pesan seragam: tidak mengungkap apakah email terdaftar.
        return redirect()->route('password.reset.form', ['email' => $request->input('email')])
            ->with('success', 'Jika email terdaftar dan memiliki nomor WhatsApp, kode OTP telah dikirim.');
    }

    public function showResetForm(Request $request): View
    {
        return view('auth.reset', ['email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $key = 'otp-verify|'.$data['email'].'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'otp' => 'Terlalu banyak percobaan. Minta kode baru.',
            ]);
        }

        $record = DB::table('password_reset_tokens')->where('email', $data['email'])->first();
        $ttl = (int) config('walikelas.otp_ttl_minutes', 15);

        // Carbon::parse: created_at dari query builder berupa string.
        // addMinutes()->isFuture() tidak terpengaruh perubahan tanda
        // diffInMinutes() antara Carbon 2 dan 3.
        $valid = $record
            && Carbon::parse($record->created_at)->addMinutes($ttl)->isFuture()
            && Hash::check($data['otp'], $record->token);

        if (! $valid) {
            RateLimiter::hit($key, 900);

            throw ValidationException::withMessages([
                'otp' => 'Kode OTP salah atau sudah kedaluwarsa.',
            ]);
        }

        $user = User::where('email', $data['email'])->firstOrFail();
        $user->update(['password' => $data['password']]); // cast 'hashed' pada model

        // OTP sekali pakai.
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
        RateLimiter::clear($key);

        return redirect()->route('login')
            ->with('success', 'Kata sandi berhasil diubah. Silakan masuk.');
    }
}
