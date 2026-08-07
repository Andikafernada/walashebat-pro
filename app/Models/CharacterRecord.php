<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterRecord extends Model
{
    use BelongsToTenant, HasFactory;

    // Type constants
    const TYPE_POSITIVE = 'positive';
    const TYPE_NEGATIVE = 'negative';
    const TYPE_OBSERVATION = 'observation';
    const TYPE_ACHIEVEMENT = 'achievement';

    const TYPES = [
        self::TYPE_POSITIVE,
        self::TYPE_NEGATIVE,
        self::TYPE_OBSERVATION,
        self::TYPE_ACHIEVEMENT,
    ];

    protected $fillable = [
        'user_id',
        'student_id',
        'class_id',
        'character_dimension_id',
        'type',
        'score',
        'title',
        'description',
        'evidence',
        'context',
        'record_date',
        'recorded_by',
        'is_acknowledged',
        'acknowledged_at',
        'acknowledged_by',
        'notify_parent',
        'is_sent',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'record_date' => 'date',
            'is_acknowledged' => 'boolean',
            'acknowledged_at' => 'datetime',
            'notify_parent' => 'boolean',
            'is_sent' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Contexts.
     */
    const CONTEXT_IN_CLASS = 'in_class';
    const CONTEXT_EXTRACURRICULAR = 'extracurricular';
    const CONTEXT_BREAK_TIME = 'break_time';
    const CONTEXT_EXAM = 'exam';
    const CONTEXT_ACTIVITY = 'activity';

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
     * Get the recorder.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get the acknowledger.
     */
    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Scope for students.
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('record_date', [$startDate, $endDate]);
    }

    /**
     * Scope for type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get type badge color.
     */
    public function getTypeBadgeClass(): string
    {
        return match($this->type) {
            self::TYPE_POSITIVE => 'badge--success',
            self::TYPE_NEGATIVE => 'badge--danger',
            self::TYPE_ACHIEVEMENT => 'badge--warning',
            default => 'badge--slate',
        };
    }

    /**
     * Get score display with sign.
     */
    public function getScoreDisplay(): string
    {
        return $this->score > 0 ? '+' . $this->score : (string) $this->score;
    }
}
