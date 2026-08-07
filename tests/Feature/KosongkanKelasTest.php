<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mengosongkan kelas sekaligus.
 *
 * Satu-satunya tombol di aplikasi ini yang menghapus puluhan baris dalam sekali
 * tekan, jadi penjagaannya diuji lebih ketat daripada tombol hapus lain: bukan
 * hanya bahwa ia bekerja, tetapi bahwa ia TIDAK bekerja ketika seharusnya
 * tidak — konfirmasi yang salah, dan kelas milik guru lain.
 */
class KosongkanKelasTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create();
        $this->actingAs($this->guru);
    }

    private function kelas(string $nama, string $jenis = Classroom::JENIS_PERWALIAN): Classroom
    {
        return Classroom::factory()->create([
            'user_id' => $this->guru->id,
            'name' => $nama,
            'jenis' => $jenis,
        ]);
    }

    private function isi(Classroom $kelas, int $jumlah): void
    {
        Student::factory()->count($jumlah)->create([
            'user_id' => $kelas->user_id,
            'class_id' => $kelas->id,
            'is_active' => true,
        ]);
    }

    // -- Jalur utama --------------------------------------------------------

    public function test_seluruh_siswa_pindah_ke_arsip(): void
    {
        $kelas = $this->kelas('XII TKJ D');
        $this->isi($kelas, 5);

        $this->delete(route('classes.students.destroy-all', $kelas), ['nama_kelas' => 'XII TKJ D'])
            ->assertRedirect(route('classes.students.index', $kelas))
            ->assertSessionHas('success');

        $this->assertSame(0, $kelas->students()->count(), 'daftar aktif harus kosong');
        $this->assertSame(5, $kelas->students()->onlyTrashed()->count(), 'kelimanya harus ada di arsip');
    }

    /** Guru mapel mengimpor daftarnya sendiri, jadi fiturnya berlaku di sana juga. */
    public function test_berlaku_juga_di_kelas_ajar(): void
    {
        $kelas = $this->kelas('XII RPL', Classroom::JENIS_AJAR);
        $this->isi($kelas, 3);

        $this->delete(route('classes.students.destroy-all', $kelas), ['nama_kelas' => 'XII RPL'])
            ->assertRedirect(route('classes.students.index', $kelas));

        $this->assertSame(0, $kelas->students()->count());
        $this->assertSame(3, $kelas->students()->onlyTrashed()->count());
    }

    /**
     * Soft delete, bukan penghapusan sungguhan: yang dihapus masih bisa
     * dipulihkan lewat halaman Arsip yang sudah ada.
     */
    public function test_siswa_yang_diarsipkan_bisa_dipulihkan(): void
    {
        $kelas = $this->kelas('XII TKJ D');
        $this->isi($kelas, 2);

        $this->delete(route('classes.students.destroy-all', $kelas), ['nama_kelas' => 'XII TKJ D']);

        $siswa = $kelas->students()->onlyTrashed()->first();

        $this->patch(route('classes.students.restore', [$kelas, $siswa->id]))
            ->assertRedirect(route('classes.students.trashed', $kelas));

        $this->assertSame(1, $kelas->students()->count());
    }

    /** Riwayat absensi tidak ikut hilang — itulah gunanya soft delete. */
    public function test_riwayat_absensi_tetap_utuh(): void
    {
        $kelas = $this->kelas('XII TKJ D');
        $this->isi($kelas, 1);
        $siswa = $kelas->students()->first();

        $sesi = AttendanceSession::create([
            'user_id' => $this->guru->id,
            'class_id' => $kelas->id,
            'session_date' => today(),
            'sequence' => 1,
            'token' => 'tok'.uniqid(),
            'pin_hash' => bcrypt('123456'),
            'expires_at' => now()->addDay(),
            'status' => 'submitted',
        ]);
        Attendance::create([
            'user_id' => $this->guru->id,
            'attendance_session_id' => $sesi->id,
            'student_id' => $siswa->id,
            'status' => 'hadir',
        ]);

        $this->delete(route('classes.students.destroy-all', $kelas), ['nama_kelas' => 'XII TKJ D']);

        $this->assertDatabaseHas('attendances', ['student_id' => $siswa->id, 'status' => 'hadir']);
    }

    // -- Penjagaan ----------------------------------------------------------

    public function test_nama_kelas_salah_tidak_menghapus_apa_pun(): void
    {
        $kelas = $this->kelas('XII TKJ D');
        $this->isi($kelas, 4);

        $this->from(route('classes.students.index', $kelas))
            ->delete(route('classes.students.destroy-all', $kelas), ['nama_kelas' => 'XII TKJ C'])
            ->assertRedirect(route('classes.students.index', $kelas))
            ->assertSessionHasErrors('nama_kelas');

        $this->assertSame(4, $kelas->students()->count(), 'tidak satu pun boleh terhapus');
    }

    public function test_tanpa_konfirmasi_tidak_menghapus_apa_pun(): void
    {
        $kelas = $this->kelas('XII TKJ D');
        $this->isi($kelas, 4);

        $this->from(route('classes.students.index', $kelas))
            ->delete(route('classes.students.destroy-all', $kelas), [])
            ->assertSessionHasErrors('nama_kelas');

        $this->assertSame(4, $kelas->students()->count());
    }

    /**
     * Atribut `disabled` pada tombol hanya menahan salah tekan di browser.
     * Permintaan yang dikirim langsung harus tetap ditolak server, kalau tidak
     * pengamannya sekadar hiasan.
     */
    public function test_beda_huruf_besar_kecil_dan_spasi_tetap_diterima(): void
    {
        $kelas = $this->kelas('XII TKJ D');
        $this->isi($kelas, 2);

        $this->delete(route('classes.students.destroy-all', $kelas), ['nama_kelas' => '  xii   tkj d '])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $kelas->students()->count());
    }

    public function test_tidak_bisa_mengosongkan_kelas_guru_lain(): void
    {
        $lain = User::factory()->create();
        $kelasLain = Classroom::factory()->create([
            'user_id' => $lain->id,
            'name' => 'XII IPA 1',
        ]);
        Student::factory()->count(3)->create([
            'user_id' => $lain->id,
            'class_id' => $kelasLain->id,
        ]);

        // $this->guru sudah login dari setUp().
        $this->delete(route('classes.students.destroy-all', $kelasLain), ['nama_kelas' => 'XII IPA 1'])
            ->assertNotFound();

        $this->assertSame(3, $kelasLain->students()->withoutGlobalScopes()->count());
    }

    public function test_kelas_kosong_tidak_menimbulkan_galat(): void
    {
        $kelas = $this->kelas('XII TKJ D');

        $this->delete(route('classes.students.destroy-all', $kelas), ['nama_kelas' => 'XII TKJ D'])
            ->assertRedirect(route('classes.students.index', $kelas))
            ->assertSessionHas('info');
    }

    // -- Tampilan -----------------------------------------------------------

    public function test_tombol_muncul_saat_ada_siswa(): void
    {
        $kelas = $this->kelas('XII TKJ D');
        $this->isi($kelas, 3);

        $this->get(route('classes.students.index', $kelas))
            ->assertOk()
            ->assertSee('Kosongkan Kelas')
            ->assertSee('Pindahkan 3 Siswa ke Arsip');
    }

    /** Kelas yang memang kosong tidak perlu menawarkan tombol pengosongan. */
    public function test_tombol_disembunyikan_saat_kelas_kosong(): void
    {
        $kelas = $this->kelas('XII TKJ D');

        $this->get(route('classes.students.index', $kelas))
            ->assertOk()
            ->assertDontSee('Kosongkan Kelas');
    }

    /**
     * Rutenya statis dan didaftarkan sebelum Route::resource('students').
     * Bila urutannya terbalik, "hapus-semua" tertangkap sebagai students/{student}
     * dan tombolnya menghasilkan 404 — kegagalan yang hanya muncul di produksi.
     */
    public function test_rute_hapus_semua_tidak_tertukar_dengan_siswa(): void
    {
        $kelas = $this->kelas('XII TKJ D');
        $this->isi($kelas, 1);

        $this->assertStringEndsWith(
            '/students/hapus-semua',
            route('classes.students.destroy-all', $kelas),
        );

        $this->delete(route('classes.students.destroy-all', $kelas), ['nama_kelas' => 'XII TKJ D'])
            ->assertRedirect(route('classes.students.index', $kelas));
    }
}
