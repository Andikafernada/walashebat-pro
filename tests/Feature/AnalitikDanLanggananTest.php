<?php

namespace Tests\Feature;

use App\Models\CashBook;
use App\Models\Classroom;
use App\Models\PaymentProof;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationType;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AnalitikDanLanggananTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private Classroom $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->guru = User::factory()->create();
        $this->class = Classroom::factory()->create(['user_id' => $this->guru->id, 'is_active' => true]);
    }

    // -- Analitik ----------------------------------------------------------

    /**
     * Saldo kas = pemasukan DIKURANGI pengeluaran. Menjumlahkan seluruh amount
     * membuat pengeluaran ikut menambah saldo.
     */
    public function test_saldo_kas_mengurangi_pengeluaran(): void
    {
        foreach ([['in', 500000], ['out', 300000]] as [$jenis, $nominal]) {
            CashBook::create([
                'user_id' => $this->guru->id,
                'class_id' => $this->class->id,
                'transaction_date' => now()->toDateString(),
                'type' => $jenis,
                'amount' => $nominal,
                'description' => 'uji',
                'balance_after' => 0,
            ]);
        }

        $this->actingAs($this->guru)
            ->get(route('analytics.index', ['class_id' => $this->class->id]))
            ->assertOk()
            ->assertViewHas('summaryStats', fn ($stats) => $stats['cash_balance'] === 200000);
    }

    /**
     * Nama jenis pelanggaran lazim memuat emoji. Pemotongan per byte
     * menghasilkan UTF-8 rusak, json_encode mengembalikan false, dan baris
     * `const violationsData = {!! json_encode(...) !!}` menjadi galat sintaks
     * yang mematikan seluruh grafik di halaman.
     */
    public function test_nama_ber_emoji_tetap_menghasilkan_json_yang_sah(): void
    {
        $jenis = ViolationType::create([
            'user_id' => $this->guru->id,
            'name' => 'Terlambat 🏆 Upacara Bendera',
            'category' => 'ringan',
            'points' => -5,
        ]);

        Violation::create([
            'user_id' => $this->guru->id,
            'class_id' => $this->class->id,
            'student_id' => \App\Models\Student::factory()->create([
                'user_id' => $this->guru->id, 'class_id' => $this->class->id,
            ])->id,
            'violation_type_id' => $jenis->id,
            'points' => -5,
            'occurred_on' => now()->toDateString(),
        ]);

        $respons = $this->actingAs($this->guru)
            ->get(route('analytics.index', ['class_id' => $this->class->id]))
            ->assertOk();

        $data = $respons->viewData('violationsByCategory');

        $this->assertNotEmpty($data);
        $this->assertTrue(
            mb_check_encoding($data[0]['short_name'], 'UTF-8'),
            'Nama terpotong harus tetap UTF-8 yang sah'
        );
        $this->assertNotFalse(
            json_encode($data),
            'json_encode wajib berhasil; bila false, seluruh grafik halaman mati'
        );
    }

    // -- Langganan ---------------------------------------------------------

    private function bukti(string $status = 'pending'): PaymentProof
    {
        $bukti = PaymentProof::create([
            'user_id' => $this->guru->id,
            'plan_type' => 'monthly',
            'amount' => 19000,
            'proof_image' => 'payment_proofs/bukti.jpg',
            'sender_name' => 'Budi',
        ]);

        // status sengaja TIDAK mass-assignable di PaymentProof; penulisannya
        // resmi lewat forceFill, sama seperti yang dilakukan controller.
        $bukti->forceFill(['status' => $status])->save();

        return $bukti;
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'whatsapp_number' => '628999999999',
        ]);
    }

    /** Konfirmasi PRO harus benar-benar terkirim, bukan gagal senyap. */
    public function test_persetujuan_mengirim_konfirmasi_ke_wali_kelas(): void
    {
        $this->guru->forceFill(['whatsapp_number' => '628123456789'])->save();
        $bukti = $this->bukti();

        $mock = Mockery::mock(NotificationChannel::class);
        $mock->shouldReceive('send')->once()
            ->withArgs(fn ($to, $pesan) => $to === '628123456789' && str_contains($pesan, 'PRO'))
            ->andReturn(true);
        $this->app->instance(NotificationChannel::class, $mock);

        $this->actingAs($this->admin())
            ->post(route('admin.subscriptions.approve', $bukti))
            ->assertRedirect();

        $this->assertSame('approved', $bukti->fresh()->status);
        $this->assertSame('pro', $this->guru->fresh()->subscription_tier);
    }

    /** Gateway mati tidak boleh membatalkan persetujuan yang sudah sah. */
    public function test_gagal_kirim_tidak_membatalkan_persetujuan(): void
    {
        $this->guru->forceFill(['whatsapp_number' => '628123456789'])->save();
        $bukti = $this->bukti();

        $mock = Mockery::mock(NotificationChannel::class);
        $mock->shouldReceive('send')->andThrow(new \RuntimeException('gateway mati'));
        $this->app->instance(NotificationChannel::class, $mock);

        $this->actingAs($this->admin())
            ->post(route('admin.subscriptions.approve', $bukti))
            ->assertRedirect();

        $this->assertSame('approved', $bukti->fresh()->status);
        $this->assertSame('pro', $this->guru->fresh()->subscription_tier);
    }

    /** Pembayaran yang sudah disetujui tidak boleh dibalik menjadi ditolak. */
    public function test_bukti_yang_sudah_disetujui_tidak_bisa_ditolak(): void
    {
        $bukti = $this->bukti('approved');

        $this->actingAs($this->admin())
            ->post(route('admin.subscriptions.reject', $bukti), ['reason' => 'coba balik'])
            ->assertRedirect();

        $this->assertSame('approved', $bukti->fresh()->status, 'Status tidak boleh berubah');
    }

    /** Alasan penolakan panjang ditolak validasi, bukan menjadi galat 500. */
    public function test_alasan_penolakan_terlalu_panjang_ditolak_dengan_pesan(): void
    {
        $bukti = $this->bukti();

        $this->actingAs($this->admin())
            ->post(route('admin.subscriptions.reject', $bukti), ['reason' => str_repeat('a', 200)])
            ->assertSessionHasErrors('reason');

        $this->assertSame('pending', $bukti->fresh()->status);
    }
}
