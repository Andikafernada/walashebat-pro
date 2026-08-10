<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman yang sudah jadi harus bisa DICAPAI dari antarmuka.
 *
 * Audit 2026-08-11 menemukan empat halaman lengkap dan berfungsi yang tidak
 * punya satu pun tautan dari mana pun — Analitik (view 363 baris), Profil,
 * Impor Siswa, dan Rekap Kehadiran. Rutenya ada, controllernya ada, halamannya
 * merender dengan benar, dan satu-satunya cara membukanya adalah mengetik URL
 * sendiri. Fitur yang tidak bisa dicapai sama saja dengan tidak ada, tetapi
 * tetap dirawat dan tetap menanggung risiko.
 *
 * Tombol "Impor Excel" bahkan lebih buruk daripada tidak ada: ia menunjuk rute
 * POST lewat <a href>, jadi menekannya membalas 405 Method Not Allowed.
 */
class HalamanTerjangkauTest extends TestCase
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

    public function test_sidebar_menautkan_analitik_dan_profil(): void
    {
        $html = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString(route('analytics.index'), $html,
            'Halaman Analitik tidak punya pintu masuk dari sidebar');
        $this->assertStringContainsString(route('profile.edit'), $html,
            'Halaman Profil tidak punya pintu masuk dari sidebar');
    }

    public function test_tombol_impor_menunjuk_halaman_bukan_rute_post(): void
    {
        $html = $this->get(route('classes.students.index', $this->kelas))->assertOk()->getContent();

        $this->assertStringContainsString(
            route('classes.students.import.form', $this->kelas),
            $html,
            'Tombol impor harus menunjuk halaman GET; menunjuk rute POST membalas 405',
        );
    }

    public function test_halaman_absensi_menautkan_rekap_kehadiran(): void
    {
        $html = $this->get(route('classes.attendance.index', $this->kelas))->assertOk()->getContent();

        $this->assertStringContainsString(route('classes.reports.attendance', $this->kelas), $html);
    }

    /**
     * Keempatnya harus benar-benar terbuka, bukan sekadar tertaut.
     *
     * @dataProvider halamanYatim
     */
    public function test_halaman_yang_dulu_yatim_tetap_terbuka(string $rute, bool $perluKelas): void
    {
        $url = $perluKelas ? route($rute, $this->kelas) : route($rute);

        $this->get($url)->assertOk();
    }

    /** @return array<string, array{0: string, 1: bool}> */
    public static function halamanYatim(): array
    {
        return [
            'analitik' => ['analytics.index', false],
            'profil' => ['profile.edit', false],
            'impor siswa' => ['classes.students.import.form', true],
            'rekap kehadiran' => ['classes.reports.attendance', true],
        ];
    }
}
