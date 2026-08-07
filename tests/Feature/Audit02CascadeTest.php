<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\OrganizationStructure;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Audit: satu kegagalan gateway pada queue sync membatalkan seluruh sisa
 * perulangan pembuatan sesi absensi terjadwal.
 */
class Audit02CascadeTest extends TestCase
{
    use RefreshDatabase;

    private function siapkanKelas(User $user, string $nama, string $phone): Classroom
    {
        $class = Classroom::factory()->create([
            'user_id' => $user->id,
            'name' => $nama,
            'auto_attendance' => true,
            'homeroom_wa' => $phone,
        ]);

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'phone' => $phone,
        ]);

        OrganizationStructure::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'student_id' => $student->id,
            'role' => 'seksi_absensi',
        ]);

        Schedule::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'day_of_week' => 1, // Senin
            'subject' => 'Matematika',
            'start_time' => '07:00',
            'end_time' => '15:00',
        ]);

        return $class;
    }

    public function test_audit_satu_gateway_gagal_membatalkan_semua_kelas(): void
    {
        config(['walikelas.whatsapp.driver' => 'log']);

        $user = User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'wa_session_status' => 'connected',
            'wa_connected_at' => now(),
        ]);

        $kelasA = $this->siapkanKelas($user, 'Kelas A', '6285700000001');
        $kelasB = $this->siapkanKelas($user, 'Kelas B', '6285700000002');
        $kelasC = $this->siapkanKelas($user, 'Kelas C', '6285700000003');

        // Gateway mati total.
        $this->app->instance(NotificationChannel::class, new class implements NotificationChannel {
            public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
            {
                return false;
            }
        });

        // Senin 06:55 -> jendela kirim mencakup jam pertama 07:00 (lead 10).
        Carbon::setTestNow(Carbon::parse('next monday 06:55', config('app.timezone')));

        $lempar = null;
        try {
            $this->artisan('walikelas:generate-attendance --lead=10')->run();
        } catch (\Throwable $e) {
            $lempar = $e;
        }

        $sesi = AttendanceSession::withoutTenant()->pluck('class_id')->all();

        fwrite(STDERR, sprintf(
            "CASCADE exception=%s msg=%s\n",
            $lempar ? get_class($lempar) : 'NONE',
            $lempar ? $lempar->getMessage() : '-'
        ));
        fwrite(STDERR, 'CASCADE kelas dijadwalkan = 3 ('.$kelasA->id.', '.$kelasB->id.', '.$kelasC->id.')'."\n");
        fwrite(STDERR, 'CASCADE sesi absensi yang benar-benar terbuat = '.count($sesi)
            .' -> class_id '.json_encode($sesi)."\n");

        Carbon::setTestNow();
        $this->assertTrue(true);
    }

    /** Pembanding: bila gateway sehat, ketiga kelas dapat sesi. */
    public function test_audit_pembanding_gateway_sehat(): void
    {
        config(['walikelas.whatsapp.driver' => 'log']);

        $user = User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'wa_session_status' => 'connected',
            'wa_connected_at' => now(),
        ]);

        $this->siapkanKelas($user, 'Kelas A', '6285700000001');
        $this->siapkanKelas($user, 'Kelas B', '6285700000002');
        $this->siapkanKelas($user, 'Kelas C', '6285700000003');

        $this->app->instance(NotificationChannel::class, new class implements NotificationChannel {
            public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
            {
                return true;
            }
        });

        Carbon::setTestNow(Carbon::parse('next monday 06:55', config('app.timezone')));
        $this->artisan('walikelas:generate-attendance --lead=10')->run();

        fwrite(STDERR, 'CASCADE-SEHAT sesi terbuat = '
            .AttendanceSession::withoutTenant()->count()."\n");

        Carbon::setTestNow();
        $this->assertTrue(true);
    }
}
