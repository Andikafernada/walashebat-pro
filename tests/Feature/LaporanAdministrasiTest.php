<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\CashBook;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use App\Services\ClassReportBuilder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanAdministrasiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create([
            'name' => 'Andika Fernanda',
            'nip' => '19850712201001004',
            'school_name' => 'SMK Pasundan 2 Bandung',
            'school_city' => 'Bandung',
            'principal_name' => 'Drs. Suryana',
            'principal_nip' => '19700101199001001',
        ]);
        $this->class = Classroom::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'XII RPL 1',
        ]);

        $this->actingAs($this->user);
    }

    private function siswa(int $jumlah = 3)
    {
        return Student::factory()->count($jumlah)->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
        ]);
    }

    /**
     * REGRESI: 'bagian' pernah di-hardcode menjadi array kosong di controller,
     * sehingga in_array() pada tampilan selalu false dan SELURUH isi laporan
     * tidak pernah tampil — yang muncul hanya halaman sampul. Ini bug yang
     * membuat menu Laporan Administrasi terlihat kosong sama sekali.
     */
    public function test_semua_bagian_tampil_secara_bawaan(): void
    {
        $this->siswa();

        $html = $this->get(route('classes.reports.full', $this->class))->assertOk()->getContent();

        foreach (array_keys(ClassReportBuilder::SECTIONS) as $kunci) {
            $this->assertStringContainsString(
                'data-bagian="'.$kunci.'"',
                $html,
                "Bagian \"{$kunci}\" tidak tampil di laporan"
            );
        }
    }

    public function test_bagian_bisa_dipilih_sebagian(): void
    {
        $this->siswa();

        $html = $this->get(route('classes.reports.full', [
            $this->class, 'pilih_bagian' => 1, 'bagian' => ['kehadiran'],
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('data-bagian="kehadiran"', $html);
        $this->assertStringNotContainsString('data-bagian="kas"', $html);
        $this->assertStringNotContainsString('data-bagian="siswa"', $html);
    }

    /** Mengosongkan seluruh centang adalah pilihan sadar, bukan bawaan. */
    public function test_bagian_kosong_menghasilkan_laporan_kosong(): void
    {
        $this->siswa();

        // Persis seperti kiriman formulir dengan semua centang dimatikan:
        // penanda terkirim, bagian[] tidak ada sama sekali.
        $this->get(route('classes.reports.full', [$this->class, 'pilih_bagian' => 1]))
            ->assertOk()
            ->assertSee('Tidak ada bagian yang dipilih');
    }

    public function test_pdf_terunduh_dan_berkasnya_valid(): void
    {
        $this->siswa();

        $response = $this->get(route('classes.reports.full.pdf', $this->class))->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition'));

        $isi = $response->getContent();
        $this->assertStringStartsWith('%PDF', $isi, 'Berkas yang dikirim bukan PDF');
        $this->assertGreaterThan(10_000, strlen($isi), 'PDF terlalu kecil, kemungkinan kosong');
    }

    /** Persentase kehadiran memakai penyebut isian siswa, bukan pertemuan kelas. */
    public function test_persen_kehadiran_memakai_penyebut_isian_siswa(): void
    {
        $siswa = $this->siswa(2);

        // Dua pertemuan, tapi siswa kedua hanya terisi di satu pertemuan
        // (mis. baru pindah masuk). Dia tidak boleh dihitung bolos.
        foreach ([0, 1] as $ke) {
            $sesi = AttendanceSession::create([
                'user_id' => $this->user->id,
                'class_id' => $this->class->id,
                'session_date' => today()->subDays($ke),
                'sequence' => 1,
                'token' => 'tok'.$ke.uniqid(),
                'pin_hash' => bcrypt('123456'),
                'expires_at' => now()->addDay(),
                'status' => 'submitted',
            ]);

            Attendance::create([
                'user_id' => $this->user->id,
                'attendance_session_id' => $sesi->id,
                'student_id' => $siswa[0]->id,
                'status' => $ke === 0 ? 'hadir' : 'alfa',
            ]);

            if ($ke === 0) {
                Attendance::create([
                    'user_id' => $this->user->id,
                    'attendance_session_id' => $sesi->id,
                    'student_id' => $siswa[1]->id,
                    'status' => 'hadir',
                ]);
            }
        }

        $data = app(ClassReportBuilder::class)->build(
            $this->class, today()->subDays(7)->startOfDay(), today()->endOfDay(), ['kehadiran']
        );

        $rekap = $data['kehadiran']->keyBy(fn ($k) => $k['siswa']->id);

        $this->assertSame(50, $rekap[$siswa[0]->id]['persen'], 'Hadir 1 dari 2 isian = 50%');
        $this->assertSame(100, $rekap[$siswa[1]->id]['persen'], 'Hadir 1 dari 1 isian = 100%');
    }

    /** Saldo kas periode harus dimulai dari saldo akhir periode sebelumnya. */
    public function test_buku_kas_punya_saldo_awal_dan_saldo_berjalan(): void
    {
        // Pakai subMonthNoOverflow untuk hindari edge case: subMonth di tanggal 31
        // akan overflow ke tanggal 1 bulan yang sama, bukan bulan sebelumnya.
        $bulanLalu = today()->subMonthNoOverflow();
        CashBook::create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id,
            'type' => 'in', 'amount' => 100000, 'description' => 'Iuran bulan lalu',
            'transaction_date' => $bulanLalu,
        ]);
        CashBook::create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id,
            'type' => 'in', 'amount' => 50000, 'description' => 'Iuran bulan ini',
            'transaction_date' => today(),
        ]);
        CashBook::create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id,
            'type' => 'out', 'amount' => 20000, 'description' => 'Spidol',
            'transaction_date' => today(),
        ]);

        $data = app(ClassReportBuilder::class)->build(
            $this->class, today()->startOfMonth(), today()->endOfMonth(), ['kas']
        );

        $this->assertSame(100000, $data['kas']['saldo_awal']);
        $this->assertSame(50000, $data['kas']['masuk']);
        $this->assertSame(20000, $data['kas']['keluar']);
        $this->assertSame(130000, $data['kas']['saldo_akhir']);
        $this->assertSame(130000, $data['kas']['transaksi']->last()->saldo_berjalan);
    }

    /** Daftar perhatian membawa alasannya, bukan cuma nama. */
    public function test_siswa_perlu_perhatian_menyertakan_alasan(): void
    {
        $siswa = $this->siswa(1);
        $siswa[0]->update(['discipline_points' => 60]);

        Violation::create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id,
            'student_id' => $siswa[0]->id, 'points' => -40,
            'note' => 'Terlambat berulang', 'occurred_on' => today(),
        ]);

        $data = app(ClassReportBuilder::class)->build(
            $this->class, today()->startOfMonth(), today()->endOfMonth(),
            array_keys(ClassReportBuilder::SECTIONS)
        );

        $this->assertCount(1, $data['perhatian']);
        $this->assertNotEmpty($data['perhatian'][0]['alasan']);
        $this->assertStringContainsString('Poin disiplin 60', implode(' ', $data['perhatian'][0]['alasan']));
    }

    /** Semester mengikuti kalender pendidikan, bukan tahun kalender. */
    public function test_periode_semester_memakai_kalender_pendidikan(): void
    {
        $this->siswa();

        // Label kini menyebut cakupannya ("Penuh") karena ada pula varian
        // tengah/akhir semester. Tahun ajarannya yang penting: 2025/2026.
        $this->get(route('classes.reports.full', [
            $this->class, 'mode' => 'semester', 'semester' => '1', 'tahun' => 2025,
        ]))->assertOk()->assertSee('Semester 1 Penuh T.A. 2025/2026');

        $this->get(route('classes.reports.full', [
            $this->class, 'mode' => 'semester', 'semester' => '2', 'tahun' => 2025,
        ]))->assertOk()->assertSee('Semester 2 Penuh T.A. 2025/2026');
    }

    public function test_periode_rentang_tanggal(): void
    {
        $this->siswa();

        $this->get(route('classes.reports.full', [
            $this->class, 'mode' => 'rentang', 'dari' => '2026-01-05', 'sampai' => '2026-01-20',
        ]))->assertOk()->assertSee('05 Jan 2026');
    }

    public function test_bagian_yang_tidak_dikenal_ditolak(): void
    {
        $this->siswa();

        $this->get(route('classes.reports.full', [
            $this->class, 'pilih_bagian' => 1, 'bagian' => ['bukan-bagian'],
        ]))->assertSessionHasErrors('bagian.0');
    }

    /** Denah harus persegi: baris pendek dipadankan agar posisi duduk akurat. */
    public function test_denah_dipadankan_menjadi_matriks_persegi(): void
    {
        $siswa = $this->siswa(6);

        // 4 kolom di baris pertama, hanya 2 di baris kedua.
        foreach ($siswa as $i => $s) {
            \App\Models\Seat::create([
                'user_id' => $this->user->id,
                'class_id' => $this->class->id,
                'student_id' => $s->id,
                'row_index' => intdiv($i, 4),
                'col_index' => $i % 4,
            ]);
        }

        $data = app(ClassReportBuilder::class)->build(
            $this->class, today()->startOfMonth(), today()->endOfMonth(), ['denah']
        );

        $this->assertCount(2, $data['grid']);
        foreach ($data['grid'] as $baris) {
            $this->assertCount(4, $baris, 'Setiap baris denah harus punya lebar sama');
        }
        // Kursi kosong tetap ada sebagai null, bukan hilang dari matriks.
        $this->assertNull($data['grid'][1][2]);
    }

    /** Laporan kelas orang lain tidak boleh terbuka. */
    public function test_tidak_bisa_membuka_laporan_kelas_orang_lain(): void
    {
        $lain = Classroom::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->get(route('classes.reports.full', $lain))->assertNotFound();
        $this->get(route('classes.reports.full.pdf', $lain))->assertNotFound();
    }
}
