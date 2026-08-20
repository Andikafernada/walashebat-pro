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
 * Kegagalan gateway pada satu kelas TIDAK BOLEH menghentikan kelas lain.
 *
 * GenerateScheduledAttendance dulu memanggil dispatchMagicLink() DI LUAR
 * try/catch mana pun (try di atasnya hanya menangkap QueryException). Begitu
 * panggilan itu melempar, seluruh kelas yang belum sempat diproses pagi itu
 * tidak mendapat sesi sama sekali — dan tidak ada satu pun galat yang sampai
 * ke wali kelasnya. Yang terlihat hanyalah absensi yang "tidak muncul hari
 * ini". Sudah diperbaiki: dispatchMagicLink() kini di dalam try/catch yang
 * sama, menangkap \Throwable apa pun di luar tabrakan unique.
 *
 * Di test QUEUE_CONNECTION=sync sehingga kegagalan gateway langsung melempar.
 * Di produksi antreannya redis, jadi kegagalan gateway sendiri tidak menembus
 * ke sini — tetapi kegagalan lain bisa (redis tidak terjangkau, payload gagal
 * diserialkan), dan akibatnya sama persis.
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

    /** Gateway mati total untuk SEMUA kelas — ketiganya tetap dapat sesi sendiri-sendiri. */
    public function test_kegagalan_gateway_tidak_menghentikan_sisa_kelas(): void
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

        $this->artisan('walikelas:generate-attendance --lead=10')->assertSuccessful();

        $sesi = AttendanceSession::withoutTenant()->pluck('class_id')->all();

        Carbon::setTestNow();

        $this->assertCount(3, $sesi, 'ketiga kelas tetap mendapat sesi walau gateway mati total');
        $this->assertContains($kelasA->id, $sesi);
        $this->assertContains($kelasB->id, $sesi);
        $this->assertContains($kelasC->id, $sesi);
    }

    /** Pembanding: bila gateway sehat, ketiga kelas dapat sesi. */
    public function test_gateway_sehat_memberi_sesi_ke_semua_kelas(): void
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

        $jumlah = AttendanceSession::withoutTenant()->count();

        Carbon::setTestNow();

        $this->assertSame(3, $jumlah, 'ketiga kelas harus mendapat sesi');
    }
}
