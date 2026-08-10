<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Scopes\TenantScope;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Student;
use App\Services\AttendanceSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Alur pengisian absensi lewat magic link (tanpa login).
 *
 * Keamanan:
 * - Sesi diambil lewat token acak (bukan ID tebakan), TANPA tenant scope
 *   (tidak ada user login), sehingga wajib pakai withoutTenant().
 * - PIN harian harus cocok (di-hash, dibandingkan dengan Hash::check).
 * - Sesi harus berstatus open dan belum kedaluwarsa.
 * - Setelah submit, status -> submitted agar tidak bisa diisi ulang.
 *
 * Bentuk alurnya: PIN (POST verify) -> REDIRECT -> roster (GET) -> POST submit.
 * Halaman roster sengaja punya URL GET sendiri. Sebelumnya roster dirender
 * langsung sebagai balasan POST /verify, dan itu punya dua akibat nyata di
 * lapangan: menyegarkan halaman memicu dialog "kirim ulang formulir", dan
 * setiap kegagalan validasi saat submit membuat Laravel redirect() balik ke
 * URL POST tadi -- yang lewat GET berujung 405 Method Not Allowed.
 */
class AttendanceMagicLinkController extends Controller
{
    /** Halaman awal: minta PIN. */
    public function show(Request $request, string $token): View|RedirectResponse
    {
        $session = $this->resolveSession($token);

        if ($session->status === 'submitted') {
            return redirect()->route('magic.done', $token);
        }

        // PIN sudah diverifikasi di perangkat ini -- antar langsung ke roster.
        if ($session->isOpen() && $this->verified($request, $token)) {
            return redirect()->route('magic.roster', $token);
        }

        return view('attendance.public.pin', [
            'session' => $session,
            'locked' => ! $session->isOpen(),
        ]);
    }

    /** Verifikasi PIN, lalu antar ke halaman roster. */
    public function verify(Request $request, string $token): RedirectResponse
    {
        $session = $this->resolveSession($token);

        if (! $session->isOpen()) {
            return redirect()->route('magic.show', $token);
        }

        $request->validate(['pin' => ['required', 'string']], [], ['pin' => 'PIN']);

        /*
         * Batas percobaan PER TOKEN. throttle pada rute hanya membatasi per IP,
         * sehingga penyerang dengan banyak IP tetap bisa menggempur satu sesi.
         * Kunci ini mengikat percobaan ke sesi itu sendiri.
         */
        $key = 'magic-pin|'.$session->id;
        $maxAttempts = (int) config('walikelas.pin_max_attempts', 10);
        $decaySeconds = (int) config('walikelas.pin_decay_seconds', 600);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $menit = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

            throw ValidationException::withMessages([
                'pin' => "Terlalu banyak percobaan PIN. Coba lagi dalam {$menit} menit.",
            ]);
        }

        // Spasi, tanda hubung, dan tanda bintang ikut tersalin saat PIN
        // di-copy dari pesan WhatsApp yang ditebalkan.
        $pin = preg_replace('/\D/', '', (string) $request->input('pin')) ?? '';

        if ($pin === '' || ! $session->verifyPin($pin)) {
            RateLimiter::hit($key, $decaySeconds);
            $sisa = max(0, $maxAttempts - RateLimiter::attempts($key));

            throw ValidationException::withMessages([
                'pin' => $sisa > 0
                    ? "PIN salah. Sisa {$sisa} percobaan."
                    : 'PIN salah dan percobaan sudah habis. Minta tautan baru ke wali kelas.',
            ]);
        }

        RateLimiter::clear($key);

        // Penanda lolos PIN, supaya submit tidak perlu meminta PIN lagi.
        $request->session()->put("magic_ok:{$token}", true);

