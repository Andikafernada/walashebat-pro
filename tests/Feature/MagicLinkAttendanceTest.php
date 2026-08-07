<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceSessionService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MagicLinkAttendanceTest extends TestCase
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
    }

    /**
     * @return array{0: \App\Models\AttendanceSession, 1: string, 2: \Illuminate\Database\Eloquent\Collection}
     */
    private function buatSesi(int $jumlahSiswa = 3): array
    {
        $students = Student::factory()->count($jumlahSiswa)->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
        ]);

        $this->actingAs($this->user);
        ['session' => $session, 'pin' => $pin] = app(AttendanceSessionService::class)->create($this->class);
        auth()->logout();

        return [$session, $pin, $students];
    }

    public function test_pin_salah_ditolak_dan_pin_benar_membuka_roster(): void
    {
        [$session, $pin] = $this->buatSesi();

        $this->post(route('magic.verify', $session->token), ['pin' => '000000'])
            ->assertSessionHasErrors('pin');

        // PIN benar mengantar ke halaman roster lewat redirect, bukan merender
        // roster sebagai balasan POST.
        $this->post(route('magic.verify', $session->token), ['pin' => $pin])
            ->assertRedirect(route('magic.roster', $session->token));

        $this->get(route('magic.roster', $session->token))
            ->assertOk()
            ->assertSee('Kirim absensi');
    }

    /**
     * REGRESI: form roster pernah dikirim tanpa token CSRF, dan setiap kiriman
     * petugas ditolak sebagai 419 Page Expired tepat setelah seluruh siswa
     * ditandai. Middleware CSRF selalu dilewati di dalam pengujian, jadi yang
     * diperiksa di sini adalah keberadaan token pada HTML yang dirender.
     */
    public function test_form_roster_menyertakan_token_csrf(): void
    {
        [$session, $pin] = $this->buatSesi();

        $this->post(route('magic.verify', $session->token), ['pin' => $pin]);

        $html = $this->get(route('magic.roster', $session->token))->assertOk()->getContent();

        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString(csrf_token(), $html);
    }

    public function test_roster_tidak_bisa_dibuka_tanpa_verifikasi_pin(): void
    {
        [$session] = $this->buatSesi();

        $this->get(route('magic.roster', $session->token))
            ->assertRedirect(route('magic.show', $session->token));
    }

    public function test_submit_menyimpan_kehadiran_dan_menutup_sesi(): void
    {
        [$session, $pin, $students] = $this->buatSesi(3);

        $this->post(route('magic.verify', $session->token), ['pin' => $pin]);

        $kehadiran = [
            $students[0]->id => 'hadir',
            $students[1]->id => 'sakit',
            $students[2]->id => 'alfa',
        ];

        $this->post(route('magic.submit', $session->token), [
            'attendance' => $kehadiran,
            'notes' => [$students[1]->id => 'Surat dokter'],
        ])->assertRedirect(route('magic.done', $session->token));

        $this->assertSame('submitted', $session->fresh()->status);
        $this->assertSame(3, Attendance::withoutTenant()->where('attendance_session_id', $session->id)->count());

        $sakit = Attendance::withoutTenant()
            ->where('attendance_session_id', $session->id)
            ->where('student_id', $students[1]->id)
            ->first();

        $this->assertSame('sakit', $sakit->status);
        $this->assertSame('Surat dokter', $sakit->note);
        // user_id diisi dari sesi, bukan dari auth() yang memang kosong di sini.
        $this->assertSame($this->user->id, $sakit->user_id);
    }

    /** Tautan yang sudah dipakai mengarah ke halaman selesai, bukan ke galat. */
    public function test_sesi_yang_sudah_terkirim_diarahkan_ke_halaman_selesai(): void
    {
        [$session, $pin, $students] = $this->buatSesi(1);

        $this->post(route('magic.verify', $session->token), ['pin' => $pin]);
        $this->post(route('magic.submit', $session->token), [
            'attendance' => [$students[0]->id => 'hadir'],
        ]);

        $this->get(route('magic.show', $session->token))
            ->assertRedirect(route('magic.done', $session->token));

        $this->get(route('magic.done', $session->token))
            ->assertOk()
            ->assertSee('Absensi terkirim');
    }

    /**
     * Sesi verifikasi yang hangus TIDAK boleh berujung 403. Di WebView WhatsApp
     * halaman galat mentah tidak memberi petugas jalan keluar apa pun.
     */
    public function test_submit_tanpa_verifikasi_diarahkan_bukan_ditolak_403(): void
    {
        [$session, , $students] = $this->buatSesi(1);

        $this->post(route('magic.submit', $session->token), [
            'attendance' => [$students[0]->id => 'hadir'],
        ])->assertRedirect(route('magic.show', $session->token));

        $this->assertSame('open', $session->fresh()->status);
    }

    /** Kiriman yang belum lengkap ditolak, dan sesinya tidak ikut terkunci. */
    public function test_submit_yang_kurang_lengkap_ditolak_dan_sesi_tetap_terbuka(): void
    {
        [$session, $pin, $students] = $this->buatSesi(3);

        $this->post(route('magic.verify', $session->token), ['pin' => $pin]);

        $this->post(route('magic.submit', $session->token), [
            'attendance' => [$students[0]->id => 'hadir'],
        ])->assertSessionHasErrors('attendance');

        $this->assertSame('open', $session->fresh()->status);
        $this->assertSame(0, Attendance::withoutTenant()->where('attendance_session_id', $session->id)->count());

        // Penanda lolos PIN tidak ikut hilang: petugas cukup memperbaiki isian.
        $this->get(route('magic.roster', $session->token))->assertOk();
    }

    /** Siswa dari kelas lain tidak bisa diselipkan ke dalam kiriman. */
    public function test_id_siswa_kelas_lain_tidak_diterima(): void
    {
        [$session, $pin, $students] = $this->buatSesi(2);

        $lain = Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => Classroom::factory()->create(['user_id' => $this->user->id])->id,
        ]);

        $this->post(route('magic.verify', $session->token), ['pin' => $pin]);

        $this->post(route('magic.submit', $session->token), [
            'attendance' => [
                $students[0]->id => 'hadir',
                $lain->id => 'hadir',
            ],
        ])->assertSessionHasErrors('attendance');

        $this->assertSame(0, Attendance::withoutTenant()->count());
    }

    /** PIN yang disalin dari WhatsApp sering membawa spasi dan tanda bintang. */
    public function test_pin_dengan_spasi_dan_tanda_baca_tetap_diterima(): void
    {
        [$session, $pin] = $this->buatSesi();

        $this->post(route('magic.verify', $session->token), ['pin' => ' *'.$pin.'* '])
            ->assertRedirect(route('magic.roster', $session->token));
    }

    public function test_sesi_kedaluwarsa_tidak_bisa_diisi(): void
    {
        [$session] = $this->buatSesi();

        $session->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->get(route('magic.show', $session->token))->assertSee('kedaluwarsa');
    }

    /** Kelas tanpa siswa aktif tidak boleh menghasilkan sesi terkunci kosong. */
    public function test_kelas_tanpa_siswa_tidak_bisa_disubmit(): void
    {
        $this->actingAs($this->user);
        ['session' => $session, 'pin' => $pin] = app(AttendanceSessionService::class)->create($this->class);
        auth()->logout();

        $this->post(route('magic.verify', $session->token), ['pin' => $pin]);

        $this->post(route('magic.submit', $session->token), ['attendance' => [1 => 'hadir']])
            ->assertRedirect(route('magic.show', $session->token));

        $this->assertSame('open', $session->fresh()->status);
    }
}
