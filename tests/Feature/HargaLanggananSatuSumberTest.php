<?php

namespace Tests\Feature;

use App\Http\Controllers\LandingController;
use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Harga langganan hidup di SATU tempat.
 *
 * Dulu di empat: halaman muka dan formulir unggah bukti menyebut Rp 10.000
 * kepada guru, controller menyimpan Rp 19.000 ke kolom amount, dan panel
 * operator melabelinya "PRO BULANAN (19rb)".
 *
 * Yang membuatnya berbahaya bukan selisihnya, melainkan siapa yang membaca
 * masing-masing: guru mentransfer sesuai angka yang dilihatnya, operator
 * memutuskan terima/tolak berdasarkan angka yang tersimpan. Dua angka berbeda
 * pada satu transaksi berarti pembayaran yang sah bisa ditolak — dan tabelnya
 * masih kosong, jadi ini tertangkap sebelum ada satu pun korban.
 */
class HargaLanggananSatuSumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_harga_yang_diiklankan_sama_dengan_yang_tersimpan(): void
    {
        Storage::fake('local');

        $guru = User::factory()->create();

        $this->actingAs($guru)->post(route('subscription.upload'), [
            'plan_type' => 'monthly',
            'sender_name' => 'Andika Fernanda',
            'proof_image' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();

        $this->assertSame(
            PaymentProof::HARGA_BULANAN,
            (int) PaymentProof::sole()->amount,
            'Nominal tersimpan harus sama dengan harga yang dilihat guru.'
        );
    }

    public function test_halaman_langganan_menampilkan_harga_dari_konstanta(): void
    {
        $guru = User::factory()->create();

        $rupiah = 'Rp '.number_format(PaymentProof::HARGA_BULANAN, 0, ',', '.');

        $this->actingAs($guru)
            ->get(route('subscription.index'))
            ->assertOk()
            ->assertSee($rupiah)
            // Harga lama tidak boleh tertinggal di mana pun pada halaman ini.
            ->assertDontSee('Rp 19.000')
            ->assertDontSee('19rb');
    }

    /** Halaman muka dan formulir bayar tidak boleh menyebut angka berbeda. */
    public function test_halaman_muka_memakai_harga_yang_sama(): void
    {
        $this->assertSame(PaymentProof::HARGA_BULANAN, LandingController::HARGA_PRO);
    }

    /** Tahunan adalah 12 bulan, bukan angka yang dikarang terpisah. */
    public function test_paket_tahunan_dua_belas_kali_harga_bulanan(): void
    {
        Storage::fake('local');

        $guru = User::factory()->create();

        $this->actingAs($guru)->post(route('subscription.upload'), [
            'plan_type' => 'yearly',
            'sender_name' => 'Andika Fernanda',
            'proof_image' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();

        $this->assertSame(
            PaymentProof::HARGA_BULANAN * 12,
            (int) PaymentProof::sole()->amount
        );
    }

    /**
     * Panel operator harus MEMBACA nominal baris, bukan mengetik ulang harga.
     * Label yang mengulang harga adalah sumber kebenaran kedua yang cepat atau
     * lambat menyimpang — persis yang sudah terjadi sekali.
     */
    public function test_panel_operator_membaca_nominal_tersimpan(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $guru = User::factory()->create();

        // Nominal sengaja tidak lazim: kalau layar tetap menyebut angka lain,
        // berarti ia mengetik ulang harga alih-alih membaca baris ini.
        PaymentProof::create([
            'user_id' => $guru->id,
            'plan_type' => 'monthly',
            'amount' => 12345,
            'proof_image' => 'payment_proofs/x.jpg',
            'sender_name' => 'Andika',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.subscriptions.index'))
            ->assertOk()
            ->assertSee('Rp 12.345');
    }
}
