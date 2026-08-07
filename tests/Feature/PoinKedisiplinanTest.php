<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationType;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Poin kedisiplinan dimulai dari 100 dan BERKURANG setiap pelanggaran —
 * ambang disciplineTone() (80/50) dan needsAttention() (<50) dibangun di atas
 * anggapan itu. Karena itu points pada jenis pelanggaran adalah selisih
 * bertanda: negatif untuk pelanggaran, positif untuk penghargaan.
 *
 * Dua hal dulu tidak sejalan dengan itu:
 *  - halaman "Jenis Pelanggaran" hanya menerima angka positif (min:1) dan
 *    menyimpannya apa adanya, sehingga setiap jenis yang dibuat lewat aplikasi
 *    justru MENAIKKAN poin siswa saat pelanggarannya dicatat;
 *  - penghapusan catatan memakai abs(), jadi menghapus penghargaan +20
 *    menambah 20 lagi alih-alih menariknya kembali.
 */
class PoinKedisiplinanTest extends TestCase
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
    }

    private function siswa(int $poin = 100): Student
    {
        return Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'discipline_points' => $poin,
        ]);
    }

    // -- Jenis dibuat lewat halaman aplikasi --------------------------------

    public function test_jenis_pelanggaran_tersimpan_bertanda_negatif(): void
    {
        $this->actingAs($this->user)
            ->post(route('violation-types.store'), [
                'name' => 'Terlambat masuk kelas',
                'category' => 'ringan',
                'points' => 5,
            ])->assertSessionHasNoErrors();

        $this->assertSame(-5, ViolationType::withoutTenant()->sole()->points);
    }

    public function test_jenis_penghargaan_tersimpan_bertanda_positif(): void
    {
        $this->actingAs($this->user)
            ->post(route('violation-types.store'), [
                'name' => 'Juara lomba',
                'category' => 'penghargaan',
                'points' => 20,
            ])->assertSessionHasNoErrors();

        $this->assertSame(20, ViolationType::withoutTenant()->sole()->points);
    }

    /** Jenis yang dibuat lewat halaman benar-benar MENURUNKAN poin siswa. */
    public function test_pelanggaran_dari_jenis_buatan_pengguna_menurunkan_poin(): void
    {
        $siswa = $this->siswa(100);

        $this->actingAs($this->user)->post(route('violation-types.store'), [
            'name' => 'Terlambat', 'category' => 'ringan', 'points' => 5,
        ]);

        $jenis = ViolationType::withoutTenant()->sole();

        $this->actingAs($this->user)
            ->post(route('classes.violations.store', $this->class), [
                'student_id' => $siswa->id,
                'violation_type_id' => $jenis->id,
                'occurred_on' => now()->toDateString(),
            ])->assertSessionHasNoErrors();

        $this->assertSame(95, $siswa->fresh()->discipline_points);
    }

    // -- Penghapusan mengembalikan persis yang diterapkan -------------------

    public function test_menghapus_pelanggaran_mengembalikan_poin(): void
    {
        $siswa = $this->siswa(100);
        $jenis = ViolationType::create([
            'user_id' => $this->user->id, 'name' => 'Terlambat', 'category' => 'ringan', 'points' => -5,
        ]);

        $this->actingAs($this->user)->post(route('classes.violations.store', $this->class), [
            'student_id' => $siswa->id,
            'violation_type_id' => $jenis->id,
            'occurred_on' => now()->toDateString(),
        ]);

        $this->assertSame(95, $siswa->fresh()->discipline_points);

        $pelanggaran = Violation::withoutTenant()->sole();

        $this->actingAs($this->user)
            ->delete(route('classes.violations.destroy', [$this->class, $pelanggaran]))
            ->assertRedirect();

        $this->assertSame(100, $siswa->fresh()->discipline_points, 'Poin harus kembali seperti semula');
    }

    /** Menghapus penghargaan harus MENARIK poinnya, bukan menambah lagi. */
    public function test_menghapus_penghargaan_menarik_kembali_poinnya(): void
    {
        // Sengaja di bawah 100: mutator disciplinePoints() mengurung poin di
        // rentang 0-100, jadi penghargaan pada siswa berpoin penuh tidak akan
        // menggerakkan apa pun dan tesnya jadi tidak membuktikan apa-apa.
        $siswa = $this->siswa(70);
        $jenis = ViolationType::create([
            'user_id' => $this->user->id, 'name' => 'Juara lomba', 'category' => 'penghargaan', 'points' => 20,
        ]);

        $this->actingAs($this->user)->post(route('classes.violations.store', $this->class), [
            'student_id' => $siswa->id,
            'violation_type_id' => $jenis->id,
            'occurred_on' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertSame(90, $siswa->fresh()->discipline_points);

        $catatan = Violation::withoutTenant()->sole();

        $this->actingAs($this->user)
            ->delete(route('classes.violations.destroy', [$this->class, $catatan]))
            ->assertRedirect();

        $this->assertSame(
            70,
            $siswa->fresh()->discipline_points,
            'Menghapus penghargaan tidak boleh menambah poin untuk kedua kalinya'
        );
    }

    /** Poin tidak boleh menembus nol. */
    public function test_poin_tidak_menjadi_negatif(): void
    {
        $siswa = $this->siswa(3);
        $jenis = ViolationType::create([
            'user_id' => $this->user->id, 'name' => 'Perkelahian', 'category' => 'berat', 'points' => -50,
        ]);

        $this->actingAs($this->user)->post(route('classes.violations.store', $this->class), [
            'student_id' => $siswa->id,
            'violation_type_id' => $jenis->id,
            'occurred_on' => now()->toDateString(),
        ]);

        $this->assertSame(0, $siswa->fresh()->discipline_points);
    }
}
