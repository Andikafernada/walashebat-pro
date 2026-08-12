<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Susunan navigasi.
 *
 * Dulu sidebar dasbor SaaS: sembilan belas tautan di bawah enam judul kategori,
 * memaksa "satu kelas dengan beberapa bagian" tampil sebagai sembilan belas
 * tujuan sederajat. Sekarang pembatas buku — sampul kelas yang bisa diklik
 * untuk ganti kelas, lalu deret pembatas berisi bagian-bagian kelas itu.
 *
 * Yang dijaga di sini bukan urutan atau labelnya — itu boleh berubah —
 * melainkan hal-hal yang kalau rusak TIDAK menimbulkan galat apa pun, jadi
 * tidak akan ketahuan sampai ada wali kelas yang mengeluh. Karena itu yang
 * diperiksa alamat tujuannya, bukan teks labelnya: label pendek seperti "Poin"
 * bisa muncul kebetulan di isi halaman dan membuat test lulus/gagal palsu.
 */
class NavigasiPembatasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pembatas kelas hanya muncul bila ADA kelas yang sedang dibuka:
     * $kelasAktif dibaca dari variabel yang dikirim view anak, dan /dashboard
     * tidak mengirimkannya. Karena itu test di bawah membuka halaman kelas,
     * bukan dashboard — kalau tidak, semuanya lulus tanpa menguji apa pun.
     */
    private function halamanKelas(string $jenis): array
    {
        $guru = User::factory()->create();
        $kelas = Classroom::factory()->create(['user_id' => $guru->id, 'jenis' => $jenis]);

        $html = $this->actingAs($guru)
            ->get(route('classes.students.index', $kelas))
            ->assertOk()
            ->getContent();

        return [$html, $kelas];
    }

    public function test_bagian_yang_jarang_dipakai_tetap_ada_di_pembatas(): void
    {
        /*
         * Jadwal, denah, dan struktur organisasi diisi sekali per semester lalu
         * ditinggal — tetapi tetap harus terjangkau. Dulu ketiganya dilipat di
         * dalam <details>; sekarang duduk di ujung kanan deret pembatas dan
         * tinggal digeser. Yang tak boleh terjadi: hilang sama sekali.
         */
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
         * Buku kas, laporan administrasi, jadwal, denah, dan buku poin adalah
         * dokumen wali kelas; pada kelas ajar wali kelasnya orang lain.
         */
        [$html, $kelas] = $this->halamanKelas(Classroom::JENIS_AJAR);

        foreach (['cashbook', 'schedules', 'seating', 'organization', 'violations'] as $bagian) {
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
        // Sisi sebaliknya: menyembunyikan kebanyakan sama merusaknya.
        [$html, $kelas] = $this->halamanKelas(Classroom::JENIS_AJAR);

        $this->assertStringContainsString(route('classes.attendance.index', $kelas), $html);
        $this->assertStringContainsString(route('classes.character-portfolio.index', $kelas), $html);
    }

    public function test_halaman_milik_akun_terjangkau_tanpa_memilih_kelas(): void
    {
        /*
         * Kalender dulu bersarang di dalam blok kelas aktif, padahal rutenya
         * global: wali kelas yang belum punya kelas tidak punya jalan ke sana
         * sama sekali. Kini semuanya hidup di balik avatar — dan yang hidup di
         * balik menu paling gampang hilang tanpa ada yang sadar.
         */
        $guru = User::factory()->create();

        $html = $this->actingAs($guru)->get(route('dashboard'))->assertOk()->getContent();

        foreach (['holidays.index', 'whatsapp.index', 'analytics.index', 'subscription.index', 'profile.edit'] as $rute) {
            $this->assertStringContainsString(route($rute), $html, "{$rute} tidak terjangkau dari mana pun.");
        }
    }

    public function test_operator_tidak_kebagian_chrome_wali_kelas(): void
    {
        /*
         * Admin adalah operator SaaS, bukan pemegang kelas: ia tidak punya
         * kelas, kalender, integrasi WA, atau langganan pribadi. Chrome-nya
         * sama, isinya saja yang lain.
         */
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString(route('admin.teachers.index'), $html);
        $this->assertStringNotContainsString(route('holidays.index'), $html);
        $this->assertStringNotContainsString(route('subscription.index'), $html);
    }
}
