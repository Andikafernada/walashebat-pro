<?php

namespace Tests\Feature;

use App\Models\CharacterDimension;
use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Audit01KeamananTest extends TestCase
{
    use RefreshDatabase;

    private User $guruA;
    private User $guruB;
    private Classroom $kelasA;
    private Classroom $kelasB;
    private Student $siswaB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->guruA = User::factory()->create(['email' => 'a@a.id']);
        $this->guruB = User::factory()->create(['email' => 'b@b.id']);

        $this->kelasA = Classroom::factory()->create(['user_id' => $this->guruA->id, 'name' => 'KELAS A']);
        $this->kelasB = Classroom::factory()->create(['user_id' => $this->guruB->id, 'name' => 'KELAS B']);

        $this->siswaB = Student::factory()->create([
            'user_id' => $this->guruB->id,
            'class_id' => $this->kelasB->id,
            'name' => 'SISWA RAHASIA B',
            'nis' => '999999',
        ]);
    }

    /** API: daftar siswa kelas milik tenant lain. */
    public function test_api_students_kelas_orang_lain(): void
    {
        Sanctum::actingAs($this->guruA);
        $r = $this->getJson('/api/v1/classes/'.$this->kelasB->id.'/students');
        fwrite(STDERR, "\n[API students kelas B oleh guru A] status=".$r->status()." body=".$r->getContent()."\n");
        $this->assertTrue(true);
    }

    /** API: rekap absensi kelas milik tenant lain. */
    public function test_api_attendance_today_kelas_orang_lain(): void
    {
        Sanctum::actingAs($this->guruA);
        $r = $this->getJson('/api/v1/classes/'.$this->kelasB->id.'/attendance/today');
        fwrite(STDERR, "\n[API attendance today kelas B oleh guru A] status=".$r->status()." body=".$r->getContent()."\n");
        $this->assertTrue(true);
    }

    /** Sanctum abilities: token hanya punya read+attendance:verify. Apakah ditegakkan? */
    public function test_token_ability_ditegakkan(): void
    {
        Sanctum::actingAs($this->guruB, ['nonsense-ability']);
        $r = $this->getJson('/api/v1/classes');
        fwrite(STDERR, "\n[API classes dgn ability ngawur] status=".$r->status()."\n");
        $this->assertTrue(true);
    }

    /** Form biodata publik: bisakah orang luar melihat roster kelas tenant lain? */
    public function test_form_biodata_publik_membocorkan_roster(): void
    {
        $r = $this->get('/isi-biodata/'.$this->kelasB->id);
        fwrite(STDERR, "\n[Form biodata publik kelas B] status=".$r->status()
            ." memuat_nama_siswa=".(str_contains($r->getContent() ?: '', 'SISWA RAHASIA B') ? 'YA' : 'tidak')
            ." memuat_nis=".(str_contains($r->getContent() ?: '', '999999') ? 'YA' : 'tidak')."\n");
        $this->assertTrue(true);
    }

    /** Form biodata publik: bisakah orang luar MENGUBAH biodata siswa tenant lain? */
    public function test_form_biodata_publik_menimpa_data_siswa(): void
    {
        $r = $this->post('/isi-biodata/'.$this->kelasB->id, [
            'student_id' => $this->siswaB->id,
            'name' => 'DIUBAH PENYERANG',
            'gender' => 'L',
            'parent_phone' => '08123456789',
        ]);
        $this->siswaB->refresh();
        fwrite(STDERR, "\n[POST biodata publik timpa siswa B] status=".$r->status()
            ." nama_sekarang=".$this->siswaB->name."\n");
        $this->assertTrue(true);
    }

    /** Form biodata publik: sisip siswa baru ke kelas tenant lain. */
    public function test_form_biodata_publik_sisip_siswa(): void
    {
        $r = $this->post('/isi-biodata/'.$this->kelasB->id, [
            'name' => 'SISIPAN PENYERANG',
            'gender' => 'L',
            'parent_phone' => '08123456789',
        ]);
        $jml = Student::withoutTenant()->where('class_id', $this->kelasB->id)->count();
        fwrite(STDERR, "\n[POST biodata publik sisip siswa] status=".$r->status()." jumlah_siswa_kelas_B={$jml}\n");
        $this->assertTrue(true);
    }

    /** Refleksi publik: student_id lintas tenant (tidak dibatasi ke $class). */
    public function test_refleksi_publik_student_id_lintas_kelas(): void
    {
        $dim = CharacterDimension::create([
            'code' => 'X', 'name' => 'Dim', 'is_active' => true, 'sort_order' => 1,
        ]);

        $r = $this->post('/refleksi-karakter/'.$this->kelasA->id, [
            'student_id' => $this->siswaB->id, // milik tenant B, dikirim ke kelas A
            'character_dimension_id' => $dim->id,
            'self_rating' => 5,
            'what_went_well' => 'x',
            'what_to_improve' => 'y',
            'action_plan' => 'z',
        ]);

        $ref = CharacterReflection::withoutTenant()->where('student_id', $this->siswaB->id)->first();
        fwrite(STDERR, "\n[POST refleksi publik student_id lintas tenant] status=".$r->status()
            ." tersimpan=".($ref ? 'YA (user_id='.$ref->user_id.', class_id='.$ref->class_id.')' : 'tidak')."\n");
        $this->assertTrue(true);
    }

    /** NIS tidak unik global: login siswa memakai ->first() tanpa batas tenant. */
    public function test_login_siswa_nis_bentrok_antar_tenant(): void
    {
        $siswaA = Student::factory()->create([
            'user_id' => $this->guruA->id,
            'class_id' => $this->kelasA->id,
            'name' => 'SISWA A',
            'nis' => '999999', // NIS sama dgn siswa B
            'password' => Hash::make('rahasiaA'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $this->siswaB->update([
            'password' => Hash::make('rahasiaB'),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $r = $this->post('/student/login', ['nis' => '999999', 'password' => 'rahasiaA']);
        $login = auth('student')->id();
        fwrite(STDERR, "\n[Login siswa NIS bentrok] siswaA_id={$siswaA->id} siswaB_id={$this->siswaB->id}"
            ." status=".$r->status()." login_sebagai=".var_export($login, true)."\n");

        auth('student')->logout();
        $r2 = $this->post('/student/login', ['nis' => '999999', 'password' => 'rahasiaB']);
        fwrite(STDERR, "[Login siswa B dgn passwordnya sendiri] status=".$r2->status()
            ." login_sebagai=".var_export(auth('student')->id(), true)."\n");
        $this->assertTrue(true);
    }

    /** shareBiodataWa: hanya middleware auth (tanpa auth.tenant). Kelas orang lain? */
    public function test_share_biodata_wa_kelas_orang_lain(): void
    {
        $this->actingAs($this->guruA);
        $r = $this->post('/classes/'.$this->kelasB->id.'/share-biodata-wa', ['group_id' => '628999@g.us']);
        fwrite(STDERR, "\n[shareBiodataWa kelas B oleh guru A] status=".$r->status()."\n");
        $this->assertTrue(true);
    }

    /** Rantai pengambilalihan: ubah parent_phone lewat form publik -> OTP ke penyerang. */
    public function test_takeover_siswa_via_form_publik(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $this->siswaB->update(['parent_phone' => '628111111111', 'is_active' => true]);

        $terkirim = [];
        $this->app->bind(\App\Support\Contracts\NotificationChannel::class, function () use (&$terkirim) {
            return new class($terkirim) implements \App\Support\Contracts\NotificationChannel {
                public function __construct(public &$log) {}
                public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
                { $this->log[] = [$to, $message]; return true; }
            };
        });

        // Langkah 1: penyerang menimpa parent_phone siswa milik tenant lain.
        $this->post('/isi-biodata/'.$this->kelasB->id, [
            'student_id' => $this->siswaB->id,
            'name' => $this->siswaB->name,
            'gender' => 'L',
            'parent_phone' => '628999999999',   // nomor penyerang
        ]);
        $this->siswaB->refresh();
        fwrite(STDERR, "\n[Takeover 1] parent_phone sekarang=".$this->siswaB->parent_phone."\n");

        // Langkah 2: minta OTP reset kata sandi siswa.
        $this->post('/student/lupa-password', ['nis' => $this->siswaB->nis]);
        fwrite(STDERR, "[Takeover 2] OTP dikirim ke: ".json_encode(array_column($terkirim, 0))."\n");
        if ($terkirim) {
            preg_match('/\*(\d{6})\*/', $terkirim[0][1], $m);
            $otp = $m[1] ?? null;
            fwrite(STDERR, "[Takeover 3] OTP terbaca penyerang = ".var_export($otp, true)."\n");

            $r = $this->post('/student/otp/'.$this->siswaB->nis, [
                'otp' => $otp,
                'password' => 'PasswordPenyerang9!',
                'password_confirmation' => 'PasswordPenyerang9!',
            ]);
            fwrite(STDERR, "[Takeover 4] reset status=".$r->status()."\n");

            $r2 = $this->post('/student/login', ['nis' => $this->siswaB->nis, 'password' => 'PasswordPenyerang9!']);
            fwrite(STDERR, "[Takeover 5] login sebagai siswa tenant lain = ".var_export(auth('student')->id(), true)
                ." (target id=".$this->siswaB->id.")\n");
        }
        $this->assertTrue(true);
    }

    /** Refleksi publik: user_id tidak fillable -> refleksi jadi yatim, tak terlihat wali kelas. */
    public function test_refleksi_publik_user_id_null(): void
    {
        $dim = CharacterDimension::create(['code' => 'Y', 'name' => 'Dim', 'is_active' => true, 'sort_order' => 1]);
        $siswaA = Student::factory()->create(['user_id' => $this->guruA->id, 'class_id' => $this->kelasA->id]);

        $rr = $this->post('/refleksi-karakter/'.$this->kelasA->id, [
            'student_id' => $siswaA->id,
            'character_dimension_id' => $dim->id,
            'self_rating' => 5,
            'what_went_well' => 'a', 'what_to_improve' => 'b', 'action_plan' => 'c',
        ]);

        fwrite(STDERR, "[Refleksi publik] status=".$rr->status()." jml=".\Illuminate\Support\Facades\DB::table('character_reflections')->count()." errors=".json_encode(session('errors') ? session('errors')->getBag('default')->all() : [])."\n");
        $raw = \Illuminate\Support\Facades\DB::table('character_reflections')->first();
        fwrite(STDERR, "\n[Refleksi publik] baris tersimpan user_id=".var_export($raw->user_id ?? 'NO-ROW', true)."\n");

        $this->actingAs($this->guruA);
        $terlihat = CharacterReflection::where('student_id', $siswaA->id)->count();
        fwrite(STDERR, "[Refleksi publik] terlihat oleh guru A (dgn TenantScope) = {$terlihat}\n");
        $this->assertTrue(true);
    }

    /** Sapu IDOR: guru A menembak objek milik guru B lewat rute anak kelas. */
    public function test_sapu_idor_objek_tenant_lain(): void
    {
        $g = $this->guruB;
        $k = $this->kelasB;

        $violationType = \App\Models\ViolationType::withoutTenant()->create([
            'user_id' => $g->id, 'name' => 'VT', 'points' => 5, 'category' => 'ringan',
        ]);
        $violation = \App\Models\Violation::withoutTenant()->create([
            'user_id' => $g->id, 'class_id' => $k->id, 'student_id' => $this->siswaB->id,
            'violation_type_id' => $violationType->id, 'points' => 5,
            'occurred_on' => now()->toDateString(),
        ]);
        $cashbook = \App\Models\CashBook::withoutTenant()->create([
            'user_id' => $g->id, 'class_id' => $k->id, 'type' => 'in',
            'amount' => 1000, 'description' => 'x', 'transaction_date' => now()->toDateString(),
        ]);
        $schedule = \App\Models\Schedule::withoutTenant()->create([
            'user_id' => $g->id, 'class_id' => $k->id, 'day_of_week' => 1,
            'subject' => 'MTK', 'start_time' => '07:00', 'end_time' => '08:00',
        ]);
        $org = \App\Models\OrganizationStructure::withoutTenant()->create([
            'user_id' => $g->id, 'class_id' => $k->id, 'role' => 'Ketua',
            'student_id' => $this->siswaB->id,
        ]);
        $holiday = \App\Models\Holiday::create([
            'user_id' => $g->id, 'start_date' => '2026-12-25', 'end_date' => '2026-12-25', 'description' => 'Natal',
        ]);
        $dim = CharacterDimension::create(['code' => 'Z', 'name' => 'D', 'is_active' => true, 'sort_order' => 1]);
        $record = \App\Models\CharacterRecord::withoutTenant()->create([
            'user_id' => $g->id, 'class_id' => $k->id, 'student_id' => $this->siswaB->id,
            'character_dimension_id' => $dim->id, 'type' => 'positive', 'score' => 3,
            'title' => 'T', 'record_date' => now()->toDateString(), 'recorded_by' => $g->id,
        ]);
        $refl = \App\Models\CharacterReflection::withoutTenant()->create([
            'user_id' => $g->id, 'class_id' => $k->id, 'student_id' => $this->siswaB->id,
            'character_dimension_id' => $dim->id, 'period' => 'daily',
            'reflection_date' => now()->toDateString(), 'status' => 'submitted',
        ]);

        $this->actingAs($this->guruA);

        $probes = [
            ['DELETE', '/classes/'.$k->id.'/violations/'.$violation->id, []],
            ['DELETE', '/classes/'.$k->id.'/cashbook/'.$cashbook->id, []],
            ['DELETE', '/classes/'.$k->id.'/schedules/'.$schedule->id, []],
            ['DELETE', '/classes/'.$k->id.'/organization/'.$org->id, []],
            ['DELETE', '/libur/'.$holiday->id, []],
            ['DELETE', '/violation-types/'.$violationType->id, []],
            ['PATCH',  '/classes/'.$k->id.'/karakter/catatan/'.$record->id, ['type' => 'positive', 'score' => 1, 'title' => 'x']],
            ['DELETE', '/classes/'.$k->id.'/karakter/catatan/'.$record->id, []],
            ['POST',   '/classes/'.$k->id.'/karakter/catatan/'.$record->id.'/konfirmasi', []],
            ['POST',   '/classes/'.$k->id.'/karakter/refleksi/'.$refl->id.'/feedback', ['teacher_feedback' => 'x']],
            ['GET',    '/classes/'.$k->id.'/karakter/'.$this->siswaB->id, []],
            ['GET',    '/classes/'.$k->id.'/karakter/'.$this->siswaB->id.'/catatan/'.$record->id, []],
            ['GET',    '/classes/'.$k->id.'/ekspor/karakter/'.$this->siswaB->id.'/pdf', []],
        ];

        fwrite(STDERR, "\n--- SAPU IDOR (guru A -> objek guru B) ---\n");
        foreach ($probes as [$m, $u, $d]) {
            $r = $this->call($m, $u, $d);
            fwrite(STDERR, str_pad($m.' '.$u, 70).' => '.$r->status()."\n");
        }

        fwrite(STDERR, "violation masih ada: ".(\App\Models\Violation::withoutTenant()->find($violation->id) ? 'ya' : 'TIDAK/TERHAPUS')."\n");
        fwrite(STDERR, "cashbook masih ada: ".(\App\Models\CashBook::withoutTenant()->find($cashbook->id) ? 'ya' : 'TIDAK/TERHAPUS')."\n");
        fwrite(STDERR, "record masih ada: ".(\App\Models\CharacterRecord::withoutTenant()->find($record->id) ? 'ya' : 'TIDAK/TERHAPUS')."\n");
        fwrite(STDERR, "holiday masih ada: ".(\App\Models\Holiday::find($holiday->id) ? 'ya' : 'TIDAK/TERHAPUS')."\n");
        $this->assertTrue(true);
    }

    /** Rute anak kelas dgn objek MILIK SENDIRI tapi KELAS LAIN (dalam tenant yg sama). */
    public function test_lintas_kelas_dalam_tenant_sama(): void
    {
        $kelasA2 = Classroom::factory()->create(['user_id' => $this->guruA->id, 'name' => 'KELAS A2']);
        $siswaA2 = Student::factory()->create(['user_id' => $this->guruA->id, 'class_id' => $kelasA2->id]);
        $dim = CharacterDimension::create(['code' => 'W', 'name' => 'D', 'is_active' => true, 'sort_order' => 1]);
        $record = \App\Models\CharacterRecord::withoutTenant()->create([
            'user_id' => $this->guruA->id, 'class_id' => $kelasA2->id, 'student_id' => $siswaA2->id,
            'character_dimension_id' => $dim->id, 'type' => 'positive', 'score' => 3,
            'title' => 'MILIK KELAS A2', 'record_date' => now()->toDateString(), 'recorded_by' => $this->guruA->id,
        ]);

        $this->actingAs($this->guruA);
        $r = $this->patch('/classes/'.$this->kelasA->id.'/karakter/catatan/'.$record->id,
            ['type' => 'negative', 'score' => -5, 'title' => 'DIUBAH LEWAT KELAS LAIN']);
        $record->refresh();
        fwrite(STDERR, "\n[PATCH record kelas A2 lewat URL kelas A] status=".$r->status()
            ." judul_sekarang=".$record->title."\n");
        $this->assertTrue(true);
    }

    /** Jalur normal (kelas yang BENAR): apakah edit catatan karakter berfungsi? */
    public function test_edit_catatan_karakter_jalur_normal(): void
    {
        $siswaA = Student::factory()->create(['user_id' => $this->guruA->id, 'class_id' => $this->kelasA->id]);
        $dim = CharacterDimension::create(['code' => 'N', 'name' => 'D', 'is_active' => true, 'sort_order' => 1]);
        $record = \App\Models\CharacterRecord::withoutTenant()->create([
            'user_id' => $this->guruA->id, 'class_id' => $this->kelasA->id, 'student_id' => $siswaA->id,
            'character_dimension_id' => $dim->id, 'type' => 'positive', 'score' => 3,
            'title' => 'ASLI', 'record_date' => now()->toDateString(), 'recorded_by' => $this->guruA->id,
        ]);
        $refl = \App\Models\CharacterReflection::withoutTenant()->create([
            'user_id' => $this->guruA->id, 'class_id' => $this->kelasA->id, 'student_id' => $siswaA->id,
            'character_dimension_id' => $dim->id, 'period' => 'daily',
            'reflection_date' => now()->toDateString(), 'status' => 'submitted',
        ]);

        $this->actingAs($this->guruA);
        $u = '/classes/'.$this->kelasA->id.'/karakter/catatan/'.$record->id;

        fwrite(STDERR, "\n--- JALUR NORMAL (pemilik sendiri, kelas benar) ---\n");
        foreach ([
            ['PATCH', $u, ['type' => 'positive', 'score' => 1, 'title' => 'DIUBAH']],
            ['POST', $u.'/konfirmasi', []],
            ['DELETE', $u, []],
            ['POST', '/classes/'.$this->kelasA->id.'/karakter/refleksi/'.$refl->id.'/feedback', ['teacher_feedback' => 'bagus']],
            ['GET', '/classes/'.$this->kelasA->id.'/karakter/'.$siswaA->id.'/catatan/'.$record->id, []],
        ] as [$m, $uu, $d]) {
            $r = $this->call($m, $uu, $d);
            fwrite(STDERR, str_pad($m.' '.$uu, 62).' => '.$r->status()."\n");
        }

        // Ambil pesan galat sesungguhnya.
        $this->withoutExceptionHandling();
        try {
            $this->patch($u, ['type' => 'positive', 'score' => 1, 'title' => 'X']);
        } catch (\Throwable $e) {
            fwrite(STDERR, "EXCEPTION: ".get_class($e).": ".$e->getMessage()."\n");
        }
        $this->assertTrue(true);
    }

    /** PaymentProof: status/approved_by ada di $guarded -> update() dibuang diam-diam. */
    public function test_persetujuan_pembayaran_tidak_pernah_menutup_bukti(): void
    {
        $admin = User::factory()->create(['email' => 'admin@x.id']);
        $admin->forceFill(['role' => 'admin'])->save();

        $proof = \App\Models\PaymentProof::create([
            'user_id' => $this->guruB->id, 'plan_type' => 'monthly', 'amount' => 19000,
            'proof_image' => 'x.jpg', 'sender_name' => 'B',
        ]);

        $this->actingAs($admin);
        for ($i = 1; $i <= 3; $i++) {
            $r = $this->post('/admin/subscriptions/'.$proof->id.'/approve');
            $proof->refresh();
            $this->guruB->refresh();
            fwrite(STDERR, "\n[approve ke-{$i}] status=".$r->status()
                ." proof.status=".$proof->status
                ." proof.approved_by=".var_export($proof->approved_by, true)
                ." langganan_sampai=".($this->guruB->subscription_ends_at?->toDateString() ?? 'null'));
        }
        fwrite(STDERR, "\n");
        $this->assertTrue(true);
    }
}
