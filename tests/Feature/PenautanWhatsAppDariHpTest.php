<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guru yang mendaftar dari ponsel, sampai WhatsApp-nya tersambung.
 *
 * Dua kebocoran yang dijaga di sini, keduanya gagal dengan senyap sehingga
 * tidak akan ketahuan tanpa test:
 *
 * 1. Nomor mustahil diterima formulir. Regex lama hanya menuntut "isinya
 *    angka", sehingga "0", "00", dan "08" lolos; Phone::normalize memolesnya
 *    jadi "62", "62", dan "628" — bentuk yang diterima gateway seperti nomor
 *    sungguhan, tercatat terkirim, dan tidak pernah sampai ke siapa pun.
 *
 * 2. Absensi otomatis dinyalakan tanpa WhatsApp tersambung. Penjadwal tetap
 *    berjalan, tautan presensi tidak pernah terkirim, dan guru baru tahu
 *    keesokan pagi. Guru harus diantar ke halaman penautan di detik ia
 *    menyalakan centangnya, bukan diberi tahu setelah gagal.
 */
class PenautanWhatsAppDariHpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /** @return array<string, array{string}> */
    public static function nomorMustahil(): array
    {
        return [
            'nol saja' => ['0'],
            'nol ganda' => ['00'],
            'awalan tanpa nomor' => ['08'],
            'terlalu pendek' => ['0812345'],
            'terlalu panjang' => ['0812345678901234'],
            'awalan operator mustahil' => ['08012345678'],
            'bukan seluler' => ['0221234567'],
        ];
    }

    /**
     * @dataProvider nomorMustahil
     */
    public function test_pendaftaran_menolak_nomor_yang_mustahil(string $nomor): void
    {
        $this->post('/register', [
            'name' => 'Bu Sri',
            'email' => 'sri@sekolah.sch.id',
            'whatsapp_number' => $nomor,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('whatsapp_number');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_pendaftaran_menerima_nomor_wajar_dan_menyimpannya_berformat_62(): void
    {
        $this->post('/register', [
            'name' => 'Bu Sri',
            'email' => 'sri@sekolah.sch.id',
            'whatsapp_number' => '0812-3456-7890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasNoErrors();

        $this->assertSame('6281234567890', User::firstOrFail()->whatsapp_number);
    }

    public function test_menyalakan_absensi_otomatis_mengantar_ke_halaman_whatsapp(): void
    {
        $guru = User::factory()->create(['wa_session_status' => 'disconnected']);

        $this->actingAs($guru)
            ->post('/classes', [
                'name' => 'VI-A',
                'academic_year' => '2026/2027',
                'auto_attendance' => '1',
            ])
            ->assertRedirect(route('whatsapp.index'));
    }

    public function test_kelas_tanpa_absensi_otomatis_tidak_diseret_ke_halaman_whatsapp(): void
    {
        $guru = User::factory()->create(['wa_session_status' => 'disconnected']);

        $this->actingAs($guru)
            ->post('/classes', [
                'name' => 'VI-B',
                'academic_year' => '2026/2027',
                'auto_attendance' => '0',
            ])
            ->assertRedirectContains('/classes/');
    }

    public function test_menyunting_kelas_yang_sudah_otomatis_tidak_mengganggu_guru(): void
    {
        $guru = User::factory()->create(['wa_session_status' => 'disconnected']);
        $kelas = Classroom::factory()->create([
            'user_id' => $guru->id,
            'auto_attendance' => true,
        ]);

        $this->actingAs($guru)
            ->put("/classes/{$kelas->id}", [
                'name' => 'VI-A Unggulan',
                'academic_year' => $kelas->academic_year,
                'auto_attendance' => '1',
            ])
            ->assertRedirect(route('classes.show', $kelas));
    }
}
