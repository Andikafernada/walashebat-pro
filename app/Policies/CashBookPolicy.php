<?php

namespace App\Policies;

use App\Models\CashBook;
use App\Models\User;

/**
 * Policy untuk CashBook (buku kas).
 *
 * TenantScope sudah menyaring berdasarkan user_id.
 */
class CashBookPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CashBook $cashBook): bool
    {
        return $cashBook->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CashBook $cashBook): bool
    {
        return $cashBook->user_id === $user->id;
    }

    public function delete(User $user, CashBook $cashBook): bool
    {
        return $cashBook->user_id === $user->id;
    }
}
