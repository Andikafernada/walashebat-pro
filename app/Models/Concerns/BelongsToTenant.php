<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Trait tenancy.
 *
 * - Menambahkan TenantScope (filter user_id = user login) ke semua query.
 * - Mengisi user_id secara otomatis saat pembuatan record.
 * - Menyediakan helper withoutTenant() untuk melewati scope dengan aman.
 *
 * Prasyarat: tabel model memiliki kolom `user_id`.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            $column = $model->getTenantColumn();

            if (empty($model->{$column}) && Auth::hasUser()) {
                $model->{$column} = Auth::id();
            }
        });
    }

    /** Nama kolom pemilik/tenant. Override bila berbeda. */
    public function getTenantColumn(): string
    {
        return 'user_id';
    }

    /** Query builder tanpa batasan tenant (gunakan hati-hati). */
    public static function withoutTenant(): Builder
    {
        return static::withoutGlobalScope(TenantScope::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, $this->getTenantColumn());
    }
}
