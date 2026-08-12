<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CashBook;
use App\Models\OrganizationStructure;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Nama kolom & nilai enum yang dibaca UI benar-benar ada.
 *
 * Audit menemukan enam cacat dari kelas yang sama, dan semuanya bekerja persis
 * seperti ini: kode menyebut nama yang salah, Eloquent mengembalikan NULL
 * diam-diam atau pembanding string tidak pernah cocok, dan TIDAK ADA galat apa
 * pun. Halaman tetap termuat, ekspor tetap terunduh, formulir tetap menjawab
 * "berhasil disimpan" — hanya datanya yang tidak pernah sampai.
 *
 *   $attendance->notes        → kolomnya `note`
 *   $attendance->filled_at    → tidak pernah ada
 *   $struktur->position       → kolomnya `role`
 *   $student->alamat          → kolomnya `address`
 *   status === 'alpha'        → enumnya 'alfa'
 *   cash_books.type 'income'  → enumnya 'in'
 *
 * Test ini murah dan membosankan, dan itulah gunanya: ia gagal pada menit
 * pertama seseorang mengetik nama yang salah lagi.
 */
class KolomYangDibacaUiBenarAdaTest extends TestCase
{
    // Tanpa ini database uji kosong dan SELURUH hasColumn() menjawab false —
    // test lulus/gagal karena alasan yang salah.
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function kolomYangDipakaiUi(): array
    {
        return [
            'catatan absensi' => ['attendances', 'note'],
            'alamat siswa' => ['students', 'address'],
            'jabatan pengurus kelas' => ['organization_structures', 'role'],
            'jenis transaksi kas' => ['cash_books', 'type'],
            'poin kedisiplinan' => ['students', 'discipline_points'],
            'foto siswa' => ['students', 'foto_path'],
        ];
    }

    /**
     * @dataProvider kolomYangDipakaiUi
     */
    public function test_kolom_yang_dibaca_ui_ada_di_skema(string $tabel, string $kolom): void
    {
        $this->assertTrue(
            Schema::hasColumn($tabel, $kolom),
            "Kolom {$tabel}.{$kolom} dibaca UI tetapi tidak ada di skema."
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function kolomYangTidakAda(): array
    {
        return [
            'notes (jamak)' => ['attendances', 'notes'],
            'filled_at' => ['attendances', 'filled_at'],
            'position' => ['organization_structures', 'position'],
            'alamat' => ['students', 'alamat'],
        ];
    }

    /**
     * Sisi sebaliknya: kalau salah satu nama lama ini SUATU SAAT benar-benar
     * ditambahkan sebagai kolom, test ini gagal dan memaksa orang memutuskan
     * mana yang dipakai — bukan membiarkan dua nama hidup berdampingan.
     *
     * @dataProvider kolomYangTidakAda
     */
    public function test_nama_kolom_yang_pernah_salah_tetap_tidak_ada(string $tabel, string $kolom): void
    {
        $this->assertFalse(
            Schema::hasColumn($tabel, $kolom),
            "{$tabel}.{$kolom} kini ada. Rapikan: dulu ini nama salah yang dipakai UI."
        );
    }

    public function test_atribut_yang_salah_nama_mengembalikan_null_tanpa_galat(): void
    {
        /*
         * Inilah sebabnya cacat ini bisa hidup berbulan-bulan tanpa ketahuan.
         * Kalau Eloquent melempar galat untuk atribut yang tidak ada, keenam
         * cacat di atas akan ketahuan pada pemakaian pertama.
         */
        $siswa = new Student;

        $this->assertNull($siswa->alamat);
        $this->assertNull((new Attendance)->notes);
        $this->assertNull((new OrganizationStructure)->position);
    }

    /**
     * Nilai enum yang DIBANDINGKAN kode harus benar-benar ada di migrasinya.
     *
     * Diperiksa dari berkas migrasi, bukan dari database uji: test berjalan di
     * SQLite yang tidak menegakkan enum sama sekali (dan tidak paham SHOW
     * COLUMNS), sehingga nilai sesat seperti 'alpha' akan tersimpan diam-diam
     * di sini dan tetap lolos — persis kebutaan yang membuat cacatnya hidup
     * berbulan-bulan di produksi MySQL.
     */
    public function test_nilai_enum_yang_dipakai_kode_ada_di_migrasi(): void
    {
        $migrasi = collect(glob(database_path('migrations/*.php')))
            ->map(fn ($f) => file_get_contents($f))
            ->join("\n");

        // Absensi: 'alfa' (bukan 'alpha'), dan 'terlambat' ditambahkan menyusul.
        $this->assertStringContainsString("'hadir', 'sakit', 'izin', 'alfa'", $migrasi);
        $this->assertStringContainsString('terlambat', $migrasi);
        $this->assertStringNotContainsString("'alpha'", $migrasi, "Enum absensi memakai 'alfa'; 'alpha' tidak pernah ada.");

        // Buku kas: 'in'/'out', bukan 'income'/'expense'.
        $this->assertStringContainsString("enum('type', ['in', 'out'])", $migrasi);
        $this->assertStringNotContainsString("'income'", $migrasi);

        // Dan kode yang membacanya tidak boleh memakai nama lama itu lagi.
        $kode = collect([
            app_path('Http/Controllers/ExportController.php'),
            app_path('Exports/AttendanceReportExport.php'),
            app_path('Exports/CashBookReportExport.php'),
        ])->map(fn ($f) => file_get_contents($f))->join("\n");

        /*
         * Dicocokkan pada BENTUK KODE, bukan sekadar kata: komentar di berkas
         * itu menyebut 'alpha' dan 'income' justru untuk menjelaskan mengapa
         * keduanya salah, dan pencarian kata polos akan tersandung padanya.
         */
        foreach (["\$row['alpha']", "=== 'alpha'", "'alpha' =>", "=== 'income'", "'income')"] as $sesat) {
            $this->assertStringNotContainsString($sesat, $kode, "Nilai enum sesat {$sesat} dipakai lagi.");
        }
    }
}
