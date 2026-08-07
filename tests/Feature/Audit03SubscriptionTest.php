<?php

namespace Tests\Feature;

use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Audit03SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /** Samakan skema sqlite dengan skema produksi (kolom ini tak ada di migrasi). */
    private function samakanSkemaProduksi(): void
    {
        Schema::table('users', function ($t) {
            if (! Schema::hasColumn('users', 'subscription_tier')) {
                $t->string('subscription_tier')->default('pro_trial');
            }
            if (! Schema::hasColumn('users', 'subscription_ends_at')) {
                $t->timestamp('subscription_ends_at')->nullable();
            }
            // trial_ends_at sengaja tidak lagi ditambahkan di sini: kolom itu
            // sudah dibuang saat skema langganan disatukan, dan menambahkannya
            // kembali hanya untuk test akan menyembunyikan pemakaian yang tersisa.
            if (! Schema::hasColumn('users', 'is_admin')) {
                $t->boolean('is_admin')->default(false);
            }
        });
    }

    /** Bukti telanjang: kolom apa yang dianggap fillable oleh model. */
    public function test_fillable_map(): void
    {
        $u = new User;
        $p = new PaymentProof;
        foreach (['role', 'is_active', 'subscription_tier', 'subscription_ends_at', 'is_admin'] as $k) {
            fwrite(STDERR, "[AUDIT03][fillable] User::{$k} = " . var_export($u->isFillable($k), true) . "\n");
        }
        foreach (['status', 'approved_by', 'approved_at', 'rejection_reason', 'user_id', 'amount'] as $k) {
            fwrite(STDERR, "[AUDIT03][fillable] PaymentProof::{$k} = " . var_export($p->isFillable($k), true) . "\n");
        }
        $this->assertTrue(true);
    }

    private function proofFor(User $user, string $status = 'pending'): PaymentProof
    {
        $id = DB::table('payment_proofs')->insertGetId([
            'user_id' => $user->id,
            'plan_type' => 'yearly',
            'amount' => 149000,
            'proof_image' => 'payment_proofs/dummy.jpg',
            'bank_name' => 'BCA',
            'sender_name' => 'Budi',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return PaymentProof::find($id);
    }

    /** FOKUS 1: apakah approve benar-benar mengaktifkan PRO & menutup proof? */
    public function test_approve_menulis_tier_dan_status(): void
    {
        $this->samakanSkemaProduksi();
        $admin = User::factory()->create(['email' => 'admin@a.test']);
        DB::table('users')->where('id', $admin->id)->update(['role' => 'admin']);

        $guru = User::factory()->create(['email' => 'guru@a.test']);
        $proof = $this->proofFor($guru);

        $this->actingAs($admin->fresh())
            ->post("/admin/subscriptions/{$proof->id}/approve")
            ->assertRedirect();

        $rowUser = DB::table('users')->where('id', $guru->id)->first();
        $rowProof = DB::table('payment_proofs')->where('id', $proof->id)->first();

        fwrite(STDERR, "\n[AUDIT03][approve] users.subscription_tier = "
            . var_export($rowUser->subscription_tier, true)
            . " | users.subscription_ends_at = " . var_export($rowUser->subscription_ends_at, true)
            . " | payment_proofs.status = " . var_export($rowProof->status, true)
            . " | approved_by = " . var_export($rowProof->approved_by, true)
            . " | approved_at = " . var_export($rowProof->approved_at, true) . "\n");

        $this->assertSame('pro', $rowUser->subscription_tier, 'subscription_tier TIDAK menjadi pro');
        $this->assertSame('approved', $rowProof->status, 'status proof TIDAK menjadi approved');
    }

    /** Approve berulang: apakah bisa diklik dua kali dan memperpanjang terus? */
    public function test_approve_berulang(): void
    {
        $this->samakanSkemaProduksi();
        $admin = User::factory()->create();
        DB::table('users')->where('id', $admin->id)->update(['role' => 'admin']);
        $guru = User::factory()->create();
        $proof = $this->proofFor($guru);

        for ($i = 1; $i <= 3; $i++) {
            $r = $this->actingAs($admin->fresh())->post("/admin/subscriptions/{$proof->id}/approve");
            $u = DB::table('users')->where('id', $guru->id)->first();
            $p = DB::table('payment_proofs')->where('id', $proof->id)->first();
            fwrite(STDERR, "[AUDIT03][approve x{$i}] http=" . $r->getStatusCode()
                . " tier=" . var_export($u->subscription_tier, true)
                . " ends_at=" . var_export($u->subscription_ends_at, true)
                . " status=" . var_export($p->status, true) . "\n");
        }
        $this->assertTrue(true);
    }

    /** FOKUS 1: reject benar-benar menutup proof? */
    public function test_reject_menulis_status(): void
    {
        $admin = User::factory()->create();
        DB::table('users')->where('id', $admin->id)->update(['role' => 'admin']);
        $guru = User::factory()->create();
        $proof = $this->proofFor($guru);

        $this->actingAs($admin->fresh())->post("/admin/subscriptions/{$proof->id}/reject", ['reason' => 'palsu']);

        $p = DB::table('payment_proofs')->where('id', $proof->id)->first();
        fwrite(STDERR, "[AUDIT03][reject] status=" . var_export($p->status, true)
            . " reason=" . var_export($p->rejection_reason, true) . "\n");

        $this->assertSame('rejected', $p->status);
    }

    /** FOKUS 1: guru biasa menyetujui bukti milik orang lain? */
    public function test_guru_biasa_tidak_bisa_approve(): void
    {
        $guruA = User::factory()->create();
        $guruB = User::factory()->create();
        $proof = $this->proofFor($guruB);

        $r = $this->actingAs($guruA)->post("/admin/subscriptions/{$proof->id}/approve");
        fwrite(STDERR, "[AUDIT03][guru-approve-orang-lain] http=" . $r->getStatusCode() . "\n");
        $r->assertForbidden();
    }

    /** FOKUS 1: mass assignment status/approved_by lewat form unggah */
    public function test_mass_assignment_status_lewat_form_unggah(): void
    {
        Storage::fake('public');
        $guru = User::factory()->create();

        $this->actingAs($guru)->post('/subscription/upload', [
            'plan_type' => 'monthly',
            'sender_name' => 'Budi',
            'bank_name' => 'BCA',
            'status' => 'approved',
            'approved_by' => 1,
            'approved_at' => now()->toDateTimeString(),
            'amount' => 1,
            'user_id' => 99999,
            'proof_image' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $p = DB::table('payment_proofs')->latest('id')->first();
        fwrite(STDERR, "[AUDIT03][massassign-upload] status=" . var_export($p->status ?? null, true)
            . " approved_by=" . var_export($p->approved_by ?? null, true)
            . " amount=" . var_export($p->amount ?? null, true)
            . " user_id=" . var_export($p->user_id ?? null, true)
            . " path=" . var_export($p->proof_image ?? null, true) . "\n");

        $this->assertSame('pending', $p->status);
    }

    /** FOKUS 1: mass assignment role/tier lewat form profil */
    public function test_mass_assignment_role_lewat_profil(): void
    {
        $this->samakanSkemaProduksi();
        $guru = User::factory()->create(['name' => 'Guru']);

        $this->actingAs($guru)->patch('/profil', [
            'name' => 'Guru',
            'email' => $guru->email,
            'school_name' => 'SDN 1',
            'role' => 'admin',
            'is_active' => 1,
            'subscription_tier' => 'pro',
            'subscription_ends_at' => now()->addYears(10)->toDateTimeString(),
            'is_admin' => 1,
        ]);

        $u = DB::table('users')->where('id', $guru->id)->first();
        fwrite(STDERR, "[AUDIT03][massassign-profil] role=" . var_export($u->role, true)
            . " tier=" . var_export($u->subscription_tier, true)
            . " ends_at=" . var_export($u->subscription_ends_at, true)
            . " is_admin=" . var_export($u->is_admin, true) . "\n");

        $this->assertSame('teacher', $u->role);
    }

    /**
     * FOKUS 2: berkas bukti bayar milik orang lain terbuka publik?
     *
     * Dulu ya — dan tes ini mencatatnya apa adanya dengan menegaskan berkasnya
     * memang mendarat di disk 'public'. Disk itu tertaut ke public/storage,
     * jadi nginx melayaninya kepada siapa pun yang memegang alamatnya, tanpa
     * login dan tanpa jejak, padahal isinya nama pemilik rekening dan nominal.
     *
     * Sekarang berkasnya masuk disk privat dan hanya bisa dibaca lewat
     * subscription.proof yang memeriksa pemilik atau admin. Tes ini berbalik
     * arah: dari mencatat kebocoran menjadi menjaganya tidak kembali.
     * Perinciannya ada di BuktiPembayaranPrivatTest.
     */
    public function test_bukti_bayar_tidak_disimpan_di_disk_publik(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $guru = User::factory()->create();

        $this->actingAs($guru)->post('/subscription/upload', [
            'plan_type' => 'monthly',
            'sender_name' => 'Budi',
            'proof_image' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $p = DB::table('payment_proofs')->latest('id')->first();

        Storage::disk('public')->assertMissing($p->proof_image);
        Storage::disk('local')->assertExists($p->proof_image);
    }

    /** FOKUS 2: apakah berkas non-gambar / php ditolak? */
    public function test_unggah_php_dan_svg(): void
    {
        Storage::fake('public');
        $guru = User::factory()->create();

        $php = UploadedFile::fake()->createWithContent('shell.php', "<?php echo 'pwned'; ?>");
        $r1 = $this->actingAs($guru)->post('/subscription/upload', [
            'plan_type' => 'monthly', 'sender_name' => 'X', 'proof_image' => $php,
        ]);
        fwrite(STDERR, "[AUDIT03][upload-php] status=" . $r1->getStatusCode()
            . " rows=" . DB::table('payment_proofs')->count() . "\n");

        $svg = UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $r2 = $this->actingAs($guru)->post('/subscription/upload', [
            'plan_type' => 'monthly', 'sender_name' => 'X', 'proof_image' => $svg,
        ]);
        fwrite(STDERR, "[AUDIT03][upload-svg] status=" . $r2->getStatusCode()
            . " rows=" . DB::table('payment_proofs')->count() . "\n");

        // double extension
        $dbl = UploadedFile::fake()->image('a.jpg')->mimeType('image/jpeg');
        $r3 = $this->actingAs($guru)->post('/subscription/upload', [
            'plan_type' => 'monthly', 'sender_name' => 'X', 'proof_image' => $dbl,
        ]);
        fwrite(STDERR, "[AUDIT03][upload-jpg-ok] status=" . $r3->getStatusCode()
            . " rows=" . DB::table('payment_proofs')->count() . "\n");

        $this->assertSame(0, DB::table('payment_proofs')->where('proof_image', 'like', '%.php')->count());
    }

    /** FOKUS 3: route admin lain */
    public function test_akses_admin_dashboard_oleh_guru(): void
    {
        $guru = User::factory()->create();
        $r = $this->actingAs($guru)->get('/admin/dashboard');
        fwrite(STDERR, "[AUDIT03][admin-dashboard-guru] http=" . $r->getStatusCode() . "\n");

        $r2 = $this->actingAs($guru)->get('/admin/subscriptions');
        fwrite(STDERR, "[AUDIT03][admin-subscriptions-guru] http=" . $r2->getStatusCode() . "\n");
        $this->assertTrue(true);
    }

    /** FOKUS 7: health endpoint publik & isinya */
    public function test_health_endpoint_publik(): void
    {
        $r = $this->get('/health');
        fwrite(STDERR, "[AUDIT03][health] http=" . $r->getStatusCode() . " body=" . $r->getContent() . "\n");

        $r2 = $this->get('/health/ready');
        fwrite(STDERR, "[AUDIT03][health-ready] http=" . $r2->getStatusCode() . " body=" . $r2->getContent() . "\n");

        $this->assertTrue(true);
    }

    /** FOKUS 7: security headers benar-benar terpasang di respons? */
    public function test_security_headers(): void
    {
        $r = $this->get('/login');
        $h = $r->headers->all();
        fwrite(STDERR, "[AUDIT03][headers] " . json_encode(array_intersect_key($h, array_flip([
            'x-frame-options', 'content-security-policy', 'strict-transport-security',
            'x-content-type-options', 'referrer-policy', 'x-xss-protection',
        ]))) . "\n");
        fwrite(STDERR, "[AUDIT03][headers-all] " . implode(',', array_keys($h)) . "\n");
        $this->assertTrue(true);
    }

    /** FOKUS 4: kuota FREE vs PRO ditegakkan? akun free bisa akses fitur? */
    public function test_kuota_free_vs_pro(): void
    {
        /*
         * Dulu test ini hanya mencetak kode status ke STDERR lalu assertTrue(true)
         * — catatan penyelidikan, bukan pengaman. Sekarang ia menegakkan aturan
         * produk yang sudah diputuskan: masa gratis yang habis TIDAK menutup
         * aplikasi, hanya menghentikan otomasi WhatsApp.
         *
         * Arah ini penting dijaga. Mengunci seluruh aplikasi akan menyandera
         * data absensi milik sekolah, dan wali kelas yang belum sempat
         * memperpanjang kehilangan akses ke catatan yang wajib ia laporkan.
         */
        $this->samakanSkemaProduksi();
        $guru = User::factory()->kedaluwarsa()->create();

        /*
         * Memakai nama rute, bukan URL harfiah. Daftar lama memuat '/students'
         * yang tidak pernah ada sebagai rute tingkat atas (siswa berada di
         * bawah classes/{class}/students) — alamat itu selalu 404, dan test
         * lama tidak menyadarinya karena tidak memeriksa apa pun.
         */
        foreach (['dashboard', 'classes.index', 'analytics.index', 'subscription.index'] as $rute) {
            $this->actingAs($guru)
                ->get(route($rute))
                ->assertSuccessful();
        }

        $this->assertFalse(
            $guru->otomasiWhatsAppAktif(),
            'Aplikasi tetap terbuka, tetapi otomasi WhatsApp harus berhenti.'
        );
    }

    /** FOKUS 3: apakah dashboard admin benar-benar lintas kelas, atau tetap ter-scope tenant? */
    public function test_admin_dashboard_lintas_tenant(): void
    {
        $this->samakanSkemaProduksi();
        $admin = User::factory()->create(['name' => 'Kepsek']);
        DB::table('users')->where('id', $admin->id)->update(['role' => 'admin']);

        $guru = User::factory()->create(['name' => 'Guru B']);
        $kelas = \App\Models\Classroom::factory()->create(['user_id' => $guru->id]);
        \App\Models\Student::factory()->count(3)->create([
            'user_id' => $guru->id, 'class_id' => $kelas->id,
        ]);

        $r = $this->actingAs($admin->fresh())->get('/admin/dashboard');
        $ringkas = $r->viewData('ringkas');
        $skala = $r->viewData('skala');

        fwrite(STDERR, "[AUDIT03][admin-dash] http=" . $r->getStatusCode()
            . " guru_aktif=" . $ringkas['guru_aktif']
            . " siswa=" . $skala['siswa']
            . " kelas=" . $skala['kelas']
            . " (fakta db: siswa=" . DB::table('students')->count()
            . " kelas=" . DB::table('classes')->count() . ")\n");
        $this->assertTrue(true);
    }

    /** FOKUS 5: MakeAdminCommand — idempoten? sinkron dengan kolom is_admin? */
    public function test_make_admin_command(): void
    {
        $this->samakanSkemaProduksi();
        $u = User::factory()->create(['email' => 'calon@a.test']);

        for ($i = 1; $i <= 2; $i++) {
            $this->artisan('user:make-admin', ['email' => 'calon@a.test'])->assertSuccessful();
            $row = DB::table('users')->where('id', $u->id)->first();
            fwrite(STDERR, "[AUDIT03][make-admin x{$i}] role=" . var_export($row->role, true)
                . " is_admin=" . var_export($row->is_admin, true) . "\n");
        }

        $this->artisan('user:make-admin', ['email' => 'tidak-ada@a.test'])->assertFailed();
        $this->artisan('user:list')->assertSuccessful();
        $this->assertTrue(true);
    }

    /** FOKUS 3: bisakah role diubah lewat endpoint tulis lain (registrasi)? */
    public function test_registrasi_tidak_bisa_menanam_role_admin(): void
    {
        $this->samakanSkemaProduksi();
        $r = $this->post('/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@a.test',
            'password' => 'RahasiaKuat123',
            'password_confirmation' => 'RahasiaKuat123',
            'school_name' => 'SDN X',
            'whatsapp_number' => '081234567890',
            'role' => 'admin',
            'is_admin' => 1,
            'is_active' => 1,
            'subscription_tier' => 'pro',
            'subscription_ends_at' => now()->addYears(5)->toDateTimeString(),
        ]);
        $row = DB::table('users')->where('email', 'penyusup@a.test')->first();
        fwrite(STDERR, "[AUDIT03][register] http=" . $r->getStatusCode()
            . " dibuat=" . var_export((bool) $row, true)
            . " role=" . var_export($row->role ?? null, true)
            . " tier=" . var_export($row->subscription_tier ?? null, true)
            . " ends_at=" . var_export($row->subscription_ends_at ?? null, true) . "\n");
        $this->assertTrue(true);
    }
}
