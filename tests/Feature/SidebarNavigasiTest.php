<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Susunan menu samping.
 *
 * Sidebar ini dulu merangkap dua pekerjaan dalam satu daftar datar: navigasi
 * aplikasi dan navigasi satu kelas. Sembilan belas tautan di bawah enam judul
 * kategori membuat "Absensi", yang dibuka tiap hari, tampak sederajat dengan
 * "Denah Tempat Duduk", yang diisi sekali lalu ditinggal.
 *
 * Yang dijaga di sini bukan urutannya — itu boleh berubah — melainkan dua hal
 * yang kalau rusak tidak menimbulkan galat apa pun, jadi tidak akan ketahuan
 * sampai ada wali kelas yang mengeluh.
 */
class SidebarNavigasiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sidebar hanya menampilkan menu kelas bila ADA kelas yang sedang dibuka:
     * $kelasAktif dibaca dari variabel yang dikirim view anak, dan /dashboard
     * tidak mengirimkannya. Karena itu test di bawah membuka halaman kelas,
     * bukan dashboard — kalau tidak, semuanya lulus tanpa menguji apa pun.
     */
    private function halamanKelas(string $jenis): string
    {
        $guru = User::factory()->create();
        $kelas = Classroom::factory()->create(['user_id' => $guru->id, 'jenis' => $jenis]);

        return $this->actingAs($guru)
            ->get(route('classes.students.index', $kelas))
            ->assertOk()
            ->getContent();
    }

    public function test_menu_yang_jarang_dipakai_dilipat_bukan_dihapus(): void
    {
        /*
         * Dilipat, bukan dibuang: keempatnya tetap harus ada di DOM supaya
         * pencarian teks peramban menemukannya walau lipatannya tertutup.
         */
        $html = $this->halamanKelas(Classroom::JENIS_PERWALIAN);

        $this->assertStringContainsString('Pengaturan Kelas', $html);

        foreach (['Jadwal Pelajaran', 'Pelanggaran', 'Denah Tempat Duduk', 'Struktur Organisasi'] as $menu) {
            $this->assertStringContainsString($menu, $html, "{$menu} hilang dari sidebar, bukan sekadar terlipat");
        }
    }

    public function test_guru_mapel_tidak_melihat_lipatan_kosong(): void
    {
        /*
         * Seluruh isi lipatan itu milik wali kelas. Pada kelas ajar keempatnya
         * tersembunyi — dan lipatan yang isinya kosong lebih buruk daripada
         * tidak ada lipatan: ia mengaku menyimpan sesuatu, lalu tidak
         * menyimpan apa-apa.
         */
        $html = $this->halamanKelas(Classroom::JENIS_AJAR);

        $this->assertStringNotContainsString('Pengaturan Kelas', $html);
    }

    public function test_guru_mapel_tidak_kebagian_menu_wali_kelas(): void
    {
        // Buku kas dan laporan administrasi adalah dokumen wali kelas; pada
        // kelas ajar wali kelasnya orang lain.
        $html = $this->halamanKelas(Classroom::JENIS_AJAR);

        foreach (['Buku Kas Kelas', 'Laporan PDF', 'Denah Tempat Duduk', 'Pelanggaran &amp; Poin'] as $menu) {
            $this->assertStringNotContainsString($menu, $html, "{$menu} bocor ke guru mapel");
        }
    }

    public function test_kalender_sekolah_terjangkau_tanpa_memilih_kelas(): void
    {
        /*
         * Kalender dulu bersarang di dalam blok kelas aktif, padahal rutenya
         * global: wali kelas yang belum punya kelas tidak punya jalan ke sana
         * sama sekali.
         */
        $guru = User::factory()->create();

        $html = $this->actingAs($guru)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString(route('holidays.index'), $html);
    }
}
