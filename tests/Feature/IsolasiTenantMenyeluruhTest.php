<?php

namespace Tests\Feature;

use App\Models\CashBook;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Isolasi antar wali kelas, diuji sebagai PERILAKU bukan pembacaan kode.
 *
 * Audit yang hanya membaca kode berulang kali salah menuduh kebocoran di
 * tempat yang sebenarnya aman, dan sebaliknya. Berkas ini menutup celah itu:
 * tiap kasus benar-benar mengirim permintaan sebagai wali kelas lain dan
 * memeriksa apa yang keluar.
 */
class IsolasiTenantMenyeluruhTest extends TestCase
{
    use RefreshDatabase;

    private User $guruA;

    private User $guruB;

    private Classroom $kelasB;

    private Student $siswaB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->guruA = User::factory()->create(['email' => 'a@sekolah-a.id']);
        $this->guruB = User::factory()->create(['email' => 'b@sekolah-b.id']);

        $this->kelasB = Classroom::factory()->create([
            'user_id' => $this->guruB->id,
            'name' => 'KELAS MILIK B',
        ]);

        $this->siswaB = Student::factory()->create([
            'user_id' => $this->guruB->id,
            'class_id' => $this->kelasB->id,
            'name' => 'SISWA MILIK B',
            'nis' => '999999',
        ]);
    }

    /** Endpoint ExamBrowser tidak boleh menelusuri NIS sekolah lain. */
    public function test_api_verify_tidak_menemukan_nis_sekolah_lain(): void
    {
        Sanctum::actingAs($this->guruA);

        $this->postJson('/api/v1/exam/verify', ['nis' => '999999'])
            ->assertNotFound()
            ->assertJson(['found' => false]);
    }

    /** Sanity check: guru pemiliknya sendiri tetap bisa. */
    public function test_api_verify_bekerja_untuk_pemiliknya(): void
    {
        Sanctum::actingAs($this->guruB);

        $this->postJson('/api/v1/exam/verify', ['nis' => '999999'])
            ->assertOk()
            ->assertJson(['found' => true]);
    }

    public function test_api_daftar_kelas_hanya_milik_sendiri(): void
    {
        Sanctum::actingAs($this->guruA);

        $this->getJson('/api/v1/classes')
            ->assertOk()
            ->assertDontSee('KELAS MILIK B');
    }

    /**
     * Seluruh halaman GET yang menerima kelas/siswa dari URL.
     * Satu saja yang mengembalikan 200 berarti data lintas sekolah bocor.
     */
    public function test_semua_halaman_kelas_menolak_kelas_orang_lain(): void
    {
        $this->actingAs($this->guruA);

        $rute = [
            'classes.show', 'classes.edit',
            'classes.students.index', 'classes.students.create',
            'classes.students.export', 'classes.students.trashed',
            'classes.schedules.index', 'classes.organization.index',
            'classes.violations.index', 'classes.cashbook.index',
            'classes.seating.index', 'classes.attendance.index',
            'classes.reports.attendance', 'classes.reports.full',
            'classes.reports.full.pdf',
        ];

        foreach ($rute as $nama) {
            $this->get(route($nama, $this->kelasB))
                ->assertNotFound("BOCOR: {$nama} membuka kelas milik wali kelas lain");
        }
    }

    public function test_profil_dan_pdf_siswa_orang_lain_ditolak(): void
    {
        $this->actingAs($this->guruA);

        $this->get(route('classes.students.show', [$this->kelasB, $this->siswaB]))->assertNotFound();
        $this->get(route('classes.students.pdf', [$this->kelasB, $this->siswaB]))->assertNotFound();
    }

    /** Menulis ke kelas orang lain harus ditolak, bukan hanya membaca. */
    public function test_tidak_bisa_menulis_ke_kelas_orang_lain(): void
    {
        $this->actingAs($this->guruA);

        $this->post(route('classes.attendance.store', $this->kelasB))->assertNotFound();

        $this->post(route('classes.students.store', $this->kelasB), [
            'name' => 'Sisipan', 'nis' => '123',
        ])->assertNotFound();

        $this->delete(route('classes.destroy', $this->kelasB))->assertNotFound();

        $this->assertDatabaseHas('classes', ['id' => $this->kelasB->id, 'deleted_at' => null]);
    }

    /** Poin disiplin siswa sekolah lain tidak boleh ikut berubah. */
    public function test_pelanggaran_tidak_bisa_menyentuh_siswa_sekolah_lain(): void
    {
        $kelasA = Classroom::factory()->create(['user_id' => $this->guruA->id]);
        $poinAwal = $this->siswaB->discipline_points;

        $this->actingAs($this->guruA);

        // Kelas milik sendiri, tapi student_id milik sekolah lain.
        $this->post(route('classes.violations.store', $kelasA), [
            'student_id' => $this->siswaB->id,
            'points' => -50,
            'occurred_on' => today()->toDateString(),
        ])->assertSessionHasErrors('student_id');

        $this->assertSame($poinAwal, $this->siswaB->fresh()->discipline_points);
    }

    /** Buku kas: angka uang kelas lain tidak boleh terlihat. */
    public function test_buku_kas_kelas_lain_tidak_terlihat(): void
    {
        CashBook::create([
            'user_id' => $this->guruB->id,
            'class_id' => $this->kelasB->id,
            'type' => 'in',
            'amount' => 7654321,
            'description' => 'TRANSAKSI RAHASIA B',
            'transaction_date' => today(),
        ]);

        $this->actingAs($this->guruA);
        $kelasA = Classroom::factory()->create(['user_id' => $this->guruA->id]);

        $this->get(route('classes.cashbook.index', $kelasA))
            ->assertOk()
            ->assertDontSee('TRANSAKSI RAHASIA B')
            ->assertDontSee('7.654.321');
    }

    /** Dashboard tidak boleh menampilkan siswa sekolah lain di daftar perhatian. */
    public function test_dashboard_tidak_membocorkan_siswa_sekolah_lain(): void
    {
        $this->siswaB->update(['discipline_points' => 10]);

        Violation::create([
            'user_id' => $this->guruB->id,
            'class_id' => $this->kelasB->id,
            'student_id' => $this->siswaB->id,
            'points' => -90,
            'occurred_on' => today(),
        ]);

        $this->actingAs($this->guruA);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('SISWA MILIK B')
            ->assertDontSee('KELAS MILIK B');
    }
}
