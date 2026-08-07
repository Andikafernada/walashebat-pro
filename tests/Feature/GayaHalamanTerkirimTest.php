<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gaya yang dititipkan halaman lewat @push('styles') harus benar-benar terkirim.
 *
 * Lima halaman menitipkan CSS-nya dengan cara itu — termasuk halaman masuk —
 * tetapi tidak ada satu pun layout yang merender @stack('styles'), sehingga
 * seluruh titipan dibuang tanpa jejak. Tidak ada galat: halaman tetap tampil,
 * hanya saja tanpa animasi, tanpa sorotan fokus, dan sel kalender kehadiran
 * tanpa bentuk. Kegagalan seperti ini hanya bisa ditangkap dengan memeriksa
 * keluarannya.
 */
class GayaHalamanTerkirimTest extends TestCase
{
    use RefreshDatabase;

    public function test_gaya_halaman_masuk_ikut_terkirim(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('login-float', false);
    }

    public function test_gaya_halaman_analitik_ikut_terkirim(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('.heatmap-cell', false);
    }

    /**
     * @apply diproses Tailwind saat build. Bila ia sampai ke browser, artinya
     * aturan itu ditulis di tempat yang tidak pernah bisa memprosesnya — dan
     * seluruh isi bloknya tidak berlaku.
     */
    public function test_tidak_ada_apply_tailwind_yang_lolos_ke_browser(): void
    {
        $this->get('/login')->assertDontSee('@apply', false);

        $this->actingAs(User::factory()->create())
            ->get(route('analytics.index'))
            ->assertDontSee('@apply', false);
    }
}
