<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBadge extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'student_id',
        'character_badge_id',
        'current_progress',
        'is_earned',
        'earned_at',
        'notes',
        'granted_by',
    ];

    protected function casts(): array
    {
        return [
            'current_progress' => 'integer',
            'is_earned' => 'boolean',
            'earned_at' => 'datetime',
        ];
    }

    /**
     * Get the student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the badge.
     */
    public function badge(): BelongsTo
    {
        return $this->belongsTo(CharacterBadge::class, 'character_badge_id');
    }

    /**
     * Get the granter.
     */
    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Progress percentage.
     */
    public function getProgressPercentage(): int
    {
        if (!$this->badge || $this->badge->min_occurrences === 0) {
            return 0;
        }

        return min(100, (int) (($this->current_progress / $this->badge->min_occurrences) * 100));
    }
}