        return redirect()->route('magic.roster', $token);
    }

    /** Daftar siswa untuk diisi. Hanya bisa dibuka setelah PIN benar. */
    public function roster(Request $request, string $token): View|RedirectResponse
    {
        $session = $this->resolveSession($token);

        if ($session->status === 'submitted') {
            return redirect()->route('magic.done', $token);
        }

        if (! $session->isOpen()) {
            return redirect()->route('magic.show', $token);
        }

        if (! $this->verified($request, $token)) {
            return redirect()->route('magic.show', $token)
                ->with('warning', 'Masukkan PIN harian dulu untuk membuka daftar siswa.');
        }

        $students = Student::withoutTenant()
            ->where('class_id', $session->class_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'nis']);

        return view('attendance.public.roster', [
            'session' => $session,
            'students' => $students,
            'statuses' => Attendance::STATUSES,
        ]);
    }

    /** Simpan absensi. */
    public function submit(Request $request, string $token): RedirectResponse
    {
        $session = $this->resolveSession($token);

        /*
         * get(), BUKAN pull(): bila submit gagal validasi, petugas harus bisa
         * memperbaiki isian tanpa dimintai PIN ulang. Flag dihapus setelah
         * sukses. Sesi yang kadung hangus diarahkan, bukan di-abort 403 --
         * halaman galat mentah di WebView WhatsApp tidak memberi petugas jalan
         * keluar apa pun.
         */
        if (! $this->verified($request, $token)) {
            return redirect()->route('magic.show', $token)
                ->with('warning', 'Sesi verifikasi berakhir. Masukkan PIN sekali lagi lalu isi ulang daftarnya.');
        }

        if ($session->status === 'submitted') {
            return redirect()->route('magic.done', $token);
        }

        if (! $session->isOpen()) {
            return redirect()->route('magic.show', $token);
        }

        /*
         * ID siswa yang sah untuk sesi ini. Divalidasi sebagai himpunan, bukan
         * satu-satu di dalam transaksi, supaya kiriman yang kurang lengkap
         * ditolak terang-terangan alih-alih tersimpan setengah jalan lalu
         * sesinya terkunci sebagai "submitted".
         */
        $idSah = Student::withoutTenant()
            ->where('class_id', $session->class_id)
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        if ($idSah === []) {
            return redirect()->route('magic.show', $token)
                ->with('warning', 'Kelas ini belum punya data siswa aktif. Hubungi wali kelas.');
        }

        $validated = $request->validate([
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', 'in:'.implode(',', Attendance::STATUSES)],
            'notes' => ['sometimes', 'array'],
            'notes.*' => ['nullable', 'string', 'max:200'],
        ], [
            'attendance.required' => 'Belum ada siswa yang ditandai.',
            'attendance.*.required' => 'Masih ada siswa yang belum ditandai.',
            'attendance.*.in' => 'Status kehadiran tidak dikenali.',
            'notes.*.max' => 'Catatan maksimal 200 karakter.',
        ]);

        $kehadiran = array_intersect_key($validated['attendance'], array_flip($idSah));

        if (count($kehadiran) !== count($idSah)) {
            throw ValidationException::withMessages([
                'attendance' => 'Masih ada siswa yang belum ditandai. Periksa lagi daftarnya.',
            ]);
        }

        $catatan = $validated['notes'] ?? [];

        DB::transaction(function () use ($session, $kehadiran, $catatan, $request) {
            foreach ($kehadiran as $studentId => $status) {
                $session->attendances()->updateOrCreate(
                    ['student_id' => $studentId],
                    [
                        // Diisi eksplisit: tidak ada user login di konteks
                        // publik, jadi auto-isi tenant tidak berlaku di sini.
                        'user_id' => $session->user_id,
                        'status' => $status,
                        'note' => trim(strip_tags((string) ($catatan[$studentId] ?? ''))) ?: null,
                    ]
                );
            }

            $session->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'submitted_ip' => $request->ip(),
            ]);
        });

        // Verifikasi PIN sudah dipakai; bersihkan agar tautan tak bisa dipakai ulang.
        $request->session()->forget("magic_ok:{$token}");

        /*
         * Rekap ke grup orang tua sengaja dibungkus try/catch.
         *
         * Absensinya SUDAH tersimpan pada titik ini. Bila gateway WhatsApp mati
         * atau nomor grupnya salah, petugas tidak boleh melihat halaman galat --
         * dari sisi dia pekerjaannya memang sudah selesai, dan mengulanginya
         * hanya akan tertolak karena sesi sudah submitted. Kegagalannya masuk
         * log, bukan ditelan tanpa jejak.
         */
        try {
            app(AttendanceSessionService::class)->dispatchParentRecap($session);
        } catch (\Throwable $e) {
            Log::error('[absensi] Rekap orang tua gagal diantrikan', [
                'session_id' => $session->id,
                'class_id' => $session->class_id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('magic.done', $token);
    }

    public function done(string $token): View
    {
        $session = $this->resolveSession($token);

        $session->loadCount([
            'attendances as hadir_count' => fn ($q) => $q->where('status', 'hadir'),
            'attendances as absen_count' => fn ($q) => $q->where('status', '!=', 'hadir'),
        ]);

        return view('attendance.public.done', ['session' => $session]);
    }

    /** Apakah PIN sudah diverifikasi di perangkat/browser ini? */
    private function verified(Request $request, string $token): bool
    {
        return (bool) $request->session()->get("magic_ok:{$token}", false);
    }

    private function resolveSession(string $token): AttendanceSession
    {
        /*
         * Tanpa tenant scope: konteks publik, akses sah lewat token rahasia.
         *
         * classroom TIDAK di-eager-load di sini. withoutTenant() hanya melepas
         * scope pada query terluar, sedangkan with() menjalankan query relasi
         * tersendiri yang masih membawanya — dan itu berjalan sebelum tenantnya
         * sempat ditetapkan di bawah, sehingga kelasnya terbaca null dan seluruh
         * halaman berakhir 404. Dibiarkan lazy, ia dimuat setelah tenant
         * diketahui dan tersaring dengan benar.
         */
        $session = AttendanceSession::withoutTenant()
            ->where('token', $token)
            ->first();

        if (! $session) {
            abort(404);
        }

        /*
         * Token cocok, jadi tenantnya tidak lagi tidak diketahui.
         *
         * withoutTenant() di atas hanya melepas query terluar; setiap relasi
         * sesudahnya ($session->classroom, ->attendances, daftar siswa) membawa
         * TenantScope sendiri dan kini gagal-tertutup, sehingga tanpa baris ini
         * halaman absensi kosong tanpa galat. Menambal tiap relasi satu per satu
         * berarti belasan titik yang harus diingat — dan yang terlewat gagal
         * senyap, persis kekeliruan yang sedang diperbaiki.
         */
        TenantScope::pakaiTenant($session->user_id);

        // Kelas bisa diarsipkan (soft delete) setelah tautan terkirim.
        // Tanpa penjagaan ini, view akan error saat mengakses $session->classroom.
        abort_if($session->classroom === null, 404);

        return $session;
    }
}
