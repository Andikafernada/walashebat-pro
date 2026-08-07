<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use App\Services\StudentProfileBuilder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilSiswaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    private Student $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create(['name' => 'Bu Rina', 'nip' => '1985']);
        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);
        $this->siswa = Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'name' => 'Fitriani Salsabila',
            'nis' => '2025006',
            'discipline_points' => 100,
        ]);

        $this->actingAs($this->user);
    }

    private function absen(string $status, int $mundur = 0): void
    {
        $tgl = today()->subDays($mundur);

        $sesi = AttendanceSession::create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'session_date' => $tgl,
            'sequence' => 1,
            'token' => 'tok'.uniqid(),
            'pin_hash' => bcrypt('123456'),
            'expires_at' => $tgl->copy()->addHours(8),
            'status' => 'submitted',
        ]);

        Attendance::create([
            'user_id' => $this->user->id,
            'attendance_session_id' => $sesi->id,
            'student_id' => $this->siswa->id,
            'status' => $status,
        ]);
    }

    /** Rute ini dulu sengaja dimatikan lewat ->except(['show']). */
    public function test_halaman_profil_siswa_terbuka(): void
    {
        $this->get(route('classes.students.show', [$this->class, $this->siswa]))
            ->assertOk()
            ->assertSee('Fitriani Salsabila')
            ->assertSee('2025006')
            ->assertSee('Tren kehadiran 6 bulan');
    }

    public function test_nama_di_daftar_menautkan_ke_profil(): void
    {
        $this->get(route('classes.students.index', $this->class))
            ->assertOk()
            ->assertSee(route('classes.students.show', [$this->class, $this->siswa]), false);
    }

    public function test_rekap_kehadiran_dihitung_benar(): void
    {
        $this->absen('hadir', 0);
        $this->absen('hadir', 1);
        $this->absen('sakit', 2);
        $this->absen('alfa', 3);

        $profil = app(StudentProfileBuilder::class)
            ->build($this->siswa, today()->subDays(10)->startOfDay(), today()->endOfDay());

        $this->assertSame(2, $profil['kehadiran']['jumlah']['hadir']);
        $this->assertSame(1, $profil['kehadiran']['jumlah']['sakit']);
        $this->assertSame(1, $profil['kehadiran']['jumlah']['alfa']);
        $this->assertSame(4, $profil['kehadiran']['total']);
        $this->assertSame(50, $profil['kehadiran']['persen']);
    }

    /** Tanpa data sama sekali, persentase harus null — bukan 0%. */
    public function test_tanpa_data_persen_bernilai_null_bukan_nol(): void
    {
        $profil = app(StudentProfileBuilder::class)
            ->build($this->siswa, today()->startOfMonth(), today()->endOfMonth());

        $this->assertNull($profil['kehadiran']['persen'], '0% berarti tidak pernah hadir; itu klaim berbeda dari "belum ada data"');
        $this->assertSame(0, $profil['kehadiran']['total']);

        $this->get(route('classes.students.show', [$this->class, $this->siswa]))
            ->assertOk()
            ->assertSee('Belum ada catatan kehadiran');
    }

    public function test_kelengkapan_data_menyebut_kolom_yang_kosong(): void
    {
        $this->siswa->update(['nisn' => null, 'nama_ayah' => null]);

        $profil = app(StudentProfileBuilder::class)
            ->build($this->siswa, today()->startOfMonth(), today()->endOfMonth());

        $this->assertContains('NISN', $profil['kelengkapan']['kosong']);
        $this->assertContains('Nama ayah', $profil['kelengkapan']['kosong']);
        $this->assertLessThan(100, $profil['kelengkapan']['persen']);

        // Wali kelas harus tahu APA yang kurang, bukan cuma bahwa ada yang kurang.
        $this->get(route('classes.students.show', [$this->class, $this->siswa]))
            ->assertOk()
            ->assertSee('NISN');
    }

    public function test_pelanggaran_dan_poin_tampil(): void
    {
        Violation::create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'student_id' => $this->siswa->id,
            'points' => -25,
            'note' => 'Terlambat tiga kali',
            'occurred_on' => today(),
        ]);
        $this->siswa->update(['discipline_points' => 75]);

        $profil = app(StudentProfileBuilder::class)
            ->build($this->siswa, today()->startOfMonth(), today()->endOfMonth());

        $this->assertSame(1, $profil['poin']['kejadian']);
        $this->assertSame(-25, $profil['poin']['periode']);
        $this->assertSame(75, $profil['poin']['sekarang']);
    }

    /** Tren selalu enam bulan, termasuk bulan yang kosong. */
    public function test_tren_selalu_enam_bulan(): void
    {
        $this->absen('hadir', 0);

        $profil = app(StudentProfileBuilder::class)
            ->build($this->siswa, today()->startOfMonth(), today()->endOfMonth());

        $this->assertCount(6, $profil['tren']);
        $this->assertSame(100, $profil['tren']->last()['persen']);
    }

    public function test_pdf_profil_terunduh(): void
    {
        $this->absen('hadir', 0);

        $response = $this->get(route('classes.students.pdf', [$this->class, $this->siswa]))->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_tidak_bisa_membuka_siswa_kelas_lain(): void
    {
        $lain = Classroom::factory()->create(['user_id' => $this->user->id]);

        $this->get(route('classes.students.show', [$lain, $this->siswa]))->assertNotFound();
        $this->get(route('classes.students.pdf', [$lain, $this->siswa]))->assertNotFound();
    }

    public function test_tidak_bisa_membuka_siswa_wali_kelas_lain(): void
    {
        $orangLain = User::factory()->create();
        $kelasLain = Classroom::factory()->create(['user_id' => $orangLain->id]);
        $siswaLain = Student::factory()->create([
            'user_id' => $orangLain->id,
            'class_id' => $kelasLain->id,
        ]);

        $this->get(route('classes.students.show', [$kelasLain, $siswaLain]))->assertNotFound();
    }

    /** Periode semester mengikuti kalender pendidikan. */
    public function test_periode_semester_berlaku_di_profil(): void
    {
        $this->get(route('classes.students.show', [
            $this->class, $this->siswa, 'mode' => 'semester', 'semester' => '1', 'tahun' => 2025,
        ]))->assertOk()->assertSee('Semester 1 Penuh T.A. 2025/2026');
    }
}
