<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Notifications\N8nSessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WhatsApp membatasi laju groupFetchAllParticipating dengan ketat: dua
 * permintaan berdekatan sudah cukup memicu "rate-overlimit". Dua sifat yang
 * dikunci di sini:
 *
 *  1. Hasil yang berhasil di-cache, supaya memuat halaman berulang kali tidak
 *     memanggil WhatsApp berulang kali.
 *  2. Kegagalan dilaporkan sebagai kegagalan, bukan sebagai daftar kosong —
 *     dan tidak ikut tersimpan di cache.
 */
class DaftarGrupWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * CACHE_STORE=array bertahan sepanjang satu proses PHPUnit, jadi tanpa
         * pembersihan ini penghitung CircuitBreaker menumpuk antar-test dan
         * membuka circuit di test yang sebenarnya sehat.
         */
        Cache::flush();

        // phpunit.xml memakai WHATSAPP_DRIVER=log, yang mengikat NullSessionManager.
        // Test ini menguji implementasi n8n, jadi ikatannya ditimpa.
        $this->app->bind(
            \App\Support\Contracts\WhatsAppSessionManager::class,
            fn () => $this->manager(),
        );
    }

    private function guru(): User
    {
        return User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'wa_session_id' => null,
            'wa_session_status' => 'connected',
        ]);
    }

    private function manager(): N8nSessionManager
    {
        return new N8nSessionManager('http://127.0.0.1:3000/session', 'rahasia');
    }

    private function grupPalsu(): array
    {
        return [
            ['id' => '628123-1@g.us', 'subject' => 'ORANG TUA/WALI XII TKJE', 'peserta' => 30],
            ['id' => '120363111@g.us', 'subject' => 'Wali Murid 12 PPLG', 'peserta' => 13],
        ];
    }

    public function test_hasil_berhasil_disimpan_di_cache(): void
    {
        $guru = $this->guru();
        Http::fake(fn () => Http::response(['groups' => $this->grupPalsu()], 200));

        $m = $this->manager();

        $pertama = $m->groupsResult($guru);
        $this->assertTrue($pertama['ok']);
        $this->assertFalse($pertama['cached']);
        $this->assertCount(2, $pertama['groups']);

        $kedua = $m->groupsResult($guru);
        $this->assertTrue($kedua['ok']);
        $this->assertTrue($kedua['cached'], 'Panggilan kedua harus dilayani dari cache');
        $this->assertSame($pertama['groups'], $kedua['groups']);

        // Inti perbaikannya: WhatsApp hanya dihubungi sekali untuk dua panggilan.
        $this->assertCount(1, Http::recorded());
    }

    public function test_muat_ulang_paksa_melewati_cache(): void
    {
        $guru = $this->guru();
        Http::fake(fn () => Http::response(['groups' => $this->grupPalsu()], 200));

        $m = $this->manager();
        $m->groupsResult($guru);
        $m->groupsResult($guru);

        $paksa = $m->groupsResult($guru, true);

        $this->assertTrue($paksa['ok']);
        $this->assertFalse($paksa['cached']);
        $this->assertCount(2, Http::recorded(), 'Muat ulang paksa harus menghubungi gateway lagi');
    }

    /**
     * Http::fake() yang dipanggil dua kali MENGGABUNGKAN stub, dan callback
     * pertama tetap menang. Untuk mengubah perilaku gateway di tengah test,
     * satu-satunya cara yang benar adalah satu fake dengan saklar.
     */
    private function gatewayBerubah(object $saklar): void
    {
        Http::fake(fn () => $saklar->gagal
            ? Http::response(['error' => 'rate-overlimit'], 500)
            : Http::response(['groups' => $this->grupPalsu()], 200));
    }

    public function test_muat_ulang_gagal_tidak_menghapus_daftar_yang_sudah_ada(): void
    {
        $guru = $this->guru();
        $saklar = new \stdClass;
        $saklar->gagal = false;
        $this->gatewayBerubah($saklar);

        $m = $this->manager();
        $m->groupsResult($guru); // isi cache

        // Guru menekan "Muat ulang" tepat saat WhatsApp membatasi laju.
        $saklar->gagal = true;
        $hasil = $m->groupsResult($guru, true);

        $this->assertTrue($hasil['ok'], 'Daftar lama masih layak ditampilkan');
        $this->assertCount(2, $hasil['groups'], 'Daftar tidak boleh dikosongkan');
        $this->assertTrue($hasil['cached']);
        $this->assertNotNull($hasil['error'], 'Kegagalan menyegarkan tetap harus dilaporkan');
    }

    public function test_endpoint_tetap_200_saat_muat_ulang_gagal_tapi_cache_ada(): void
    {
        $guru = $this->guru();
        $saklar = new \stdClass;
        $saklar->gagal = false;
        $this->gatewayBerubah($saklar);

        $this->actingAs($guru)->getJson(route('whatsapp.groups'))->assertOk();

        $saklar->gagal = true;

        $this->actingAs($guru)
            ->getJson(route('whatsapp.groups', ['refresh' => 1]))
            ->assertOk()
            ->assertJson(['ok' => true, 'cached' => true])
            ->assertJsonCount(2, 'groups')
            ->assertJsonPath('warning', 'Gagal menyegarkan daftar grup, yang ditampilkan adalah data terakhir. Coba lagi sebentar.');
    }

    public function test_kegagalan_dilaporkan_bukan_sebagai_daftar_kosong(): void
    {
        $guru = $this->guru();
        Http::fake(fn () => Http::response(['error' => 'rate-overlimit'], 500));

        $hasil = $this->manager()->groupsResult($guru);

        $this->assertFalse($hasil['ok'], 'Gateway 500 harus dilaporkan gagal');
        $this->assertSame([], $hasil['groups']);
        $this->assertNotNull($hasil['error']);
    }

    public function test_kegagalan_tidak_disimpan_di_cache(): void
    {
        $guru = $this->guru();
        $saklar = new \stdClass;
        $saklar->gagal = true;
        $this->gatewayBerubah($saklar);

        $m = $this->manager();
        $this->assertFalse($m->groupsResult($guru)['ok']);
        $this->assertFalse($m->groupsResult($guru)['ok']);

        // Kalau kegagalan ikut di-cache, panggilan kedua tidak akan keluar.
        $this->assertCount(2, Http::recorded());

        // Dan begitu gateway pulih, hasilnya harus langsung benar.
        $saklar->gagal = false;
        $pulih = $m->groupsResult($guru);

        $this->assertTrue($pulih['ok']);
        $this->assertCount(2, $pulih['groups']);
    }

    public function test_daftar_kosong_yang_sah_berbeda_dari_kegagalan(): void
    {
        $guru = $this->guru();
        Http::fake(fn () => Http::response(['groups' => []], 200));

        $hasil = $this->manager()->groupsResult($guru);

        $this->assertTrue($hasil['ok'], 'Guru tanpa grup bukan kondisi gagal');
        $this->assertSame([], $hasil['groups']);
        $this->assertNull($hasil['error']);
    }

    public function test_cache_dibuang_saat_sesi_diputus(): void
    {
        $guru = $this->guru();
        Http::fake(fn () => Http::response(['groups' => $this->grupPalsu(), 'ok' => true], 200));

        $m = $this->manager();
        $m->groupsResult($guru);
        $this->assertNotNull(Cache::get('wa:grup:guru-'.$guru->id));

        $m->disconnect($guru);

        $this->assertNull(
            Cache::get('wa:grup:guru-'.$guru->id),
            'Nomor bisa berganti setelah diputus; daftar grup lama tidak boleh bertahan'
        );
    }

    // -- Endpoint JSON yang dipakai halaman WhatsApp -----------------------

    public function test_endpoint_melaporkan_ok_false_saat_pengambilan_gagal(): void
    {
        $guru = $this->guru();
        Http::fake(fn () => Http::response(['error' => 'rate-overlimit'], 500));

        $this->actingAs($guru)
            ->getJson(route('whatsapp.groups'))
            ->assertStatus(503)
            ->assertJson(['connected' => true, 'ok' => false, 'groups' => []])
            ->assertJsonPath('gateway_healthy', true);
    }

    public function test_endpoint_melaporkan_ok_true_saat_berhasil(): void
    {
        $guru = $this->guru();
        Http::fake(fn () => Http::response(['groups' => $this->grupPalsu()], 200));

        $this->actingAs($guru)
            ->getJson(route('whatsapp.groups'))
            ->assertOk()
            ->assertJson(['connected' => true, 'ok' => true])
            ->assertJsonCount(2, 'groups');
    }

    // -- Nama grup diingat, supaya pilihan tersimpan tak perlu dipindai ----
    //
    // Grup yang sudah dipilih dan disimpan hanya perlu ditampilkan namanya.
    // Selama nama itu tidak tersimpan di mana pun, satu-satunya cara
    // mengetahuinya adalah memindai seluruh daftar grup — sehingga setiap kali
    // halaman dibuka WhatsApp ikut dihubungi, padahal tidak ada yang berubah.

    public function test_nama_grup_diingat_setelah_pemindaian_berhasil(): void
    {
        $guru = $this->guru();
        Http::fake(fn () => Http::response(['groups' => $this->grupPalsu()], 200));

        $m = $this->manager();
        $m->groupsResult($guru);

        $label = $m->groupLabels($guru);

        $this->assertSame(
            ['subject' => 'Wali Murid 12 PPLG', 'peserta' => 13],
            $label['120363111@g.us'] ?? null,
            'Nama grup harus bisa ditemukan lewat JID-nya tanpa memindai ulang'
        );
    }

    public function test_groupLabels_tidak_pernah_menghubungi_gateway(): void
    {
        $guru = $this->guru();
        Http::fake(fn () => Http::response(['groups' => $this->grupPalsu()], 200));

        $m = $this->manager();
        $m->groupsResult($guru);

        $sebelum = count(Http::recorded());
        $m->groupLabels($guru);
        $m->groupLabels($guru);

        $this->assertCount(
            $sebelum,
            Http::recorded(),
            'groupLabels() dibaca murni dari simpanan; menghubungi gateway di sini '
            .'justru mengembalikan pemindaian yang ingin dihindari'
        );
    }

    public function test_nama_grup_lama_tidak_hilang_saat_pemindaian_berikutnya_tidak_memuatnya(): void
    {
        $guru = $this->guru();
        $saklar = new \stdClass;
        $saklar->sebagian = false;

        /*
         * Pengambilan kedua hanya melaporkan satu grup — hal biasa saat
         * WhatsApp memangkas hasil atau guru sementara keluar dari satu grup.
         * Kalau peta nama ditimpa, grup yang MASIH dipilih kehilangan namanya
         * dan tampil sebagai JID mentah di layar guru.
         */
        Http::fake(fn () => Http::response([
            'groups' => $saklar->sebagian
                ? [['id' => '628123-1@g.us', 'subject' => 'ORANG TUA/WALI XII TKJE', 'peserta' => 31]]
                : $this->grupPalsu(),
        ], 200));

        $m = $this->manager();
        $m->groupsResult($guru);

        $saklar->sebagian = true;
        $m->groupsResult($guru, true);

        $label = $m->groupLabels($guru);

        $this->assertSame('Wali Murid 12 PPLG', $label['120363111@g.us']['subject'] ?? null,
            'Nama grup yang tidak terbawa pengambilan terakhir tetap harus diingat');
        $this->assertSame(31, $label['628123-1@g.us']['peserta'] ?? null,
            'Grup yang ikut terbawa harus tersegarkan, bukan sekadar dipertahankan');
    }

    public function test_halaman_membawa_nama_grup_tersimpan_ke_peramban(): void
    {
        $guru = $this->guru();

        Http::fake(function ($request) {
            $isi = $request->data();

            return match ($isi['action'] ?? '') {
                'autoreply-status' => Http::response([
                    'enabled' => true,
                    'groups' => ['120363111@g.us'],
                    'jam' => '06:00-15:00',
                ], 200),
                default => Http::response(['groups' => $this->grupPalsu()], 200),
            };
        });

        // Pemindaian yang pernah terjadi sebelumnya — inilah yang mengisi peta nama.
        $this->manager()->groupsResult($guru);

        /*
         * Halaman harus mengirim nama grupnya sendiri ke peramban. Tanpa itu
         * satu-satunya cara menampilkan pilihan tersimpan adalah memindai dari
         * sisi peramban begitu halaman terbuka.
         */
        $this->actingAs($guru)
            ->get(route('whatsapp.index'))
            ->assertOk()
            ->assertSee('Wali Murid 12 PPLG', false)
            ->assertSee('120363111@g.us', false);
    }
}
