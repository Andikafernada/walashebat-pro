<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

/**
 * MODEL AKSES SAAT INI: SATU PERAN — WALI KELAS.
 *
 * Wali kelas (pemilik/owner kelas) melihat dan mengubah SEMUA data kelasnya.
 * Belum ada peran guru mapel / co-wali.
 *
 * Isolasi antar-tenant TIDAK bergantung pada policy ini: penegakan
 * sesungguhnya ada di TenantScope (WHERE user_id = Auth::id()) yang berjalan
 * di level query, sehingga kelas milik orang lain sudah 404 sejak route
 * model binding. Policy ini disimpan sebagai satu tempat terpusat untuk
 * diperluas nanti ketika peran guru mapel jadi ditambahkan.
 */
class ClassroomPolicy
{
    public function view(User $user, Classroom $classroom): bool
    {
        return $this->owns($user, $classroom);
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return $this->owns($user, $classroom);
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return $this->owns($user, $classroom);
    }

    private function owns(User $user, Classroom $classroom): bool
    {
        return $classroom->user_id === $user->id;
    }
}
