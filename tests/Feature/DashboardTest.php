<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create(['name' => 'Bu Rina, S.Pd']);
        $this->class = Classroom::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'XII RPL 1',
        ]);

        $this->actingAs($this->user);
    }

    private function siswa(int $n = 4)
    {
        return Student::factory()->count($n)->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
        ]);
    }

    private function sesi(string $status = 'submitted', bool $kedaluwarsa = false): AttendanceSession
    {
        return AttendanceSession::create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'session_date' => today(),
            'sequence' => 1,
            'token' => 'tok'.uniqid(),
            'pin_hash' => bcrypt('123456'),
            'expires_at' => $kedaluwarsa ? now()->subHour() : now()->addHours(4),
            'status' => $status,
        ]);
    }

    /*
     * Dashboard dirombak jadi papan statistik. Panel status sesi PER KELAS
     * ("belum dibuat" / "menunggu diisi" / "selesai" / "tautan hangus") tidak
     * ada lagi di sini — pengujiannya pindah ke AttendanceNavigationTest,
     * yang memeriksa halaman absensi tempat status itu kini ditampilkan.
     */
    public function test_dashboard_terbuka(): void
    {
        $this->siswa();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Presensi Hari Ini')
            ->assertSee('Total Siswa');
    }

    public function test_dashboard_menampilkan_rasio_kehadiran_sesi_terkirim(): void
    {
        $siswa = $this->siswa(4);
        $sesi = $this->sesi('submitted');

        foreach ($siswa as $i => $s) {
            Attendance::create([
                'user_id' => $this->user->id,
                'attendance_session_id' => $sesi->id,
                'student_id' => $s->id,
                'status' => $i === 0 ? 'alfa' : 'hadir',
            ]);
        }

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('75%')      // 3 masuk dari 4 isian
            ->assertSee('H 3');
    }

    /**
     * Kehadiran harus tampil sebagai persentase dengan penyebut.
     * Cacah telanjang seperti "58" tidak bisa ditafsirkan tanpa tahu dari berapa.
     */
    public function test_kehadiran_tampil_sebagai_persentase(): void
    {
        $siswa = $this->siswa(4);
        $sesi = $this->sesi('submitted');

        foreach ($siswa as $i => $s) {
            Attendance::create([
                'user_id' => $this->user->id,
                'attendance_session_id' => $sesi->id,
                'student_id' => $s->id,
                'status' => $i < 2 ? 'hadir' : 'alfa',
            ]);
        }

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('50%')
            // Persentase saja ambigu; cacah yang masuk harus ikut terbaca.
            ->assertSee('H 2');
    }

    /** Terlambat ikut dihitung masuk, konsisten dengan laporan. */
    public function test_terlambat_dihitung_masuk_di_dashboard(): void
    {
        $siswa = $this->siswa(2);
        $sesi = $this->sesi('submitted');

        Attendance::create([
            'user_id' => $this->user->id, 'attendance_session_id' => $sesi->id,
            'student_id' => $siswa[0]->id, 'status' => 'terlambat',
        ]);
        Attendance::create([
            'user_id' => $this->user->id, 'attendance_session_id' => $sesi->id,
            'student_id' => $siswa[1]->id, 'status' => 'hadir',
        ]);

        $this->get(route('dashboard'))->assertOk()->assertSee('100%');
    }

    /** Tanpa absensi sama sekali, tampilkan "—" bukan "0%". */
    public function test_tanpa_absensi_menampilkan_strip_bukan_nol_persen(): void
    {
        $this->siswa();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('—');
    }

    /** Daftar perhatian menyebut alasannya, bukan cuma nama. */
    public function test_siswa_perlu_perhatian_menyertakan_alasan(): void
    {
        $siswa = $this->siswa(1);
        $siswa[0]->update(['discipline_points' => 50]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee($siswa[0]->name)
            ->assertSee('Poin 50');
    }

    public function test_alfa_berulang_masuk_daftar_perhatian(): void
    {
        $siswa = $this->siswa(1);

        foreach (range(0, 3) as $i) {
            $sesi = AttendanceSession::create([
                'user_id' => $this->user->id,
                'class_id' => $this->class->id,
                'session_date' => today()->subDays($i),
                'sequence' => 1,
                'token' => 'tk'.uniqid(),
                'pin_hash' => bcrypt('1'),
                'expires_at' => now(),
                'status' => 'submitted',
            ]);

            Attendance::create([
                'user_id' => $this->user->id,
                'attendance_session_id' => $sesi->id,
                'student_id' => $siswa[0]->id,
                'status' => 'alfa',
            ]);
        }

        $this->get(route('dashboard'))->assertOk()->assertSee('Alfa 4× (30 hari)');
    }

    /** Kelas wali kelas lain tidak boleh bocor ke dashboard. */
    public function test_kelas_orang_lain_tidak_tampil(): void
    {
        $lain = User::factory()->create();
        Classroom::factory()->create(['user_id' => $lain->id, 'name' => 'KELAS RAHASIA']);

        $this->get(route('dashboard'))->assertOk()->assertDontSee('KELAS RAHASIA');
    }

    /**
     * Aksi Cepat dulu keempatnya menuju classes.index apa pun labelnya —
     * "cepat"-nya cuma nama. Dengan satu kelas perwalian (pola paling umum),
     * tombolnya harus loncat langsung ke kelas itu.
     */
    public function test_aksi_cepat_langsung_ke_kelas_utama_saat_perwalian_tunggal(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('classes.attendance.index', $this->class), false)
            ->assertSee(route('classes.students.index', $this->class), false)
            ->assertSee(route('classes.character-portfolio.index', $this->class), false);
    }

    /** Tanpa kelas perwalian tunggal yang jelas, tidak ada yang bisa ditebak — jatuh balik ke daftar kelas. */
    public function test_aksi_cepat_jatuh_balik_ke_daftar_kelas_saat_ambigu(): void
    {
        Classroom::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'X RPL 2',
            'jenis' => Classroom::JENIS_AJAR,
            'mapel' => ['Informatika'],
        ]);

        // Kelas perwalian dari setUp() + satu kelas ajar baru = tidak lagi satu-satunya kelas,
        // tapi kelas perwaliannya tetap tunggal — jadi kasus ambigu sungguhan butuh kelas
        // perwalian KEDUA.
        Classroom::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'XII RPL 2',
            'jenis' => Classroom::JENIS_PERWALIAN,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('classes.index'), false)
            ->assertDontSee(route('classes.attendance.index', $this->class), false);
    }
}
