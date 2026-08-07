<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceRevision;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Koreksi absensi oleh wali kelas.
 *
 * Kemampuan ini WAJIB ada — siswa yang menyusul membawa surat dokter sehari
 * kemudian adalah kejadian rutin. Tapi karena seluruh premis aplikasi ini
 * mencegah absensi asal-asalan, koreksinya harus berjejak: siapa, kapan, dari
 * status apa ke apa, dan alasannya.
 */
class KoreksiAbsensiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    private AttendanceSession $session;

    /** @var \Illuminate\Database\Eloquent\Collection<int, Student> */
    private $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create();
        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);
        $this->siswa = Student::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
        ]);

        $this->session = AttendanceSession::create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'session_date' => today(),
            'sequence' => 1,
            'token' => 'tok'.uniqid(),
            'pin_hash' => bcrypt('123456'),
            'expires_at' => now()->subHour(),
            'status' => 'submitted',
            'submitted_at' => now()->subHour(),
        ]);

        foreach ($this->siswa as $s) {
            Attendance::create([
                'user_id' => $this->user->id,
                'attendance_session_id' => $this->session->id,
                'student_id' => $s->id,
                'status' => 'alfa',
            ]);
        }

        $this->actingAs($this->user);
    }

    private function kirim(array $ubah, string $alasan = 'Surat dokter menyusul')
    {
        $kehadiran = $this->siswa->mapWithKeys(fn ($s) => [$s->id => 'alfa'])->all();

        return $this->patch(
            route('classes.attendance.update', [$this->class, $this->session]),
            ['attendance' => array_replace($kehadiran, $ubah), 'reason' => $alasan]
        );
    }

    /** Sesi yang sudah dikirim petugas tetap bisa dibuka untuk dikoreksi. */
    public function test_sesi_submitted_bisa_dibuka_untuk_koreksi(): void
    {
        $this->get(route('classes.attendance.edit', [$this->class, $this->session]))
            ->assertOk()
            ->assertSee('Koreksi Absensi')
            ->assertSee('Simpan koreksi');
    }

    public function test_koreksi_mengubah_status_dan_mencatat_jejak(): void
    {
        $this->kirim([$this->siswa[0]->id => 'sakit'])
            ->assertRedirect(route('classes.attendance.show', [$this->class, $this->session]))
            ->assertSessionHas('success');

        $baris = Attendance::withoutTenant()
            ->where('attendance_session_id', $this->session->id)
            ->where('student_id', $this->siswa[0]->id)
            ->first();

        $this->assertSame('sakit', $baris->status);

        $jejak = AttendanceRevision::where('attendance_id', $baris->id)->first();
        $this->assertNotNull($jejak, 'Koreksi wajib meninggalkan jejak');
        $this->assertSame('alfa', $jejak->from_status);
        $this->assertSame('sakit', $jejak->to_status);
        $this->assertSame('Surat dokter menyusul', $jejak->reason);
        $this->assertSame($this->user->id, $jejak->user_id);
    }

    /** Status yang tidak berubah tidak boleh mengotori riwayat. */
    public function test_status_yang_tidak_berubah_tidak_membuat_jejak(): void
    {
        $this->kirim([])->assertSessionHas('info');

        $this->assertSame(0, AttendanceRevision::count());
    }

    public function test_alasan_wajib_diisi(): void
    {
        $kehadiran = $this->siswa->mapWithKeys(fn ($s) => [$s->id => 'hadir'])->all();

        $this->patch(route('classes.attendance.update', [$this->class, $this->session]), [
            'attendance' => $kehadiran,
        ])->assertSessionHasErrors('reason');

        $this->assertSame(0, AttendanceRevision::count());
        $this->assertSame('alfa', Attendance::withoutTenant()->first()->status);
    }

    /** Beberapa koreksi berurutan harus menyisakan seluruh riwayat. */
    public function test_koreksi_berulang_menyimpan_seluruh_riwayat(): void
    {
        $this->kirim([$this->siswa[0]->id => 'sakit'], 'Surat dokter');
        $this->kirim([$this->siswa[0]->id => 'izin'], 'Ternyata izin keluarga');

        $baris = Attendance::withoutTenant()->where('student_id', $this->siswa[0]->id)->first();

        $this->assertSame('izin', $baris->status);
        $this->assertSame(2, AttendanceRevision::where('attendance_id', $baris->id)->count());
    }

    /** Siswa yang terlewat petugas bisa ditambahkan, tetap dengan jejak. */
    public function test_siswa_yang_belum_terisi_bisa_ditambahkan(): void
    {
        $baru = Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
        ]);

        $kehadiran = $this->siswa->mapWithKeys(fn ($s) => [$s->id => 'alfa'])->all();
        $kehadiran[$baru->id] = 'hadir';

        $this->patch(route('classes.attendance.update', [$this->class, $this->session]), [
            'attendance' => $kehadiran,
            'reason' => 'Siswa pindahan, terlewat saat pengisian',
        ])->assertSessionHas('success');

        $baris = Attendance::withoutTenant()->where('student_id', $baru->id)->first();
        $this->assertNotNull($baris);
        $this->assertSame('hadir', $baris->status);
        $this->assertSame('-', AttendanceRevision::where('attendance_id', $baris->id)->first()->from_status);
    }

    /** Catatan bisa diperbarui tanpa mengubah status. */
    public function test_catatan_bisa_diperbarui(): void
    {
        $kehadiran = $this->siswa->mapWithKeys(fn ($s) => [$s->id => 'alfa'])->all();

        $this->patch(route('classes.attendance.update', [$this->class, $this->session]), [
            'attendance' => $kehadiran,
            'notes' => [$this->siswa[0]->id => 'Sudah dihubungi orang tua'],
            'reason' => 'Menambah keterangan',
        ]);

        $this->assertSame(
            'Sudah dihubungi orang tua',
            Attendance::withoutTenant()->where('student_id', $this->siswa[0]->id)->first()->note
        );
    }

    public function test_tidak_bisa_mengoreksi_sesi_kelas_lain(): void
    {
        $lain = Classroom::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->get(route('classes.attendance.edit', [$lain, $this->session]))->assertNotFound();
    }

    /** Penanda koreksi harus terlihat di daftar rekap, bukan tersembunyi. */
    public function test_penanda_dikoreksi_tampil_di_halaman_sesi(): void
    {
        $this->kirim([$this->siswa[0]->id => 'sakit']);

        $this->get(route('classes.attendance.show', [$this->class, $this->session]))
            ->assertOk()
            ->assertSee('Dikoreksi');
    }
}
