<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterReflection extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'character_dimension_id',
        'period',
        'reflection_date',
        'self_rating',
        'what_went_well',
        'what_to_improve',
        'action_plan',
        'pesan_ortu',
        'kesan_teman',
        'teacher_feedback',
        'teacher_rating',
        'feedback_by',
        'feedback_at',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'reflection_date' => 'date',
            'self_rating' => 'integer',
            'teacher_rating' => 'integer',
            'feedback_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Reflection periods.
     */
    const PERIOD_DAILY = 'daily';
    const PERIOD_WEEKLY = 'weekly';
    const PERIOD_MONTHLY = 'monthly';

    /**
     * Status values.
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_REVIEWED = 'reviewed';

    /**
     * Get the student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the classroom.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    /**
     * Get the dimension.
     */
    public function dimension(): BelongsTo
    {
        return $this->belongsTo(CharacterDimension::class, 'character_dimension_id');
    }

    /**
     * Get the feedback giver.
     */
    public function feedbackGiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'feedback_by');
    }

    /**
     * Get rating stars display.
     */
    public function getSelfRatingStars(): string
    {
        return str_repeat('⭐', $this->self_rating ?? 0);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'badge--slate',
            self::STATUS_SUBMITTED => 'badge--info',
            self::STATUS_REVIEWED => 'badge--success',
            default => 'badge--slate',
        };
    }
}
