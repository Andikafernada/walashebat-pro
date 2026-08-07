<?php

namespace Tests\Feature;

use App\Models\CashBook;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Formulir kas sudah lama menawarkan "Siswa Pembayar" dan daftarnya sudah
 * menyiapkan tempat menampilkan namanya, tetapi kolom student_id tidak pernah
 * ada di tabel — pilihan guru dibuang diam-diam pada setiap penyimpanan.
 */
class KasSiswaPembayarTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->user = User::factory()->create();
        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);
    }

    private function siswa(array $atribut = []): Student
    {
        return Student::factory()->create($atribut + [
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
        ]);
    }

    private function catat(array $isi = []): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('classes.cashbook.store', $this->class), $isi + [
            'transaction_date' => '2026-08-04',
            'type' => 'in',
            'amount' => 5000,
            'description' => 'Kas mingguan',
        ]);
    }

    public function test_siswa_pembayar_tersimpan(): void
    {
        $siswa = $this->siswa(['name' => 'Ani Lestari']);

        $this->catat(['student_id' => $siswa->id])->assertSessionHasNoErrors();

        $baris = CashBook::withoutTenant()->sole();

        $this->assertSame($siswa->id, $baris->student_id);
        $this->assertSame('Ani Lestari', $baris->student->name);
    }

    public function test_transaksi_umum_boleh_tanpa_siswa(): void
    {
        $this->siswa();

        $this->catat(['description' => 'Beli spidol'])->assertSessionHasNoErrors();

        $baris = CashBook::withoutTenant()->sole();

        $this->assertNull($baris->student_id);
        $this->assertNull($baris->student);
    }

    public function test_nama_siswa_tampil_di_daftar_kas(): void
    {
        $siswa = $this->siswa(['name' => 'Budi Santoso']);
        $this->catat(['student_id' => $siswa->id]);

        $this->get(route('classes.cashbook.index', $this->class))
            ->assertOk()
            ->assertSee('Budi Santoso');
    }

    /** Siswa kelas lain tidak boleh dijadikan pembayar. */
    public function test_siswa_kelas_lain_ditolak(): void
    {
        $kelasLain = Classroom::factory()->create(['user_id' => $this->user->id]);
        $asing = Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $kelasLain->id,
        ]);

        $this->catat(['student_id' => $asing->id])
            ->assertSessionHasErrors('student_id');

        $this->assertSame(0, CashBook::withoutTenant()->count());
    }

    /** Termasuk siswa milik sekolah lain, yang lolos aturan exists polos. */
    public function test_siswa_sekolah_lain_ditolak(): void
    {
        $guruLain = User::factory()->create();
        $kelasLain = Classroom::factory()->create(['user_id' => $guruLain->id]);
        $asing = Student::factory()->create([
            'user_id' => $guruLain->id, 'class_id' => $kelasLain->id,
        ]);

        $this->catat(['student_id' => $asing->id])
            ->assertSessionHasErrors('student_id');

        $this->assertSame(0, CashBook::withoutTenant()->count());
    }

    /**
     * Uang yang pernah masuk tetap pernah masuk.
     *
     * Menghapus barisnya saat siswa dihapus akan membuat seluruh balance_after
     * sesudahnya meleset tanpa ada yang menyadari.
     */
    public function test_menghapus_siswa_tidak_menghapus_riwayat_kas(): void
    {
        $siswa = $this->siswa();
        $this->catat(['student_id' => $siswa->id]);

        $siswa->forceDelete();

        $baris = CashBook::withoutTenant()->sole();

        $this->assertNotNull($baris, 'Baris kas tidak boleh ikut terhapus');
        $this->assertSame(5000, $baris->amount);
        $this->assertNull($baris->student_id, 'Tautannya yang dilepas, bukan barisnya');
    }

    public function test_formulir_menawarkan_siswa_kelas_ini_saja(): void
    {
        $milik = $this->siswa(['name' => 'Citra Kelas Ini']);

        $kelasLain = Classroom::factory()->create(['user_id' => $this->user->id]);
        Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $kelasLain->id,
            'name' => 'Dedi Kelas Lain',
        ]);

        $this->get(route('classes.cashbook.index', $this->class))
            ->assertOk()
            ->assertSee('Citra Kelas Ini')
            ->assertDontSee('Dedi Kelas Lain');
    }
}
