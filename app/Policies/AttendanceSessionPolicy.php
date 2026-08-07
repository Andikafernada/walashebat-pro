<?php

namespace App\Policies;

use App\Models\AttendanceSession;
use App\Models\User;

/**
 * Policy untuk AttendanceSession.
 *
 * Kebijakan: wali kelas hanya bisa mengelola sesi milik kelasnya sendiri.
 * TenantScope sudah menyaring berdasarkan user_id.
 */
class AttendanceSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AttendanceSession $session): bool
    {
        return $session->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AttendanceSession $session): bool
    {
        return $session->user_id === $user->id;
    }

    /**
     * Kirim ulang magic link - hanya untuk sesi milik sendiri.
     */
    public function resend(User $user, AttendanceSession $session): bool
    {
        return $session->user_id === $user->id;
    }

    /**
     * Batalkan sesi - hanya jika masih terbuka.
     */
    public function cancel(User $user, AttendanceSession $session): bool
    {
        return $session->user_id === $user->id && $session->status === 'open';
    }

    /**
     * Koreksi absensi - sesi harus sudah submitted.
     */
    public function edit(User $user, AttendanceSession $session): bool
    {
        return $session->user_id === $user->id && $session->status === 'submitted';
    }

    public function delete(User $user, AttendanceSession $session): bool
    {
        // Hanya hapus sesi open, submitted tidak boleh dihapus
        return $session->user_id === $user->id && $session->status === 'open';
    }
}
