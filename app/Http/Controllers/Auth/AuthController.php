<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\NomorWhatsApp;
use App\Support\Phone;
use App\Services\CharacterDimensionProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** Maksimal percobaan login sebelum dikunci sementara. */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function __construct() {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // Hitung percobaan gagal per kombinasi email + IP.
            RateLimiter::hit($this->throttleKey($request), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        // Cegah session fixation.
        $request->session()->regenerate();

        /*
         * Admin mendarat di halamannya sendiri.
         *
         * Dashboard wali kelas menampilkan kelas, absensi, dan siswa MILIK
         * pengguna — dan akun admin biasanya tidak punya satu pun. Yang terlihat
         * hanyalah halaman kosong tanpa penjelasan, sehingga admin menyangka
         * salah masuk atau aplikasinya rusak.
         *
         * intended() tetap didahulukan: pengguna yang tadinya menuju halaman
         * tertentu lalu diminta login harus sampai ke tujuan aslinya.
         */
        return redirect()->intended(
            Auth::user()->isAdmin() ? route('admin.dashboard') : route('dashboard')
        );
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Langsung daftar tanpa OTP
     */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'whatsapp_number' => ['required', 'string', 'max:20', new NomorWhatsApp],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Langsung buat user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'whatsapp_number' => $this->normalizePhone($data['whatsapp_number']),
            /*
             * false, dan itu memang keadaan sebenarnya.
             *
             * Pendaftaran tidak mengirim OTP ke nomor ini — pendaftar boleh
             * mengetik nomor siapa pun. Menandainya 'verified' mencatatkan
             * sesuatu yang tidak pernah terjadi. Hari ini kolom itu belum
             * dibaca kode mana pun sehingga belum membuka celah, tetapi
             * begitu ada yang menulis `if ($user->whatsapp_verified)` sebagai
             * penjaga, penjagaan itu lolos untuk semua orang sejak menit
             * pertama — dan tidak ada yang akan mencurigai baris ini.
             *
             * Yang berhak mengubahnya menjadi true hanya OtpService, setelah
             * kode OTP benar-benar dibalas dari nomor tersebut.
             */
            'whatsapp_verified' => false,
        ]);

        /*
         * Masa gratis tiga bulan untuk setiap pendaftar. Sesudahnya otomasi
         * WhatsApp berhenti sendiri karena User::otomasiWhatsAppAktif()
         * membandingkan tanggal ini dengan waktu sekarang — tidak ada proses
         * lain yang perlu membaliknya, jadi tidak ada yang bisa lupa berjalan.
         *
         * forceFill() dipakai karena 'role', 'is_active', dan kolom langganan
         * memang tidak fillable. Sebelumnya 'is_active' dan 'role' dititipkan
         * lewat User::create() di atas dan dibuang diam-diam oleh mass
         * assignment; hasilnya kebetulan benar hanya karena cocok dengan nilai
         * bawaan $attributes — ketergantungan yang tidak terlihat dari sini.
         */
        $user->forceFill([
            'is_active' => true,
            'role' => User::ROLE_TEACHER,
            'subscription_tier' => User::TIER_TRIAL,
            'subscription_ends_at' => User::akhirMasaGratis(),
        ])->save();

        /*
         * Dimensi karakter disiapkan sejak awal.
         *
         * Tanpa ini seluruh halaman Jurnal & Portofolio Karakter — termasuk
         * formulir refleksi publik yang diisi siswa — tampil tanpa satu pun
         * pilihan dimensi dan tidak bisa dikirim. Wali kelas baru tidak punya
         * cara menyadari bahwa yang kurang adalah daftar dimensinya.
         */
        (new CharacterDimensionProvisioner)->provisionFor($user->id);

        // Login user baru
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Pendaftaran berhasil! Selamat menggunakan WaliKelas Pro.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Kunci sementara bila percobaan gagal melewati batas.
     * Kunci throttle memakai email saja agar penyerang tidak bisa
     * melewati batas dengan mengganti IP.
     */
    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    /**
     * Kunci per AKUN, bukan per akun+IP.
     *
     * Versi lama menyertakan alamat IP, sehingga pembatas ini justru tidak
     * melakukan hal yang dijanjikan komentarnya sendiri: penyerang yang
     * berganti IP mendapat lima percobaan baru setiap kali, dan satu akun bisa
     * digempur tanpa batas. Menghapus IP membuat batasnya melekat pada akun
     * yang diserang.
     *
     * Konsekuensinya diterima sadar: seseorang bisa mengunci akun orang lain
     * dengan sengaja salah memasukkan kata sandi. Kuncinya hanya 60 detik
     * (DECAY_SECONDS), jauh lebih ringan daripada membiarkan kata sandi
     * ditebak sampai ketemu.
     *
     * Penyemprotan kata sandi — satu kata sandi dicoba ke BANYAK akun, yang
     * tidak tersentuh kunci per akun — dibendung terpisah oleh throttle per IP
     * pada rute POST /login.
     */
    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')));
    }

    /** Normalisasi nomor — sumbernya sama dengan mutator model User. */
    private function normalizePhone(string $phone): string
    {
        return Phone::normalize($phone) ?? '';
    }
}
