<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ekspor portofolio karakter menerima {class} dan {student} sebagai dua
 * binding INDEPENDEN — aturan exists polos pada route-model-binding tidak
 * memeriksa keduanya benar-benar berpasangan. Tanpa penjagaan, guru dengan
 * 2+ kelas bisa menukar id siswa di URL dan mendapat dokumen resmi berkop
 * kelas A berisi data karakter siswa kelas lainnya.
 */
class EksporKarakterLintasKelasTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private Classroom $kelasA;

    private Classroom $kelasB;

    private Student $siswaKelasB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create();
        $this->kelasA = Classroom::factory()->create(['user_id' => $this->guru->id, 'name' => 'Kelas A']);
        $this->kelasB = Classroom::factory()->create(['user_id' => $this->guru->id, 'name' => 'Kelas B']);
        $this->siswaKelasB = Student::factory()->create(['user_id' => $this->guru->id, 'class_id' => $this->kelasB->id]);

        $this->actingAs($this->guru);
    }

    public function test_excel_menolak_siswa_kelas_lain(): void
    {
        $this->get(route('classes.exports.character.excel', [$this->kelasA, $this->siswaKelasB]))
            ->assertNotFound();
    }

    public function test_pdf_menolak_siswa_kelas_lain(): void
    {
        $this->get(route('classes.exports.character.pdf', [$this->kelasA, $this->siswaKelasB]))
            ->assertNotFound();
    }

    public function test_excel_berhasil_untuk_siswa_kelasnya_sendiri(): void
    {
        $this->get(route('classes.exports.character.excel', [$this->kelasB, $this->siswaKelasB]))
            ->assertOk();
    }

    public function test_pdf_berhasil_untuk_siswa_kelasnya_sendiri(): void
    {
        $this->get(route('classes.exports.character.pdf', [$this->kelasB, $this->siswaKelasB]))
            ->assertOk();
    }
}
