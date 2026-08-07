<?php

namespace App\Console\Commands;

use App\Models\AttendanceSession;
use Illuminate\Console\Command;

/**
 * Menandai sesi absensi yang sudah lewat waktu menjadi 'expired'.
 * Tanpa ini, daftar sesi menampilkan status 'open' untuk sesi kemarin.
 */
class ExpireAttendanceSessions extends Command
{
    protected $signature = 'walikelas:expire-attendance';

    protected $description = 'Tandai sesi absensi yang kedaluwarsa';

    public function handle(): int
    {
        $count = AttendanceSession::withoutTenant()
            ->where('status', 'open')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        if ($count > 0) {
            $this->info("{$count} sesi ditandai expired.");
        }

        return self::SUCCESS;
    }
}
