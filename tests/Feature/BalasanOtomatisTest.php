<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Contracts\WhatsAppSessionManager;
use App\Support\Notifications\N8nSessionManager;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Pengaturan balasan otomatis lewat UI.
 *
 * Gateway-lah yang benar-benar menjalankan fitur ini; aplikasi hanya
 * mengubah pengaturannya. Karena itu yang diuji di sini adalah PENJAGAAN
 * sebelum permintaan dikirim ke gateway — bagian yang menentukan bot tidak
 * pernah membalas di tempat yang salah.
 */
class BalasanOtomatisTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'wa_session_status' => 'connected',
        ]);

        $this->actingAs($this->user);
    }

    private function palsukanManager(): Mockery\MockInterface
    {
        $mock = Mockery::mock(WhatsAppSessionManager::class);
        $mock->shouldReceive('autoreplyStatus')->andReturn(
            ['enabled' => false, 'groups' => [], 'jam' => '06:00-15:00']
        )->byDefault();
        $mock->shouldReceive('isHealthy')->andReturn(true)->byDefault();
        $mock->shouldReceive('getCircuitStatus')->andReturn([
            'name' => 'test',
            'state' => 'closed',
            'failures' => 0,
            'threshold' => 3,
            'time_until_retry' => 0,
        ])->byDefault();

        $this->app->instance(WhatsAppSessionManager::class, $mock);

        return $mock;
    }

    public function test_halaman_menampilkan_bagian_balasan_otomatis(): void
    {
        $this->palsukanManager();

        $this->get(route('whatsapp.index'))
            ->assertOk()
            // Judul bagian berubah saat halaman WhatsApp didesain ulang.
            ->assertSee('Pilih &amp; Kelola Grup WhatsApp yang Dibalas Otomatis', false)
            ->assertSee('Grup Terkoneksi');
    }

    public function test_bisa_menyalakan_untuk_grup_terpilih(): void
    {
        $mock = $this->palsukanManager();
        $mock->shouldReceive('autoreplySave')
            ->once()
            ->with(Mockery::type(User::class), true, ['120363321166050533@g.us'], [], [], Mockery::type('array'), Mockery::type('array'))
            ->andReturn(true);

        $this->post(route('whatsapp.autoreply'), [
            'enabled' => '1',
            'groups' => ['120363321166050533@g.us'],
        ])->assertRedirect()->assertSessionHas('success');
    }

    public function test_bisa_dimatikan(): void
    {
        $mock = $this->palsukanManager();
        $mock->shouldReceive('autoreplySave')
            ->once()
            ->with(Mockery::type(User::class), false, [], [], [], Mockery::type('array'), Mockery::type('array'))
            ->andReturn(true);

        $this->post(route('whatsapp.autoreply'), ['enabled' => '0'])
            ->assertRedirect()->assertSessionHas('success');
    }

    /**
     * PENJAGAAN TERPENTING: hanya JID grup yang boleh masuk.
     * Tanpa ini, satu salah pilih bisa membuat bot membalas di chat pribadi
     * orang tua.
     */
    public function test_menolak_jid_yang_bukan_grup(): void
    {
        $mock = $this->palsukanManager();
        $mock->shouldNotReceive('autoreplySave');

        $this->post(route('whatsapp.autoreply'), [
            'enabled' => '1',
            'groups' => ['6281234567890@s.whatsapp.net'],
        ])->assertSessionHasErrors('groups.0');
    }

    /** Menyalakan tanpa memilih grup tidak ada gunanya — cegah, jangan diam. */
    public function test_menolak_aktif_tanpa_grup(): void
    {
        $mock = $this->palsukanManager();
        $mock->shouldNotReceive('autoreplySave');

        $this->post(route('whatsapp.autoreply'), ['enabled' => '1', 'groups' => []])
            ->assertRedirect()->assertSessionHas('warning');
    }

    public function test_menolak_bila_whatsapp_belum_tersambung(): void
    {
        $this->user->update(['wa_session_status' => 'disconnected']);

        $mock = $this->palsukanManager();
        $mock->shouldNotReceive('autoreplySave');

        $this->post(route('whatsapp.autoreply'), [
            'enabled' => '1',
            'groups' => ['120363321166050533@g.us'],
        ])->assertRedirect()->assertSessionHas('warning');
    }

    /** Gateway tidak merespons: beri tahu jujur, jangan mengaku tersimpan. */
    public function test_kegagalan_gateway_dilaporkan_apa_adanya(): void
    {
        $mock = $this->palsukanManager();
        $mock->shouldReceive('autoreplySave')->once()->andReturn(false);

        $this->post(route('whatsapp.autoreply'), [
            'enabled' => '1',
            'groups' => ['120363321166050533@g.us'],
        ])->assertRedirect()->assertSessionHas('error');
    }

    /** Kotak pencarian harus ada — nomor guru bisa tergabung di puluhan grup. */
    public function test_ada_kotak_pencarian_grup(): void
    {
        $this->palsukanManager();

        $this->get(route('whatsapp.index'))
            ->assertOk()
            // Kotak pencarian kini memakai x-model Alpine, bukan id 'cari-grup'.
            ->assertSee('Cari nama grup WhatsApp')
            ->assertSee('x-model="cari"', false);
    }

    /**
     * Grup yang sudah dipilih tapi tersaring keluar tetap ikut terkirim.
     * Tanpa ini, mencari grup lain diam-diam membatalkan pilihan sebelumnya.
     */
    public function test_grup_terpilih_tetap_terkirim_meski_tersaring(): void
    {
        $mock = $this->palsukanManager();
        $mock->shouldReceive('autoreplySave')
            ->once()
            ->with(Mockery::type(User::class), true, ['120363111@g.us', '120363222@g.us'], [], [], Mockery::type('array'), Mockery::type('array'))
            ->andReturn(true);

        $this->post(route('whatsapp.autoreply'), [
            'enabled' => '1',
            'groups' => ['120363111@g.us', '120363222@g.us'],
        ])->assertSessionHas('success');
    }

    /** Bagian ini tidak muncul kalau nomor belum ditautkan. */
    public function test_bagian_disembunyikan_bila_belum_tersambung(): void
    {
        $this->user->update(['wa_session_status' => 'disconnected']);
        $this->palsukanManager();

        $this->get(route('whatsapp.index'))
            ->assertOk()
            ->assertDontSee('Nyalakan balasan otomatis');
    }

    /**
     * REGRESI: desain ulang 1 Agustus menghapus checkbox-nya dan menyisakan
     * hidden value="1", sehingga formulir selalu mengirim "nyala". Wali kelas
     * bisa menyalakan bot yang membalas orang tua di grup, tetapi tidak punya
     * satu pun cara mematikannya selain memutus seluruh sambungan WhatsApp.
     */
    public function test_bisa_dimatikan_lewat_formulir_di_halaman(): void
    {
        $mock = $this->palsukanManager();
        $mock->shouldReceive('autoreplySave')
            ->once()
            ->with(Mockery::type(User::class), false, ['120363321166050533@g.us'], [], [], [], Mockery::type('array'))
            ->andReturn(true);

        // Persis yang dikirim peramban saat centang DILEPAS: hidden 0 saja,
        // sementara grup pilihannya tetap tercentang.
        $this->post(route('whatsapp.autoreply'), [
            'enabled' => '0',
            'groups' => ['120363321166050533@g.us'],
        ])->assertRedirect()->assertSessionHas('success');
    }

    public function test_halaman_menyediakan_kendali_mematikan(): void
    {
        $this->palsukanManager();

        $this->get(route('whatsapp.index'))
            ->assertOk()
            // Pasangan hidden 0 + checkbox 1 — pola baku Laravel.
            ->assertSee('name="enabled" value="0"', false)
            ->assertSee('type="checkbox" name="enabled" value="1"', false);
    }

    // -- Saat gateway tidak terjangkau --------------------------------------

    /**
     * autoreplyStatus() membalas "mati, tanpa grup" ketika gateway tak
     * terjangkau — kebetulan sama persis dengan tampilan guru yang memang
     * mematikannya. Menyajikannya sebagai fakta membuat guru menyangka
     * pengaturannya hilang lalu menyetel ulang yang sebenarnya utuh.
     */
    public function test_gateway_tak_terjangkau_tidak_disajikan_sebagai_mati(): void
    {
        /*
         * Sengaja memalsukan kelas KONKRET, bukan antarmukanya. Controller
         * memakai method_exists() untuk getCircuitStatus(), dan method itu
         * hanya ada di N8nSessionManager — mock antarmuka membuatnya bernilai
         * false, sehingga hitung mundurnya tidak pernah muncul dan tes tidak
         * mencerminkan produksi.
         */
        $mock = Mockery::mock(N8nSessionManager::class);
        $mock->shouldReceive('autoreplyStatus')->andReturn([
            'enabled' => false,
            'groups' => [],
            'jam' => null,
            'error' => 'Gateway sedang tidak tersedia.',
        ]);
        $mock->shouldReceive('isHealthy')->andReturn(false);
        $mock->shouldReceive('getCircuitStatus')->andReturn([
            'name' => 'test', 'state' => 'open', 'failures' => 3,
            'threshold' => 3, 'time_until_retry' => 45,
        ]);
        $this->app->instance(WhatsAppSessionManager::class, $mock);

        $this->get(route('whatsapp.index'))
            ->assertOk()
            ->assertSee('Tidak diketahui')
            ->assertSee('tidak hilang')
            ->assertSee('45 detik')
            // Yang paling penting: JANGAN mengaku tahu bahwa fiturnya mati.
            ->assertDontSee('Sedang mati');
    }

    /** Saat gateway sehat, tidak ada peringatan yang mengganggu. */
    public function test_gateway_sehat_tidak_memunculkan_peringatan(): void
    {
        $this->palsukanManager();

        $this->get(route('whatsapp.index'))
            ->assertOk()
            ->assertDontSee('Tidak diketahui')
            ->assertDontSee('belum bisa dibaca');
    }
}
