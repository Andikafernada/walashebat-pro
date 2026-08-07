<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show student login form.
     */
    public function showLogin()
    {
        return view('student.auth.login');
    }

    /**
     * Login student via NIS.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'nis' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        /*
         * NIS unik PER SEKOLAH, bukan se-aplikasi — jadi tidak boleh
         * diselesaikan dengan first().
         *
         * NIS di lapangan sering berupa nomor urut pendek ("36"), sehingga dua
         * sekolah punya siswa ber-NIS sama adalah keadaan biasa, bukan
         * kebetulan. Versi lama mengambil satu baris pertama menurut id: siswa
         * yang id-nya lebih besar SELALU gagal masuk memakai kata sandinya
         * sendiri — terkunci permanen tanpa penjelasan — dan bila kebetulan
         * kata sandinya sama dengan siswa satunya, ia justru masuk ke akun
         * sekolah lain.
         *
         * Kata sandi yang membedakan: seluruh kandidat dicoba, dan hanya yang
         * cocok yang masuk. TenantScope tidak berlaku di sini karena belum ada
         * siapa pun yang login; withoutTenant() ditulis tegas agar itu terbaca.
         */
        $kandidat = Student::withoutTenant()
            ->where('nis', $credentials['nis'])
            ->where('is_active', true)
            ->get();

        $cocok = $kandidat->filter(
            fn (Student $s) => filled($s->password) && Hash::check($credentials['password'], $s->password)
        );

        if ($cocok->count() > 1) {
            /*
             * Dua siswa berbeda dengan NIS DAN kata sandi yang sama persis.
             * Menebak salah satunya berarti membuka data sekolah orang lain,
             * jadi di sini kami berhenti.
             */
            throw ValidationException::withMessages([
                'nis' => 'NIS ini terdaftar di lebih dari satu kelas dengan kata sandi yang sama. '
                    .'Hubungi wali kelas Anda untuk mengganti kata sandi.',
            ]);
        }

        $student = $cocok->first();

        if (! $student) {
            // Akun yang kata sandinya belum diterbitkan wali kelas dibedakan,
            // supaya siswa tidak terus mencoba kata sandi yang memang belum ada.
            if ($kandidat->count() === 1 && blank($kandidat->first()->password)) {
                throw ValidationException::withMessages([
                    'nis' => 'Akun belum diaktifkan. Hubungi wali kelas Anda.',
                ]);
            }

            throw ValidationException::withMessages([
                'nis' => 'NIS atau kata sandi salah.',
            ]);
        }

        // Login student
        Auth::guard('student')->login($student);

        $request->session()->regenerate();

        return redirect()->intended(route('student.dashboard'));
    }

    /**
     * Logout student.
     */
    public function logout(Request $request)
    {
        Auth::guard('student')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }

    /**
     * Show change password form (first login or reset).
     */
    public function showPasswordChange()
    {
        $student = Auth::guard('student')->user();

        return view('student.auth.password-change', [
            'student' => $student,
            'must_change' => $student->must_change_password,
        ]);
    }

    /**
     * Change student password.
     */
    public function changePassword(Request $request)
    {
        $student = Auth::guard('student')->user();

        /*
         * Kata sandi lama WAJIB. Aturan sebelumnya berbunyi
         * required_if:must_change,true — merujuk field `must_change` yang tidak
         * pernah dikirim formulir, sehingga tidak pernah aktif; digabung
         * `nullable`, menghilangkan current_password justru lolos validasi dan
         * kata sandi tetap berganti. Siapa pun yang menemukan sesi siswa
         * terbuka dapat mengambil alih akunnya tanpa tahu kata sandi lama.
         *
         * Aman diminta bahkan pada mode wajib-ganti: kata sandi awal siswa
         * diterbitkan wali kelas, jadi siswa memang mengetahuinya.
         */
        $data = $request->validate([
            'current_password' => ['required', 'current_password:student'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $student->update([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ]);

        return redirect()->route('student.dashboard')->with('success', 'Password berhasil diubah.');
    }

    /**
     * Forgot password - request OTP via WhatsApp.
     */
    public function showForgotPassword()
    {
        return view('student.auth.forgot-password');
    }

    /**
     * Kirim OTP reset kata sandi ke WhatsApp orang tua.
     *
     * Pengirimannya lewat NotificationChannel — kontrak yang sama dengan reset
     * kata sandi wali kelas. Sebelumnya kode ini hanya menulis OTP ke Log::info
     * dengan penanda "TODO: Send via WhatsApp", dan karena LOG_LEVEL=error di
     * produksi, kode itu tidak mendarat di mana pun: siswa diberi tahu OTP
     * sudah dikirim padahal tidak pernah ada yang terkirim.
     */
    public function sendOtp(Request $request, NotificationChannel $notifier)
    {
        $validated = $request->validate([
            'nis' => ['required', 'string'],
        ]);

        /*
         * Seluruh siswa ber-NIS itu, bukan yang pertama saja.
         *
         * NIS unik per sekolah. Dengan first(), siswa yang id-nya lebih besar
         * tidak pernah bisa mereset kata sandinya — OTP-nya melayang ke nomor
         * orang tua siswa sekolah lain yang kebetulan ber-NIS sama.
         *
         * Masing-masing menerima kode berbeda, dan kuncinya per siswa. Kode itu
         * sendiri yang nanti menentukan siswa mana yang dimaksud: hanya orang
         * tua yang benar-benar menerimanya yang bisa memakainya.
         */
        $kandidat = Student::withoutTenant()
            ->where('nis', $validated['nis'])
            ->where('is_active', true)
            ->get();

        $ttl = (int) config('walikelas.otp_ttl_minutes', 15);

        foreach ($kandidat as $student) {
            if (blank($student->parent_phone)) {
                continue;
            }

            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            cache()->put("student_otp_{$student->id}", [
                'otp' => Hash::make($otp),
                'nis' => $student->nis,
            ], now()->addMinutes($ttl));

            $notifier->send($student->parent_phone, implode("\n", [
                '*WaliKelas Pro - Reset Kata Sandi Siswa*',
                '',
                'Kode OTP untuk '.$student->name.': *'.$otp.'*',
                'Berlaku '.$ttl.' menit.',
                '',
                'Abaikan pesan ini bila tidak ada permintaan reset kata sandi.',
                'Jangan bagikan kode ini kepada siapa pun.',
            ]), ['type' => 'student_password_reset_otp', 'student_id' => $student->id]);
        }

        /*
         * Pesan seragam, apa pun hasilnya. Membalas "NIS tidak ditemukan"
         * mengubah formulir ini menjadi alat penyisiran: NIS bersifat
         * berurutan, sehingga penyerang dapat memetakan seluruh siswa satu
         * sekolah hanya dengan mencobanya satu per satu.
         */
        return redirect()->route('student.otp.verify', ['nis' => $validated['nis']])
            ->with('status', 'Jika NIS terdaftar dan nomor WhatsApp orang tua tersimpan, kode OTP telah dikirim.');
    }

    /**
     * Verify OTP.
     */
    public function verifyOtp(Request $request, $nis)
    {
        return view('student.auth.otp-verify', ['nis' => $nis]);
    }

    /**
     * Process OTP verification and reset password.
     */
    public function processOtp(Request $request, $nis)
    {
        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        /*
         * Throttle per NIS, bukan hanya per IP seperti middleware route: tanpa
         * ini satu penyerang dengan beberapa alamat IP tetap bisa menyisir
         * seluruh 1 juta kemungkinan kode 6 digit selama masa berlaku OTP.
         */
        $key = 'student-otp-verify|'.$nis.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'otp' => 'Terlalu banyak percobaan. Minta kode baru.',
            ]);
        }

        /*
         * Kodenya sendiri yang menentukan siswa mana yang dimaksud.
         *
         * NIS unik per sekolah, jadi satu NIS bisa menunjuk lebih dari satu
         * siswa. Setiap kandidat menerima kode berbeda ke nomor orang tuanya
         * masing-masing, sehingga kode yang cocok hanya dimiliki satu orang —
         * dan mereset kata sandi siswa yang benar.
         */
        $student = Student::withoutTenant()
            ->where('nis', $nis)
            ->where('is_active', true)
            ->get()
            ->first(function (Student $s) use ($data) {
                $tersimpan = cache()->get("student_otp_{$s->id}");

                return $tersimpan && Hash::check($data['otp'], $tersimpan['otp']);
            });

        if (! $student) {
            RateLimiter::hit($key, 900);

            throw ValidationException::withMessages([
                'otp' => 'Kode OTP salah atau kadaluarsa.',
            ]);
        }

        $student->update([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ]);

        // OTP sekali pakai.
        cache()->forget("student_otp_{$student->id}");
        RateLimiter::clear($key);

        return redirect()->route('student.login')->with('success', 'Password berhasil direset. Silakan login.');
    }
}
