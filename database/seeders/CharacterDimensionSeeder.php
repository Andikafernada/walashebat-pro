<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\CharacterDimensionProvisioner;
use Illuminate\Database\Seeder;

/**
 * Dimensi karakter dimiliki per wali kelas, bukan satu daftar bersama.
 *
 * Versi lama menyisipkan enam baris tanpa user_id sama sekali. Seluruh
 * kueri menyaring berdasarkan pemilik, jadi baris-baris itu tidak pernah
 * terlihat oleh siapa pun dan halaman Jurnal & Portofolio Karakter tampil
 * kosong.
 */
class CharacterDimensionSeeder extends Seeder
{
    public function run(): void
    {
        $provisioner = new CharacterDimensionProvisioner;

        User::query()->each(function (User $user) use ($provisioner) {
            $provisioner->provisionFor($user->id);
        });
    }
}
