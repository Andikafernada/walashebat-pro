<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharacterDimension extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'name_en',
        'description',
        'indicators',
        'icon',
        'color',
        'positive_score',
        'negative_score',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'indicators' => 'array',
            'positive_score' => 'integer',
            'negative_score' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get records for this dimension.
     */
    public function records(): HasMany
    {
        return $this->hasMany(CharacterRecord::class);
    }

    /**
     * Get badges for this dimension.
     */
    public function badges(): HasMany
    {
        return $this->hasMany(CharacterBadge::class);
    }

    /**
     * Get reflections for this dimension.
     */
    public function reflections(): HasMany
    {
        return $this->hasMany(CharacterReflection::class);
    }

    /**
     * Get active dimensions ordered by sort_order.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Batasi pada dimensi milik seorang wali kelas.
     *
     * Model ini sengaja tidak memakai BelongsToTenant: dimensi dibaca juga dari
     * jalur tanpa login (formulir publik) dan dari guard 'student', dua konteks
     * yang tidak punya user login untuk disandarkan TenantScope. Pemiliknya
     * karena itu harus disebut tegas di setiap kueri — tanpa itu daftar dimensi
     * satu sekolah bocor ke sekolah lain.
     */
    public function scopeForOwner($query, ?int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get indicators as array.
     */
    public function getIndicatorsList(): array
    {
        return $this->indicators ?? [];
    }
}
