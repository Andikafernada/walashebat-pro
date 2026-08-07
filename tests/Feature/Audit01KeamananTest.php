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

/**
 * Batas antar tenant: guru A tidak boleh menyentuh apa pun milik guru B.
 *
 * Berkas ini semula alat investigasi — lima belas probe yang mencetak status
 * HTTP ke STDERR lalu ditutup assertTrue(true). Bentuk itu berbahaya justru
 * karena terlihat seperti test: ia ikut gagal ketika kode berubah, sehingga
 * menuntut perawatan, tanpa pernah menjaga satu perilaku pun. Sepanjang sesi
 * ini ia dua kali memaksa perbaikan atas perubahan yang sepenuhnya sah.
 *
 * Keluarannya bahkan sempat menyesatkan: rangkaian "pengambilalihan siswa" di
 * bawah mencetak "login sebagai siswa tenant lain = 1", yang terbaca seperti
 * celah terbuka. Padahal langkah pertamanya sudah 404 — yang terbaca adalah
 * OTP yang dibaca test dari channel palsunya sendiri, sesuatu yang penyerang
 * sungguhan tidak punya. Angka yang mengerikan tanpa assertion tidak
 * memberitahu apa pun tentang aman atau tidaknya aplikasi.
 *
 * Setiap probe kini menyatakan perilaku yang BENAR. Yang dijaga sebagian besar
 * adalah perbaikan yang sudah dilakukan — terutama form publik yang kini dicari
 * lewat token, bukan id kelas yang berurutan.
 */
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

    // -- API ----------------------------------------------------------------

    public function test_api_students_kelas_orang_lain_ditolak(): void
    {
        Sanctum::actingAs($this->guruA);

        $this->getJson('/api/v1/classes/'.$this->kelasB->id.'/students')
            ->assertNotFound()
            ->assertDontSee('SISWA RAHASIA B');
    }

    public function test_api_attendance_today_kelas_orang_lain_ditolak(): void
    {
        Sanctum::actingAs($this->guruA);

        $this->getJson('/api/v1/classes/'.$this->kelasB->id.'/attendance/today')
            ->assertNotFound();
    }

    /**
     * Token API hanya boleh melihat kelas pemiliknya sendiri.
     *
     * CATATAN: ability token ('read', 'attendance:verify' dari
     * Api\AuthController::issueToken) TIDAK ditegakkan di mana pun — rute
     * /api/v1 hanya memasang 'auth:sanctum', tanpa middleware 'abilities'.
     * Token berability ngawur pun tetap dilayani. Yang benar-benar melindungi
     * data adalah TenantScope, dan itulah yang dikunci di sini; ability-nya
     * dilaporkan terpisah sebagai temuan, bukan dikunci dalam keadaan lemah.
     */
    public function test_token_api_terkurung_pada_tenant_pemiliknya(): void
    {
        Sanctum::actingAs($this->guruA, ['nonsense-ability']);

        $this->getJson('/api/v1/classes')
            ->assertOk()
            ->assertJsonMissing(['name' => 'KELAS B'])
            ->assertDontSee('KELAS B');
    }

    // -- Form publik: dicari lewat token, bukan id berurutan ----------------

    public function test_form_biodata_tidak_bisa_dibuka_lewat_id_kelas(): void
    {
        $r = $this->get('/isi-biodata/'.$this->kelasB->id);

        $r->assertNotFound();
        $this->assertStringNotContainsString('SISWA RAHASIA B', $r->getContent() ?: '');
        $this->assertStringNotContainsString('999999', $r->getContent() ?: '');
    }

    public function test_form_biodata_lewat_id_tidak_bisa_menimpa_siswa(): void
    {
        $this->post('/isi-biodata/'.$this->kelasB->id, [
            'student_id' => $this->siswaB->id,
            'name' => 'DIUBAH PENYERANG',
            'gender' => 'L',
            'parent_phone' => '08123456789',
        ])->assertNotFound();

        $this->assertSame('SISWA RAHASIA B', $this->siswaB->refresh()->name);
    }

    public function test_form_biodata_lewat_id_tidak_bisa_menyisipkan_siswa(): void
    {
        $this->post('/isi-biodata/'.$this->kelasB->id, [
            'name' => 'SISIPAN PENYERANG',
            'gender' => 'L',
            'parent_phone' => '08123456789',
        ])->assertNotFound();

        $this->assertSame(1, Student::withoutTenant()->where('class_id', $this->kelasB->id)->count());
    }

    public function test_refleksi_publik_tidak_bisa_dibuka_lewat_id_kelas(): void
    {
        $dim = CharacterDimension::create([
            'code' => 'X', 'name' => 'Dim', 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->post('/refleksi-karakter/'.$this->kelasA->id, [
            'student_id' => $this->siswaB->id, // milik tenant B, dikirim ke kelas A
            'character_dimension_id' => $dim->id,
            'self_rating' => 5,
            'what_went_well' => 'x',
            'what_to_improve' => 'y',
            'action_plan' => 'z',
        ])->assertNotFound();

        $this->assertSame(0, CharacterReflection::withoutTenant()->count());
    }

    public function test_refleksi_publik_lewat_id_tidak_melahirkan_baris_yatim(): void
    {
        $dim = CharacterDimension::create(['code' => 'Y', 'name' => 'Dim', 'is_active' => true, 'sort_order' => 1]);
        $siswaA = Student::factory()->create(['user_id' => $this->guruA->id, 'class_id' => $this->kelasA->id]);

        $this->post('/refleksi-karakter/'.$this->kelasA->id, [
            'student_id' => $siswaA->id,
            'character_dimension_id' => $dim->id,
            'self_rating' => 5,
            'what_went_well' => 'a', 'what_to_improve' => 'b', 'action_plan' => 'c',
        ])->assertNotFound();

        $this->assertDatabaseCount('character_reflections', 0);
    }

    /**
     * Rantai pengambilalihan siswa, dikunci pada mata rantai PERTAMANYA.
     *
     * Rancangan serangannya: timpa parent_phone siswa milik tenant lain lewat
     * form publik, lalu minta OTP reset kata sandi — OTP itu akan mendarat di
     * nomor penyerang. Yang memutus rantai ini adalah form publik yang tidak
     * lagi bisa dijangkau lewat id kelas, jadi di sanalah pengunciannya.
     */
    public function test_nomor_orang_tua_tidak_bisa_ditimpa_lewat_form_publik(): void
    {
        $this->siswaB->update(['parent_phone' => '628111111111', 'is_active' => true]);

        $terkirim = [];
        $this->app->bind(\App\Support\Contracts\NotificationChannel::class, function () use (&$terkirim) {
            return new class($terkirim) implements \App\Support\Contracts\NotificationChannel {
                public function __construct(public &$log) {}

                public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
                {
                    $this->log[] = [$to, $message];

                    return true;
                }
            };
        });

        $this->post('/isi-biodata/'.$this->kelasB->id, [
            'student_id' => $this->siswaB->id,
            'name' => $this->siswaB->name,
            'gender' => 'L',
            'parent_phone' => '628999999999',   // nomor penyerang
        ])->assertNotFound();

        $this->assertSame(
            '628111111111',
            $this->siswaB->refresh()->parent_phone,
            'nomor orang tua tidak boleh bisa diubah dari luar',
        );

        // Dan karena nomornya tidak berubah, OTP tetap mendarat di nomor yang sah.
        $this->post('/student/lupa-password', ['nis' => $this->siswaB->nis]);

        $tujuan = array_column($terkirim, 0);
        $this->assertNotContains('628999999999', $tujuan, 'OTP tidak boleh sampai ke nomor penyerang');
    }

    // -- Login siswa --------------------------------------------------------

    /**
     * NIS tidak unik antar tenant: dua sekolah bisa sama-sama punya siswa
     * bernomor 999999. Masing-masing harus masuk sebagai dirinya sendiri.
     */
    public function test_nis_yang_sama_antar_tenant_masuk_ke_akun_yang_benar(): void
    {
        $siswaA = Student::factory()->create([
            'user_id' => $this->guruA->id,
            'class_id' => $this->kelasA->id,
            'name' => 'SISWA A',
            'nis' => '999999',
            'password' => Hash::make('rahasiaA'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $this->siswaB->update([
            'password' => Hash::make('rahasiaB'),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->post('/student/login', ['nis' => '999999', 'password' => 'rahasiaA']);
        $this->assertSame($siswaA->id, auth('student')->id(), 'kata sandi A harus membuka akun A');

        auth('student')->logout();

        $this->post('/student/login', ['nis' => '999999', 'password' => 'rahasiaB']);
        $this->assertSame($this->siswaB->id, auth('student')->id(), 'kata sandi B harus membuka akun B');
    }

    public function test_share_biodata_wa_kelas_orang_lain_ditolak(): void
    {
        $this->actingAs($this->guruA);

        $this->post('/classes/'.$this->kelasB->id.'/share-biodata-wa', ['group_id' => '628999@g.us'])
            ->assertNotFound();
    }

    // -- Sapu IDOR ----------------------------------------------------------

    /**
     * Tiga belas rute anak kelas ditembak sekaligus dengan objek milik guru
     * lain. Semuanya harus ditolak, dan — yang lebih penting — tidak satu pun
     * objeknya boleh ikut terhapus.
     */
    public function test_tidak_ada_rute_anak_kelas_yang_tembus_ke_tenant_lain(): void
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

        foreach ($probes as [$m, $u, $d]) {
            $status = $this->call($m, $u, $d)->status();

            $this->assertContains(
                $status,
                [403, 404],
                "{$m} {$u} harus ditolak, tetapi menjawab {$status}",
            );
        }

        // Penolakan status saja tidak cukup — buktikan tidak ada yang lenyap.
        $this->assertNotNull(\App\Models\Violation::withoutTenant()->find($violation->id));
        $this->assertNotNull(\App\Models\CashBook::withoutTenant()->find($cashbook->id));
        $this->assertNotNull(\App\Models\Schedule::withoutTenant()->find($schedule->id));
        $this->assertNotNull(\App\Models\OrganizationStructure::withoutTenant()->find($org->id));
        $this->assertNotNull(\App\Models\CharacterRecord::withoutTenant()->find($record->id));
        $this->assertNotNull(\App\Models\Holiday::find($holiday->id));
        $this->assertSame('T', \App\Models\CharacterRecord::withoutTenant()->find($record->id)->title);
    }

    /**
     * Batasnya bukan cuma antar tenant, tetapi antar KELAS di dalam satu tenant:
     * catatan milik kelas A2 tidak boleh disunting lewat URL kelas A, sekalipun
     * kedua kelas itu milik guru yang sama (scopeBindings).
     */
    public function test_objek_kelas_lain_tidak_bisa_disunting_lewat_url_kelas_ini(): void
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

        $this->patch('/classes/'.$this->kelasA->id.'/karakter/catatan/'.$record->id, [
            'type' => 'negative', 'score' => -5, 'title' => 'DIUBAH LEWAT KELAS LAIN',
        ])->assertNotFound();

        $this->assertSame('MILIK KELAS A2', $record->refresh()->title);
    }

    /**
     * Pembanding: penjagaan di atas tidak boleh sampai mengunci pemiliknya
     * sendiri. Tiap aksi memakai catatan BARU — versi lama memakai satu catatan
     * untuk semua aksi, sehingga DELETE di tengah membuat dua probe terakhir
     * 404 dan terbaca seperti kerusakan, padahal catatannya memang sudah tiada.
     */
    public function test_pemilik_tetap_bisa_menyunting_catatan_karakternya(): void
    {
        $siswaA = Student::factory()->create(['user_id' => $this->guruA->id, 'class_id' => $this->kelasA->id]);
        $dim = CharacterDimension::create(['code' => 'N', 'name' => 'D', 'is_active' => true, 'sort_order' => 1]);

        $buatCatatan = fn () => \App\Models\CharacterRecord::withoutTenant()->create([
            'user_id' => $this->guruA->id, 'class_id' => $this->kelasA->id, 'student_id' => $siswaA->id,
            'character_dimension_id' => $dim->id, 'type' => 'positive', 'score' => 3,
            'title' => 'ASLI', 'record_date' => now()->toDateString(), 'recorded_by' => $this->guruA->id,
        ]);

        $this->actingAs($this->guruA);

        $ubah = $buatCatatan();
        $this->patch('/classes/'.$this->kelasA->id.'/karakter/catatan/'.$ubah->id, [
            'type' => 'positive', 'score' => 1, 'title' => 'DIUBAH',
        ])->assertRedirect();
        $this->assertSame('DIUBAH', $ubah->refresh()->title);

        $konfirmasi = $buatCatatan();
        $this->post('/classes/'.$this->kelasA->id.'/karakter/catatan/'.$konfirmasi->id.'/konfirmasi')
            ->assertRedirect();

        $hapus = $buatCatatan();
        $this->delete('/classes/'.$this->kelasA->id.'/karakter/catatan/'.$hapus->id)
            ->assertRedirect();
        $this->assertNull(\App\Models\CharacterRecord::withoutTenant()->find($hapus->id));
    }

    // -- Persetujuan pembayaran ---------------------------------------------

    /**
     * Menyetujui bukti yang sama berkali-kali tidak boleh menambah masa
     * langganan. Tanpa penjagaan itu, admin yang menekan tombol dua kali —
     * atau menyegarkan halaman setelah menyetujui — memberi bulan gratis yang
     * tidak pernah dibayar, dan tidak ada yang akan menyadarinya.
     */
    public function test_bukti_pembayaran_hanya_bisa_disetujui_sekali(): void
    {
        $admin = User::factory()->create(['email' => 'admin@x.id']);
        $admin->forceFill(['role' => 'admin'])->save();

        $proof = \App\Models\PaymentProof::create([
            'user_id' => $this->guruB->id, 'plan_type' => 'monthly', 'amount' => 19000,
            'proof_image' => 'x.jpg', 'sender_name' => 'B',
        ]);

        $this->actingAs($admin);

        $this->post('/admin/subscriptions/'.$proof->id.'/approve');
        $proof->refresh();
        $berakhirSetelahSekali = $this->guruB->refresh()->subscription_ends_at;

        $this->assertSame('approved', $proof->status);
        $this->assertSame($admin->id, $proof->approved_by);
        $this->assertNotNull($berakhirSetelahSekali);

        // Dua percobaan berikutnya harus tidak berpengaruh sama sekali.
        $this->post('/admin/subscriptions/'.$proof->id.'/approve');
        $this->post('/admin/subscriptions/'.$proof->id.'/approve');

        $this->assertEquals(
            $berakhirSetelahSekali->toDateTimeString(),
            $this->guruB->refresh()->subscription_ends_at->toDateTimeString(),
            'persetujuan ulang tidak boleh memperpanjang langganan',
        );
    }
}
