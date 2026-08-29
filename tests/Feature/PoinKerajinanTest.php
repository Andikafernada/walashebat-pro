<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\OrganizationStructure;
use App\Models\Student;
use App\Models\User;
use App\Support\PoinKerajinan;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Poin kerajinan adalah cache, bukan buku besar.
 *
 * students.diligence_points selalu dihitung ULANG dari nol atas seluruh
 * absensi & struktur organisasi, tidak pernah ditambah sebagai delta.
 */
class PoinKerajinanTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private Classroom $kelas;

    private Student $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->guru = User::factory()->create();
        $this->kelas = Classroom::factory()->create(['user_id' => $this->guru->id]);
        $this->siswa = Student::factory()->create([
            'user_id' => $this->guru->id,
            'class_id' => $this->kelas->id,
            'name' => 'Ani Rajin',
        ]);
    }

    private function sesi(string $tanggal, string $status = 'submitted'): AttendanceSession
    {
        return AttendanceSession::create([
            'user_id' => $this->guru->id,
            'class_id' => $this->kelas->id,
            'session_date' => $tanggal,
            'sequence' => 1,
            'status' => $status,
            'token' => Str::random(40),
            'pin_hash' => bcrypt('123456'),
            'expires_at' => now()->addHours(2),
        ]);
    }

    private function absen(AttendanceSession $sesi, string $status, ?Student $siswa = null): Attendance
    {
        return Attendance::create([
            'user_id' => $this->guru->id,
            'attendance_session_id' => $sesi->id,
            'student_id' => ($siswa ?? $this->siswa)->id,
            'status' => $status,
        ]);
    }

    private function poin(): int
    {
        return (int) $this->siswa->fresh()->diligence_points;
    }

    public function test_hadir_menambah_dan_alfa_mengurangi(): void
    {
        $this->absen($this->sesi('2026-08-03'), 'hadir');
        $this->absen($this->sesi('2026-08-04'), 'hadir');
        $this->absen($this->sesi('2026-08-05'), 'alfa');

        $this->assertSame(0, PoinKerajinan::NILAI['hadir'] * 2 + PoinKerajinan::NILAI['alfa'] - $this->poin(),
            'Poin harus persis mengikuti tabel PoinKerajinan::NILAI');
    }

    /** Izin dan sakit memotong 3 poin, terlambat bernilai 0. */
    public function test_izin_dan_sakit_memotong_poin(): void
    {
        $this->absen($this->sesi('2026-08-11'), 'izin');
        $this->assertSame(-3, $this->poin());

        $this->absen($this->sesi('2026-08-12'), 'sakit');
        $this->assertSame(-6, $this->poin());

        $this->absen($this->sesi('2026-08-13'), 'terlambat');
        $this->assertSame(-6, $this->poin());
    }

    /** Menjadi pengurus kelas menambah 2 poin keaktifan. */
    public function test_pengurus_kelas_mendapat_bonus_keaktifan(): void
    {
        $this->absen($this->sesi('2026-08-14'), 'hadir');
        $this->assertSame(5, $this->poin());

        OrganizationStructure::create([
            'user_id' => $this->guru->id,
            'class_id' => $this->kelas->id,
            'student_id' => $this->siswa->id,
            'role' => 'ketua',
        ]);

        PoinKerajinan::hitungUlang((int) $this->siswa->id);
        $this->assertSame(7, $this->poin(), 'Pengurus kelas mendapat bonus +2');
    }

    /**
     * Koreksi absensi TIDAK boleh menumpuk.
     */
    public function test_koreksi_absensi_tidak_menumpuk(): void
    {
        $absen = $this->absen($this->sesi('2026-08-06'), 'alfa');
        $this->assertSame(PoinKerajinan::NILAI['alfa'], $this->poin());

        $absen->update(['status' => 'hadir']);

        $this->assertSame(PoinKerajinan::NILAI['hadir'], $this->poin(),
            'Setelah dikoreksi, poin harus sama dengan kalau sejak awal diisi hadir');
    }

    public function test_absensi_dihapus_mengembalikan_poin(): void
    {
        $absen = $this->absen($this->sesi('2026-08-07'), 'hadir');
        $absen->delete();

        $this->assertSame(0, $this->poin());
    }

    /**
     * Membatalkan sesi hanya mengubah status SESI, baris absensinya tidak
     * tersentuh — jadi tanpa observer sesi, poinnya akan tertinggal.
     */
    public function test_sesi_dibatalkan_menghapus_poinnya(): void
    {
        $sesi = $this->sesi('2026-08-08');
        $this->absen($sesi, 'hadir');
        $this->assertSame(PoinKerajinan::NILAI['hadir'], $this->poin());

        $sesi->update(['status' => 'cancelled']);
        $this->assertSame(0, $this->poin(), 'Sesi batal tidak boleh menyisakan poin');

        $sesi->update(['status' => 'submitted']);
        $this->assertSame(PoinKerajinan::NILAI['hadir'], $this->poin(),
            'Sesi yang batalnya dibatalkan harus mengembalikan poin');
    }

    /** Perintah retroaktif menghitung dari data absensi yang sudah ada. */
    public function test_perintah_hitung_ulang_memperbaiki_poin_melenceng(): void
    {
        $this->absen($this->sesi('2026-08-09'), 'hadir');

        // Rusak cache-nya seperti kalau data lama masuk lewat jalur non-Eloquent.
        \DB::table('students')->where('id', $this->siswa->id)->update(['diligence_points' => 999]);

        $this->artisan('poin:hitung-ulang')->assertSuccessful();

        $this->assertSame(PoinKerajinan::NILAI['hadir'], $this->poin());
    }

    // -- Peringkat & sertifikat ----------------------------------------------

    public function test_peringkat_terurut_dan_terbatas_rentang(): void
    {
        $malas = Student::factory()->create([
            'user_id' => $this->guru->id, 'class_id' => $this->kelas->id, 'name' => 'Budi Malas',
        ]);

        $sesiDalam = $this->sesi('2026-08-10');
        $this->absen($sesiDalam, 'hadir');
        $this->absen($sesiDalam, 'alfa', $malas);

        // Di luar rentang: tidak boleh ikut terhitung.
        $this->absen($this->sesi('2026-01-15'), 'hadir', $malas);

        $peringkat = PoinKerajinan::peringkat(
            $this->kelas,
            \Illuminate\Support\Carbon::parse('2026-07-01'),
            \Illuminate\Support\Carbon::parse('2026-12-31'),
        );

        $this->assertCount(2, $peringkat);
        $this->assertSame('Ani Rajin', $peringkat->first()->name, 'Poin tertinggi harus di puncak');
        $this->assertSame(PoinKerajinan::NILAI['alfa'], (int) $peringkat->last()->poin);
    }

    public function test_halaman_kerajinan_menampilkan_peringkat(): void
    {
        $this->absen($this->sesi(now()->toDateString()), 'hadir');

        $this->actingAs($this->guru)
            ->get(route('classes.kerajinan.index', $this->kelas))
            ->assertOk()
            ->assertSee('Ani Rajin');
    }

    public function test_sertifikat_terunduh_sebagai_pdf(): void
    {
        $this->absen($this->sesi(now()->toDateString()), 'hadir');

        $respons = $this->actingAs($this->guru)
            ->get(route('classes.kerajinan.sertifikat', $this->kelas))
            ->assertOk();

        $this->assertSame('application/pdf', $respons->headers->get('content-type'));
    }

    /** Tanpa data kehadiran tidak ada juara — jangan cetak sertifikat kosong. */
    public function test_sertifikat_tanpa_data_ditolak(): void
    {
        $this->actingAs($this->guru)
            ->get(route('classes.kerajinan.sertifikat', $this->kelas))
            ->assertNotFound();
    }

    public function test_kelas_guru_lain_tidak_bisa_dibuka(): void
    {
        $lain = User::factory()->create();
        $kelasLain = Classroom::factory()->create(['user_id' => $lain->id]);

        $this->actingAs($this->guru)
            ->get(route('classes.kerajinan.index', $kelasLain))
            ->assertNotFound();
    }
}
