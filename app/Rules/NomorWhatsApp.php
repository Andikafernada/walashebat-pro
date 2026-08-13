<?php

namespace App\Rules;

use App\Support\Phone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Nomor harus bisa dinormalkan menjadi nomor seluler Indonesia.
 *
 * Dipasang di formulir, bukan hanya di mutator model. Mutator cuma bisa
 * mengembalikan null: nomor salah ketik lenyap tanpa sepatah kata, halaman
 * tetap berkata "berhasil disimpan", dan baru ketahuan berminggu-minggu
 * kemudian ketika pesan ke orang tua tidak pernah sampai. Ditolak di formulir,
 * yang mengetiknya masih ada di depan layar dan bisa langsung membetulkan.
 *
 * Kosong sengaja dibiarkan lolos — wajib atau tidaknya sebuah kolom adalah
 * urusan aturan 'required'/'nullable' di sebelahnya.
 */
class NomorWhatsApp implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        if (! Phone::isValid((string) $value)) {
            $fail('Nomor :attribute tidak dikenali sebagai nomor seluler Indonesia. Contoh yang benar: 081234567890 atau 6281234567890.');
        }
    }
}
