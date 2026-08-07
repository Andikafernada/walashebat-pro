<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Violation;

/**
 * Policy untuk Violation (pelanggaran/poin siswa).
 *
 * TenantScope sudah menyaring berdasarkan user_id.
 */
class ViolationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Violation $violation): bool
    {
        return $violation->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Violation $violation): bool
    {
        return $violation->user_id === $user->id;
    }

    public function delete(User $user, Violation $violation): bool
    {
        return $violation->user_id === $user->id;
    }
}
