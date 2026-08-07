<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Batas sesi absensi per kelas per hari.
 *
 * Satu sesi per hari terlalu rapuh: magic link yang telat dibuka, PIN yang
 * bocor, atau WhatsApp yang gagal terkirim membuat kelas itu kehilangan
 * absensi seharian tanpa jalan keluar. Batasnya dinaikkan, bukan dihapus.
 */
class DailyAttendanceQuotaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Queue::fake();

        $this->user = User::factory()->create();
        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);
        Student::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
        ]);

        $this->actingAs($this->user);
    }

    private function buatSesi(bool $paksa = false): \Illuminate\Testing\TestResponse
    {
        return $this->post(
            route('classes.attendance.store', $this->class),
            $paksa ? ['force_new' => 1] : []
        );
    }

    /** Sesi yang masih terbuka diarahkan ulang, bukan ditumpuk. */
    public function test_sesi_terbuka_diarahkan_ulang_bukan_membuat_sesi_baru(): void
    {
        $this->buatSesi();
        $this->assertSame(1, AttendanceSession::count());

        $this->buatSesi()->assertSessionHas('info');

        $this->assertSame(1, AttendanceSession::count());
    }

    /** Sesi tambahan hanya lahir bila wali kelas memintanya secara sadar. */
    public function test_force_new_membuat_sesi_tambahan_dengan_urutan_berikutnya(): void
    {
        $this->buatSesi();
        $this->buatSesi(paksa: true);

        $this->assertSame(2, AttendanceSession::count());
        $this->assertSame([1, 2], AttendanceSession::orderBy('sequence')->pluck('sequence')->all());
    }

    public function test_maksimal_tiga_sesi_per_hari(): void
    {
        config(['walikelas.max_sessions_per_day' => 3]);

        $this->buatSesi();
        $this->buatSesi(paksa: true);
        $this->buatSesi(paksa: true);

        $this->assertSame(3, AttendanceSession::count());

        // Percobaan keempat ditolak dengan peringatan, bukan galat SQL.
        $this->buatSesi(paksa: true)->assertSessionHas('warning');

        $this->assertSame(3, AttendanceSession::count());
    }

    /** Batasnya bisa disetel lewat konfigurasi, bukan angka mati di kode. */
    public function test_batas_mengikuti_konfigurasi(): void
    {
        config(['walikelas.max_sessions_per_day' => 1]);

        $this->buatSesi();
        $this->buatSesi(paksa: true)->assertSessionHas('warning');

        $this->assertSame(1, AttendanceSession::count());
    }

    /**
     * Kuota harian dihitung per hari, bukan seumur hidup kelas: sesi kemarin
     * tidak boleh memakan jatah hari ini.
     */
    public function test_sesi_hari_sebelumnya_tidak_memakan_kuota_hari_ini(): void
    {
        config(['walikelas.max_sessions_per_day' => 1]);

        AttendanceSession::create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'title' => 'Absensi kemarin',
            'session_date' => today()->subDay(),
            'sequence' => 1,
            'token' => 'token-kemarin-'.uniqid(),
            'pin_hash' => bcrypt('123456'),
            'expires_at' => now()->subDay(),
            'status' => 'submitted',
        ]);

        $this->buatSesi();

        $this->assertSame(1, AttendanceSession::whereDate('session_date', today())->count());
    }

    /** Kelas milik wali kelas lain tidak ikut menghabiskan kuota. */
    public function test_kuota_dihitung_per_kelas(): void
    {
        config(['walikelas.max_sessions_per_day' => 1]);

        $lain = Classroom::factory()->create(['user_id' => $this->user->id]);

        $this->buatSesi();
        $this->post(route('classes.attendance.store', $lain));

        $this->assertSame(2, AttendanceSession::whereDate('session_date', today())->count());
    }
}
