<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Akun admin harus benar-benar bisa MENJANGKAU fungsi adminnya.
 *
 * Pernah tidak bisa: aplikasi punya dua penanda admin yang berbeda — kolom
 * `role` yang dipercaya middleware dan policy, serta kolom `is_admin` yang
 * tidak pernah diisi siapa pun. Sidebar memeriksa yang kedua, sehingga akun
 * ber-role admin masuk lalu tidak melihat satu pun menu admin. Halaman
 * admin/dashboard bahkan tidak ditautkan dari mana pun.
 *
 * Gagalnya senyap: tidak ada galat, hanya dashboard wali kelas kosong — dan
 * admin menyangka salah masuk atau aplikasinya rusak.
 */
class AksesAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $u->refresh();
    }

    public function test_login_admin_mendarat_di_dashboard_admin(): void
    {
        $admin = $this->admin();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_login_guru_tetap_mendarat_di_dashboard_biasa(): void
    {
        $guru = User::factory()->create();

        $this->post('/login', ['email' => $guru->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }

    /** Menu admin memakai `role`, bukan kolom `is_admin` yang tidak pernah diisi. */
    public function test_menu_admin_tampil_untuk_role_admin(): void
    {
        $admin = $this->admin();

        // Sengaja DIBIARKAN 0 — persis keadaan produksi yang menyebabkan bug.
        $this->assertFalse((bool) $admin->is_admin);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Panel Operator')
            ->assertSee('Persetujuan PRO');
    }

    public function test_menu_admin_tidak_tampil_untuk_guru(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Dashboard Admin')
            ->assertDontSee('Persetujuan PRO');
    }

    public function test_guru_tetap_tidak_boleh_membuka_halaman_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
