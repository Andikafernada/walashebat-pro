<?php

namespace App\Console\Commands;

use App\Support\PoinKerajinan;
use Illuminate\Console\Command;

/**
 * Menyetel ulang poin kerajinan seluruh siswa dari data absensi yang ada.
 * Dijalankan sekali saat retroaktif, atau setiap aturan PoinKerajinan::NILAI
 * berubah.
 */
class HitungUlangPoinKerajinan extends Command
{
    protected $signature = 'poin:hitung-ulang';

    protected $description = 'Hitung ulang poin kerajinan semua siswa dari absensi';

    public function handle(): int
    {
        $jumlah = PoinKerajinan::hitungUlangSemua();

        $this->info("Poin kerajinan {$jumlah} siswa dihitung ulang.");

        return self::SUCCESS;
    }
}
