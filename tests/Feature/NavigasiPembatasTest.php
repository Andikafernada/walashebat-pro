<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Susunan navigasi pemisahan Kelas Wali vs Kelas Mapel.
 */
class NavigasiPembatasTest extends TestCase
{
    use RefreshDatabase;

    private function halamanKelas(string $jenis): array
    {
        $guru = User::factory()->create();
        $kelas = Classroom::factory()->create(['user_id' => $guru->id, 'jenis' => $jenis]);

        $html = $this->actingAs($guru)
            ->get(route('classes.show', $kelas))
            ->assertOk()
            ->getContent();

        return [$html, $kelas];
    }

    public function test_bagian_yang_jarang_dipakai_tetap_ada_di_pembatas(): void
    {
        [$html, $kelas] = $this->halamanKelas(Classroom::JENIS_PERWALIAN);

        foreach (['schedules', 'seating', 'organization', 'violations'] as $bagian) {
            $this->assertStringContainsString(
                route("classes.{$bagian}.index", $kelas),
                $html,
                "Bagian {$bagian} hilang dari deret pembatas."
            );
        }
    }

    public function test_guru_mapel_tidak_kebagian_bagian_wali_kelas(): void
    {
        /*
         * Buku kas, laporan administrasi, jadwal, denah, portofolio karakter,
         * dan buku pelanggaran adalah dokumen wali kelas; pada kelas ajar wali kelasnya orang lain.
         */
        [$html, $kelas] = $this->halamanKelas(Classroom::JENIS_AJAR);

        foreach (['cashbook', 'schedules', 'seating', 'organization', 'violations', 'character-portfolio'] as $bagian) {
            $this->assertStringNotContainsString(
                route("classes.{$bagian}.index", $kelas),
                $html,
                "Bagian {$bagian} bocor ke guru mapel."
            );
        }

        $this->assertStringNotContainsString(route('classes.reports.full', $kelas), $html);
    }

    public function test_guru_mapel_tetap_kebagian_bagian_hariannya(): void
    {
        // 5 Fitur Resmi Kelas Mapel: Siswa, Absensi, Nilai, Jurnal Mengajar, Analisis
        [$html, $kelas] = $this->halamanKelas(Classroom::JENIS_AJAR);

        $this->assertStringContainsString(route('classes.students.index', $kelas), $html);
        $this->assertStringContainsString(route('classes.attendance.index', $kelas), $html);
        $this->assertStringContainsString(route('classes.nilai.index', $kelas), $html);
        $this->assertStringContainsString(route('classes.jurnal.index', $kelas), $html);
        $this->assertStringContainsString(route('classes.reports.analisis', $kelas), $html);
    }

    public function test_halaman_milik_akun_terjangkau_tanpa_memilih_kelas(): void
    {
        $guru = User::factory()->create();

        $html = $this->actingAs($guru)->get(route('dashboard'))->assertOk()->getContent();

        foreach (['holidays.index', 'whatsapp.index', 'analytics.index', 'subscription.index', 'profile.edit'] as $rute) {
            $this->assertStringContainsString(route($rute), $html, "{$rute} tidak terjangkau dari mana pun.");
        }
    }

    public function test_operator_tidak_kebagian_chrome_wali_kelas(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString(route('admin.teachers.index'), $html);
        $this->assertStringNotContainsString(route('holidays.index'), $html);
        $this->assertStringNotContainsString(route('subscription.index'), $html);
    }
}
