<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kelas ajar: kelas yang gurunya hanya mengampu mapelnya, bukan mewalikan.
 *
 * Kebanyakan wali kelas di Indonesia juga guru mapel — satu kelas diwalikan,
 * beberapa kelas lain hanya diajar. Bedanya bukan kosmetik: pada kelas ajar,
 * wali kelasnya ORANG LAIN, sehingga buku kas, struktur organisasi, laporan
 * administrasi, dan terutama grup WhatsApp orang tua bukan urusan guru mapel.
 */
class KelasAjarGuruMapelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->user = User::factory()->create(['whatsapp_number' => '628111111111']);
        $this->actingAs($this->user);
    }

    private function kelasAjar(array $atribut = []): Classroom
    {
        return Classroom::factory()->create($atribut + [
            'user_id' => $this->user->id,
            'jenis' => Classroom::JENIS_AJAR,
            'mapel' => ['Matematika'],
        ]);
    }

    // -- Bawaan tidak mengubah apa pun --------------------------------------

    public function test_kelas_lama_dan_kelas_baru_tetap_perwalian(): void
    {
        $kelas = Classroom::factory()->create(['user_id' => $this->user->id]);

        $this->assertSame(Classroom::JENIS_PERWALIAN, $kelas->jenis);
        $this->assertFalse($kelas->kelasAjar());
        $this->assertTrue($kelas->bolehAbsensiOtomatis());
    }

    // -- Penjagaan terpenting: magic link tidak boleh ke kelas ajar ---------

    /**
     * Magic link + PIN dikirim ke Seksi Absensi lewat grup/nomor kelas. Pada
     * kelas ajar grup itu milik wali kelas LAIN — mengirimnya berarti menaruh
     * tautan absensi beserta PIN di percakapan yang bukan milik pengirim.
     */
    public function test_absensi_otomatis_tidak_bisa_dinyalakan_di_kelas_ajar(): void
    {
        $kelas = $this->kelasAjar(['auto_attendance' => true]);

        $this->assertFalse($kelas->fresh()->auto_attendance, 'kelas ajar tidak boleh ber-absensi otomatis');
    }

    public function test_menyalakan_lewat_update_pun_tetap_ditolak(): void
    {
        $kelas = $this->kelasAjar();

        $kelas->update(['auto_attendance' => true]);

        $this->assertFalse($kelas->fresh()->auto_attendance);
    }

    /** Lapis kedua: penjadwal melewati kelas ajar walau datanya terlanjur true. */
    public function test_penjadwal_melewati_kelas_ajar(): void
    {
        $this->app->instance(NotificationChannel::class, new class implements NotificationChannel
        {
            public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
            {
                return true;
            }
        });

        $kelas = $this->kelasAjar(['homeroom_wa' => '628222222222']);
        // Menembus penjagaan model dengan menulis langsung ke basis data,
        // meniru baris lama yang terlanjur bernilai true.
        \DB::table('classes')->where('id', $kelas->id)->update(['auto_attendance' => 1]);

        Student::factory()->create(['user_id' => $this->user->id, 'class_id' => $kelas->id]);
        Schedule::create([
            'user_id' => $this->user->id,
            'class_id' => $kelas->id,
            'day_of_week' => now()->dayOfWeekIso,
            'subject' => 'Matematika',
            'start_time' => now()->addMinutes(5)->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
        ]);

        $this->artisan('walikelas:generate-attendance')->assertSuccessful();

        $this->assertSame(
            0,
            AttendanceSession::withoutTenant()->where('class_id', $kelas->id)->count(),
            'penjadwal tidak boleh membuat sesi untuk kelas ajar'
        );
    }

    // -- Menu ---------------------------------------------------------------

    public function test_modul_perwalian_disembunyikan_di_kelas_ajar(): void
    {
        $kelas = $this->kelasAjar();

        $balasan = $this->get(route('classes.attendance.index', $kelas))->assertOk();

        foreach (['Buku Kas', 'Struktur', 'Denah', 'Laporan'] as $modul) {
            $balasan->assertDontSee($modul);
        }
    }

    public function test_modul_perwalian_tetap_tampil_di_kelas_perwalian(): void
    {
        $kelas = Classroom::factory()->create(['user_id' => $this->user->id]);

        $this->get(route('classes.attendance.index', $kelas))
            ->assertOk()
            ->assertSee('Buku Kas')
            ->assertSee('Struktur');
    }

    // -- Pilihan jenis kelas harus BISA DIJANGKAU ---------------------------

    /**
     * Halaman Buat dan Ubah dulu menyalin kolom formulir yang sama, dan
     * salinannya menyimpang: pilihan jenis kelas ditambahkan di partial yang
     * hanya dipakai halaman Ubah, sehingga fiturnya ada di kode tapi tidak
     * bisa dijangkau dari tempat orang pertama kali membuat kelas.
     */
    public function test_pilihan_jenis_kelas_tampil_di_halaman_buat(): void
    {
        $this->get(route('classes.create'))
            ->assertOk()
            ->assertSee('Kelas Ajar (Guru Mapel)')
            ->assertSee('name="jenis" value="ajar"', false)
            ->assertSee('name="mapel[]"', false);
    }

    public function test_pilihan_jenis_kelas_tampil_di_halaman_ubah(): void
    {
        $kelas = $this->kelasAjar();

        $this->get(route('classes.edit', $kelas))
            ->assertOk()
            ->assertSee('Kelas Ajar (Guru Mapel)')
            ->assertSee('name="mapel[]"', false)
            // Mapel yang sudah tersimpan harus terisi kembali di formulir.
            ->assertSee('value="Matematika"', false);
    }

    /**
     * Bagian WhatsApp (absensi otomatis + nomor penerimanya) hanya milik kelas
     * perwalian, dan penyembunyiannya harus terikat pada pilihan jenis kelas —
     * bukan sekadar tersembunyi saat halaman dimuat.
     */
    public function test_absensi_otomatis_terikat_pada_pilihan_jenis_kelas(): void
    {
        $balasan = $this->get(route('classes.create'))->assertOk();

        // Blok WhatsApp muncul hanya ketika jenisnya BUKAN kelas ajar.
        $balasan->assertSee('x-show="jenis !== \'ajar\'"', false);

        // Dan blok itu memang yang memuat absensi otomatis.
        $isi = $balasan->getContent();
        $mulai = strpos($isi, 'x-show="jenis !== \'ajar\'"');
        $this->assertNotFalse($mulai, 'pembungkus blok WhatsApp tidak ditemukan');
        $this->assertStringContainsString(
            'name="auto_attendance"',
            substr($isi, $mulai),
            'absensi otomatis harus berada DI DALAM blok yang disembunyikan'
        );
    }

    /** Membuka formulir kelas ajar langsung menampilkan keadaan yang benar. */
    public function test_formulir_kelas_ajar_terbuka_dengan_jenis_ajar(): void
    {
        $kelas = $this->kelasAjar();

        $this->get(route('classes.edit', $kelas))
            ->assertOk()
            ->assertSee("x-data=\"{ jenis: 'ajar' }\"", false);
    }

    // -- Presensi mapel -----------------------------------------------------

    public function test_presensi_menyimpan_mapel_dan_materi(): void
    {
        $kelas = $this->kelasAjar();
        $siswa = Student::factory()->create(['user_id' => $this->user->id, 'class_id' => $kelas->id]);

        $this->post(route('classes.attendance.manual.store', $kelas), [
            'session_date' => today()->toDateString(),
            'mapel' => 'Matematika',
            'materi' => 'Sistem bilangan biner',
            'attendance' => [$siswa->id => 'hadir'],
        ])->assertSessionHasNoErrors();

        $sesi = AttendanceSession::withoutTenant()->where('class_id', $kelas->id)->firstOrFail();

        $this->assertSame('Matematika', $sesi->mapel);
        $this->assertSame('Sistem bilangan biner', $sesi->materi);
    }

    /** Presensi wali kelas tetap tanpa mapel — tidak boleh ikut berubah. */
    public function test_presensi_wali_kelas_tetap_tanpa_mapel(): void
    {
        $kelas = Classroom::factory()->create(['user_id' => $this->user->id]);
        $siswa = Student::factory()->create(['user_id' => $this->user->id, 'class_id' => $kelas->id]);

        $this->post(route('classes.attendance.manual.store', $kelas), [
            'session_date' => today()->toDateString(),
            'attendance' => [$siswa->id => 'hadir'],
        ])->assertSessionHasNoErrors();

        $sesi = AttendanceSession::withoutTenant()->where('class_id', $kelas->id)->firstOrFail();

        $this->assertNull($sesi->mapel);
        $this->assertNull($sesi->materi);
    }

    // -- Daftar mapel -------------------------------------------------------

    public function test_mapel_kosong_dibuang_saat_menyimpan_kelas(): void
    {
        $this->post(route('classes.store'), [
            'name' => 'XII TKJ A',
            'jenis' => 'ajar',
            'mapel' => ['Matematika', '   ', ''],
        ])->assertSessionHasNoErrors();

        $kelas = Classroom::where('name', 'XII TKJ A')->firstOrFail();

        $this->assertSame(['Matematika'], $kelas->mapelDiampu());
    }

    public function test_bisa_mengampu_dua_mapel(): void
    {
        $kelas = $this->kelasAjar(['mapel' => ['Matematika', 'Informatika']]);

        $this->assertSame(['Matematika', 'Informatika'], $kelas->mapelDiampu());
    }
}
