<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\PaymentProof;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daftar guru yang mendaftar — siapa, bukan berapa.
 *
 * Panel Operator sudah menjawab BERAPA: guru aktif, pendaftar 30 hari
 * terakhir, dan grafik pendaftaran mingguan. Yang tidak dijawabnya adalah
 * SIAPA. Ketika seorang guru menelepon atau mengirim WhatsApp — "saya sudah
 * transfer", "absensi saya tidak terkirim", "kok masih diminta bayar" —
 * operator tidak punya satu pun tempat untuk mencari orangnya, kecuali kalau
 * kebetulan namanya sedang muncul di daftar tagih atau di antrean bukti bayar.
 *
 * Karena itu halaman ini sengaja dangkal: satu daftar, satu kotak cari, satu
 * saringan segmen. Bukan panel manajemen pengguna dengan sunting dan hapus —
 * yang dibutuhkan operator adalah menemukan orang, dan tindakan atas akunnya
 * sudah punya tempatnya sendiri di halaman langganan.
 */
class AdminTeacherController extends Controller
{
    /**
     * Empat segmen ini HARUS memakai definisi yang sama persis dengan
     * AdminDashboardController::segmenLangganan(). Bila keduanya berselisih,
     * operator melihat "12 berbayar" di Panel Operator lalu membuka daftar ini
     * dan menemukan 11 — dan tidak ada cara mengetahui mana yang benar.
     */
    private const SEGMEN = ['masa_gratis', 'gratis_habis', 'berbayar', 'berbayar_lewat_tempo'];

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));
        $segmen = in_array($request->query('segmen'), self::SEGMEN, true)
            ? $request->query('segmen')
            : null;

        /*
         * lintasSeluruhTenant() wajib. Tanpa itu TenantScope membatasi query ke
         * user_id admin yang sedang login, dan akun admin tidak memiliki satu
         * pun guru — halamannya kosong tanpa satu pun galat yang menjelaskan.
         */
        [$guru, $jumlahSegmen] = TenantScope::lintasSeluruhTenant(fn () => [
            $this->daftar($cari, $segmen),
            $this->hitungPerSegmen(),
        ]);

        return view('admin.teachers.index', [
            'guru' => $guru,
            'jumlahSegmen' => $jumlahSegmen,
            'cari' => $cari,
            'segmen' => $segmen,
        ]);
    }

    /**
     * Satu guru, dilihat dari sisi operator.
     *
     * Halaman ini menjawab keluhan yang benar-benar masuk lewat WhatsApp:
     * "absensi saya tidak terkirim", "saya sudah transfer", "kok masih diminta
     * bayar". Ketiganya butuh riwayat, bukan angka — jadi yang dikumpulkan di
     * sini adalah jejak: sesi absensi terakhir beserta sebab kegagalan
     * pengirimannya, bukti bayar beserta putusannya, dan keadaan gateway.
     *
     * Tetap tanpa tombol tindakan. Menyetujui pembayaran sudah punya tempatnya
     * di halaman langganan; menyalinnya ke sini berarti dua tempat yang bisa
     * berselisih tentang hal yang sama.
     */
    public function show(User $guru): View
    {
        /*
         * Route model binding menjalankan query-nya SEBELUM controller ini
         * dipanggil, jadi ia tidak ikut terbungkus lintasSeluruhTenant() di
         * bawah. Karena itu keanggotaannya diperiksa di sini: tanpa ini,
         * operator bisa membuka /admin/guru/{id} milik akun admin lain dan
         * halaman ini menampilkannya sebagai pelanggan.
         */
        abort_unless($guru->role === User::ROLE_TEACHER, 404);

        return view('admin.teachers.show', TenantScope::lintasSeluruhTenant(fn () => [
            'guru' => $guru,
            'kelas' => Classroom::where('user_id', $guru->id)
                ->withCount(['students' => fn ($q) => $q->where('is_active', true)])
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
            // Cukup untuk menjawab "kapan terakhir terkirim, dan kenapa gagal".
            'sesiTerakhir' => AttendanceSession::with('classroom:id,name')
                ->where('user_id', $guru->id)
                ->latest('session_date')
                ->latest('id')
                ->limit(10)
                ->get(),
            'pembayaran' => PaymentProof::where('user_id', $guru->id)
                ->latest('created_at')
                ->get(),
        ]));
    }

    /**
     * Nonaktifkan / aktifkan akun guru.
     *
     * is_active=false punya gigi nyata: EnsureUserIsActive ('auth.tenant')
     * langsung me-logout akun itu pada request berikutnya. Jadi ini bukan
     * penanda kosmetik — operator memakainya untuk menghentikan penyalahguna
     * atau membekukan akun yang bermasalah.
     *
     * is_active tidak fillable (kolom sensitif di $guarded), jadi diisi lewat
     * penetapan properti langsung, bukan update() massal.
     */
    public function toggleAktif(User $guru): \Illuminate\Http\RedirectResponse
    {
        abort_unless($guru->role === User::ROLE_TEACHER, 404);

        $guru->is_active = ! $guru->is_active;
        $guru->save();

        return back()->with('success', $guru->is_active
            ? "Akun {$guru->name} diaktifkan kembali."
            : "Akun {$guru->name} dinonaktifkan — ia akan ter-logout dan tidak bisa masuk.");
    }

    /**
     * Beri / perpanjang PRO manual — tanpa bukti bayar.
     *
     * Jalur cepat operator untuk kompensasi, keluhan, atau perpanjangan trial,
     * saat tidak ada (atau tidak perlu) upload bukti. Aturan penumpukan bulan
     * memakai User::tambahPro yang SAMA dengan persetujuan pembayaran, jadi
     * keduanya tak pernah menghitung tanggal akhir dengan cara berbeda.
     */
    public function beriPro(Request $request, User $guru): \Illuminate\Http\RedirectResponse
    {
        abort_unless($guru->role === User::ROLE_TEACHER, 404);

        $data = $request->validate([
            'bulan' => ['required', 'integer', 'min:1', 'max:24'],
        ], [
            'bulan.max' => 'Maksimal 24 bulan sekali beri.',
            'bulan.min' => 'Jumlah bulan minimal 1.',
        ]);

        $newEnd = $guru->tambahPro($data['bulan']);

        // Guru harus tahu ia mendapat PRO; pola & try/catch sama dengan
        // persetujuan pembayaran. Gagal kirim dicatat, bukan membatalkan hibah.
        if ($guru->whatsapp_number) {
            $msg = "🎉 *AKSES PRO DIBERIKAN*\n\n"
                . "Halo Bpk/Ibu *{$guru->name}*,\n"
                . "Akses PRO {$data['bulan']} bulan telah diaktifkan untuk akun Anda.\n\n"
                . "✅ Status: *PRO AKTIF*\n"
                . "📅 Berlaku Hingga: *{$newEnd->translatedFormat('d F Y')}*\n\n"
                . "Terima kasih telah menggunakan WaliKelas Pro! 🚀";

            try {
                app(\App\Support\Contracts\NotificationChannel::class)->send(
                    $guru->whatsapp_number,
                    $msg,
                    ['type' => 'subscription_granted', 'user_id' => $guru->id],
                    auth()->user()->whatsapp_number,
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('[langganan] Konfirmasi PRO manual gagal dikirim', [
                    'user_id' => $guru->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', "{$guru->name} diberi PRO {$data['bulan']} bulan — aktif hingga {$newEnd->translatedFormat('d F Y')}.");
    }

    /**
     * Reset kata sandi guru — keluhan support paling umum ("saya lupa sandi").
     *
     * Sandi sementara dibuat acak dan ditampilkan SEKALI di flash agar operator
     * membacakannya ke guru; ia tidak disimpan dalam bentuk terbaca di mana pun.
     * Tanpa simbol supaya mudah dieja lewat telepon/WA. Kolom password 'hashed'
     * cast, jadi update() otomatis mem-bcrypt-nya.
     */
    public function resetPassword(User $guru): \Illuminate\Http\RedirectResponse
    {
        abort_unless($guru->role === User::ROLE_TEACHER, 404);

        $sementara = \Illuminate\Support\Str::password(10, letters: true, numbers: true, symbols: false);
        $guru->update(['password' => $sementara]);

        return back()->with('success', "Kata sandi {$guru->name} direset. Sandi sementara (tampil sekali): {$sementara} — minta guru menggantinya setelah masuk.");
    }

    /**
     * Masuk sebagai guru (impersonate) untuk menelusuri keluhan "di akun saya
     * error" dari sisi guru itu sendiri.
     *
     * TenantScope memakai Auth::id(), jadi begitu login berpindah ke guru,
     * SELURUH data otomatis ter-scope ke guru itu — tidak ada jalan pintas yang
     * menembus tenant. id admin disimpan di sesi sebagai remah untuk kembali.
     *
     * Dicatat sebagai jejak audit: masuk ke akun orang lain harus terlihat.
     * Akun nonaktif tidak bisa disamari — EnsureUserIsActive akan langsung
     * menendang keluar; suruh operator mengaktifkannya dulu.
     */
    public function masukSebagai(User $guru): \Illuminate\Http\RedirectResponse
    {
        abort_unless($guru->role === User::ROLE_TEACHER, 404);

        if (! $guru->is_active) {
            return back()->with('error', 'Akun ini nonaktif — aktifkan dulu sebelum masuk sebagai guru.');
        }

        \Illuminate\Support\Facades\Log::info('[impersonate] Operator masuk sebagai guru', [
            'admin_id' => auth()->id(),
            'guru_id' => $guru->id,
        ]);

        session(['impersonator_id' => auth()->id()]);
        \Illuminate\Support\Facades\Auth::login($guru);

        return redirect()->route('dashboard');
    }

    /**
     * Kembali ke akun operator. Sengaja DI LUAR grup role:admin: saat dipanggil,
     * yang login adalah guru (bukan admin), jadi middleware role:admin justru
     * akan menghalanginya kembali. Penjaganya remah sesi impersonator_id.
     */
    public function berhentiMenyamar(): \Illuminate\Http\RedirectResponse
    {
        $adminId = session()->pull('impersonator_id');
        abort_unless($adminId, 403);

        \Illuminate\Support\Facades\Auth::loginUsingId($adminId);

        return redirect()->route('admin.teachers.index');
    }

    /**
     * Hapus akun guru secara permanen beserta seluruh datanya.
     *
     * Cascades: classrooms (soft delete), students, attendances, violations,
     * cashbooks, character records, notifications, dan sessão WA.
     * Wizard akun yang login sendiri tidak bisa menghapus dirinya sendiri.
     */
    public function destroy(\App\Models\User $guru): \Illuminate\Http\RedirectResponse
    {
        if ($guru->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        if ($guru->role !== \App\Models\User::ROLE_TEACHER) {
            return redirect()->back()
                ->with('error', 'Hanya akun guru yang bisa dihapus dari sini.');
        }

        $nama = $guru->name;

        \Illuminate\Support\Facades\DB::transaction(function () use ($guru) {
            // Hapus sesi WA dari gateway dulu agar tidak orphan
            try {
                $manager = app(\App\Support\Contracts\WhatsAppSessionManager::class);
                $manager->disconnect($guru);
            } catch (\Throwable) {
                // Gateway offline bukan penghalang penghapusan
            }

            $guru->delete(); // soft delete
            $guru->forceDelete(); // hapus permanen
        });

        return redirect()->route('admin.teachers.index')
            ->with('success', "Akun {$nama} beserta seluruh datanya telah dihapus permanen.");
    }

    private function daftar(string $cari, ?string $segmen): LengthAwarePaginator
    {
        return User::query()
            ->where('role', User::ROLE_TEACHER)
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$cari}%")
                ->orWhere('email', 'like', "%{$cari}%")
                ->orWhere('school_name', 'like', "%{$cari}%")
                ->orWhere('whatsapp_number', 'like', "%{$cari}%")))
            ->when($segmen, fn ($q) => $this->saringSegmen($q, $segmen))
            /*
             * withCount, bukan perulangan yang menghitung kelas per baris:
             * 20 baris berarti 20 query tambahan, dan itu tumbuh diam-diam
             * seiring jumlah guru — halaman yang cepat hari ini, lambat tahun
             * depan, tanpa satu pun perubahan kode.
             */
            ->withCount(['classes' => fn ($q) => $q->where('is_active', true)])
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<User>  $q */
    private function saringSegmen($q, string $segmen)
    {
        $aktif = fn ($w) => $w->where('subscription_ends_at', '>', now());
        $habis = fn ($w) => $w->where(fn ($x) => $x->whereNull('subscription_ends_at')
            ->orWhere('subscription_ends_at', '<=', now()));

        return match ($segmen) {
            'masa_gratis' => $q->where('subscription_tier', User::TIER_TRIAL)->where($aktif),
            'gratis_habis' => $q->where('subscription_tier', User::TIER_TRIAL)->where($habis),
            'berbayar' => $q->where('subscription_tier', User::TIER_PRO)->where($aktif),
            'berbayar_lewat_tempo' => $q->where('subscription_tier', User::TIER_PRO)->where($habis),
        };
    }

    /** @return array<string, int> */
    private function hitungPerSegmen(): array
    {
        $hitung = fn (string $segmen) => $this->saringSegmen(
            User::query()->where('role', User::ROLE_TEACHER),
            $segmen,
        )->count();

        return array_combine(self::SEGMEN, array_map($hitung, self::SEGMEN));
    }
}
