<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Support\PoinKerajinan;

/**
 * Setiap baris absensi tersimpan/terhapus, poin kerajinan siswanya dihitung
 * ulang. Semua penulisan absensi lewat Eloquent (updateOrCreate), jadi satu
 * observer ini menangkap seluruh jalur: magic link, input wali kelas, dsb.
 */
class AttendanceObserver
{
    public function saved(Attendance $attendance): void
    {
        if ($attendance->student_id) {
            PoinKerajinan::hitungUlang((int) $attendance->student_id);
        }
    }

    public function deleted(Attendance $attendance): void
    {
        if ($attendance->student_id) {
            PoinKerajinan::hitungUlang((int) $attendance->student_id);
        }
    }
}
