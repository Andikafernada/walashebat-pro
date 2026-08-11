<?php

namespace Tests\Feature;

use App\Models\CashBook;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rekap kas per siswa: siapa sudah setor, siapa belum.
 *
 * Buku kas berbentuk buku besar — urut waktu, bercampur masuk dan keluar.
 * Bentuk itu tepat untuk mempertanggungjawabkan saldo, tetapi tidak menjawab
 * pertanyaan yang paling sering diajukan wali kelas: "siapa yang belum
 * bayar?". Jawabannya tersebar di puluhan baris dan harus dijumlahkan sendiri
 * per anak.
 */
class KasPerSiswaTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private Classroom $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create();
        $this->kelas = Classroom::factory()->create(['user_id' => $this->guru->id]);
        $this->actingAs($this->guru);
    }

    private function siswa(string $nama): Student
    {
        return Student::factory()->create([
            'user_id' => $this->guru->id,
            'class_id' => $this->kelas->id,
            'name' => $nama,
            'is_active' => true,
        ]);
    }

    private function transaksi(?Student $siswa, string $tipe, int $jumlah, ?string $tanggal = null): void
    {
        CashBook::create([
            'user_id' => $this->guru->id,
            'class_id' => $this->kelas->id,
            'student_id' => $siswa?->id,
            'transaction_date' => $tanggal ?? today()->toDateString(),
            'type' => $tipe,
            'amount' => $jumlah,
            'description' => 'uji',
            'balance_after' => 0,
        ]);
    }

    private function halaman(): string
    {
        return $this->get(route('classes.cashbook.per-siswa', $this->kelas))
            ->assertOk()
            ->getContent();
    }

    public function test_memisahkan_yang_sudah_dan_belum_setor(): void
    {
        $andi = $this->siswa('Andi Sudah');
        $this->siswa('Budi Belum');

        $this->transaksi($andi, 'in', 20000);

        $html = $this->halaman();

        $this->assertStringContainsString('Andi Sudah', $html);
        $this->assertStringContainsString('Budi Belum', $html,
            'Siswa yang belum setor justru yang paling perlu tampil');
        $this->assertStringContainsString('20.000', $html);
    }

    public function test_beberapa_setoran_dijumlahkan(): void
    {
        $andi = $this->siswa('Andi');

        $this->transaksi($andi, 'in', 20000);
        $this->transaksi($andi, 'in', 15000);

        $this->assertStringContainsString('35.000', $this->halaman());
    }

    public function test_pengeluaran_tidak_mengurangi_setoran_siswa(): void
    {
        /*
         * Uang yang KELUAR bukan setoran. Menjumlahkannya akan membuat anak
         * yang uangnya dipakai untuk keperluan kelas terlihat lebih sedikit
         * membayar daripada yang sebenarnya.
         */
        $andi = $this->siswa('Andi');

        $this->transaksi($andi, 'in', 50000);
        $this->transaksi($andi, 'out', 30000);

        $this->assertStringContainsString('50.000', $this->halaman());
    }

    public function test_pemasukan_tanpa_nama_siswa_tetap_dilaporkan(): void
    {
        /*
         * Kalau tidak, jumlah di halaman ini tidak akan pernah cocok dengan
         * saldo di buku besar, dan selisihnya terlihat seperti uang hilang.
         */
        $this->siswa('Andi');
        $this->transaksi(null, 'in', 75000);

        $html = $this->halaman();

        $this->assertStringContainsString('75.000', $html);
        $this->assertStringContainsString('tanpa nama siswa', $html);
    }

    public function test_setoran_di_luar_periode_tidak_ikut(): void
    {
        $andi = $this->siswa('Andi');
        $this->transaksi($andi, 'in', 20000, today()->subYear()->toDateString());

        $html = $this->get(route('classes.cashbook.per-siswa', [
            $this->kelas, 'mode' => 'bulan', 'bulan' => today()->format('Y-m'),
        ]))->assertOk()->getContent();

        $this->assertStringNotContainsString('20.000', $html);
    }

    public function test_kelas_lain_tidak_bisa_dibuka(): void
    {
        $lain = User::factory()->create();
        $kelasLain = Classroom::factory()->create(['user_id' => $lain->id]);

        $this->get(route('classes.cashbook.per-siswa', $kelasLain))->assertNotFound();
    }

    public function test_setoran_massal_mencatat_semua_yang_dicentang(): void
    {
        $andi = $this->siswa('Andi');
        $budi = $this->siswa('Budi');
        $this->siswa('Cici');

        $this->post(route('classes.cashbook.setoran-massal', $this->kelas), [
            'transaction_date' => today()->toDateString(),
            'amount' => 20000,
            'description' => 'Kas Agustus',
            'students' => [$andi->id, $budi->id],
        ])->assertRedirect();

        $this->assertSame(2, CashBook::count());
        // Saldo berjalan ikut dihitung ulang, bukan tertinggal nol.
        $this->assertSame(40000, (int) CashBook::orderBy('id')->get()->last()->balance_after);
        $this->assertStringContainsString('Cici', $this->halaman());
    }

    public function test_setoran_massal_menolak_siswa_kelas_lain(): void
    {
        /*
         * Daftar id datang dari formulir. Tanpa penjagaan ini, request yang
         * disusun sendiri bisa menyisipkan setoran atas nama anak kelas lain.
         */
        $lain = User::factory()->create();
        $kelasLain = Classroom::factory()->create(['user_id' => $lain->id]);
        $siswaLain = Student::factory()->create([
            'user_id' => $lain->id,
            'class_id' => $kelasLain->id,
            'is_active' => true,
        ]);

        $this->post(route('classes.cashbook.setoran-massal', $this->kelas), [
            'transaction_date' => today()->toDateString(),
            'amount' => 20000,
            'description' => 'Kas Agustus',
            'students' => [$siswaLain->id],
        ])->assertSessionHasErrors('students.0');

        $this->assertSame(0, CashBook::count());
    }

    public function test_halaman_buku_kas_menautkan_rekap_ini(): void
    {
        $this->get(route('classes.cashbook.index', $this->kelas))
            ->assertOk()
            ->assertSee(route('classes.cashbook.per-siswa', $this->kelas));
    }
}
