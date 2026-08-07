<?php

namespace Tests\Feature;

use App\Imports\StudentsImport;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Halaman impor menganjurkan alur ekspor - sunting - unggah ulang. Pencocokan
 * nama yang lama membandingkan LOWER(name) apa adanya, sehingga ejaan yang
 * bergeser sedikit saja — spasi ganda, titik sebagai pemisah — dianggap orang
 * baru dan melahirkan siswa kembar setiap kali berkasnya diunggah lagi.
 */
class ImporSiswaCocokNamaTest extends TestCase
{
    use RefreshDatabase;

    private Classroom $class;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->class = Classroom::factory()->create(['user_id' => $user->id]);
    }

    private function impor(array $baris): StudentsImport
    {
        $import = new StudentsImport($this->class);
        $import->collection(new Collection([new Collection($baris)]));

        return $import;
    }

    private function siswaLama(array $ubah = []): Student
    {
        return Student::factory()->create($ubah + [
            'user_id' => $this->class->user_id,
            'class_id' => $this->class->id,
            'name' => 'Salsa Virlyana',
            'nis' => null,
            'hobi' => 'Membaca',
        ]);
    }

    /** @return array<int, array<string, string>> */
    public static function variasiNama(): array
    {
        return [
            'spasi ganda' => ['Salsa  Virlyana'],
            'huruf besar' => ['SALSA VIRLYANA'],
            'titik pemisah' => ['Salsa.Virlyana'],
            'spasi menggantung' => ['  Salsa Virlyana  '],
        ];
    }

    /**
     * @dataProvider variasiNama
     */
    public function test_variasi_ejaan_tidak_melahirkan_siswa_kembar(string $ejaan): void
    {
        $this->siswaLama();

        $this->impor(['nama_lengkap' => $ejaan, 'hobi' => 'Memancing']);

        $this->assertSame(
            1,
            Student::withoutTenant()->where('class_id', $this->class->id)->count(),
            "Ejaan \"{$ejaan}\" seharusnya dikenali sebagai siswa yang sama"
        );
        $this->assertSame('Memancing', Student::withoutTenant()->sole()->hobi);
    }

    public function test_siswa_yang_benar_benar_baru_tetap_dibuat(): void
    {
        $this->siswaLama();

        $this->impor(['nama_lengkap' => 'Rendy Moerdany']);

        $this->assertSame(2, Student::withoutTenant()->where('class_id', $this->class->id)->count());
    }

    /** NIS tetap menjadi penanda utama, mendahului nama. */
    public function test_nis_didahulukan_daripada_nama(): void
    {
        $this->siswaLama(['nis' => '242510234']);

        $this->impor(['nis' => '242510234', 'nama_lengkap' => 'Nama Yang Berbeda Sekali']);

        $this->assertSame(1, Student::withoutTenant()->where('class_id', $this->class->id)->count());
    }

    /** Nama kembar di satu kelas tidak boleh ditebak. */
    public function test_nama_kembar_dilaporkan_bukan_ditebak(): void
    {
        $this->siswaLama();
        $this->siswaLama(['hobi' => 'Melukis']);

        $import = $this->impor(['nama_lengkap' => 'Salsa Virlyana', 'hobi' => 'Memancing']);

        $this->assertSame(2, Student::withoutTenant()->where('class_id', $this->class->id)->count(),
            'Tidak boleh membuat siswa ketiga');

        $ringkasan = $import->ringkasan();
        $pesan = collect($ringkasan['catatan'] ?? [])->pluck('pesan')->implode(' ');

        $this->assertStringContainsString('2 siswa bernama', $pesan);
        $this->assertStringContainsString('NIS', $pesan);
    }
}
