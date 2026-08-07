<?php

namespace App\Services;

use App\Models\CashBook;
use App\Models\Classroom;
use App\Models\Notification;
use App\Models\Student;
use App\Models\User;
use App\Models\Violation;

/**
 * Service untuk membuat dan mengirim notifikasi
 */
class NotificationService
{
    /**
     * Kirim notifikasi pelanggaran baru
     */
    public static function newViolation(Violation $violation): Notification
    {
        $student = $violation->student;
        $class = $violation->classroom;

        return Notification::createForUser(
            $class->user,
            'Pelanggaran Baru',
            "{$student->name} melakukan pelanggaran {$violation->type->name}" .
                ($violation->points > 0 ? " (-{$violation->points} poin)" : ''),
            'App\\Notifications\\NewViolation',
            route('classes.violations.index', ['class' => $class->id]),
            [
                'student_id' => $student->id,
                'violation_id' => $violation->id,
                'class_id' => $class->id,
                'points' => $violation->points,
            ],
            'alert',
            'rose'
        );
    }

    /**
     * Kirim notifikasi reminder absensi
     */
    public static function attendanceReminder(Classroom $class): Notification
    {
        return Notification::createForUser(
            $class->user,
            'Pengingat Absensi',
            "Segera isi absensi untuk kelas {$class->name}",
            'App\\Notifications\\AttendanceReminder',
            route('classes.attendance.index', ['class' => $class->id]),
            [
                'class_id' => $class->id,
            ],
            'bell',
            'indigo'
        );
    }

    /**
     * Kirim notifikasi kas kelas rendah
     */
    public static function lowCashbook(Classroom $class, float $balance): Notification
    {
        $threshold = 100000; // Ambang batas 100rb

        if ($balance >= $threshold) {
            return null; // Tidak perlu kirim notifikasi
        }

        return Notification::createForUser(
            $class->user,
            'Kas Kelas Rendah',
            "Saldo kas kelas {$class->name} tinggal Rp " . number_format($balance, 0, ',', '.'),
            'App\\Notifications\\LowCashbook',
            route('classes.cashbook.index', ['class' => $class->id]),
            [
                'class_id' => $class->id,
                'balance' => $balance,
            ],
            'alert',
            'amber'
        );
    }

    /**
     * Kirim notifikasi siswa alfa berulang
     */
    public static function repeatAlphaWarning(Student $student, int $alphaCount): Notification
    {
        $class = $student->classroom;

        return Notification::createForUser(
            $class->user,
            'Peringatan Alfa',
            "{$student->name} sudah alfa {$alphaCount}× dalam 30 hari terakhir",
            'App\\Notifications\\RepeatAlphaWarning',
            route('classes.students.index', ['class' => $class->id]),
            [
                'student_id' => $student->id,
                'class_id' => $class->id,
                'alpha_count' => $alphaCount,
            ],
            'alert',
            'rose'
        );
    }

    /**
     * Kirim notifikasi poin karakter rendah
     */
    public static function lowCharacterPoints(Student $student): Notification
    {
        $class = $student->classroom;

        return Notification::createForUser(
            $class->user,
            'Poin Karakter Rendah',
            "{$student->name} memiliki poin karakter {$student->discipline_points} (< 75)",
            'App\\Notifications\\LowCharacterPoints',
            route('classes.students.index', ['class' => $class->id]),
            [
                'student_id' => $student->id,
                'class_id' => $class->id,
                'points' => $student->discipline_points,
            ],
            'alert',
            'amber'
        );
    }

    /**
     * Broadcast notification to user's push subscribers
     */
    public static function broadcastPush(Notification $notification): void
    {
        $preference = $notification->user->notificationPreference;

        if (!$preference || !$preference->isPushEnabled()) {
            return;
        }

        // In production, you would use Laravel Web Push package here
        // For now, we just mark it as sent
        // Example with minishlink/web-push:
        // $webPush = new WebPush($vapidKeys);
        // $webPush->sendNotification(
        //     $preference->push_subscription,
        //     json_encode([
        //         'title' => $notification->title,
        //         'body' => $notification->body,
        //         'icon' => '/icon.png',
        //         'url' => $notification->action_url,
        //     ])
        // );
    }
}
