<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\CharacterDimension;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Menutup sisa permukaan yang tidak tersapu SapuRouteTulisTest: API Sanctum,
 * seluruh area siswa dengan guard `student`, alur magic link publik, serta
 * ekspor Excel dan PDF.
 */
class SapuSisaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Queue::fake();

        $this->user = User::factory()->create();
        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);
        $this->student = Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'password' => Hash::make('rahasia123'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    /** API Sanctum: tidak boleh ada yang 500. */
    public function test_api_tidak_meledak(): void
    {
        Sanctum::actingAs($this->user);

        $subs = ['class' => $this->class->id];
        $rusak = [];
        $diuji = 0;

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (! str_starts_with($uri, 'api/')) {
                continue;
            }

            $metode = array_values(array_diff($route->methods(), ['HEAD', 'OPTIONS']));
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
                $rusak[] = "TIDAK TERUJI: $uri";

                continue;
            }

            $diuji++;
            $kode = $this->call($metode[0], '/'.ltrim($isi, '/'))->getStatusCode();
            if ($kode >= 500) {
                $rusak[] = "$metode[0] $uri -> HTTP $kode";
            }
        }

        fwrite(STDERR, "\n[SAPU-API] diuji=$diuji rusak=".count($rusak)."\n");
        foreach ($rusak as $r) {
            fwrite(STDERR, "  $r\n");
        }

        $this->assertSame([], $rusak);
    }

    /** Seluruh area siswa dengan guard `student`. */
    public function test_area_siswa_tidak_meledak(): void
    {
        $dimension = CharacterDimension::create([
            'code' => 'uji', 'name' => 'Dimensi Uji', 'name_en' => 'Test',
            'icon' => 'star', 'color' => 'indigo',
        ]);

        $this->actingAs($this->student, 'student');

        $subs = ['dimension' => $dimension->id, 'nis' => $this->student->nis ?? '12345'];
        $rusak = [];
        $diuji = 0;

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (! str_starts_with($uri, 'student/')) {
                continue;
            }
            // Logout mengakhiri sesi dan membuat sisa sapuan tak bermakna.
            if (str_contains($uri, 'logout')) {
                continue;
            }

            $metode = array_values(array_diff($route->methods(), ['HEAD', 'OPTIONS']));
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
                $rusak[] = "TIDAK TERUJI: $uri";

                continue;
            }

            $diuji++;
            $kode = $this->call($metode[0], '/'.ltrim($isi, '/'))->getStatusCode();
            if ($kode >= 500) {
                $rusak[] = "$metode[0] $uri -> HTTP $kode";
            }
        }

        fwrite(STDERR, "\n[SAPU-SISWA] diuji=$diuji rusak=".count($rusak)."\n");
        foreach ($rusak as $r) {
            fwrite(STDERR, "  $r\n");
        }

        $this->assertSame([], $rusak);
    }

    /** Alur magic link publik: buka → verifikasi PIN → kirim roster. */
    public function test_alur_magic_link_utuh(): void
    {
        $sesi = AttendanceSession::create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'session_date' => today(),
            'sequence' => 1,
            'token' => 'magic'.uniqid(),
            'pin_hash' => Hash::make('123456'),
            'expires_at' => now()->addHour(),
            'status' => 'open',
        ]);

        $this->get("/a/{$sesi->token}")->assertOk();

        $this->post("/a/{$sesi->token}/verify", ['pin' => '123456'])
            ->assertRedirect();

        $this->post("/a/{$sesi->token}/submit", [
            'attendance' => [$this->student->id => 'hadir'],
        ])->assertRedirect();

        $sesi->refresh();
        $this->assertSame('submitted', $sesi->status, 'Sesi harus tertutup setelah roster dikirim');
        $this->assertSame(1, $sesi->attendances()->count());
        $this->assertSame('hadir', $sesi->attendances()->first()->status);
    }

    /** PIN salah harus ditolak, bukan meloloskan. */
    public function test_magic_link_menolak_pin_salah(): void
    {
        $sesi = AttendanceSession::create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'session_date' => today(),
            'sequence' => 1,
            'token' => 'magic'.uniqid(),
            'pin_hash' => Hash::make('123456'),
            'expires_at' => now()->addHour(),
            'status' => 'open',
        ]);

        $this->post("/a/{$sesi->token}/verify", ['pin' => '999999'])
            ->assertSessionHasErrors();
    }

    /** Ekspor Excel dan PDF benar-benar menghasilkan berkas, bukan galat. */
    public function test_ekspor_dan_pdf_menghasilkan_berkas(): void
    {
        $this->actingAs($this->user);

        $target = [
            'classes.students.export' => [$this->class],
            'classes.students.template' => [$this->class],
            'classes.reports.full.pdf' => [$this->class],
            'classes.students.pdf' => [$this->class, $this->student],
        ];

        $rusak = [];
        foreach ($target as $nama => $param) {
            if (! Route::has($nama)) {
                $rusak[] = "ROUTE HILANG: $nama";

                continue;
            }

            $resp = $this->get(route($nama, $param));
            $kode = $resp->getStatusCode();
            $base = $resp->baseResponse;

            // Tiga bentuk respons berkas yang dipakai aplikasi ini punya cara
            // baca isi yang berbeda; menyamaratakannya membuat ekspor yang
            // sehat terbaca "kosong".
            $ukuran = match (true) {
                $base instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
                    => $base->getFile()->getSize(),
                $base instanceof \Symfony\Component\HttpFoundation\StreamedResponse
                    => strlen($resp->streamedContent()),
                default => strlen((string) $resp->getContent()),
            };

            if ($kode !== 200) {
                $rusak[] = "$nama -> HTTP $kode";
            } elseif ($ukuran < 100) {
                $rusak[] = "$nama -> berkas kosong ($ukuran byte)";
            } else {
                fwrite(STDERR, "  OK $nama -> $ukuran byte (".get_class($base).")\n");
            }
        }

        fwrite(STDERR, "\n[SAPU-EKSPOR] diuji=".count($target).' rusak='.count($rusak)."\n");
        foreach ($rusak as $r) {
            fwrite(STDERR, "  $r\n");
        }

        $this->assertSame([], $rusak);
    }
}
