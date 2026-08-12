<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Pengingat iuran bulanan ke grup WhatsApp orang tua.
 *
 * Yang dijaga di sini bukan bunyi pesannya, melainkan hal-hal yang kalau salah
 * TIDAK BISA ditarik kembali: pesan yang masuk grup sudah dibaca puluhan orang
 * tua sebelum siapa pun sempat menyadarinya.
 */
class PengingatIuranBulananTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->guru = User::factory()->create(['name' => 'Andika Fernanda']);
        $this->actingAs($this->guru);
    }

    private function kelas(array $timpa = []): Classroom
    {
        return Classroom::factory()->create($timpa + [
            'user_id' => $this->guru->id,
            'is_active' => true,
            'parent_group_wa' => '628123456789@g.us',
            'spp_pengingat_aktif' => true,
            'spp_pengingat_tanggal' => now()->day,
        ]);
    }

    private function jalankan(): void
    {
        $this->artisan('walikelas:kirim-pengingat-spp')->assertSuccessful();
    }

    public function test_terkirim_pada_tanggal_yang_dipilih(): void
    {
        $kelas = $this->kelas(['name' => 'XII TKJ D']);

        $this->jalankan();

        Queue::assertPushed(SendWhatsAppMessage::class, function ($job) use ($kelas) {
            return $job->to === '628123456789@g.us'
                && $job->userId === $this->guru->id
                && str_contains($job->message, 'XII TKJ D');
        });

        $this->assertTrue($kelas->fresh()->spp_pengingat_terkirim_pada->isToday());
    }

    /**
     * Yang paling penting di berkas ini. Penjadwal bisa dijalankan ulang, dan
     * pesan yang sudah masuk grup tidak bisa ditarik kembali.
     */
    public function test_tidak_mengirim_dua_kali_di_bulan_yang_sama(): void
    {
        $this->kelas();

        $this->jalankan();
        $this->jalankan();
        $this->jalankan();

        Queue::assertPushed(SendWhatsAppMessage::class, 1);
    }

    public function test_tidak_terkirim_pada_tanggal_lain(): void
    {
        $this->kelas(['spp_pengingat_tanggal' => now()->addDays(3)->day]);

        $this->jalankan();

        Queue::assertNothingPushed();
    }

    public function test_kelas_yang_mematikannya_tidak_dikirimi(): void
    {
        $this->kelas(['spp_pengingat_aktif' => false]);

        $this->jalankan();

        Queue::assertNothingPushed();
    }

    /** Tanpa grup tujuan, tidak ada yang bisa dikirimi. */
    public function test_kelas_tanpa_grup_dilewati(): void
    {
        $this->kelas(['parent_group_wa' => null]);

        $this->jalankan();

        Queue::assertNothingPushed();
    }

    /** Iuran kelas urusan wali kelasnya, bukan guru mapel. */
    public function test_kelas_ajar_dilewati(): void
    {
        $this->kelas(['jenis' => Classroom::JENIS_AJAR]);

        $this->jalankan();

        Queue::assertNothingPushed();
    }

    /**
     * Tanggal 31 tidak ada di semua bulan. Tanpa penjatuhan ke hari terakhir,
     * pengingatnya hilang empat bulan setahun tanpa ada yang menyadarinya.
     */
    public function test_tanggal_31_jatuh_ke_hari_terakhir_bulan_pendek(): void
    {
        $this->travelTo(now()->setDate(2026, 2, 28));

        $this->kelas(['spp_pengingat_tanggal' => 31]);

        $this->jalankan();

        Queue::assertPushed(SendWhatsAppMessage::class, 1);
    }

    public function test_pesan_bawaan_dipakai_bila_teks_kosong(): void
    {
        $this->kelas(['name' => 'XII TKJ D', 'spp_pengingat_teks' => null]);

        $this->jalankan();

        Queue::assertPushed(SendWhatsAppMessage::class, function ($job) {
            return str_contains($job->message, 'Pengingat Iuran Kelas XII TKJ D')
                && str_contains($job->message, 'Andika Fernanda');
        });
    }

    public function test_penanda_diterjemahkan(): void
    {
        $this->kelas([
            'name' => 'XII TKJ D',
            'spp_pengingat_teks' => 'Iuran {nama_kelas} bulan {bulan} {tahun}, salam {wali_kelas}.',
        ]);

        $this->jalankan();

        Queue::assertPushed(SendWhatsAppMessage::class, function ($job) {
            return str_contains($job->message, 'Iuran XII TKJ D bulan '.now()->translatedFormat('F').' '.now()->year)
                && str_contains($job->message, 'Andika Fernanda')
                && ! str_contains($job->message, '{');
        });
    }

    public function test_wali_kelas_bisa_menyimpan_pengaturannya(): void
    {
        $kelas = $this->kelas(['spp_pengingat_aktif' => false]);

        $this->post(route('classes.cashbook.pengingat', $kelas), [
            'spp_pengingat_aktif' => '1',
            'spp_pengingat_tanggal' => 15,
            'spp_pengingat_teks' => 'Mohon iuran bulan ini dilunasi ya Bapak/Ibu.',
        ])->assertRedirect();

        $segar = $kelas->fresh();
        $this->assertTrue($segar->spp_pengingat_aktif);
        $this->assertSame(15, $segar->spp_pengingat_tanggal);
    }

    /** "Aktif" tidak boleh berarti mengirim pesan kosong ke grup. */
    public function test_teks_terlalu_pendek_ditolak(): void
    {
        $kelas = $this->kelas();

        $this->post(route('classes.cashbook.pengingat', $kelas), [
            'spp_pengingat_aktif' => '1',
            'spp_pengingat_tanggal' => 10,
            'spp_pengingat_teks' => '-',
        ])->assertSessionHasErrors('spp_pengingat_teks');
    }
}
