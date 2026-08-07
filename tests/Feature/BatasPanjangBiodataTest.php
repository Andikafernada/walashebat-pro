<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Aturan validasi tidak boleh lebih longgar daripada lebar kolomnya.
 *
 * rt_rw pernah divalidasi max:20 di atas kolom varchar(10). Siswa yang
 * mengetik format lazim "RT 08 / RW 06" (13 karakter) lolos validasi lalu
 * ditolak MySQL — HTTP 500, dan SELURUH biodata yang sudah diketik hilang,
 * bukan hanya kolom yang bermasalah.
 */
class BatasPanjangBiodataTest extends TestCase
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

    /** Nilai sepanjang batas validasi harus muat di kolomnya. */
    public function test_isian_sepanjang_batas_validasi_tersimpan(): void
    {
        /*
         * phone & parent_phone sengaja tidak diuji panjangnya: keduanya punya
         * mutator yang menormalkan nomor, jadi panjang tersimpan memang boleh
         * berbeda dari yang dikirim.
         */
        $panjang = [
            'nis' => 50, 'nisn' => 50, 'tempat_lahir' => 191,
            'nama_ayah' => 191, 'pekerjaan_ayah' => 191,
            'nama_ibu' => 191, 'pekerjaan_ibu' => 191, 'nama_wali' => 191,
            'pekerjaan_wali' => 191, 'rt_rw' => 20, 'kelurahan' => 191,
            'kecamatan' => 191, 'moda_transportasi' => 191, 'asal_sekolah' => 191,
            'hobi' => 191, 'cita_cita' => 191,
        ];

        $isi = [
            'name' => str_repeat('N', 191),
            'gender' => 'L',
            'parent_phone' => '081234567890',
        ];

        foreach ($panjang as $kolom => $n) {
            $isi[$kolom] = str_repeat('x', $n);
        }

        $this->post(route('public.biodata.store', $this->class), $isi)
            ->assertSessionHasNoErrors();

        $siswa = Student::withoutTenant()->sole();

        foreach ($panjang as $kolom => $n) {
            $this->assertSame(
                $n,
                mb_strlen((string) $siswa->{$kolom}),
                "Kolom {$kolom} terpotong: validasi mengizinkan {$n} karakter tetapi kolomnya lebih sempit"
            );
        }
    }

    /** Format RT/RW yang lazim ditulis orang harus diterima. */
    public function test_format_rt_rw_yang_lazim_diterima(): void
    {
        foreach (['RT 08 / RW 06', 'RT 01/ RW 28', '003/007', 'RT.05/RW.09'] as $nilai) {
            $siswa = Student::factory()->create([
                'user_id' => $this->user->id, 'class_id' => $this->class->id,
            ]);

            $this->post(route('public.biodata.store', $this->class), [
                'student_id' => $siswa->id,
                'name' => $siswa->name,
                'gender' => 'L',
                'parent_phone' => '081234567890',
                'rt_rw' => $nilai,
            ])->assertSessionHasNoErrors();

            $this->assertSame($nilai, $siswa->fresh()->rt_rw);
        }
    }

    /**
     * Penjaga menyeluruh: tiap aturan max:N pada formulir biodata publik
     * dibandingkan langsung dengan lebar kolomnya di basis data.
     */
    public function test_tidak_ada_aturan_max_yang_melebihi_lebar_kolom(): void
    {
        $sumber = file_get_contents(app_path('Http/Controllers/PublicStudentFormController.php'));

        preg_match_all("/'([a-z_]+)' => \[[^\]]*'max:(\d+)'/", $sumber, $cocok, PREG_SET_ORDER);

        $this->assertNotEmpty($cocok, 'Aturan validasi tidak terbaca — polanya mungkin berubah');

        $bentrok = [];

        foreach ($cocok as [, $kolom, $maks]) {
            if (! Schema::hasColumn('students', $kolom)) {
                continue;
            }

            $tipe = Schema::getColumnType('students', $kolom);

            // Hanya kolom berbatas panjang yang relevan; text/integer tidak.
            if (! in_array($tipe, ['string', 'varchar'], true)) {
                continue;
            }

            $lebar = $this->lebarKolom($kolom);

            if ($lebar !== null && (int) $maks > $lebar) {
                $bentrok[] = "{$kolom}: validasi max:{$maks} tetapi kolom hanya {$lebar}";
            }
        }

        $this->assertSame([], $bentrok, "Aturan validasi lebih longgar daripada kolomnya:\n".implode("\n", $bentrok));
    }

    private function lebarKolom(string $kolom): ?int
    {
        // SQLite pada test tidak menyimpan panjang; ambil dari definisi migrasi.
        $baris = collect(Schema::getColumns('students'))->firstWhere('name', $kolom);

        if (! $baris) {
            return null;
        }

        return preg_match('/\((\d+)\)/', $baris['type'] ?? '', $m) ? (int) $m[1] : null;
    }
}
