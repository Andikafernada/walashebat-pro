<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Console\Command;

class PurgeJunkAccounts extends Command
{
    protected $signature = 'accounts:purge-junk {--dry-run : Only list junk accounts without deleting}';
    protected $description = 'Identifikasi dan bersihkan akun dummy/contoh dan email asal-asalan yang tidak memiliki siswa aktif';

    public function handle(): int
    {
        $this->info('🔍 Memeriksa akun guru dan mendeteksi data sampah/dummy...');
        $dryRun = $this->option('dry-run');

        $junkUsers = User::where('role', '!=', 'admin')
            ->where(function ($q) {
                $q->where('email', 'like', '%@example.com')
                    ->orWhere('email', 'like', '%@example.org')
                    ->orWhere('email', 'like', '%@example.net')
                    ->orWhere('email', 'like', '%@test.com')
                    ->orWhere('email', 'like', '%@dummy.com')
                    ->orWhere('email', 'like', '%@mailinator.com')
                    ->orWhere('email', 'like', '%@tempmail%')
                    ->orWhereNull('whatsapp_number')
                    ->orWhere('whatsapp_number', '');
            })
            ->get();

        if ($junkUsers->isEmpty()) {
            $this->info('✅ Database bersih! Tidak ada akun sampah yang terdeteksi.');
            return 0;
        }

        $this->warn("Ditemukan {$junkUsers->count()} akun terindikasi data dummy/asal-asalan:");
        $purgedCount = 0;

        foreach ($junkUsers as $user) {
            $classCount = Classroom::withoutGlobalScopes()->where('user_id', $user->id)->count();
            $studentCount = Student::withoutGlobalScopes()->where('user_id', $user->id)->count();

            // Hanya hapus jika akun benar-benar dummy / memiliki 0 siswa riil
            if ($studentCount === 0) {
                $this->line(" - [ID: {$user->id}] {$user->name} ({$user->email}) | Kelas: {$classCount} | Siswa: {$studentCount}");

                if (! $dryRun) {
                    // Hapus data terkait sebelum menghapus user
                    Classroom::withoutGlobalScopes()->where('user_id', $user->id)->delete();
                    $user->delete();
                    $purgedCount++;
                }
            } else {
                $this->comment(" - [SKIP] [ID: {$user->id}] {$user->name} ({$user->email}) memiliki {$studentCount} siswa aktif.");
            }
        }

        if ($dryRun) {
            $this->info("ℹ️ Mode simulasi (--dry-run). {$junkUsers->count()} akun siap dibersihkan.");
        } else {
            $this->info("✨ Berhasil membersihkan {$purgedCount} akun sampah dari database.");
        }

        return 0;
    }
}
