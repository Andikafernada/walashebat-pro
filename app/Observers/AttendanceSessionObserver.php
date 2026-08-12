<?php

namespace App\Observers;

use App\Models\AttendanceSession;
use App\Support\PoinKerajinan;
use Illuminate\Support\Facades\DB;

/**
 * Membatalkan sesi hanya mengubah attendance_sessions.status, bukan baris
 * absensinya — jadi AttendanceObserver tidak terpicu. Poin kerajinan
 * mengecualikan sesi 'cancelled', maka setiap status sesi berubah, seluruh
 * siswa di sesi itu dihitung ulang.
 */
class AttendanceSessionObserver
{
    public function updated(AttendanceSession $session): void
    {
        if (! $session->wasChanged('status')) {
            return;
        }

        /*
         * DB langsung, bukan $session->attendances(): observer bisa berjalan
         * di luar sesi web (perintah artisan, job antrian) dan TenantScope
         * gagal-tertutup di sana — relasinya akan mengembalikan nol baris,
         * poin siswa tertinggal diam-diam. Sama alasannya dengan PoinKerajinan.
         */
        DB::table('attendances')
            ->where('attendance_session_id', $session->id)
            ->pluck('student_id')
            ->filter()
            ->each(fn ($id) => PoinKerajinan::hitungUlang((int) $id));
    }
}
