<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Papan tugas "yang perlu Anda kerjakan hari ini".
 *
 * Dashboard menjawab "bagaimana keadaannya"; papan ini menjawab "saya harus
 * apa sekarang". Yang dijaga di sini adalah dua kegagalan yang tidak
 * menimbulkan galat apa pun — papan yang menyuruh mengerjakan sesuatu yang
 * sudah selesai, dan papan yang diam padahal ada pekerjaan tertunda. Keduanya
 * hanya akan ketahuan lewat wali kelas yang berhenti mempercayainya.
 */
class PapanTugasHariIniTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guru = User::factory()->create();
        $this->actingAs($this->guru);
    }

    private function kelas(string $nama = 'Kelas 5A'): Classroom
    {
        $kelas = Classroom::factory()->create([
            'user_id' => $this->guru->id,
            'name' => $nama,
            'jenis' => Classroom::JENIS_PERWALIAN,
            'is_active' => true,
        ]);

        Student::factory()->create([
            'user_id' => $this->guru->id,
            'class_id' => $kelas->id,
            'is_active' => true,
        ]);

        return $kelas;
    }

    private function papan(): string
    {
        return $this->get(route('dashboard'))->assertOk()->getContent();
    }

    public function test_kelas_yang_belum_diabsen_muncul_sebagai_tugas(): void
    {
        $this->kelas('Kelas 5A');

        $html = $this->papan();

        $this->assertStringContainsString('1 kelas belum diabsen hari ini', $html);
        $this->assertStringContainsString('Kelas 5A', $html);
    }

    public function test_sesi_yang_sudah_dikirim_tidak_disuruh_diabsen_lagi(): void
    {
        /*
         * Kegagalan yang paling merusak kepercayaan: papan menyuruh
         * mengerjakan yang sudah dikerjakan. Sekali itu terjadi, seluruh
         * papannya berhenti dibaca.
         */
        $kelas = $this->kelas('Kelas 5A');

        AttendanceSession::create([
            'user_id' => $this->guru->id,
            'class_id' => $kelas->id,
            'session_date' => today(),
            'sequence' => 1,
            'token' => 'tok'.uniqid(),
            'pin_hash' => bcrypt('123456'),
            'expires_at' => now()->addDay(),
            'status' => 'submitted',
        ]);

        $html = $this->papan();

        $this->assertStringNotContainsString('belum diabsen hari ini', $html);
        $this->assertStringContainsString('Tidak ada yang tertunda', $html);
    }

    public function test_papan_tetap_ada_saat_tidak_ada_tugas(): void
    {
        // Ketiadaan tugas adalah kabar baik yang ingin dilihat. Papan yang
        // lenyap membuat guru bertanya-tanya apakah ia melewatkan sesuatu.
        $html = $this->papan();

        $this->assertStringContainsString('Yang perlu Anda kerjakan hari ini', $html);
    }

    public function test_whatsapp_putus_didahulukan_dari_ajakan_mengabsen(): void
    {
        /*
         * Selama sesi WhatsApp mati, tautan presensi tidak akan berangkat ke
         * mana pun — menyuruh "mulai absensi" lebih dulu berakhir dengan
         * tombol yang ditekan dan tidak terjadi apa-apa.
         */
        $kelas = $this->kelas('Kelas 5A');
        $kelas->update(['auto_attendance' => true]);
        $this->guru->update(['wa_session_status' => 'disconnected']);

        $html = $this->papan();

        $posisiWa = strpos($html, 'WhatsApp terputus');
        $posisiAbsen = strpos($html, 'belum diabsen hari ini');

        $this->assertNotFalse($posisiWa, 'peringatan WhatsApp putus tidak muncul');
        $this->assertNotFalse($posisiAbsen);
        $this->assertLessThan($posisiAbsen, $posisiWa, 'WhatsApp putus harus berada di atas ajakan mengabsen');
    }

    public function test_papan_tidak_menambah_query(): void
    {
        /*
         * Papan ini disusun dari koleksi yang sudah diambil index(). Bila suatu
         * saat ada yang menambahkan query di dalamnya, jumlahnya melonjak di
         * halaman yang paling sering dibuka di seluruh aplikasi.
         */
        $this->kelas('Kelas 5A');
        $this->papan(); // panaskan cache view supaya kompilasi tidak ikut terhitung

        \DB::enableQueryLog();
        $this->papan();
        $jumlah = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $this->assertLessThan(40, $jumlah, "dashboard menjalankan {$jumlah} query — papan tugas kemungkinan menambah query sendiri");
    }
}
