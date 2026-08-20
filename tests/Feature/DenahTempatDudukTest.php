<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Seat;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DenahTempatDudukTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private Classroom $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->guru = User::factory()->create();
        $this->kelas = Classroom::factory()->create(['user_id' => $this->guru->id]);
        $this->actingAs($this->guru);
    }

    public function test_menyimpan_denah_menimpa_yang_lama(): void
    {
        $siswa = Student::factory()->create(['user_id' => $this->guru->id, 'class_id' => $this->kelas->id]);

        $this->post(route('classes.seating.save', $this->kelas), [
            'seats' => [
                ['row_index' => 0, 'col_index' => 0, 'student_id' => $siswa->id, 'label' => null],
                ['row_index' => 0, 'col_index' => 1, 'student_id' => null, 'label' => 'Meja Guru'],
            ],
        ])->assertOk()->assertJson(['ok' => true, 'count' => 2]);

        $this->assertSame(2, Seat::withoutTenant()->where('class_id', $this->kelas->id)->count());

        // Simpan lagi dengan isi lebih sedikit -- yang lama harus tertimpa, bukan menumpuk.
        $this->post(route('classes.seating.save', $this->kelas), [
            'seats' => [
                ['row_index' => 0, 'col_index' => 0, 'student_id' => $siswa->id, 'label' => null],
            ],
        ])->assertOk()->assertJson(['ok' => true, 'count' => 1]);

        $this->assertSame(1, Seat::withoutTenant()->where('class_id', $this->kelas->id)->count());
    }

    /**
     * 'seats' divalidasi 'sometimes': kunci ini boleh sama sekali tidak ada
     * di request. Tanpa penjagaan, $data['seats'] tidak terdefinisi dan
     * foreach()-nya fatal -- request kosong tetap harus berhasil mengosongkan
     * denah, bukan error 500.
     */
    public function test_kunci_seats_yang_hilang_sama_sekali_mengosongkan_denah_bukan_error(): void
    {
        $siswa = Student::factory()->create(['user_id' => $this->guru->id, 'class_id' => $this->kelas->id]);
        $this->post(route('classes.seating.save', $this->kelas), [
            'seats' => [['row_index' => 0, 'col_index' => 0, 'student_id' => $siswa->id, 'label' => null]],
        ])->assertOk();

        $this->postJson(route('classes.seating.save', $this->kelas), [])
            ->assertOk()
            ->assertJson(['ok' => true, 'count' => 0]);

        $this->assertSame(0, Seat::withoutTenant()->where('class_id', $this->kelas->id)->count());
    }

    /** exists rule polos menerima siswa kelas mana pun; harus disaring ke kelas ini. */
    public function test_siswa_kelas_lain_ditolak(): void
    {
        $kelasLain = Classroom::factory()->create(['user_id' => User::factory()->create()->id]);
        $siswaLain = Student::factory()->create(['user_id' => $kelasLain->user_id, 'class_id' => $kelasLain->id]);

        $this->post(route('classes.seating.save', $this->kelas), [
            'seats' => [
                ['row_index' => 0, 'col_index' => 0, 'student_id' => $siswaLain->id, 'label' => null],
            ],
        ])->assertSessionHasErrors('seats.0.student_id');
    }
}
