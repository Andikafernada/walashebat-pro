<?php

namespace Tests\Feature;

use App\Models\PaymentProof;
use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Alur pembayaran WaliKelas Pro sepenuhnya manual: guru transfer ke DANA,
 * mengunggah bukti, lalu admin memeriksa nominal yang benar-benar masuk dan
 * menentukan berapa bulan yang diberikan.
 *
 * Karena tidak ada payment gateway yang memverifikasi apa pun, jalur inilah
 * satu-satunya yang memberi akses PRO — dan karenanya harus dijaga ketat:
 * hanya admin yang boleh menyetujui, dan sisa masa yang belum terpakai tidak
 * boleh hangus.
 */
class PersetujuanPembayaranTest extends TestCase
{
    use RefreshDatabase;

    private function saluranDiam(): void
    {
        $this->app->instance(NotificationChannel::class, new class implements NotificationChannel
        {
            public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
            {
                return true;
            }
        });
    }

    private function buktiUntuk(User $guru, string $planType = 'monthly'): PaymentProof
    {
        $id = DB::table('payment_proofs')->insertGetId([
            'user_id' => $guru->id,
            'plan_type' => $planType,
            'amount' => $planType === 'yearly' ? 149000 : 19000,
            'proof_image' => 'payment_proofs/bukti.jpg',
            'bank_name' => 'DANA',
            'sender_name' => 'Budi',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return PaymentProof::findOrFail($id);
    }

    public function test_admin_bisa_memberi_jumlah_bulan_sesuai_nominal_transfer(): void
    {
        $this->saluranDiam();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $guru = User::factory()->kedaluwarsa()->create();
        $bukti = $this->buktiUntuk($guru, 'monthly');

        // Guru memilih paket bulanan, tetapi mentransfer untuk tiga bulan.
        $this->actingAs($admin)
            ->post(route('admin.subscriptions.approve', $bukti), ['bulan' => 3])
            ->assertRedirect();

        $guru->refresh();

        $this->assertSame(User::TIER_PRO, $guru->subscription_tier);
        $this->assertTrue($guru->otomasiWhatsAppAktif());
        $this->assertEqualsWithDelta(
            now()->addMonths(3)->timestamp,
            $guru->subscription_ends_at->timestamp,
            60
        );
        $this->assertSame(3, $bukti->fresh()->granted_months, 'Jumlah bulan harus tercatat sebagai jejak audit.');
    }

    public function test_tanpa_isian_bulan_memakai_paket_yang_dipilih_guru(): void
    {
        $this->saluranDiam();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $guru = User::factory()->kedaluwarsa()->create();
        $bukti = $this->buktiUntuk($guru, 'yearly');

        // Persetujuan satu klik harus tetap berjalan seperti sebelumnya.
        $this->actingAs($admin)->post(route('admin.subscriptions.approve', $bukti));

        $this->assertEqualsWithDelta(
            now()->addMonths(12)->timestamp,
            $guru->fresh()->subscription_ends_at->timestamp,
            60
        );
        $this->assertSame(12, $bukti->fresh()->granted_months);
    }

    public function test_sisa_masa_yang_belum_terpakai_tidak_hangus(): void
    {
        $this->saluranDiam();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Guru memperpanjang saat masanya masih tersisa 40 hari.
        $guru = User::factory()->create();
        $guru->forceFill(['subscription_ends_at' => now()->addDays(40)])->save();
        $bukti = $this->buktiUntuk($guru, 'monthly');

        $this->actingAs($admin)->post(route('admin.subscriptions.approve', $bukti), ['bulan' => 1]);

        $this->assertEqualsWithDelta(
            now()->addDays(40)->addMonth()->timestamp,
            $guru->fresh()->subscription_ends_at->timestamp,
            60,
            'Membayar lebih awal tidak boleh menghanguskan sisa masa.'
        );
    }

    public function test_guru_biasa_tidak_bisa_menyetujui_pembayarannya_sendiri(): void
    {
        $this->saluranDiam();
        $guru = User::factory()->kedaluwarsa()->create();
        $bukti = $this->buktiUntuk($guru);

        $this->actingAs($guru)
            ->post(route('admin.subscriptions.approve', $bukti), ['bulan' => 24])
            ->assertForbidden();

        $this->assertFalse($guru->fresh()->otomasiWhatsAppAktif());
        $this->assertSame('pending', $bukti->fresh()->status);
    }

    public function test_jumlah_bulan_di_luar_batas_ditolak(): void
    {
        $this->saluranDiam();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $guru = User::factory()->kedaluwarsa()->create();
        $bukti = $this->buktiUntuk($guru);

        $this->actingAs($admin)
            ->post(route('admin.subscriptions.approve', $bukti), ['bulan' => 999])
            ->assertSessionHasErrors('bulan');

        $this->assertSame('pending', $bukti->fresh()->status);
        $this->assertFalse($guru->fresh()->otomasiWhatsAppAktif());
    }

    public function test_halaman_admin_menampilkan_isian_jumlah_bulan(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $guru = User::factory()->kedaluwarsa()->create(['name' => 'Ibu Sari']);
        $this->buktiUntuk($guru, 'yearly');

        $this->actingAs($admin)
            ->get(route('admin.subscriptions.index'))
            ->assertOk()
            ->assertSee('Ibu Sari')
            // Isian bulan terisi otomatis sesuai paket yang dipilih guru.
            ->assertSee('name="bulan"', false)
            ->assertSee('value="12"', false);
    }

    public function test_bukti_yang_sudah_diproses_tidak_bisa_disetujui_dua_kali(): void
    {
        $this->saluranDiam();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $guru = User::factory()->kedaluwarsa()->create();
        $bukti = $this->buktiUntuk($guru, 'monthly');

        $this->actingAs($admin)->post(route('admin.subscriptions.approve', $bukti), ['bulan' => 1]);
        $akhirPertama = $guru->fresh()->subscription_ends_at;

        // Menekan tombol dua kali tidak boleh memberi masa dobel.
        $this->actingAs($admin)->post(route('admin.subscriptions.approve', $bukti), ['bulan' => 1]);

        $this->assertEquals($akhirPertama, $guru->fresh()->subscription_ends_at);
    }
}
