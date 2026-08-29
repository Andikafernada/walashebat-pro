<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingJournal extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'user_id',
        'class_id',
        'session_date',
        'meeting_number',
        'subject',
        'topic',
        'learning_objective',
        'activity',
        'reflection',
        'p5_dimension',
        'attendance_summary',
    ];

    protected $casts = [
        'session_date' => 'date',
        'meeting_number' => 'integer',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
