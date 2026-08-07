<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\PaymentProof;
use App\Models\Scopes\TenantScope;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Dashboard operator SaaS.
     *
     * `role = admin` di sini berarti pemilik aplikasi, bukan kepala sekolah di
     * suatu sekolah. Halaman ini sebelumnya menyajikan rata-rata kehadiran
     * sekolah, pelanggaran per kategori, dan saldo kas per kelas — angka yang
     * berguna bagi kepala sekolah, jabatan yang tidak memakai produk ini. Yang
     * dibutuhkan operator adalah hal yang menuntut tindakan hari itu juga:
     * pembayaran yang menunggu verifikasi manual, masa aktif yang mau habis,
     * gateway WhatsApp yang putus, dan pekerjaan antrian yang gagal.
     *
     * SELURUH pengumpulan data dibungkus lintasSeluruhTenant(). Tanpa itu
     * TenantScope membatasi setiap query ke user_id admin yang sedang login —
     * dan akun admin tidak memiliki satu pun kelas atau siswa, sehingga
     * halamannya menampilkan nol di mana-mana tanpa satu pun galat.
     */
    public function index(): View
    {
        $data = TenantScope::lintasSeluruhTenant(fn () => [
            'ringkas' => $this->ringkasan(),
            'segmen' => $this->segmenLangganan(),
            'perluDitagih' => $this->perluDitagih(),
            'gateway' => $this->kesehatanGateway(),
            'antrian' => $this->kesehatanAntrian(),
            'pertumbuhan' => $this->pertumbuhan(),
            'skala' => $this->skalaPlatform(),
        ]);

        return view('admin.dashboard', $data);
    }

    /**
     * Angka kepala halaman — empat hal yang ingin dilihat operator lebih dulu.
     */
    private function ringkasan(): array
    {
        $pending = PaymentProof::where('status', 'pending')->oldest()->get(['created_at']);

        return [
            'guru_aktif' => $this->guru()->where('is_active', true)->count(),
            'guru_baru_30h' => $this->guru()->where('created_at', '>=', now()->subDays(30))->count(),
            'otomasi_aktif' => $this->guru()->where('subscription_ends_at', '>', now())->count(),
            'pembayaran_pending' => $pending->count(),
            // Berapa lama bukti tertua sudah menunggu. Verifikasi DANA manual,
            // jadi angka inilah SLA-nya: guru yang sudah mentransfer tetapi
            // belum diaktifkan akan menunggu selama ini.
            'pending_terlama_jam' => $pending->isNotEmpty()
                ? (int) $pending->first()->created_at->diffInHours(now())
                : 0,
            'wa_tersambung' => $this->guru()->where('wa_session_status', 'connected')->count(),
        ];
    }

    /**
     * Empat keadaan langganan yang benar-benar berbeda penanganannya.
     *
     * `subscription_tier` menyimpan apakah guru pernah membayar; masa aktifnya
     * dibaca dari `subscription_ends_at` — sumber kebenaran yang sama dipakai
     * User::otomasiWhatsAppAktif(). Silangnya menghasilkan empat segmen, dan
     * dua di antaranya adalah pekerjaan: masa gratis habis (calon konversi
     * pertama) dan pelanggan berbayar yang lewat tempo (harus ditagih ulang).
     */
    private function segmenLangganan(): array
    {
        $hitung = fn (string $tier, bool $aktif) => $this->guru()
            ->where('subscription_tier', $tier)
            ->when(
                $aktif,
                fn ($q) => $q->where('subscription_ends_at', '>', now()),
                fn ($q) => $q->where(fn ($w) => $w->whereNull('subscription_ends_at')
                    ->orWhere('subscription_ends_at', '<=', now())),
            )
            ->count();

        return [
            'masa_gratis' => $hitung(User::TIER_TRIAL, true),
            'gratis_habis' => $hitung(User::TIER_TRIAL, false),
            'berbayar' => $hitung(User::TIER_PRO, true),
            'berbayar_lewat_tempo' => $hitung(User::TIER_PRO, false),
        ];
    }

    /**
     * Guru yang masa otomasinya habis dalam 14 hari, atau baru saja habis.
     *
     * Diurutkan menurut tanggal berakhir sehingga yang paling mendesak berada
     * di atas. Yang sudah lewat dibatasi 30 hari ke belakang: setelah sebulan
     * tanpa perpanjangan, itu bukan lagi tagihan yang tertunda melainkan
     * pengguna yang berhenti, dan menumpuknya di sini hanya menenggelamkan
     * baris yang masih bisa ditindaklanjuti.
     */
    private function perluDitagih(): Collection
    {
        return $this->guru()
            ->where('is_active', true)
            ->whereNotNull('subscription_ends_at')
            ->whereBetween('subscription_ends_at', [now()->subDays(30), now()->addDays(14)])
            ->orderBy('subscription_ends_at')
            ->get(['id', 'name', 'email', 'school_name', 'whatsapp_number', 'subscription_tier', 'subscription_ends_at'])
            ->map(fn (User $u) => [
                'nama' => $u->name,
                'sekolah' => $u->school_name ?: '—',
                'whatsapp' => $u->whatsapp_number,
                'pernah_bayar' => $u->subscription_tier === User::TIER_PRO,
                'berakhir' => $u->subscription_ends_at,
                // Negatif berarti sudah lewat sekian hari. Tanda itu yang
                // membedakan "ingatkan" dari "sudah mati".
                'sisa_hari' => (int) round(now()->diffInDays($u->subscription_ends_at, absolute: false)),
            ]);
    }

    /**
     * Kesehatan gateway WhatsApp per guru.
     *
     * Dua sisi yang berbeda: sesi yang putus (guru tidak akan bisa mengirim apa
     * pun) dan pengiriman yang gagal walau sesinya tersambung. Keduanya sampai
     * ke operator sebagai keluhan yang sama — "absensi saya tidak terkirim" —
     * jadi keduanya ditampilkan berdampingan.
     */
    private function kesehatanGateway(): array
    {
        $status = $this->guru()
            ->selectRaw('wa_session_status, COUNT(*) as jumlah')
            ->groupBy('wa_session_status')
            ->pluck('jumlah', 'wa_session_status');

        $bermasalah = $this->guru()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('wa_session_status', '!=', 'connected')
                ->orWhereNotNull('wa_last_error'))
            ->orderByDesc('wa_connected_at')
            ->limit(15)
            ->get(['id', 'name', 'whatsapp_number', 'wa_session_status', 'wa_last_error', 'wa_connected_at'])
            ->map(fn (User $u) => [
                'nama' => $u->name,
                'whatsapp' => $u->whatsapp_number,
                'status' => $u->wa_session_status,
                'galat' => $u->wa_last_error,
                'terakhir_tersambung' => $u->wa_connected_at,
            ]);

        return [
            'per_status' => $status,
            'bermasalah' => $bermasalah,
            // Magic link absensi yang gagal sampai ke WhatsApp dalam sepekan.
            'kirim_gagal_7h' => DB::table('attendance_sessions')
                ->where('delivery_status', 'failed')
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];
    }

    /**
     * Antrian dan pekerjaan yang gagal.
     *
     * Queue::size() menembak Redis; bila Redis mati, halaman ini justru menjadi
     * satu-satunya tempat operator bisa mengetahuinya — maka kegagalannya
     * ditampilkan sebagai temuan, bukan dibiarkan menjatuhkan seluruh halaman
     * dengan galat 500.
     */
    private function kesehatanAntrian(): array
    {
        try {
            $antre = Queue::size();
            $redisGalat = null;
        } catch (\Throwable $e) {
            $antre = null;
            $redisGalat = $e->getMessage();
        }

        $gagalTerbaru = DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(5)
            ->get(['queue', 'payload', 'exception', 'failed_at'])
            ->map(fn ($row) => [
                'queue' => $row->queue,
                'job' => json_decode($row->payload, true)['displayName'] ?? 'Tidak diketahui',
                // Baris pertama exception sudah memuat kelas dan pesannya;
                // sisanya stack trace yang tidak terbaca di dalam tabel.
                'pesan' => str(strtok($row->exception, "\n"))->limit(160)->toString(),
                'waktu' => $row->failed_at,
            ]);

        return [
            'menunggu' => $antre,
            'redis_galat' => $redisGalat,
            'gagal_total' => DB::table('failed_jobs')->count(),
            'gagal_24j' => DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count(),
            'gagal_terbaru' => $gagalTerbaru,
        ];
    }

    /**
     * Pendaftar per pekan dan pemakaian nyata.
     *
     * Pendaftaran saja tidak cukup untuk menilai keadaan produk: akun yang
     * mendaftar lalu tidak pernah membuka sesi absensi tidak akan pernah
     * berlangganan. Karena itu pertumbuhan disandingkan dengan jumlah guru
     * yang benar-benar memakainya pekan ini.
     */
    private function pertumbuhan(): array
    {
        $mulai = now()->subWeeks(7)->startOfWeek();

        $mingguan = $this->guru()
            ->where('created_at', '>=', $mulai)
            ->get(['created_at'])
            ->groupBy(fn (User $u) => $u->created_at->startOfWeek()->format('Y-m-d'))
            ->map->count();

        // Delapan slot pekan yang utuh, termasuk pekan tanpa pendaftar — tanpa
        // ini grafiknya melompati pekan kosong dan tampak selalu naik.
        $deret = collect(range(7, 0))->map(function (int $mundur) use ($mingguan) {
            $pekan = now()->subWeeks($mundur)->startOfWeek();

            return [
                'label' => $pekan->translatedFormat('d M'),
                'jumlah' => (int) ($mingguan[$pekan->format('Y-m-d')] ?? 0),
            ];
        });

        return [
            'mingguan' => $deret,
            'puncak' => max(1, $deret->max('jumlah')),
            'guru_memakai_7h' => DB::table('attendance_sessions')
                ->where('created_at', '>=', now()->subDays(7))
                ->distinct()
                ->count('user_id'),
        ];
    }

    /** Ukuran platform: konteks bagi semua angka di atas. */
    private function skalaPlatform(): array
    {
        return [
            'kelas' => Classroom::where('is_active', true)->count(),
            'siswa' => Student::where('is_active', true)->count(),
            'sesi_30h' => DB::table('attendance_sessions')
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    /**
     * Pelanggan produk ini adalah wali kelas. Akun admin adalah operatornya
     * sendiri dan tidak boleh ikut terhitung sebagai pengguna berbayar.
     */
    private function guru(): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()->where('role', User::ROLE_TEACHER);
    }
}
