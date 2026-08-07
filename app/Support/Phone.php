<?php

namespace App\Support;

/** Normalisasi nomor Indonesia ke format internasional tanpa "+". */
class Phone
{
    public static function normalize(?string $number): ?string
    {
        if (blank($number)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number);

        if (blank($digits)) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }
}
