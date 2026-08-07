<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Violation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'class_id', 'student_id', 'violation_type_id',
        'points', 'note', 'occurred_on',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'points' => 'integer',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ViolationType::class, 'violation_type_id');
    }
}
