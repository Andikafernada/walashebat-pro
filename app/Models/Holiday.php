<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Hari libur. TIDAK memakai BelongsToTenant karena baris dengan user_id NULL
 * adalah libur nasional yang berlaku lintas tenant; penyaringan dilakukan
 * eksplisit lewat scope coveringFor().
 */
class Holiday extends Model
{
    protected $fillable = ['user_id', 'start_date', 'end_date', 'description'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    /** Libur yang mencakup tanggal tertentu untuk sebuah tenant (atau nasional). */
    public function scopeCoveringFor(Builder $q, Carbon $date, ?int $userId = null): Builder
    {
        return $q->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->where(function ($w) use ($userId) {
                $w->whereNull('user_id');
                if ($userId) {
                    $w->orWhere('user_id', $userId);
                }
            });
    }
}
