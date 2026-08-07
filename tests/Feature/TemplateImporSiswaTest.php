<?php

namespace Tests\Feature;

use App\Exports\StudentsExport;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as FormatExcel;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Template impor harus mengikuti jenis kelasnya.
 *
 * Guru mapel hanya memegang NIS dan nama. Menyerahkan template 34 kolom —
 * lengkap dengan Nama Ayah, Pekerjaan Ibu, dan Penerima KIP — kepadanya bukan
 * sekadar merepotkan: bentuk berkas menentukan apa yang orang isi, sehingga
 * template itu mengundang pengumpulan data yang bukan haknya dan melahirkan
 * salinan kedua biodata yang menyimpang dari milik wali kelas aslinya.
 */
class TemplateImporSiswaTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create();
        $this->actingAs($this->guru);
    }

    private function kelas(string $jenis): Classroom
    {
        return Classroom::factory()->create([
            'user_id' => $this->guru->id,
            'jenis' => $jenis,
            'mapel' => $jenis === Classroom::JENIS_AJAR ? ['Informatika'] : [],
        ]);
    }

    /** @return array{0: string, 1: array<int, mixed>, 2: array<int, mixed>} */
    private function bacaTemplate(Classroom $kelas): array
    {
        $berkas = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        file_put_contents(
            $berkas,
            Excel::raw(new StudentsExport($kelas, templateSaja: true), FormatExcel::XLSX)
        );

        $lembar = IOFactory::load($berkas)->getActiveSheet();
        $kolomTerakhir = $lembar->getHighestColumn();

        $judul = $lembar->rangeToArray('A1:'.$kolomTerakhir.'1')[0];
        $contoh = $lembar->rangeToArray('A2:'.$kolomTerakhir.'2')[0];

        @unlink($berkas);

        return [$kolomTerakhir, $judul, $contoh];
    }

    // -- Bentuk template ----------------------------------------------------

    public function test_template_kelas_ajar_hanya_nis_dan_nama(): void
    {
        [$kolomTerakhir, $judul, $contoh] = $this->bacaTemplate($this->kelas(Classroom::JENIS_AJAR));

        $this->assertSame('B', $kolomTerakhir, 'kelas ajar hanya boleh dua kolom');
        $this->assertSame(['NIS', 'Nama'], $judul);

        // Baris contoh tetap ada: template kosong tanpa contoh membuat guru
        // menebak formatnya sendiri.
        $this->assertSame(['2024001', 'Ahmad Fauzi'], $contoh);
    }

    public function test_template_kelas_perwalian_tetap_lengkap(): void
    {
        [$kolomTerakhir, $judul] = $this->bacaTemplate($this->kelas(Classroom::JENIS_PERWALIAN));

        $this->assertSame('AH', $kolomTerakhir);
        $this->assertSame(['NIS', 'NISN', 'Nama', 'Jenis Kelamin'], array_slice($judul, 0, 4));
        $this->assertContains('Nama Ayah', $judul);
        $this->assertContains('Penerima KIP', $judul);
    }

    public function test_template_kelas_ajar_tidak_meminta_data_orang_tua(): void
    {
        [, $judul] = $this->bacaTemplate($this->kelas(Classroom::JENIS_AJAR));

        foreach (['Nama Ayah', 'Nama Ibu', 'HP Ortu', 'Alamat', 'Penerima KIP'] as $terlarang) {
            $this->assertNotContains($terlarang, $judul, "{$terlarang} bukan urusan guru mapel");
        }
    }

    // -- Bisa diunduh dari UI -----------------------------------------------

    public function test_template_bisa_diunduh(): void
    {
        $this->get(route('classes.students.template', $this->kelas(Classroom::JENIS_AJAR)))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=Template-Import-Siswa.xlsx');
    }

    public function test_halaman_impor_menjelaskan_isi_template_sesuai_jenis(): void
    {
        $this->get(route('classes.students.import.form', $this->kelas(Classroom::JENIS_AJAR)))
            ->assertOk()
            ->assertSee('Unduh template kosong')
            ->assertSee('guru mapel');

        $this->get(route('classes.students.import.form', $this->kelas(Classroom::JENIS_PERWALIAN)))
            ->assertOk()
            ->assertSee('seluruh kolom biodata');
    }

    // -- Ekspor kelas ajar ikut ringkas -------------------------------------

    /**
     * Ekspor dan template harus sebentuk. Kalau ekspor tetap 34 kolom, guru
     * mapel yang mengunduh data lalu mengunggahnya kembali justru mengisi
     * kolom biodata yang tadi sengaja tidak diberikan.
     */
    public function test_ekspor_kelas_ajar_juga_hanya_nis_dan_nama(): void
    {
        $kelas = $this->kelas(Classroom::JENIS_AJAR);

        Student::factory()->create([
            'user_id' => $this->guru->id,
            'class_id' => $kelas->id,
            // Sengaja berawalan nol: NIS sekolah kerap begitu, dan angka nol di
            // depan hilang tanpa suara bila kolomnya diperlakukan sebagai angka.
            'nis' => '0024099',
            'name' => 'Siti Aminah',
            'is_active' => true,
        ]);

        $berkas = tempnam(sys_get_temp_dir(), 'exp').'.xlsx';
        file_put_contents($berkas, Excel::raw(new StudentsExport($kelas), FormatExcel::XLSX));

        $lembar = IOFactory::load($berkas)->getActiveSheet();

        $this->assertSame('B', $lembar->getHighestColumn());
        $this->assertSame('0024099', (string) $lembar->getCell('A2')->getValue());
        $this->assertSame('Siti Aminah', $lembar->getCell('B2')->getValue());

        @unlink($berkas);
    }
}
