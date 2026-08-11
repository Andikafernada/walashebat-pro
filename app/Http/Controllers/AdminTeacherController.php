<?php

namespace App\Http\Controllers;

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
