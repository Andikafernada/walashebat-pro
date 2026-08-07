<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

/**
 * Policy untuk Student.
 *
 * Isolasi tenant sebenarnya ditangani oleh TenantScope (WHERE user_id = Auth::id()).
 * Policy ini sebagai layer tambahan dan untuk persiapan multi-peran (guru mapel).
 */
class StudentPolicy
{
    /**
     * Siapa pun yang bisa melihat kelas bisa melihatsiswa di dalamnya.
     * TenantScope sudah menyaring, jadi ini hanya catatan.
     */
    public function viewAny(User $user): bool
    {
        return true; // TenantScope sudah memfilter
    }

    public function view(User $user, Student $student): bool
    {
        return $student->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true; // TenantScope sudah memfilter
    }

    public function update(User $user, Student $student): bool
    {
        return $student->user_id === $user->id;
    }

    public function delete(User $user, Student $student): bool
    {
        return $student->user_id === $user->id;
    }

    public function restore(User $user, Student $student): bool
    {
        return $student->user_id === $user->id;
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return $student->user_id === $user->id;
    }

    /**
     *导出Excel - hanya pemilik.
     */
    public function export(User $user, Student $student): bool
    {
        return $student->user_id === $user->id;
    }

    /**
     * Lihat profil PDF siswa.
     */
    public function viewPdf(User $user, Student $student): bool
    {
        return $student->user_id === $user->id;
    }
}
