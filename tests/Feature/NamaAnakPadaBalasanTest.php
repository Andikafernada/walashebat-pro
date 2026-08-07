<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Support\Contracts\WhatsAppSessionManager;
use App\Support\Notifications\NullSessionManager;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Balasan otomatis ke grup orang tua menyebut nama anaknya
 * ("Semoga Ananda Dinar lekas sembuh"), bukan kalimat yang sama untuk semua.
 *
 * Petanya disusun di sini lalu dikirim ke gateway saat wali kelas menyimpan
 * pengaturan balasan otomatis. Yang dijaga berkas ini adalah isi peta itu —
 * penyusunan kalimatnya sendiri diuji di /opt/wa-gateway/balasan.test.js.
 */
class NamaAnakPadaBalasanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    /** @var array<string, string> */
    private array $petaTerkirim = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create([
            'whatsapp_number' => '628111111111',
            'wa_session_status' => 'connected',
        ]);
        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);

        /*
         * Gateway tiruan: menangkap peta yang dikirim, tidak menghubungi
         * apa pun. Memperluas NullSessionManager, bukan menyalin seluruh
         * antarmuka — supaya tes ini tidak ikut rusak setiap kali ada method
         * baru di kontraknya.
         */
        $uji = $this;
        $this->app->instance(WhatsAppSessionManager::class, new class($uji) extends NullSessionManager
        {
            public function __construct(private $uji) {}

            public function autoreplySave(User $user, bool $enabled, array $groups, array $permissionKeywords = [], array $sickKeywords = [], array $students = [], array $ragam = []): bool
            {
                $this->uji->rekamPeta($students);

                return true;
            }
        });
    }

    /** @param array<string, string> $peta */
    public function rekamPeta(array $peta): void
    {
        $this->petaTerkirim = $peta;
    }

    private function siswa(string $nama, ?string $telepon, bool $aktif = true): Student
    {
        return Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'name' => $nama,
            'parent_phone' => $telepon,
            'is_active' => $aktif,
        ]);
    }

    private function simpanPengaturan(): void
    {
        $this->actingAs($this->user)
            ->post(route('whatsapp.autoreply'), [
                'enabled' => true,
                'groups' => ['62811111111-1234@g.us'],
            ])
            ->assertSessionHasNoErrors();
    }

    // -- Isi peta -----------------------------------------------------------

    public function test_nomor_orang_tua_dipetakan_ke_nama_panggilan_anak(): void
    {
        $this->siswa('DINAR NUR FADILLAH', '081234567890');

        $this->simpanPengaturan();

        $this->assertSame(['6281234567890' => 'Dinar'], $this->petaTerkirim);
    }

    /**
     * Kapitalisasi data lapangan berantakan. Tanpa dirapikan, balasannya
     * berteriak "Semoga Ananda ALDY BAYU RIONA lekas sembuh".
     */
    public function test_nama_dirapikan_apa_pun_kapitalisasi_aslinya(): void
    {
        $this->siswa('ALDY BAYU RIONA', '081111111111');
        $this->siswa('adzka muhammad', '082222222222');

        $this->simpanPengaturan();

        $this->assertSame('Aldy', $this->petaTerkirim['6281111111111']);
        $this->assertSame('Adzka', $this->petaTerkirim['6282222222222']);
    }

    /**
     * Di satu kelas bisa ada beberapa "Muhammad"; kata pertama saja tidak
     * cukup membedakan.
     */
    public function test_nama_berawalan_umum_memakai_dua_kata(): void
    {
        $this->siswa('MUHAMMAD RIZKI PRATAMA', '081111111111');
        $this->siswa('Siti Aminah Zahra', '082222222222');
        $this->siswa('Nur Halimah', '083333333333');

        $this->simpanPengaturan();

        $this->assertSame('Muhammad Rizki', $this->petaTerkirim['6281111111111']);
        $this->assertSame('Siti Aminah', $this->petaTerkirim['6282222222222']);
        $this->assertSame('Nur Halimah', $this->petaTerkirim['6283333333333']);
    }

    /**
     * Inti penjagaannya: orang tua dengan dua anak di kelas yang sama tidak
     * bisa ditentukan sedang mengabarkan yang mana. Menyebut nama yang keliru
     * di depan seluruh grup lebih buruk daripada tidak menyebut nama.
     */
    public function test_nomor_dengan_dua_anak_tidak_dipetakan(): void
    {
        $this->siswa('Dinar Fadillah', '081234567890');
        $this->siswa('Rizki Fadillah', '081234567890');
        $this->siswa('Arifa Nurul', '089999999999');

        $this->simpanPengaturan();

        $this->assertArrayNotHasKey('6281234567890', $this->petaTerkirim, 'nomor ganda harus dilewati');
        $this->assertSame('Arifa', $this->petaTerkirim['6289999999999'], 'siswa lain tetap dipetakan');
    }

    public function test_siswa_nonaktif_dan_tanpa_nomor_dilewati(): void
    {
        $this->siswa('Siswa Arsip', '081111111111', aktif: false);
        $this->siswa('Tanpa Nomor', null);
        $this->siswa('Aktif Punya', '082222222222');

        $this->simpanPengaturan();

        $this->assertSame(['6282222222222' => 'Aktif'], $this->petaTerkirim);
    }

    /** Nomor ditulis 08xx maupun 62xx harus mendarat pada kunci yang sama. */
    public function test_format_nomor_diseragamkan(): void
    {
        $this->siswa('Budi Santoso', '0813-7030-852');

        $this->simpanPengaturan();

        $this->assertSame(['628137030852' => 'Budi'], $this->petaTerkirim);
    }

    /** Peta tidak boleh memuat siswa milik wali kelas lain. */
    public function test_siswa_kelas_lain_tidak_ikut_terkirim(): void
    {
        $this->siswa('Anak Sendiri', '081111111111');

        $lain = User::factory()->create();
        $kelasLain = Classroom::factory()->create(['user_id' => $lain->id]);
        Student::factory()->create([
            'user_id' => $lain->id,
            'class_id' => $kelasLain->id,
            'name' => 'Anak Guru Lain',
            'parent_phone' => '089999999999',
            'is_active' => true,
        ]);

        $this->simpanPengaturan();

        $this->assertSame(['6281111111111' => 'Anak'], $this->petaTerkirim);
    }
}
