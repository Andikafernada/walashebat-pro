<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'class_id', 'day_of_week', 'subject',
        'teacher_name', 'start_time', 'end_time',
    ];

    public const DAYS = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
        5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function dayName(): string
    {
        return self::DAYS[$this->day_of_week] ?? '-';
    }
}
