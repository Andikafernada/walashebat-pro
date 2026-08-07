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
        try {
            if (self::$lintasTenant) {
                return;
            }

            if (! app()->bound('request')) {
                return;
            }

            if (! Auth::check()) {
                return;
            }

            $userId = Auth::id();
            if ($userId) {
                $builder->where(
                    $model->qualifyColumn($model->getTenantColumn()),
                    $userId
                );
            }
        } catch (\Throwable $e) {
            // Fail safe: if Auth/Request is not fully initialized, do not crash the query
            return;
        }
    }
}