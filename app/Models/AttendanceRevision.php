<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris jejak koreksi absensi.
 *
 * Sengaja TIDAK memakai BelongsToTenant. Jejak audit tidak boleh bisa
 * disembunyikan oleh scope; penyaringan kepemilikan dilakukan lewat relasi
 * absensi induknya, yang sudah ter-scope.
 */
class AttendanceRevision extends Model
{
    protected $fillable = ['attendance_id', 'user_id', 'from_status', 'to_status', 'reason'];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
