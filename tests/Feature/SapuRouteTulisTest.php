<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Sapuan seluruh route tulis (POST/PUT/PATCH/DELETE).
 *
 * Tujuannya BUKAN memvalidasi logika bisnis tiap endpoint, melainkan
 * memastikan tidak ada yang meledak 500 sebelum sempat memvalidasi input —
 * kelas galat yang selama ini lolos karena jalurnya memang tidak pernah
 * dijalankan siapa pun (kolom salah nama, import kurang, method tidak ada).
 *
 * 422/302/403/404 dianggap SEHAT: route hidup dan menolak dengan benar.
 * 500 berarti ada yang patah.
 */
class SapuRouteTulisTest extends TestCase
{
    use RefreshDatabase;

    public function test_tidak_ada_route_tulis_yang_meledak(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Queue::fake();

        $user = User::factory()->create();
        $class = Classroom::factory()->create(['user_id' => $user->id]);
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'class_id' => $class->id,
        ]);
        $this->actingAs($user);

        $subs = [
            'class' => $class->id,
            'classroom' => $class->id,
            'student' => $student->id,
            'user' => $user->id,
            // Rute operator memakai {guru}; disapu sebagai teacher biasa, jadi
            // yang diharapkan 403 (role:admin) — cukup asal tidak 500.
            'guru' => $user->id,
        ] + $this->fixtureTambahan($user, $class, $student);

        $rusak = [];
        $diuji = 0;
        $dilewat = [];

        foreach (Route::getRoutes() as $route) {
            $metode = array_values(array_diff($route->methods(), ['GET', 'HEAD', 'OPTIONS']));
            if (! $metode) {
                continue;
            }

            $uri = $route->uri();

            // API pakai guard Sanctum, disapu terpisah.
            if (str_starts_with($uri, 'api/')) {
                continue;
            }

            $isi = $uri;
            $lewati = false;
            if (preg_match_all('/\{([a-zA-Z_]+)\??\}/', $uri, $m)) {
                foreach ($m[1] as $p) {
                    if (! isset($subs[$p])) {
                        $lewati = true;
                        break;
                    }
                    $isi = preg_replace('/\{'.$p.'\??\}/', (string) $subs[$p], $isi);
                }
            }
            if ($lewati) {
                $dilewat[] = $uri;

                continue;
            }

            $diuji++;
            try {
                $resp = $this->call($metode[0], '/'.ltrim($isi, '/'));
                $kode = $resp->getStatusCode();
            } catch (\Throwable $e) {
                $kode = 500;
                $rusak[] = "$metode[0] $uri -> ".get_class($e).': '.substr($e->getMessage(), 0, 120);

                continue;
            }

            if ($kode >= 500) {
                $rusak[] = "$metode[0] $uri -> HTTP $kode";
            }
        }

        fwrite(STDERR, "\n[SAPU-TULIS] diuji=$diuji dilewat=".count($dilewat)." rusak=".count($rusak)."\n");
        foreach ($dilewat as $d) {
            fwrite(STDERR, "  LEWAT: $d\n");
        }
        foreach ($rusak as $r) {
            fwrite(STDERR, "  RUSAK: $r\n");
        }

        $this->assertSame([], $rusak, 'Route tulis yang meledak 500');
        $this->assertSame([], $dilewat, 'Route tulis yang tidak sempat diuji');
    }

    /**
     * Dibuat langsung lewat DB, bukan factory: model-model ini belum punya
     * factory dan yang dibutuhkan sapuan ini hanya ID yang sah.
     */
    private function fixtureTambahan(User $user, Classroom $class, Student $student): array
    {
        $now = now();

        $notification = \App\Models\Notification::create([
            'user_id' => $user->id, 'type' => 'info', 'title' => 'Uji',
            'body' => 'Uji sapuan', 'icon' => 'bell', 'color' => 'indigo',
        ]);

        $holiday = \App\Models\Holiday::create([
            'user_id' => $user->id, 'start_date' => $now->toDateString(),
            'end_date' => $now->toDateString(), 'description' => 'Libur uji',
        ]);

        $violationType = \App\Models\ViolationType::create([
            'user_id' => $user->id, 'name' => 'Terlambat uji', 'points' => 5,
        ]);

        /*
         * Penilaian harian guru mapel. Wajib ada di sini: sapuan ini menolak
         * rute tulis yang tidak sempat diuji, sehingga setiap rute baru harus
         * punya fixture-nya — itulah yang membuat 500 pada rute baru tidak
         * bisa lolos diam-diam.
         */
        $assessment = \App\Models\Assessment::create([
            'user_id' => $user->id, 'class_id' => $class->id, 'mapel' => 'Matematika',
            'capaian_pembelajaran' => 'CP uji sapuan', 'assessment_date' => $now->toDateString(),
        ]);

        $schedule = \App\Models\Schedule::create([
            'user_id' => $user->id, 'class_id' => $class->id, 'day_of_week' => 1,
            'subject' => 'Matematika', 'start_time' => '07:00', 'end_time' => '08:00',
        ]);

        $organization = \App\Models\OrganizationStructure::create([
            'user_id' => $user->id, 'class_id' => $class->id,
            'student_id' => $student->id, 'role' => 'ketua', 'sort_order' => 1,
        ]);

        $violation = \App\Models\Violation::create([
            'user_id' => $user->id, 'class_id' => $class->id, 'student_id' => $student->id,
            'violation_type_id' => $violationType->id, 'points' => 5,
            'occurred_on' => $now->toDateString(),
        ]);

        $dimension = \App\Models\CharacterDimension::create([
            'code' => 'uji', 'name' => 'Dimensi Uji', 'name_en' => 'Test Dimension',
            'icon' => 'star', 'color' => 'indigo',
        ]);

        $record = \App\Models\CharacterRecord::create([
            'user_id' => $user->id, 'student_id' => $student->id, 'class_id' => $class->id,
            'character_dimension_id' => $dimension->id, 'type' => 'positive',
            'score' => 5, 'title' => 'Catatan uji', 'record_date' => $now->toDateString(),
        ]);

        $reflection = \App\Models\CharacterReflection::create([
            'user_id' => $user->id, 'student_id' => $student->id, 'class_id' => $class->id,
            'character_dimension_id' => $dimension->id, 'period' => 'harian',
            'reflection_date' => $now->toDateString(),
        ]);

        $cashbook = \App\Models\CashBook::create([
            'user_id' => $user->id, 'class_id' => $class->id,
            'transaction_date' => $now->toDateString(), 'type' => 'in',
            'amount' => 10000, 'description' => 'Kas uji', 'balance_after' => 10000,
        ]);

        $session = \App\Models\AttendanceSession::create([
            'user_id' => $user->id, 'class_id' => $class->id,
            'session_date' => $now->toDateString(), 'sequence' => 1,
            'token' => 'sapu'.uniqid(), 'pin_hash' => bcrypt('123456'),
            'expires_at' => $now->copy()->addHour(), 'status' => 'open',
        ]);

        $proof = \App\Models\PaymentProof::create([
            'user_id' => $user->id, 'plan_type' => 'monthly', 'amount' => 19000,
            'proof_image' => 'payment_proofs/uji.jpg', 'bank_name' => 'BRI',
            'sender_name' => 'Penguji',
        ]);

        return [
            'notification' => $notification->id,
            'holiday' => $holiday->id,
            'type' => $violationType->id,
            'schedule' => $schedule->id,
            'assessment' => $assessment->id,
            'organizationStructure' => $organization->id,
            'violation' => $violation->id,
            'record' => $record->id,
            'reflection' => $reflection->id,
            'cashbook' => $cashbook->id,
            'attendanceSession' => $session->id,
            'proof' => $proof->id,
            'token' => $session->token,
            'nis' => $student->nis ?? '12345',
        ];
    }
}
