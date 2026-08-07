<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsersCommand extends Command
{
    protected $signature = 'user:list';
    protected $description = 'Menampilkan daftar semua user';

    public function handle(): int
    {
        $users = User::orderBy('role')->orderBy('name')->get(['id', 'name', 'email', 'role', 'is_active']);

        $this->table(
            ['ID', 'Nama', 'Email', 'Role', 'Status'],
            $users->map(fn ($u) => [
                $u->id,
                $u->name,
                $u->email,
                $u->role === User::ROLE_ADMIN ? '<fg=yellow>ADMIN</>' : 'teacher',
                $u->is_active ? '<fg=green>active</>' : '<fg=red>inactive</>',
            ])
        );

        $this->newLine();
        $this->info('Gunakan: php artisan user:make-admin <email> untuk jadi admin');

        return self::SUCCESS;
    }
}
