<?php

namespace App\Support;

/**
 * Normalisasi nomor seluler Indonesia ke format internasional tanpa "+".
 *
 * Menormalkan SEKALIGUS memvalidasi, dan itu disengaja. Versi sebelumnya hanya
 * membuang karakter bukan-digit lalu menempelkan awalan, sehingga '0' menjadi
 * '62', '08' menjadi '628', dan '<script>0812345</script>' menjadi '62812345'.
 * Bentuk-bentuk itu lolos ke gateway seperti nomor sungguhan: pesannya tercatat
 * "terkirim" tanpa galat, tetapi tidak pernah sampai ke siapa pun — wali kelas
 * baru menyadarinya keesokan hari ketika absensi ternyata kosong.
 *
 * Karena satu-satunya nilai kembalian yang sah kini adalah nomor yang benar,
 * null berarti "tidak mungkin jadi nomor Indonesia". Pemanggil WAJIB memutuskan
 * apa yang terjadi pada null; membiarkannya jatuh menjadi string kosong hanya
 * memindahkan kegagalan senyap ke tempat lain.
 */
class Phone
{
    /**
     * Nomor seluler Indonesia yang sudah berformat 62.
     *
     * Susunannya: 62 + 8 + kode operator + nomor pelanggan. Digit sesudah 8
     * tidak pernah 0 — seluruh awalan operator Indonesia berada di rentang
     * 811–899 — sehingga '6280…' pasti salah ketik, bukan nomor pendek.
     *
     * Panjangnya 11–14 digit. Itu adalah padanan langsung dari format nasional
     * 10–13 digit termasuk angka 0 di depan: 0812-3456-78 yang terpendek sampai
     * 0812-3456-7890-1 yang terpanjang. Di bawah 11 digit nomornya terpotong,
     * di atas 14 digit ada digit tambahan yang ikut tersalin — dua-duanya
     * mustahil dihubungi, jadi lebih baik ditolak di depan daripada
     * "berhasil dikirim" ke ruang hampa.
     */
    private const POLA = '/^628[1-9][0-9]{7,10}$/';

    /**
     * @return string|null Nomor 62… yang sah, atau null bila masukannya
     *                     mustahil menjadi nomor seluler Indonesia.
     */
    public static function normalize(?string $number): ?string
    {
        if (blank($number)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number);

        if (blank($digits)) {
            return null;
        }

        // Awalan panggilan internasional yang ikut tersalin dari kontak
        // ponsel: 0062812… adalah nomor yang sama dengan +62812….
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return preg_match(self::POLA, $digits) === 1 ? $digits : null;
    }

    /** Bisakah masukan ini dipakai mengirim WhatsApp? */
    public static function isValid(?string $number): bool
    {
        return self::normalize($number) !== null;
    }
}
