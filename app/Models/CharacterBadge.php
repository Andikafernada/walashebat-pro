<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharacterBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'character_dimension_id',
        'name',
        'icon',
        'color',
        'level',
        'description',
        'min_score',
        'min_occurrences',
        'criteria_type',
        'requirements',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_score' => 'integer',
            'min_occurrences' => 'integer',
            'requirements' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Badge levels.
     */
    const LEVEL_BRONZE = 'bronze';
    const LEVEL_SILVER = 'silver';
    const LEVEL_GOLD = 'gold';
    const LEVEL_PLATINUM = 'platinum';

    /**
     * Criteria types.
     */
    const CRITERIA_SCORE = 'score';
    const CRITERIA_COUNT = 'count';
    const CRITERIA_STREAK = 'streak';

    /**
     * Get the dimension.
     */
    public function dimension(): BelongsTo
    {
        return $this->belongsTo(CharacterDimension::class, 'character_dimension_id');
    }

    /**
     * Get student badges.
     */
    public function studentBadges(): HasMany
    {
        return $this->hasMany(StudentBadge::class);
    }

    /**
     * Get level color.
     */
    public function getLevelColor(): string
    {
        return match($this->level) {
            self::LEVEL_SILVER => '#94a3b8',
            self::LEVEL_GOLD => '#fbbf24',
            self::LEVEL_PLATINUM => '#8b5cf6',
            default => '#d97706', // bronze
        };
    }

    /**
     * Check if criteria is met.
     */
    public function checkCriteria(int $currentProgress): bool
    {
        if ($this->criteria_type === self::CRITERIA_SCORE) {
            return $currentProgress >= $this->min_score;
        }

        if ($this->criteria_type === self::CRITERIA_COUNT) {
            return $currentProgress >= $this->min_occurrences;
        }

        return false;
    }
}
