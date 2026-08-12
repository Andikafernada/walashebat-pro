<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Formulir siswa tidak boleh membatalkan kirimannya sendiri tanpa pesan.
 *
 * Formulir ini berlipat lima tab. Kolom tak sah yang sedang berada di tab
 * TERTUTUP membuat Chrome membatalkan pengiriman diam-diam: ia menolak menyorot
 * kolom yang display:none, lalu menyerah. Cukup satu tanggal lahir yang diketik
 * separuh di tab "Pribadi" — guru menekan Simpan berkali-kali, tidak terjadi
 * apa-apa, dan tidak satu pun permintaan sampai ke server. Persis yang terjadi
 * pada kelas 611: halaman sunting dibuka 10:54:50, tidak ada POST sesudahnya.
 *
 * Yang dijaga di sini bukan perilaku perambannya (itu tidak bisa diuji di sini)
 * melainkan kaitan yang membuat penanganannya mungkin: penangan submit harus
 * ada, dan setiap panel harus menyebut namanya sendiri supaya panel yang
 * menyembunyikan kolom itu bisa ditemukan lalu dibuka.
 */
class FormSiswaTidakBatalDiamDiamTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private Classroom $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create();
        $this->kelas = Classroom::factory()->create(['user_id' => $this->guru->id]);
    }

    /** @return string[] */
    private function halaman(): array
    {
        $siswa = Student::factory()->create([
            'user_id' => $this->guru->id, 'class_id' => $this->kelas->id,
        ]);

        return [
            $this->actingAs($this->guru)->get(route('classes.students.create', $this->kelas))->assertOk()->getContent(),
            $this->actingAs($this->guru)->get(route('classes.students.edit', [$this->kelas, $siswa]))->assertOk()->getContent(),
        ];
    }

    public function test_setiap_panel_menyebut_namanya_sendiri(): void
    {
        foreach ($this->halaman() as $html) {
            foreach (['identitas', 'pribadi', 'domisili', 'ortu', 'sekolah'] as $bagian) {
                $this->assertStringContainsString(
                    'data-bagian="'.$bagian.'"',
                    $html,
                    "Panel {$bagian} tidak bisa ditemukan dari kolomnya, jadi tab-nya tidak akan pernah dibuka."
                );
            }
        }
    }

    public function test_penangan_submit_memeriksa_keabsahan_lebih_dulu(): void
    {
        foreach ($this->halaman() as $html) {
            $this->assertStringContainsString('periksa($el)', $html);
            $this->assertStringContainsString(':invalid', $html);
            $this->assertStringContainsString('reportValidity', $html);
        }
    }

    /**
     * Lipatan tab hidup di <form>, bukan di dalamnya: penangan submit terpasang
     * pada <form>, dan ia harus bisa mengubah tab yang sedang terbuka.
     */
    public function test_tab_dikendalikan_dari_form_bukan_dari_dalamnya(): void
    {
        foreach ($this->halaman() as $html) {
            $this->assertStringContainsString("bagian: 'identitas'", $html);
            $this->assertStringNotContainsString('x-data="{ bagian:', $html);
        }
    }

    /** Sisi server tetap harus menyimpan kiriman yang sah. */
    public function test_penyuntingan_yang_sah_tersimpan(): void
    {
        $siswa = Student::factory()->create([
            'user_id' => $this->guru->id, 'class_id' => $this->kelas->id, 'name' => 'Nama Lama',
        ]);

        $this->actingAs($this->guru)
            ->put(route('classes.students.update', [$this->kelas, $siswa]), [
                'name' => 'Nama Baru',
                'gender' => $siswa->gender,
            ])
            ->assertRedirect();

        $this->assertSame('Nama Baru', $siswa->fresh()->name);
    }
}
