<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Daftar guru untuk operator: siapa, bukan berapa.
 *
 * Dua hal yang dijaga di sini gagal tanpa menimbulkan galat apa pun, jadi
 * keduanya hanya akan ketahuan lewat keluhan: halaman yang kosong karena
 * TenantScope, dan halaman operator yang bisa dibuka guru biasa.
 */
class AdminDaftarGuruTest extends TestCase
{
    use RefreshDatabase;

    private function operator(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function guru(array $atribut = []): User
    {
        return User::factory()->create($atribut + ['role' => User::ROLE_TEACHER]);
    }

    public function test_operator_melihat_guru_milik_seluruh_tenant(): void
    {
        /*
         * Inti halaman ini. Tanpa lintasSeluruhTenant(), TenantScope membatasi
         * query ke user_id operator yang sedang login — dan operator tidak
         * memiliki satu pun guru, sehingga daftarnya kosong tanpa satu pun
         * galat yang menjelaskan kenapa.
         */
        $this->guru(['name' => 'Bu Sinta', 'school_name' => 'SDN 1 Merdeka']);
        $this->guru(['name' => 'Pak Bagas']);

        $this->actingAs($this->operator())
            ->get(route('admin.teachers.index'))
            ->assertOk()
            ->assertSee('Bu Sinta')
            ->assertSee('Pak Bagas')
            ->assertSee('SDN 1 Merdeka');
    }

    public function test_guru_biasa_tidak_bisa_membukanya(): void
    {
        $this->actingAs($this->guru())
            ->get(route('admin.teachers.index'))
            ->assertForbidden();
    }

    public function test_pencarian_menyaring_menurut_nama_dan_sekolah(): void
    {
        $this->guru(['name' => 'Bu Sinta', 'school_name' => 'SDN 1 Merdeka']);
        $this->guru(['name' => 'Pak Bagas', 'school_name' => 'SMPN 4 Harapan']);

        $this->actingAs($this->operator())
            ->get(route('admin.teachers.index', ['cari' => 'Harapan']))
            ->assertOk()
            ->assertSee('Pak Bagas')
            ->assertDontSee('Bu Sinta');
    }

    public function test_segmen_memisahkan_berbayar_dari_masa_gratis(): void
    {
        $this->guru([
            'name' => 'Bu Sinta',
            'subscription_tier' => User::TIER_PRO,
            'subscription_ends_at' => now()->addMonth(),
        ]);
        $this->guru([
            'name' => 'Pak Bagas',
            'subscription_tier' => User::TIER_TRIAL,
            'subscription_ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($this->operator())
            ->get(route('admin.teachers.index', ['segmen' => 'berbayar']))
            ->assertOk()
            ->assertSee('Bu Sinta')
            ->assertDontSee('Pak Bagas');
    }

    public function test_jumlah_kelas_dihitung_hanya_yang_aktif(): void
    {
        $guru = $this->guru(['name' => 'Bu Sinta']);
        Classroom::factory()->count(2)->create(['user_id' => $guru->id, 'is_active' => true]);
        Classroom::factory()->create(['user_id' => $guru->id, 'is_active' => false]);

        $html = $this->actingAs($this->operator())
            ->get(route('admin.teachers.index'))
            ->assertOk()
            ->getContent();

        // Kelas yang sudah diarsipkan tidak boleh ikut menggelembungkan angka.
        $this->assertStringContainsString('>2</td>', str_replace(["\n", ' '], ['', ''], $html));
    }

    public function test_akun_operator_tidak_terhitung_sebagai_guru(): void
    {
        /*
         * Pelanggan produk ini adalah wali kelas. Operator adalah pemilik
         * aplikasinya sendiri dan tidak boleh muncul sebagai pengguna.
         *
         * Diuji dengan admin KEDUA, bukan yang sedang login: email admin yang
         * login memang tampil di halaman ini — di kartu profil sidebar — jadi
         * menguji dirinya sendiri hanya akan menangkap sidebar, bukan tabel.
         */
        User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => 'Operator Kedua']);

        $this->actingAs($this->operator())
            ->get(route('admin.teachers.index'))
            ->assertOk()
            ->assertDontSee('Operator Kedua');
    }

    public function test_panel_operator_menautkan_daftar_ini(): void
    {
        $this->actingAs($this->operator())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.teachers.index'));
    }
}
