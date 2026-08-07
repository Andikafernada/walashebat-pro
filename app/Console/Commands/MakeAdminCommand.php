<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    protected $signature = 'user:make-admin {email : Email user yang akan jadi admin}';
    protected $description = 'Mengubah user menjadi admin (kepala sekolah)';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User dengan email '{$email}' tidak ditemukan.");
            return self::FAILURE;
        }

        $user->role = User::ROLE_ADMIN;
        $user->save();

        $this->info("✓ {$user->name} ({$user->email}) sekarang adalah ADMIN (Kepala Sekolah).");
        $this->info("  Bisa akses: /admin/dashboard");

        return self::SUCCESS;
    }
}
