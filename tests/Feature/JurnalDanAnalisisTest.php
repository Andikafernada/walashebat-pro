<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Jurnal Mengajar & Analisis Kehadiran — keduanya TURUNAN.
 *
 * Tidak ada tabel baru: seluruh isinya disusun ulang dari presensi yang sudah
 * terekam. Yang dijaga berkas ini adalah bahwa penyusunannya benar, dan
 * terutama bahwa dua mapel di kelas yang sama TIDAK tercampur — kalau
 * tercampur, persentase kehadirannya tidak berarti bagi kedua mapel itu.
 */
class JurnalDanAnalisisTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->kelas = Classroom::factory()->create([
            'user_id' => $this->user->id,
            'jenis' => Classroom::JENIS_AJAR,
            'mapel' => ['Matematika', 'Informatika'],
        ]);
    }

    private function siswa(string $nama): Student
    {
        return Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->kelas->id,
            'name' => $nama,
            'is_active' => true,
        ]);
    }

    /** @param array<int, string> $status student_id => status */
    private function pertemuan(string $mapel, array $status, ?string $materi = null, int $urutan = 1): AttendanceSession
    {
        $sesi = $this->kelas->attendanceSessions()->create([
            'user_id' => $this->user->id,
            'title' => 'Pertemuan',
            'mapel' => $mapel,
            'materi' => $materi,
            'session_date' => today()->toDateString(),
            'sequence' => $urutan,
            'token' => Str::random(32),
            'pin_hash' => Hash::make('000000'),
            'expires_at' => now(),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        foreach ($status as $studentId => $s) {
            $sesi->attendances()->create([
                'user_id' => $this->user->id,
                'student_id' => $studentId,
                'status' => $s,
            ]);
        }

        return $sesi;
    }

    // -- Jurnal -------------------------------------------------------------

    public function test_jurnal_menyusun_pertemuan_dari_presensi(): void
    {
        $andi = $this->siswa('Andi');
        $budi = $this->siswa('Budi');
        $this->pertemuan('Matematika', [$andi->id => 'hadir', $budi->id => 'sakit'], 'Bilangan biner');

        $this->get(route('classes.jurnal.index', $this->kelas))
            ->assertOk()
            ->assertSee('Bilangan biner')
            ->assertSee('Matematika')
            // Nama yang tidak hadir ikut disebut — jurnal berisi angka saja
            // tidak menjawab saat guru ditanya siapa.
            ->assertSee('Budi');
    }

    public function test_materi_bisa_dilengkapi_setelah_mengajar(): void
    {
        $andi = $this->siswa('Andi');
        $sesi = $this->pertemuan('Matematika', [$andi->id => 'hadir']);

        $this->patch(route('classes.jurnal.materi', [$this->kelas, $sesi]), [
            'materi' => 'Gerbang logika',
        ])->assertRedirect();

        $this->assertSame('Gerbang logika', $sesi->fresh()->materi);
    }

    /** Sesi milik kelas lain tidak boleh disunting lewat kelas ini. */
    public function test_materi_kelas_lain_ditolak(): void
    {
        $lain = Classroom::factory()->create(['user_id' => $this->user->id]);
        $siswaLain = Student::factory()->create(['user_id' => $this->user->id, 'class_id' => $lain->id]);

        $sesiLain = $lain->attendanceSessions()->create([
            'user_id' => $this->user->id,
            'title' => 'Lain',
            'session_date' => today()->toDateString(),
            'sequence' => 1,
            'token' => Str::random(32),
            'pin_hash' => Hash::make('000000'),
            'expires_at' => now(),
            'status' => 'submitted',
        ]);
        $sesiLain->attendances()->create([
            'user_id' => $this->user->id, 'student_id' => $siswaLain->id, 'status' => 'hadir',
        ]);

        $this->patch(route('classes.jurnal.materi', [$this->kelas, $sesiLain]), ['materi' => 'X'])
            ->assertNotFound();

        $this->assertNull($sesiLain->fresh()->materi);
    }

    // -- Pemisahan mapel ----------------------------------------------------

    /**
     * Inti Tahap 2: dua mapel di kelas yang sama tidak boleh tercampur.
     * Kalau tercampur, persentase kehadirannya tidak berarti bagi keduanya.
     */
    public function test_jurnal_tersaring_per_mapel(): void
    {
        $andi = $this->siswa('Andi');
        $this->pertemuan('Matematika', [$andi->id => 'hadir'], 'Bilangan biner', 1);
        $this->pertemuan('Informatika', [$andi->id => 'hadir'], 'Algoritma dasar', 2);

        $this->get(route('classes.jurnal.index', [$this->kelas, 'mapel' => 'Matematika']))
            ->assertOk()
            ->assertSee('Bilangan biner')
            ->assertDontSee('Algoritma dasar');
    }

    public function test_analisis_tersaring_per_mapel(): void
    {
        $andi = $this->siswa('Andi');

        // Matematika: 1 dari 2 hadir -> 50%
        $this->pertemuan('Matematika', [$andi->id => 'hadir'], null, 1);
        $this->pertemuan('Matematika', [$andi->id => 'alfa'], null, 2);
        // Informatika: selalu hadir -> 100%
        $this->pertemuan('Informatika', [$andi->id => 'hadir'], null, 3);

        $this->get(route('classes.reports.analisis', [$this->kelas, 'mapel' => 'Matematika']))
            ->assertOk()
            ->assertSee('50%');

        $this->get(route('classes.reports.analisis', [$this->kelas, 'mapel' => 'Informatika']))
            ->assertOk()
            ->assertSee('100%');
    }

    /** Mapel karangan di URL tidak boleh menghasilkan laporan kosong yang menyesatkan. */
    public function test_mapel_asing_di_url_diabaikan(): void
    {
        $andi = $this->siswa('Andi');
        $this->pertemuan('Matematika', [$andi->id => 'hadir'], 'Bilangan biner');

        $this->get(route('classes.jurnal.index', [$this->kelas, 'mapel' => 'Mapel Karangan']))
            ->assertOk()
            ->assertSee('Bilangan biner');
    }

    // -- Analisis -----------------------------------------------------------

    public function test_analisis_mengurutkan_dari_kehadiran_terendah(): void
    {
        $rajin = $this->siswa('Rajin');
        $jarang = $this->siswa('Jarang');

        $this->pertemuan('Matematika', [$rajin->id => 'hadir', $jarang->id => 'alfa'], null, 1);
        $this->pertemuan('Matematika', [$rajin->id => 'hadir', $jarang->id => 'alfa'], null, 2);

        $isi = $this->get(route('classes.reports.analisis', [$this->kelas, 'mapel' => 'Matematika']))
            ->assertOk()->getContent();

        $this->assertLessThan(
            strpos($isi, 'Rajin'),
            strpos($isi, 'Jarang'),
            'siswa dengan kehadiran terendah harus berada di atas'
        );
    }

    /**
     * Siswa tanpa satu pun isian TIDAK boleh muncul sebagai 0% di puncak
     * daftar — persentasenya nol karena belum pernah tercatat, bukan karena
     * sering bolos, dan menuduhnya di baris pertama adalah keliru.
     */
    public function test_siswa_tanpa_isian_tidak_dituduh_bolos(): void
    {
        $hadir = $this->siswa('Hadir Terus');
        $this->siswa('Belum Pernah');

        $this->pertemuan('Matematika', [$hadir->id => 'hadir']);

        $isi = $this->get(route('classes.reports.analisis', [$this->kelas, 'mapel' => 'Matematika']))
            ->assertOk()
            ->assertSee('belum ada data')
            ->getContent();

        $this->assertLessThan(
            strpos($isi, 'Belum Pernah'),
            strpos($isi, 'Hadir Terus'),
            'siswa tanpa isian harus di bawah, bukan di puncak daftar perhatian'
        );
    }

    // -- Menu ---------------------------------------------------------------

    public function test_jurnal_dan_analisis_muncul_di_menu_kelas_ajar(): void
    {
        $this->get(route('classes.attendance.index', $this->kelas))
            ->assertOk()
            ->assertSee('Jurnal')
            ->assertSee('Analisis');
    }
}
