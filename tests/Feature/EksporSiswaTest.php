<?php

namespace Tests\Feature;

use App\Exports\StudentsExport;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class EksporSiswaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->user = User::factory()->create();
        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);
    }

    public function test_ekspor_berisi_siswa_kelas_ini(): void
    {
        Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id,
            'name' => 'Fitriani Salsabila', 'nis' => '2025006',
        ]);

        $baris = (new StudentsExport($this->class))->query()->get();

        $this->assertCount(1, $baris);
        $this->assertSame('Fitriani Salsabila', $baris->first()->name);
    }

    /** Ekspor dialirkan per potongan, bukan dimuat sekaligus ke memori. */
    public function test_ekspor_memakai_query_berpotongan(): void
    {
        $export = new StudentsExport($this->class);

        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\FromQuery::class, $export);
        $this->assertInstanceOf(\Maatwebsite\Excel\Concerns\WithChunkReading::class, $export);
        $this->assertSame(200, $export->chunkSize());
    }

    /** Mode template tidak boleh membocorkan siswa asli. */
    public function test_template_tidak_memuat_siswa_asli(): void
    {
        Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id,
            'name' => 'Siswa Asli',
        ]);

        $baris = (new StudentsExport($this->class, templateSaja: true))->query()->get();

        $this->assertCount(0, $baris, 'Template harus kosong dari data nyata');
    }

    public function test_unduhan_ekspor_berhasil(): void
    {
        Excel::fake();

        Student::factory()->count(3)->create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id,
        ]);

        $this->get(route('classes.students.export', $this->class))->assertOk();

        Excel::assertDownloaded('Data-Siswa-'.\Illuminate\Support\Str::slug($this->class->name).'-'.now()->format('Y-m-d').'.xlsx');
    }

    public function test_tidak_bisa_ekspor_kelas_orang_lain(): void
    {
        $lain = Classroom::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->get(route('classes.students.export', $lain))->assertNotFound();
    }
}
