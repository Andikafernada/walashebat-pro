<?php

namespace Tests\Feature;

use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bukti pembayaran adalah tangkapan layar DANA/transfer: nama pemilik
 * rekening, nominal, dan sering sebagian nomor rekening.
 *
 * Dulu berkasnya disimpan di disk 'public' yang tertaut ke public/storage,
 * sehingga nginx melayaninya langsung kepada siapa pun yang memegang
 * alamatnya — tanpa login dan tanpa jejak. Nama berkas acak hanya
 * menyembunyikan, bukan melindungi.
 */
class BuktiPembayaranPrivatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function buktiUntuk(User $guru, string $berkas = 'payment_proofs/bukti.jpg'): PaymentProof
    {
        $id = DB::table('payment_proofs')->insertGetId([
            'user_id' => $guru->id,
            'plan_type' => 'monthly',
            'amount' => 19000,
            'proof_image' => $berkas,
            'bank_name' => 'DANA',
            'sender_name' => 'Budi',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return PaymentProof::findOrFail($id);
    }

    // -- Tempat penyimpanan --------------------------------------------------

    public function test_unggahan_masuk_disk_privat_bukan_publik(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $guru = User::factory()->create();

        $this->actingAs($guru)->post(route('subscription.upload'), [
            'plan_type' => 'monthly',
            'sender_name' => 'Budi Santoso',
            'proof_image' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasNoErrors();

        $bukti = PaymentProof::where('user_id', $guru->id)->firstOrFail();

        Storage::disk('local')->assertExists($bukti->proof_image);
        Storage::disk('public')->assertMissing($bukti->proof_image);
    }

    // -- Siapa yang boleh membuka -------------------------------------------

    public function test_pengunggah_bisa_membuka_buktinya_sendiri(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payment_proofs/bukti.jpg', 'isi-gambar');

        $guru = User::factory()->create();
        $bukti = $this->buktiUntuk($guru);

        $this->actingAs($guru)->get(route('subscription.proof', $bukti))->assertOk();
    }

    public function test_admin_bisa_membuka_untuk_verifikasi(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payment_proofs/bukti.jpg', 'isi-gambar');

        $guru = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $bukti = $this->buktiUntuk($guru);

        $this->actingAs($admin)->get(route('subscription.proof', $bukti))->assertOk();
    }

    /** Inti perlindungannya: wali kelas lain bukan pihak yang berkepentingan. */
    public function test_wali_kelas_lain_ditolak(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payment_proofs/bukti.jpg', 'isi-gambar');

        $bukti = $this->buktiUntuk(User::factory()->create());
        $penyusup = User::factory()->create();

        $this->actingAs($penyusup)->get(route('subscription.proof', $bukti))->assertForbidden();
    }

    public function test_tanpa_login_ditolak(): void
    {
        $bukti = $this->buktiUntuk(User::factory()->create());

        $this->get(route('subscription.proof', $bukti))->assertRedirect(route('login'));
    }

    /**
     * Alamat lama /storage/payment_proofs/... tidak boleh lagi melayani
     * berkas apa pun tanpa tanda tangan — itulah kebocoran yang ditutup.
     */
    public function test_alamat_storage_publik_tidak_melayani_bukti(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('payment_proofs/bukti.jpg', 'isi-gambar');

        $balasan = $this->get('/storage/payment_proofs/bukti.jpg');

        /*
         * 403 di sini, 404 di produksi: rute bawaan disk lokal Laravel sengaja
         * menyamarkan penolakan menjadi "tidak ada" begitu APP_ENV=production,
         * supaya keberadaan berkas tidak ikut terkonfirmasi. Yang dijaga tes ini
         * adalah isinya tidak pernah keluar — bukan angka statusnya.
         */
        $this->assertContains($balasan->getStatusCode(), [403, 404]);
        $this->assertStringNotContainsString('isi-gambar', $balasan->getContent());
    }

    public function test_berkas_yang_hilang_menjadi_404_bukan_500(): void
    {
        Storage::fake('local');

        $guru = User::factory()->create();
        $bukti = $this->buktiUntuk($guru, 'payment_proofs/tidak-ada.jpg');

        $this->actingAs($guru)->get(route('subscription.proof', $bukti))->assertNotFound();
    }
}
