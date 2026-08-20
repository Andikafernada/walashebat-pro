<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Holiday;
use App\Models\OrganizationStructure;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceSessionService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScheduledAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Queue::fake();

        $this->user = User::factory()->create([
            'whatsapp_number' => '6281234567890',
            // Satu nomor per guru: pengiriman hanya jalan bila nomornya tertaut.
            'wa_session_status' => 'connected',
            'wa_connected_at' => now(),
        ]);
        $this->class = Classroom::factory()->create([
            'user_id' => $this->user->id,
            'auto_attendance' => true,
            'homeroom_wa' => '6281234567890',
        ]);

        $this->tunjukSeksiAbsensi('6285700000001');
    }

    /** Penerima magic link adalah siswa yang menjabat Seksi Absensi. */
    private function tunjukSeksiAbsensi(?string $phone): Student
    {
        $student = Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'phone' => $phone,
        ]);

        OrganizationStructure::create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'student_id' => $student->id,
            'role' => 'seksi_absensi',
        ]);

        return $student;
    }

    /** Jadwal jam pertama hari itu yang menentukan waktu kirim, bukan jam tetap. */
    private function jadwalkan(int $day, string $start, string $end = '15:00'): Schedule
    {
        return Schedule::create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'day_of_week' => $day,
            'subject' => 'Pemrograman Web',
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    private function senin(string $time): Carbon
    {
        return Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek()
            ->setTimeFromTimeString($time);
    }

    public function test_membuat_satu_sesi_mengikuti_jam_pelajaran_pertama(): void
    {
        // Sistem blok: Senin baru mulai pukul 09.30, bukan 07.00.
        $this->jadwalkan(1, '09:30');

        $this->travelTo($this->senin('09:20'));
        $this->artisan('walikelas:generate-attendance')->assertSuccessful();

        $this->assertSame(1, AttendanceSession::withoutTenant()->count());

        // Satu pesan saja, dikirim DARI nomor wali kelas KE Seksi Absensi.
        Queue::assertPushed(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return $job->from === '6281234567890' && $job->to === '6285700000001';
        });
    }

    public function test_tetap_satu_pesan_meski_ada_lima_mata_pelajaran(): void
    {
        // Lima mapel di hari yang sama; jam pertama 09.30.
        $this->jadwalkan(1, '09:30', '11:00');
        $this->jadwalkan(1, '11:15', '12:30');
        $this->jadwalkan(1, '13:00', '14:00');
        $this->jadwalkan(1, '14:00', '15:00');
        $this->jadwalkan(1, '15:00', '16:00');

        $this->travelTo($this->senin('09:25'));
        $this->artisan('walikelas:generate-attendance')->assertSuccessful();

        $this->assertSame(1, AttendanceSession::withoutTenant()->count());
        Queue::assertPushed(SendWhatsAppMessage::class, 1);
    }

    public function test_tanpa_penerima_sama_sekali_ditandai_gagal(): void
    {
        // Tidak ada Seksi Absensi, Ketua Kelas, maupun nomor cadangan kelas.
        OrganizationStructure::withoutTenant()->where('class_id', $this->class->id)->delete();
        $this->class->update(['homeroom_wa' => null]);
        $this->jadwalkan(1, '09:30');

        $this->travelTo($this->senin('09:25'));
        $this->artisan('walikelas:generate-attendance')->assertSuccessful();

        $session = AttendanceSession::withoutTenant()->first();

        $this->assertNotNull($session);
        $this->assertSame('failed', $session->delivery_status);
        $this->assertStringContainsString('Seksi Absensi', $session->delivery_error);
        Queue::assertNothingPushed();
    }

    public function test_tanpa_seksi_absensi_jatuh_ke_nomor_cadangan_kelas(): void
    {
        /*
         * Perilaku ini DISENGAJA: di awal tahun ajaran struktur organisasi
         * sering belum lengkap. Daripada absensi berhenti total, sistem
         * memakai nomor cadangan yang diisi wali kelas.
         */
        OrganizationStructure::withoutTenant()->where('class_id', $this->class->id)->delete();
        $this->jadwalkan(1, '09:30');

        $this->travelTo($this->senin('09:25'));
        $this->artisan('walikelas:generate-attendance')->assertSuccessful();

        $session = AttendanceSession::withoutTenant()->first();

        $this->assertSame('pending', $session->delivery_status);
        Queue::assertPushed(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return $job->to === '6281234567890';
        });
    }

    public function test_tidak_mengirim_di_luar_jendela_waktu(): void
    {
        $this->jadwalkan(1, '09:30');

        // Pukul 07.00 tidak lagi relevan bila jam pertama 09.30.
        $this->travelTo($this->senin('07:00'));
        $this->artisan('walikelas:generate-attendance')->assertSuccessful();

        $this->assertSame(0, AttendanceSession::withoutTenant()->count());
    }

    /**
     * Sebelumnya hanya QueryException (tabrakan unique) yang tertahan per
     * kelas -- galat lain di jalur pembuatan sesi (di sini: dispatchMagicLink,
     * yang dulu berada DI LUAR blok try) menghentikan seluruh perulangan.
     * Kelas kedua yang antre di belakang kelas bermasalah tidak boleh ikut
     * kehilangan sesinya.
     */
    public function test_kelas_lain_tetap_terlayani_walau_satu_kelas_gagal_dengan_galat_non_query(): void
    {
        $kelasBermasalah = Classroom::factory()->create([
            'user_id' => $this->user->id, 'auto_attendance' => true, 'homeroom_wa' => '6289999999999',
        ]);
        Schedule::create([
            'user_id' => $this->user->id, 'class_id' => $kelasBermasalah->id,
            'day_of_week' => 1, 'subject' => 'Matematika', 'start_time' => '09:30', 'end_time' => '15:00',
        ]);

        $this->jadwalkan(1, '09:30');

        Log::spy();

        $this->partialMock(AttendanceSessionService::class, function ($mock) use ($kelasBermasalah) {
            $mock->shouldReceive('dispatchMagicLink')
                ->withArgs(fn (AttendanceSession $session) => $session->class_id === $kelasBermasalah->id)
                ->andThrow(new \RuntimeException('Galat tak terduga simulasi test'));

            $mock->shouldReceive('dispatchMagicLink')->passthru();
        });

        $this->travelTo($this->senin('09:25'));
        $this->artisan('walikelas:generate-attendance')->assertSuccessful();

        // Sesi tetap dibuat untuk KEDUA kelas -- create() sendiri tidak gagal.
        $this->assertSame(2, AttendanceSession::withoutTenant()->count());

        // Hanya kelas yang sehat yang benar-benar mengirim pesan.
        Queue::assertPushed(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return $job->to === '6285700000001';
        });
        Queue::assertPushed(SendWhatsAppMessage::class, 1);

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($message) => str_contains($message, 'galat tak terduga'))
            ->once();
    }

    public function test_hanya_satu_sesi_per_hari_meski_dijalankan_berkali_kali(): void
    {
        $this->jadwalkan(1, '09:30');

        $this->travelTo($this->senin('09:25'));
        $this->artisan('walikelas:generate-attendance')->assertSuccessful();
        $this->artisan('walikelas:generate-attendance')->assertSuccessful();
        $this->artisan('walikelas:generate-attendance')->assertSuccessful();

        $this->assertSame(1, AttendanceSession::withoutTenant()->count());
    }

    public function test_hari_libur_dilewati(): void
    {
        $this->jadwalkan(1, '09:30');
        $senin = $this->senin('09:25');

        Holiday::create([
            'user_id' => $this->user->id,
            'start_date' => $senin->toDateString(),
            'end_date' => $senin->toDateString(),
            'description' => 'Libur sekolah',
        ]);

        $this->travelTo($senin);
        $this->artisan('walikelas:generate-attendance')->assertSuccessful();

        $this->assertSame(0, AttendanceSession::withoutTenant()->count());
        Queue::assertNothingPushed();
    }

    public function test_hari_tanpa_jadwal_tidak_menghasilkan_sesi(): void
    {
        // Hanya ada jadwal Selasa; Senin kosong (khas sistem blok).
        $this->jadwalkan(2, '09:30');

        $this->travelTo($this->senin('09:25'));
        $this->artisan('walikelas:generate-attendance')->assertSuccessful();

        $this->assertSame(0, AttendanceSession::withoutTenant()->count());
    }

    public function test_whatsapp_belum_tersambung_ditandai_gagal(): void
    {
        $this->user->update(['wa_session_status' => 'disconnected']);
        $this->jadwalkan(1, '09:30');

        $this->travelTo($this->senin('09:25'));
        $this->artisan('walikelas:generate-attendance')->assertSuccessful();

        $session = AttendanceSession::withoutTenant()->first();

        $this->assertNotNull($session);
        $this->assertSame('failed', $session->delivery_status);
        $this->assertStringContainsString('belum tersambung', $session->delivery_error);
        Queue::assertNothingPushed();
    }

    public function test_tanpa_nomor_wali_kelas_ditandai_gagal_bukan_senyap(): void
    {
        $this->class->update(['homeroom_wa' => null]);
        $this->user->update(['whatsapp_number' => null]);
        $this->jadwalkan(1, '09:30');

        $this->travelTo($this->senin('09:25'));
        $this->artisan('walikelas:generate-attendance')->assertSuccessful();

        $session = AttendanceSession::withoutTenant()->first();

        $this->assertNotNull($session);
        $this->assertSame('failed', $session->delivery_status);
        Queue::assertNothingPushed();
    }
}
