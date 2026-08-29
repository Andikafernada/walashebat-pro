<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\NomorWhatsApp;
use App\Rules\ValidRealEmail;
use App\Mail\RegistrationOtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Services\CharacterDimensionProvisioner;
use App\Support\Contracts\NotificationChannel;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
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
        if ($request->filled('hp_website')) {
            return redirect()->route('register')->withErrors(['email' => 'Pendaftaran tidak dapat diproses.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'max:191', 'unique:users,email', new ValidRealEmail],
            'whatsapp_number' => ['required', 'string', 'max:20', new NomorWhatsApp],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Generate 6-digit OTP and secure session token
        $token = Str::random(40);
        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Store registration payload in cache for 15 minutes
        Cache::put("reg_payload_{$token}", [
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'whatsapp_number' => $data['whatsapp_number'],
            'password_hash' => bcrypt($data['password']),
        ], 900);

        Cache::put("reg_otp_{$token}", $otp, 900);
        Cache::put("reg_cooldown_{$token}", true, 60);

        // Send Email OTP
        try {
            Mail::to($data['email'])->send(new RegistrationOtpMail(
                name: $data['name'],
                otp: $otp,
                expiryMinutes: 10
            ));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('register.otp.show', $token)
            ->with('success', 'Kode verifikasi 6 digit telah dikirimkan ke email Anda.');
    }

    /**
     * Buat akun, siapkan isinya, lalu masuk.
     *
     * Dipisahkan karena kini ada dua jalan menuju ke sini: pendaftaran tanpa
     * verifikasi, dan pendaftaran yang kodenya sudah dibalas benar.
     */
    private function selesaikanPendaftaran(
        Request $request,
        array $data,
        bool $terverifikasi
    ): RedirectResponse {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            /*
             * Sudah ter-hash sebelum sampai ke sini, dan HARUS begitu. Jalur
             * berverifikasi menitipkan kata sandi ke cache dalam bentuk hash;
             * meng-hash ulang di sini menghasilkan akun yang kata sandinya
             * tidak pernah cocok dengan apa pun yang diketik pemiliknya.
             */
            'password' => $data['password_hash'],
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
             * true hanya bila kode yang dikirim ke nomor itu benar-benar
             * dibalas — lihat verifikasi(). Dengan sakelar verifikasi mati,
             * nilainya tetap false: mencatat keadaan yang sebenarnya.
             */
            'whatsapp_verified' => $terverifikasi,
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

    /**
     * Terbitkan kode dan kirim ke nomor yang diketik pendaftar.
     *
     * Akun BELUM dibuat di sini. Membuat akun lebih dulu lalu menandainya
     * "belum terverifikasi" meninggalkan baris setengah jadi setiap kali
     * seseorang menutup halaman — dan baris itu memegang alamat surel, yang
     * unik, sehingga orang yang sama tidak bisa mendaftar ulang. Seluruh isian
     * dititipkan ke cache bersama kodenya dan hangus sendiri bila didiamkan.
     *
     * Kodenya disimpan ter-hash. Cache aplikasi ini bukan tempat rahasia:
     * siapa pun yang bisa membacanya tidak boleh langsung bisa memakai kodenya.
     */
    private function mintaKodeVerifikasi(Request $request, array $data): RedirectResponse
    {
        $nomor = Phone::normalize($data['whatsapp_number']);
        $ttl = (int) config('walikelas.otp_ttl_minutes', 15);
        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $terkirim = $this->kirimKode($nomor, $kode, $ttl);

        /*
         * Gateway mati tidak boleh menutup pendaftaran.
         *
         * Gateway menjawab 503 bila tidak ada sesi pengirim yang tersambung.
         * Menolak pendaftaran saat itu terjadi menghukum guru atas gangguan di
         * pihak kita — padahal nomor ini bukan alat masuk dan tidak menjaga apa
         * pun. Maka akunnya tetap dibuat, whatsapp_verified tetap false, dan
         * guru diberi tahu apa adanya.
         *
         * Verifikasinya tidak hilang, hanya tertunda: menautkan WhatsApp nanti
         * membuktikan kepemilikan nomor yang sama, dan di situlah tandanya
         * berubah. Yang tidak boleh terjadi hanyalah menyatakan terverifikasi
         * tanpa satu pun bukti.
         */
        if (! $terkirim) {
            $data['password_hash'] = bcrypt($data['password']);

            return $this->selesaikanPendaftaran($request, $data, terverifikasi: false)
                ->with('warning', 'Akun Anda sudah dibuat, tetapi kode verifikasi belum bisa dikirim '
                    .'karena layanan WhatsApp sedang terganggu. Nomor Anda akan terverifikasi '
                    .'sendiri begitu Anda menautkan WhatsApp di menu Integrasi WhatsApp.');
        }

        $token = Str::random(40);

        Cache::put("daftar:{$token}", [
            'kode' => Hash::make($kode),
            'nomor' => $nomor,
            'percobaan' => 0,
            'data' => [
                'name' => $data['name'],
                'email' => $data['email'],
                'whatsapp_number' => $data['whatsapp_number'],
                // Di-hash sekarang juga: kata sandi mentah tidak pernah menginap
                // di cache, meski cuma lima belas menit.
                'password_hash' => bcrypt($data['password']),
            ],
        ], now()->addMinutes($ttl));

        $request->session()->put('daftar_token', $token);

        return redirect()->route('register.verifikasi.form')
            ->with('success', 'Kode verifikasi dikirim ke WhatsApp '.$this->nomorSamar($nomor).'.');
    }

    /** Halaman pengisian kode. */
    public function showVerifikasi(Request $request): View|RedirectResponse
    {
        $titipan = $this->titipanPendaftaran($request);

        if (! $titipan) {
            return redirect()->route('register')
                ->withErrors(['whatsapp_number' => 'Sesi pendaftaran sudah berakhir. Silakan isi formulirnya lagi.']);
        }

        return view('auth.verify', [
            'nomorSamar' => $this->nomorSamar($titipan['nomor']),
        ]);
    }

    /** Cocokkan kode, lalu buat akunnya. */
    public function verifikasi(Request $request): RedirectResponse
    {
        $request->validate(['otp' => ['required', 'digits:6']]);

        $token = (string) $request->session()->get('daftar_token');
        $titipan = $this->titipanPendaftaran($request);

        if (! $titipan) {
            return redirect()->route('register')
                ->withErrors(['whatsapp_number' => 'Sesi pendaftaran sudah berakhir. Silakan isi formulirnya lagi.']);
        }

        /*
         * Enam digit bisa ditebak 1.000.000 kali; tanpa batas percobaan,
         * menebaknya lebih murah daripada menunggu pesannya datang.
         */
        if ($titipan['percobaan'] >= 5) {
            Cache::forget("daftar:{$token}");
            $request->session()->forget('daftar_token');

            return redirect()->route('register')
                ->withErrors(['whatsapp_number' => 'Terlalu banyak kode salah. Silakan daftar ulang.']);
        }

        if (! Hash::check($request->string('otp')->toString(), $titipan['kode'])) {
            $titipan['percobaan']++;
            Cache::put("daftar:{$token}", $titipan, now()->addMinutes((int) config('walikelas.otp_ttl_minutes', 15)));

            return back()->withErrors(['otp' => 'Kode tidak cocok. Sisa percobaan: '.(5 - $titipan['percobaan']).'.']);
        }

        Cache::forget("daftar:{$token}");
        $request->session()->forget('daftar_token');

        return $this->selesaikanPendaftaran($request, $titipan['data'], terverifikasi: true);
    }

    /** Terbitkan kode baru untuk titipan yang sama. */
    public function kirimUlang(Request $request): RedirectResponse
    {
        $token = (string) $request->session()->get('daftar_token');
        $titipan = $this->titipanPendaftaran($request);

        if (! $titipan) {
            return redirect()->route('register')
                ->withErrors(['whatsapp_number' => 'Sesi pendaftaran sudah berakhir. Silakan isi formulirnya lagi.']);
        }

        $ttl = (int) config('walikelas.otp_ttl_minutes', 15);
        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        if (! $this->kirimKode($titipan['nomor'], $kode, $ttl)) {
            return back()->withErrors(['otp' => 'Kode baru tidak bisa dikirim saat ini. Coba lagi beberapa menit lagi.']);
        }

        // Percobaan direset bersama kodenya: yang salah tadi menebak kode lama.
        $titipan['kode'] = Hash::make($kode);
        $titipan['percobaan'] = 0;
        Cache::put("daftar:{$token}", $titipan, now()->addMinutes($ttl));

        return back()->with('success', 'Kode baru sudah dikirim.');
    }

    /** @return array<string, mixed>|null */
    private function titipanPendaftaran(Request $request): ?array
    {
        $token = $request->session()->get('daftar_token');

        return $token ? Cache::get("daftar:{$token}") : null;
    }

    private function kirimKode(string $nomor, string $kode, int $ttl): bool
    {
        return app(NotificationChannel::class)->send($nomor, implode("\n", [
            '*WaliKelas Pro - Verifikasi Nomor*',
            '',
            'Kode pendaftaran Anda: *'.$kode.'*',
            'Berlaku '.$ttl.' menit.',
            '',
            'Abaikan pesan ini bila Anda tidak sedang mendaftar.',
            'Jangan bagikan kode ini kepada siapa pun.',
        ]), ['type' => 'registrasi_otp']);
    }

    /** 6281234****90 — cukup untuk dikenali pemiliknya, tidak cukup untuk disalin. */
    private function nomorSamar(string $nomor): string
    {
        return strlen($nomor) <= 6
            ? $nomor
            : substr($nomor, 0, 6).str_repeat('*', max(0, strlen($nomor) - 8)).substr($nomor, -2);
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
