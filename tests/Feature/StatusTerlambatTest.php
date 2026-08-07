<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\ClassReportBuilder;
use App\Services\StudentProfileBuilder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Status "terlambat".
 *
 * Keputusan pokok yang diuji di sini: terlambat DIHITUNG MASUK untuk
 * persentase kehadiran, karena siswanya memang hadir — hanya tidak tepat
 * waktu. Keterlambatannya tetap terhitung terpisah sebagai urusan disiplin.
 */
class StatusTerlambatTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    private Student $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Queue::fake();

        $this->user = User::factory()->create();
        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);
        $this->siswa = Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
        ]);

        $this->actingAs($this->user);
    }

    private function absen(string $status, int $mundur = 0): AttendanceSession
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

        return $sesi;
    }

    public function test_terlambat_diterima_sebagai_status_yang_sah(): void
    {
        $this->assertContains('terlambat', Attendance::STATUSES);

        $sesi = $this->absen('terlambat');

        $this->assertSame('terlambat', $sesi->attendances()->first()->status);
    }

    /** Inti keputusannya: terlambat masuk hitungan hadir, bukan absen. */
    public function test_terlambat_dihitung_masuk_pada_persentase(): void
    {
        $this->absen('hadir', 0);
        $this->absen('terlambat', 1);
        $this->absen('alfa', 2);
        $this->absen('alfa', 3);

        $profil = app(StudentProfileBuilder::class)
            ->build($this->siswa, today()->subDays(10)->startOfDay(), today()->endOfDay());

        // 2 masuk (hadir + terlambat) dari 4 isian = 50%.
        $this->assertSame(50, $profil['kehadiran']['persen']);
        $this->assertSame(1, $profil['kehadiran']['jumlah']['terlambat']);
        $this->assertSame(1, $profil['kehadiran']['jumlah']['hadir']);
    }

    /** Terlambat tetap terhitung terpisah, tidak melebur ke "hadir". */
    public function test_terlambat_tidak_melebur_menjadi_hadir(): void
    {
        $this->absen('terlambat', 0);

        $profil = app(StudentProfileBuilder::class)
            ->build($this->siswa, today()->subDays(3)->startOfDay(), today()->endOfDay());

        $this->assertSame(0, $profil['kehadiran']['jumlah']['hadir']);
        $this->assertSame(1, $profil['kehadiran']['jumlah']['terlambat']);
        $this->assertSame(100, $profil['kehadiran']['persen']);
    }

    /** Keterlambatan berulang memunculkan siswa di daftar perhatian. */
    public function test_terlambat_berulang_masuk_daftar_perhatian(): void
    {
        foreach (range(0, 5) as $i) {
            $this->absen('terlambat', $i);
        }

        $data = app(ClassReportBuilder::class)->build(
            $this->class,
            today()->subDays(10)->startOfDay(),
            today()->endOfDay(),
            array_keys(ClassReportBuilder::SECTIONS)
        );

        $this->assertCount(1, $data['perhatian']);
        $this->assertStringContainsString(
            'Terlambat 6 kali',
            implode(' ', $data['perhatian'][0]['alasan'])
        );

        // Kehadirannya tetap 100% — dia memang selalu masuk.
        $this->assertSame(100, $data['persenKelas']);
    }

    /** Terlambat sekali dua kali bukan pola; jangan munculkan sebagai masalah. */
    public function test_terlambat_sesekali_tidak_masuk_daftar_perhatian(): void
    {
        $this->absen('terlambat', 0);
        $this->absen('hadir', 1);

        $data = app(ClassReportBuilder::class)->build(
            $this->class,
            today()->subDays(10)->startOfDay(),
            today()->endOfDay(),
            array_keys(ClassReportBuilder::SECTIONS)
        );

        $this->assertCount(0, $data['perhatian']);
    }

    /** Petugas absensi bisa memilih terlambat dari tautan publik. */
    public function test_petugas_bisa_menandai_terlambat(): void
    {
        $sesi = AttendanceSession::create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'session_date' => today(),
            'sequence' => 2,
            'token' => 'pub'.uniqid(),
            'pin_hash' => bcrypt('123456'),
            'expires_at' => now()->addHours(4),
            'status' => 'open',
        ]);

        auth()->logout();

        $this->post(route('magic.verify', $sesi->token), ['pin' => '123456']);
        $this->get(route('magic.roster', $sesi->token))->assertOk()->assertSee('Terlambat');

        $this->post(route('magic.submit', $sesi->token), [
            'attendance' => [$this->siswa->id => 'terlambat'],
        ])->assertRedirect(route('magic.done', $sesi->token));

        $this->assertSame(
            'terlambat',
            Attendance::withoutTenant()->where('attendance_session_id', $sesi->id)->first()->status
        );
    }

    /** Rekap ke grup orang tua harus menyebut yang terlambat. */
    public function test_rekap_orang_tua_menyebut_terlambat(): void
    {
        $sesi = $this->absen('terlambat');
        $sesi->load('attendances.student');

        $this->assertStringContainsString('Terlambat', view('reports.pdf.siswa', [
            'siswa' => $this->siswa,
            'classroom' => $this->class,
            'guru' => $this->user,
            'periode' => ['label' => 'Juli 2026', 'slug' => '2026-07'],
        ] + app(StudentProfileBuilder::class)->build(
            $this->siswa, today()->subDays(3)->startOfDay(), today()->endOfDay()
        ))->render());
    }

    public function test_status_di_luar_daftar_tetap_ditolak(): void
    {
        $sesi = $this->absen('hadir');

        $this->patch(route('classes.attendance.update', [$this->class, $sesi]), [
            'attendance' => [$this->siswa->id => 'bolos'],
            'reason' => 'Coba status palsu',
        ])->assertSessionHasErrors('attendance.'.$this->siswa->id);
    }
}
