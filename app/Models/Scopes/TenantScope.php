<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope multi-tenancy.
 *
 * Membatasi seluruh query pada baris milik user (wali kelas) yang sedang login.
 * Diterapkan otomatis oleh trait BelongsToTenant. Untuk konteks tanpa auth
 * (mis. magic link publik, job antrian, seeder) gunakan Model::withoutTenant().
 */
class TenantScope implements Scope
{
    /** Aktif hanya selama closure lintasSeluruhTenant() berjalan. */
    private static bool $lintasTenant = false;

    /**
     * Tenant yang ditetapkan oleh token publik, untuk sisa request ini.
     *
     * Halaman biodata dan refleksi dibuka tanpa login, tetapi tenantnya TIDAK
     * benar-benar tidak diketahui: pemegang public_token sudah membuktikan
     * haknya atas satu kelas, dan pemiliknya adalah classes.user_id. Ditetapkan
     * sekali di Classroom::resolveRouteBinding(), lalu seluruh query turunannya
     * ($class->students(), CharacterDimension milik guru itu) tersaring sendiri
     * — tanpa withoutTenant() bertaburan di controller, yang tiap kali terlewat
     * berarti lubang baru.
     *
     * Disimpan di container, BUKAN properti statik: statik hidup sepanjang
     * proses, sedangkan ini hanya sah untuk satu request. Pekerja antrian dan
     * Octane melayani banyak request dalam satu proses — tenant milik request
     * sebelumnya akan terbawa ke request berikutnya. (Gejalanya muncul lebih
     * dulu di test: lulus sendirian, gagal dalam suite.) Container dibangun
     * ulang tiap request dan tiap test, jadi pembersihannya gratis.
     */
    private const KUNCI_PUBLIK = 'tenant.publik';

    /** Penjaga rekursi: pencarian siswa untuk MENENTUKAN tenant. */
    private static bool $menentukan = false;

    /** Dipanggil setelah public_token terbukti cocok dengan satu kelas. */
    public static function pakaiTenant(int $userId): void
    {
        app()->instance(self::KUNCI_PUBLIK, $userId);
    }

    /**
     * Jalankan $tugas tanpa batasan tenant — untuk halaman admin sekolah.
     *
     * withoutTenant() saja tidak cukup di sini. Ia hanya melepas scope pada
     * query terluar, sedangkan halaman admin juga menyentuh relasi
     * (with, withCount, whereHas) yang masing-masing membawa TenantScope
     * sendiri. Melepasnya satu per satu berarti belasan titik yang harus
     * diingat, dan yang terlewat gagal secara SENYAP: bukan galat, melainkan
     * angka nol yang terlihat seperti "sekolah belum punya data".
     *
     * Batasnya sengaja sempit: hanya selama closure, selalu dikembalikan lewat
     * finally, dan ditolak bila yang login bukan admin.
     */
    public static function lintasSeluruhTenant(callable $tugas): mixed
    {
        if (Auth::check() && ! Auth::user()->isAdmin()) {
            throw new \RuntimeException('Lintas tenant hanya untuk admin.');
        }

        return self::tanpaBatasTenant($tugas);
    }

    /**
     * Sama, untuk kode SISTEM: perintah terjadwal dan pekerja antrian.
     *
     * Dipisahkan dari lintasSeluruhTenant() karena penjagaan "harus admin" di
     * sana melindungi hal lain — seorang manusia yang melihat data seluruh
     * sekolah. Cron tidak punya manusia, dan kebetulan ada sesi guru yang
     * masih hidup di proses yang sama tidak boleh menggagalkannya.
     *
     * Yang membatasi di sini bukan peran pengguna melainkan pemanggilnya:
     * hanya kode tepercaya kita sendiri, tidak pernah dari request pengguna.
     */
    public static function sebagaiSistem(callable $tugas): mixed
    {
        return self::tanpaBatasTenant($tugas);
    }

    private static function tanpaBatasTenant(callable $tugas): mixed
    {
        $sebelumnya = self::$lintasTenant;
        self::$lintasTenant = true;

        try {
            return $tugas();
        } finally {
            self::$lintasTenant = $sebelumnya;
        }
    }

    public function apply(Builder $builder, Model $model): void
    {
        if (self::$lintasTenant || self::$menentukan) {
            return;
        }

        /*
         * Konteks sistem: perintah terjadwal dan pekerja antrian memang
         * berjalan tanpa siapa pun yang login, dan tugasnya melintasi tenant.
         * Menutupnya di sini tidak membuat apa pun lebih aman — kodenya
         * tepercaya — tetapi membuat cron DIAM-DIAM tidak melakukan apa-apa,
         * kegagalan yang tidak menimbulkan galat dan tidak terlihat sampai
         * ada yang mengeluh absensinya tidak terbit.
         *
         * Test dikecualikan: PHPUnit juga berjalan di konsol, dan tanpa
         * pengecualian ini seluruh test isolasi tenant lulus tanpa benar-benar
         * menguji apa pun.
         */
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $userId = self::tenantId();

        /*
         * Gagal TERTUTUP.
         *
         * Versi lama melepas filter begitu tidak ada yang login, dan menelan
         * setiap exception dengan hasil yang sama. Akibatnya seluruh tenant
         * terlihat oleh siapa pun yang sampai ke query tanpa sesi web —
         * termasuk siswa yang login di guard 'student', karena Auth::check()
         * hanya memeriksa guard bawaan. Yang menyelamatkan selama ini hanyalah
         * setiap controller kebetulan menyaring sendiri; satu yang lupa sudah
         * cukup, dan gagalnya senyap.
         */
        if ($userId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn($model->getTenantColumn()), $userId);
    }

    /**
     * Siapa tenant untuk request ini.
     *
     * Guard 'student' diperiksa terpisah karena Auth::check() hanya menyentuh
     * guard bawaan ('web'): siswa yang sudah login tetap terbaca sebagai tamu,
     * dan dulu itu berarti seluruh tenant terbuka baginya.
     */
    private static function tenantId(): ?int
    {
        if (app()->bound(self::KUNCI_PUBLIK)) {
            return app(self::KUNCI_PUBLIK);
        }

        // web dan sanctum: middleware auth memanggil shouldUse(), jadi guard
        // bawaan sudah menunjuk guard yang benar saat query berjalan.
        if (Auth::check()) {
            return Auth::id();
        }

        /*
         * Memuat siswa dari sesi menjalankan query Student, yang membawa scope
         * ini juga — tanpa penjaga, apply() dan tenantId() saling memanggil
         * sampai kehabisan stack. Selama penjaga menyala, scope dilewati;
         * query itu dikunci primary key dari sesi bertanda tangan, jadi tidak
         * bisa dipakai memancing baris tenant lain.
         */
        self::$menentukan = true;

        try {
            return Auth::guard('student')->user()?->user_id;
        } finally {
            self::$menentukan = false;
        }
    }
}