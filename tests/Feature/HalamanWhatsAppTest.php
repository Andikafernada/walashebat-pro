<?php

namespace Tests\Feature;

use App\Console\Commands\KirimPengingatSpp;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Halaman /whatsapp pernah memuat ulang tanpa henti.
 *
 * Penyebabnya: init() memanggil poll() seketika, dan poll() memanggil
 * window.location.reload() begitu status 'connected'. Untuk guru yang sesinya
 * memang sudah tersambung, keduanya bertemu di setiap pemuatan halaman →
 * lingkaran tak berujung. Bug ini baru muncul setelah komunikasi ke gateway
 * pulih; sebelumnya status selalu 'disconnected' sehingga cabangnya tak pernah
 * tercapai.
 */
class HalamanWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::fake(fn () => Http::response(['status' => 'connected', 'groups' => []], 200));
    }

    private function guru(string $status): User
    {
        return User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'wa_session_status' => $status,
        ]);
    }

    public function test_halaman_terbuka_untuk_guru_yang_sudah_tersambung(): void
    {
        $this->actingAs($this->guru('connected'))
            ->get(route('whatsapp.index'))
            ->assertOk();
    }

    public function test_guru_tersambung_tidak_memulai_polling_yang_memicu_muat_ulang(): void
    {
        $html = $this->actingAs($this->guru('connected'))
            ->get(route('whatsapp.index'))->assertOk()->getContent();

        // Komponen tahu sesi sudah tersambung sejak halaman dibuka…
        $this->assertStringContainsString('connected: true', $html);

        // …dan init() berhenti sebelum sempat memanggil poll(), yang di dalamnya
        // ada window.location.reload().
        $this->assertStringContainsString(
            "if (this.status === 'connected') return;",
            $html,
            'Penjaga anti-loop di init() hilang — halaman akan memuat ulang tanpa henti'
        );
    }

    public function test_guru_belum_tersambung_tetap_mendapat_polling(): void
    {
        $html = $this->actingAs($this->guru('disconnected'))
            ->get(route('whatsapp.index'))->assertOk()->getContent();

        $this->assertStringContainsString('connected: false', $html);

        // Polling tetap dibutuhkan untuk mendeteksi QR yang baru dipindai.
        $this->assertStringContainsString('setInterval(() => this.poll(), 2500)', $html);
    }

    public function test_muat_ulang_hanya_dipakai_sekali_pada_peralihan(): void
    {
        $html = $this->actingAs($this->guru('disconnected'))
            ->get(route('whatsapp.index'))->assertOk()->getContent();

        // Komentar JS ikut terkirim ke browser dan bisa menyebut nama fungsinya,
        // jadi buang dulu agar yang dihitung benar-benar pemanggilan.
        $tanpaKomentar = preg_replace('#/\*.*?\*/|//[^\n]*#s', '', $html);

        // Satu-satunya reload yang boleh ada: setelah QR berhasil dipindai.
        $this->assertSame(
            1,
            substr_count($tanpaKomentar, 'window.location.reload()'),
            'Muat ulang otomatis hanya boleh ada di jalur peralihan sesudah pemindaian QR'
        );
    }

    /**
     * Pengingat SPP pindah ke halaman ini (dulu di Buku Kas): targetnya grup
     * WhatsApp, seekor dengan pengaturan WA lain.
     */
    public function test_kelas_tanpa_grup_wa_melihat_peringatan_bukan_formulir(): void
    {
        $guru = $this->guru('connected');
        Classroom::factory()->create(['user_id' => $guru->id, 'name' => 'XII RPL 1', 'parent_group_wa' => null]);

        $html = $this->actingAs($guru)->get(route('whatsapp.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Pengingat Iuran Bulanan — XII RPL 1', $html);
        $this->assertStringContainsString('belum diatur di halaman Kelas', $html);
        $this->assertStringNotContainsString('name="spp_pengingat_teks"', $html);
    }

    /** Guru berhak melihat persis pesan yang akan dikirim, bukan kotak kosong. */
    public function test_teks_kosong_menampilkan_pesan_bawaan_sungguhan(): void
    {
        $guru = $this->guru('connected');
        Classroom::factory()->create([
            'user_id' => $guru->id, 'name' => 'XII RPL 1',
            'parent_group_wa' => '628111@g.us', 'spp_pengingat_teks' => null,
        ]);

        $html = $this->actingAs($guru)->get(route('whatsapp.index'))->assertOk()->getContent();

        $this->assertStringContainsString(e(KirimPengingatSpp::TEKS_BAWAAN), $html);
    }

    public function test_teks_yang_sudah_diisi_guru_tidak_ditimpa_bawaan(): void
    {
        $guru = $this->guru('connected');
        Classroom::factory()->create([
            'user_id' => $guru->id, 'name' => 'XII RPL 1',
            'parent_group_wa' => '628111@g.us', 'spp_pengingat_teks' => 'Mohon iuran bulan ini ya Bapak/Ibu.',
        ]);

        $html = $this->actingAs($guru)->get(route('whatsapp.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Mohon iuran bulan ini ya Bapak/Ibu.', $html);
        $this->assertStringNotContainsString(e(KirimPengingatSpp::TEKS_BAWAAN), $html);
    }

    /** Iuran kelas ajar bukan urusan guru mapel — jangan ditawarkan sama sekali. */
    public function test_kelas_ajar_tidak_menampilkan_pengingat_spp(): void
    {
        $guru = $this->guru('connected');
        Classroom::factory()->create([
            'user_id' => $guru->id, 'jenis' => Classroom::JENIS_AJAR, 'parent_group_wa' => '628111@g.us',
        ]);

        $html = $this->actingAs($guru)->get(route('whatsapp.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Pengingat Iuran Bulanan', $html);
    }

    /** Wali kelas dengan lebih dari satu kelas perwalian mengatur keduanya di sini. */
    public function test_dua_kelas_perwalian_menampilkan_dua_kartu_terpisah(): void
    {
        $guru = $this->guru('connected');
        Classroom::factory()->create(['user_id' => $guru->id, 'name' => 'X RPL 1', 'parent_group_wa' => '628111@g.us']);
        Classroom::factory()->create(['user_id' => $guru->id, 'name' => 'XI RPL 1', 'parent_group_wa' => '628222@g.us']);

        $html = $this->actingAs($guru)->get(route('whatsapp.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Pengingat Iuran Bulanan — X RPL 1', $html);
        $this->assertStringContainsString('Pengingat Iuran Bulanan — XI RPL 1', $html);
    }

    /**
     * Tautan formulir izin/sakit kini ditempel GATEWAY di setiap balasan
     * (lihat linkIzinPerGrup() di WhatsAppSessionController dan
     * balasan.tambahkanTautan() di /opt/wa-gateway/balasan.js), bukan lagi
     * disisipkan ke kalimat tambahan guru — jadi kolom templat tidak boleh
     * lagi diisi otomatis dengan tautan apa pun, termasuk untuk guru dengan
     * satu kelas perwalian.
     */
    public function test_templat_izin_sakit_tidak_lagi_disisipi_tautan_otomatis(): void
    {
        $guru = $this->guru('connected');
        $kelas = Classroom::factory()->create([
            'user_id' => $guru->id,
            'name' => 'X RPL 1',
            'parent_group_wa' => '628111@g.us',
        ]);

        $html = $this->actingAs($guru)->get(route('whatsapp.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('public.excuse.show', $kelas->tokenPublik()), $html);
    }

    /** Halaman memberi tahu guru bahwa tautan ditempel otomatis begitu grupnya sudah dipilih. */
    public function test_info_tautan_otomatis_saat_grup_sudah_dipilih(): void
    {
        $guru = $this->guru('connected');
        Classroom::factory()->create(['user_id' => $guru->id, 'name' => 'X RPL 1', 'parent_group_wa' => '628111@g.us']);

        $html = $this->actingAs($guru)->get(route('whatsapp.index'))->assertOk()->getContent();

        $this->assertStringContainsString('selalu ditambahkan otomatis', $html);
    }

    /** Sebelum grup dipilih, guru diarahkan ke halaman Kelas untuk memilihnya, bukan disuguhi janji kosong. */
    public function test_info_tautan_menunjuk_ke_pemilihan_grup_bila_belum_ada(): void
    {
        $guru = $this->guru('connected');
        Classroom::factory()->create(['user_id' => $guru->id, 'name' => 'X RPL 1', 'parent_group_wa' => null]);

        $html = $this->actingAs($guru)->get(route('whatsapp.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Bagikan Link Izin/Sakit', $html);
    }

    /** Guru yang sudah menulis templatnya sendiri tidak boleh ditimpa bawaan. */
    public function test_templat_izin_sakit_yang_sudah_diisi_guru_tidak_ditimpa(): void
    {
        $guru = $this->guru('connected');
        $guru->forceFill(['wa_permission_template' => 'Kalimat saya sendiri, {nama}.'])->save();
        Classroom::factory()->create(['user_id' => $guru->id, 'name' => 'X RPL 1']);

        $html = $this->actingAs($guru)->get(route('whatsapp.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Kalimat saya sendiri, {nama}.', $html);
    }
}
