<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * getMonthlyAttendance() dulu menjalankan 12 query terpisah (satu per bulan)
 * memakai whereHas(); disatukan jadi satu query yang dikelompokkan di PHP.
 * Test ini menjaga hasilnya tetap benar setelah penyatuan itu -- bukan
 * menjaga jumlah query.
 */
class AnalitikTrenBulananTest extends TestCase
{
    use RefreshDatabase;

    public function test_hitungan_bulan_ini_benar_dan_bulan_lain_tidak_tercampur(): void
    {
        $guru = User::factory()->create();
        $kelas = Classroom::factory()->create(['user_id' => $guru->id]);
        $siswa = Student::factory()->count(4)->create(['user_id' => $guru->id, 'class_id' => $kelas->id]);

        $sesiBulanIni = AttendanceSession::create([
            'user_id' => $guru->id, 'class_id' => $kelas->id,
            'session_date' => now()->startOfMonth()->addDays(2),
            'status' => 'submitted', 'token' => 'tok-bulan-ini', 'pin_hash' => bcrypt('123456'),
            'expires_at' => now(), 'sequence' => 1,
        ]);

        Attendance::create(['user_id' => $guru->id, 'attendance_session_id' => $sesiBulanIni->id, 'student_id' => $siswa[0]->id, 'status' => 'hadir']);
        Attendance::create(['user_id' => $guru->id, 'attendance_session_id' => $sesiBulanIni->id, 'student_id' => $siswa[1]->id, 'status' => 'hadir']);
        Attendance::create(['user_id' => $guru->id, 'attendance_session_id' => $sesiBulanIni->id, 'student_id' => $siswa[2]->id, 'status' => 'sakit']);
        Attendance::create(['user_id' => $guru->id, 'attendance_session_id' => $sesiBulanIni->id, 'status' => 'alfa', 'student_id' => $siswa[3]->id]);

        // Sesi bulan lalu -- tidak boleh ikut terhitung di bucket bulan ini,
        // dan sesi yang dibatalkan tidak boleh terhitung sama sekali.
        $sesiBulanLalu = AttendanceSession::create([
            'user_id' => $guru->id, 'class_id' => $kelas->id,
            'session_date' => now()->subMonth()->startOfMonth()->addDays(2),
            'status' => 'submitted', 'token' => 'tok-bulan-lalu', 'pin_hash' => bcrypt('123456'),
            'expires_at' => now(), 'sequence' => 1,
        ]);
        Attendance::create(['user_id' => $guru->id, 'attendance_session_id' => $sesiBulanLalu->id, 'student_id' => $siswa[0]->id, 'status' => 'hadir']);

        $sesiDibatalkan = AttendanceSession::create([
            'user_id' => $guru->id, 'class_id' => $kelas->id,
            'session_date' => now()->startOfMonth()->addDays(3),
            'status' => 'cancelled', 'token' => 'tok-batal', 'pin_hash' => bcrypt('123456'),
            'expires_at' => now(), 'sequence' => 2,
        ]);
        Attendance::create(['user_id' => $guru->id, 'attendance_session_id' => $sesiDibatalkan->id, 'student_id' => $siswa[0]->id, 'status' => 'hadir']);

        $respon = $this->actingAs($guru)->get(route('analytics.index', ['class_id' => $kelas->id]));
        $respon->assertOk();

        $bulanan = collect($respon->viewData('monthlyAttendance'));
        $bulanIni = $bulanan->firstWhere('month', now()->format('M Y'));
        $bulanLalu = $bulanan->firstWhere('month', now()->subMonth()->format('M Y'));

        $this->assertNotNull($bulanIni);
        $this->assertSame(4, $bulanIni['total'], 'sesi dibatalkan tidak boleh ikut terhitung');
        $this->assertSame(2, $bulanIni['present']);
        $this->assertSame(1, $bulanIni['sick']);
        $this->assertSame(1, $bulanIni['alpha']);

        $this->assertNotNull($bulanLalu);
        $this->assertSame(1, $bulanLalu['total'], 'bulan lalu tidak boleh tercampur ke bucket bulan ini');
    }
}
