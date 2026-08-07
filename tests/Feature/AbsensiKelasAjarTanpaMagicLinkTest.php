<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Kelas ajar tidak menerbitkan magic link absensi.
 *
 * Alur magic link mengirim tautan + PIN ke Seksi Absensi lewat grup atau nomor
 * kelas. Pada kelas ajar keduanya milik WALI KELAS LAIN, jadi mengirimkannya
 * berarti menaruh tautan absensi beserta PIN-nya di percakapan yang bukan milik
 * pengirim — persis alasan yang sudah menutup jalur penjadwal lewat
 * Classroom::bolehAbsensiOtomatis().
 *
 * Jalur satunya tetap terbuka sampai sekarang: tombol "Buat & Terbitkan Link"
 * di tab Absensi, yang centang "Kirim Otomatis via WhatsApp"-nya bahkan MENYALA
 * secara bawaan — sehingga guru mapel yang menekannya tanpa mengubah apa pun
 * mengirim PIN ke luar tanpa pernah memintanya.
 */
class AbsensiKelasAjarTanpaMagicLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create(['whatsapp_number' => '628111111111']);
        $this->guru->forceFill(['wa_session_status' => 'connected'])->save();
        $this->actingAs($this->guru);
    }

    private function kelas(string $jenis): Classroom
    {
        $kelas = Classroom::factory()->create([
            'user_id' => $this->guru->id,
            'name' => $jenis === Classroom::JENIS_AJAR ? 'XII RPL' : 'XII TKJ D',
            'jenis' => $jenis,
        ]);

        Student::factory()->count(2)->create([
            'user_id' => $this->guru->id,
            'class_id' => $kelas->id,
            'is_active' => true,
        ]);

        return $kelas;
    }

    // -- Tampilan -----------------------------------------------------------

    public function test_tab_absensi_kelas_ajar_tidak_menawarkan_sesi_otomatis(): void
    {
        $kelas = $this->kelas(Classroom::JENIS_AJAR);

        $this->get(route('classes.attendance.index', $kelas))
            ->assertOk()
            ->assertDontSee('Buat Sesi Absensi Baru')
            ->assertDontSee('Kirim Otomatis via WhatsApp')
            ->assertDontSee('Buat &amp; Terbitkan Link', false)
            ->assertDontSee('Nomor Tujuan WA')
            // Yang ditawarkan sebagai gantinya: mengisi kehadiran langsung.
            ->assertSee('Isi Absensi Sekarang')
            ->assertSee(route('classes.attendance.manual.create', $kelas), false);
    }

    public function test_tab_absensi_kelas_perwalian_tetap_menawarkannya(): void
    {
        $kelas = $this->kelas(Classroom::JENIS_PERWALIAN);

        $this->get(route('classes.attendance.index', $kelas))
            ->assertOk()
            ->assertSee('Buat Sesi Absensi Baru')
            ->assertSee('Kirim Otomatis via WhatsApp');
    }

    // -- Penjagaan server ----------------------------------------------------

    /**
     * Penjagaan tampilan saja tidak cukup: permintaan yang dikirim langsung
     * ke rute store — tanpa melewati formulir yang sudah tidak dirender —
     * tetap harus gagal mengirim apa pun.
     */
    public function test_kelas_ajar_tidak_mengirim_magic_link_walau_diminta(): void
    {
        $kelas = $this->kelas(Classroom::JENIS_AJAR);

        $palsu = Mockery::mock(NotificationChannel::class);
        $palsu->shouldNotReceive('send');
        $this->app->instance(NotificationChannel::class, $palsu);

        $this->post(route('classes.attendance.store', $kelas), [
            'send_wa' => 1,
            // Nomor diketik langsung, jadi tidak bergantung pada ada-tidaknya
            // Seksi Absensi di kelas ini.
            'target_number' => '628999999999',
        ])->assertRedirect();

        // Sesinya sendiri tetap boleh lahir; yang dilarang hanya pengirimannya.
        $this->assertSame(1, $kelas->attendanceSessions()->count());
    }

    public function test_kirim_ulang_pin_kelas_ajar_juga_tidak_mengirim(): void
    {
        $kelas = $this->kelas(Classroom::JENIS_AJAR);

        $this->post(route('classes.attendance.store', $kelas), ['send_wa' => 0]);
        $sesi = $kelas->attendanceSessions()->firstOrFail();

        $palsu = Mockery::mock(NotificationChannel::class);
        $palsu->shouldNotReceive('send');
        $this->app->instance(NotificationChannel::class, $palsu);

        $this->post(route('classes.attendance.resend', [$kelas, $sesi]), [
            'target_number' => '628999999999',
        ])->assertRedirect();
    }

    /** Kelas perwalian tidak boleh ikut terkunci oleh penjagaan di atas. */
    public function test_kelas_perwalian_masih_bisa_mengirim_magic_link(): void
    {
        $kelas = $this->kelas(Classroom::JENIS_PERWALIAN);

        $palsu = Mockery::mock(NotificationChannel::class);
        $palsu->shouldReceive('send')->atLeast()->once()->andReturn(true);
        $this->app->instance(NotificationChannel::class, $palsu);

        $this->post(route('classes.attendance.store', $kelas), [
            'send_wa' => 1,
            'target_number' => '628999999999',
        ])->assertRedirect();
    }

    /** Guru mapel tetap bisa mengisi kehadiran — tidak ada yang hilang. */
    public function test_absensi_manual_kelas_ajar_tetap_berjalan(): void
    {
        $kelas = $this->kelas(Classroom::JENIS_AJAR);
        $siswa = $kelas->students;

        $this->post(route('classes.attendance.manual.store', $kelas), [
            'session_date' => today()->toDateString(),
            'mapel' => 'Informatika',
            'materi' => 'Perulangan',
            'attendance' => $siswa->mapWithKeys(fn ($s) => [$s->id => 'hadir'])->all(),
        ])->assertRedirect(route('classes.attendance.index', $kelas));

        $this->assertSame(1, $kelas->attendanceSessions()->count());
        $this->assertDatabaseHas('attendance_sessions', [
            'class_id' => $kelas->id,
            'mapel' => 'Informatika',
            'status' => 'submitted',
        ]);
    }
}
