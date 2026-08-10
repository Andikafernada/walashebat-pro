<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Membatalkan sesi absensi hanya sah selama sesinya masih terbuka.
 *
 * Akibat membatalkan sesi yang sudah terisi jauh lebih besar daripada
 * kelihatannya: seluruh laporan menyaring `status != 'cancelled'` — rekap
 * kehadiran, analitik, ekspor, dan profil siswa. Satu klik keliru karena itu
 * melenyapkan absensi sehari penuh dari setiap rekap, tanpa galat dan tanpa
 * ada yang menyadarinya, sementara datanya sendiri masih utuh di basis data.
 */
class BatalkanSesiAbsensiTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private Classroom $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->guru = User::factory()->create();
        $this->kelas = Classroom::factory()->create(['user_id' => $this->guru->id]);
        $this->actingAs($this->guru);
    }

    private function sesi(string $status): AttendanceSession
    {
        return AttendanceSession::create([
            'user_id' => $this->guru->id,
            'class_id' => $this->kelas->id,
            'session_date' => today(),
            'sequence' => 1,
            'status' => $status,
            'token' => \Illuminate\Support\Str::random(40),
            'pin_hash' => bcrypt('123456'),
            'expires_at' => now()->addHours(2),
        ]);
    }

    public function test_sesi_terbuka_bisa_dibatalkan(): void
    {
        $sesi = $this->sesi('open');

        $this->patch(route('classes.attendance.cancel', [$this->kelas, $sesi]))
            ->assertRedirect();

        $this->assertSame('cancelled', $sesi->fresh()->status);
    }

    public function test_sesi_yang_sudah_terisi_tidak_bisa_dibatalkan(): void
    {
        $sesi = $this->sesi('submitted');

        $this->patch(route('classes.attendance.cancel', [$this->kelas, $sesi]))
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertSame('submitted', $sesi->fresh()->status,
            'Absensi yang sudah masuk tidak boleh lenyap dari laporan hanya karena tombol batal ditekan');
    }

    public function test_sesi_kelas_lain_tidak_bisa_disentuh(): void
    {
        $lain = User::factory()->create();
        $kelasLain = Classroom::factory()->create(['user_id' => $lain->id]);

        $sesi = AttendanceSession::create([
            'user_id' => $lain->id,
            'class_id' => $kelasLain->id,
            'session_date' => today(),
            'sequence' => 1,
            'status' => 'open',
            'token' => \Illuminate\Support\Str::random(40),
            'pin_hash' => bcrypt('123456'),
            'expires_at' => now()->addHours(2),
        ]);

        $this->patch(route('classes.attendance.cancel', [$this->kelas, $sesi]))
            ->assertNotFound();

        $this->assertSame('open', $sesi->fresh()->status);
    }
}
