<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** Tamu yang membuka root melihat landing page. */
    public function test_tamu_diarahkan_ke_login(): void
    {
        // Nama produk pernah berganti ("WaliKelas Pro" -> "Wali Kelas Hebat")
        // dan membuat test ini gagal padahal halamannya sehat. Diambil dari
        // config agar rebrand berikutnya tidak mematahkannya lagi.
        $this->get('/')->assertOk()->assertSee(config('app.name'));

        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    /** Halaman login dapat diakses publik. */
    public function test_halaman_login_dapat_diakses(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Masuk');
    }
}
