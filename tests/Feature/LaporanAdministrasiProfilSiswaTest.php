<?php

namespace Tests\Feature;

use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use App\Services\ClassReportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Buku administrasi memuat lembar profil siswa sebagai lampiran.
 *
 * Dulu lembar-lembar ini berdiri sendiri sebagai tombol terpisah di halaman
 * Data Siswa, sehingga rapat administrasi menerima DUA berkas yang harus
 * disatukan sendiri. Sekarang satu berkas: sampul, bab-babnya, lalu satu
 * halaman penuh per siswa di belakang.
 */
class LaporanAdministrasiProfilSiswaTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private Classroom $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create(['name' => 'Andika Fernanda']);
        $this->kelas = Classroom::factory()->create(['user_id' => $this->guru->id]);

        /*
         * WAJIB. ClassReportBuilder dipanggil langsung di sini, tanpa melewati
         * permintaan HTTP — dan TenantScope gagal-tertutup: tanpa tenant yang
         * dinyatakan, $class->students() mengembalikan kosong dan seluruh test
         * di berkas ini lulus/gagal karena alasan yang salah.
         */
        $this->actingAs($this->guru);
    }

    private function siswa(string $nama, bool $aktif = true): Student
    {
        return Student::factory()->create([
            'user_id' => $this->guru->id,
            'class_id' => $this->kelas->id,
            'name' => $nama,
            'is_active' => $aktif,
        ]);
    }

    /** @param array<int, string> $bagian */
    private function bangun(array $bagian): array
    {
        return app(ClassReportBuilder::class)->build(
            $this->kelas, now()->subMonth(), now(), $bagian
        );
    }

    /**
     * Lembar profil menembak ~6 kueri PER SISWA. Membangunnya walau bagiannya
     * tidak dicentang berarti setiap orang yang membuka laporan membayar 230
     * kueri untuk data yang langsung dibuang.
     */
    public function test_lembar_hanya_dibangun_bila_bagiannya_dipilih(): void
    {
        $this->siswa('Ahmad');
        $this->siswa('Budi');

        $this->assertCount(0, $this->bangun(['siswa', 'kehadiran'])['lembarProfil']);
        $this->assertCount(2, $this->bangun(['siswa', 'profil'])['lembarProfil']);
    }

    /** Yang sudah pindah hanya menambah tebal jilidan. */
    public function test_siswa_nonaktif_tidak_dapat_lembar(): void
    {
        $this->siswa('Masih Aktif');
        $this->siswa('Sudah Pindah', aktif: false);

        $lembar = $this->bangun(['profil'])['lembarProfil'];

        $this->assertCount(1, $lembar);
        $this->assertSame('Masih Aktif', $lembar[0]['siswa']->name);
    }

    public function test_pdf_memuat_satu_lembar_per_siswa_beserta_refleksinya(): void
    {
        $siswa = $this->siswa('Ahmad Fauzi');
        $this->siswa('Budi Santoso');

        (new CharacterReflection)->forceFill([
            'user_id' => $this->guru->id,
            'class_id' => $this->kelas->id,
            'student_id' => $siswa->id,
            'period' => CharacterReflection::PERIOD_MONTHLY,
            'reflection_date' => today()->toDateString(),
            'what_went_well' => 'Piket tanpa disuruh',
            'kesan_teman' => 'Kata Rina aku ramah tapi suka memotong bicara',
            'status' => 'submitted',
        ])->save();

        $html = $this->render(['profil']);

        $this->assertSame(2, substr_count($html, 'VI. Suara Siswa'));
        $this->assertStringContainsString('Kata Rina aku ramah tapi suka memotong bicara', $html);

        // Di dalam jilidan kop sekolah tidak diulang tiap lembar; sampulnya
        // sudah membawanya sekali.
        $this->assertStringNotContainsString('Laporan Perkembangan Siswa', $html);
    }

    public function test_bagian_profil_tidak_muncul_bila_tidak_dipilih(): void
    {
        $this->siswa('Ahmad Fauzi');

        $html = $this->render(['siswa']);

        $this->assertStringNotContainsString('VI. Suara Siswa', $html);
    }

    /**
     * Jam mengajar wali kelas disaring dari jadwal kelas lewat nama, karena
     * kolom teacher_name memang teks bebas tanpa kaitan ke tabel users.
     */
    public function test_jam_mengajar_wali_kelas_disaring_dari_jadwal(): void
    {
        Schedule::create([
            'user_id' => $this->guru->id, 'class_id' => $this->kelas->id,
            'day_of_week' => 1, 'subject' => 'Produktif TKJ',
            'teacher_name' => 'Andika Fernanda', 'start_time' => '07:00', 'end_time' => '08:30',
        ]);
        Schedule::create([
            'user_id' => $this->guru->id, 'class_id' => $this->kelas->id,
            'day_of_week' => 1, 'subject' => 'Matematika',
            'teacher_name' => 'Guru Lain', 'start_time' => '08:30', 'end_time' => '10:00',
        ]);

        $html = $this->render(['jadwal']);

        $this->assertStringContainsString('Jam Mengajar Wali Kelas', $html);
        $this->assertSame(1, substr_count($html, 'Total 1 jam pelajaran per minggu'));
    }

    /** Ejaan yang tidak cocok lebih baik diam daripada mengaku jadwal orang lain. */
    public function test_jam_mengajar_disembunyikan_bila_nama_pengajar_kosong(): void
    {
        Schedule::create([
            'user_id' => $this->guru->id, 'class_id' => $this->kelas->id,
            'day_of_week' => 1, 'subject' => 'Produktif TKJ',
            'teacher_name' => null, 'start_time' => '07:00', 'end_time' => '08:30',
        ]);

        $this->assertStringNotContainsString('Jam Mengajar Wali Kelas', $this->render(['jadwal']));
    }

    /**
     * Merender HTML-nya, bukan PDF-nya: isi PDF terkompresi sehingga pencarian
     * teks apa pun di atasnya selalu gagal.
     *
     * @param  array<int, string>  $bagian
     */
    private function render(array $bagian): string
    {
        return view('reports.pdf.administrasi', $this->bangun($bagian) + [
            'periode' => \App\Support\PeriodeLaporan::resolve(request()),
            'guru' => $this->guru,
        ])->render();
    }
}
