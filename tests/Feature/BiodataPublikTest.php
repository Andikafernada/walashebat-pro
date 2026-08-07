<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Formulir biodata mandiri dipakai siswa tanpa login, sering bertahap, dan
 * daftar pilihannya berbawaan "— Siswa Baru —". Dua akibatnya dulu:
 *
 *  - siswa yang mengetik ulang namanya melahirkan catatan kedua;
 *  - siswa yang melengkapi sebagian isian menghapus biodata lamanya, karena
 *    formulir selalu mengirim seluruh input termasuk yang kosong.
 */
class BiodataPublikTest extends TestCase
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
    }

    private function siswaLama(): Student
    {
        return Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'name' => 'Ahmad Fauzi',
            'nis' => '2025001',
            'nama_ayah' => 'Pak Slamet',
            'alamat_ortu' => 'Jl. Merdeka 10',
            'hobi' => 'Membaca',
            'cita_cita' => 'Dokter',
        ]);
    }

    private function kirim(array $isi): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('public.biodata.store', $this->class), $isi + [
            'gender' => 'L',
            'parent_phone' => '081234567890',
        ]);
    }

    // -- Nama yang sudah ada dicocokkan, bukan ditolak ---------------------

    /**
     * Siswa sering melewatkan daftar "Pilih Nama" lalu mengetik namanya.
     * Menolak mereka membuat jalan buntu; yang benar adalah memperbarui data
     * dirinya, persis seperti bila ia memilih dari daftar.
     */
    public function test_mengetik_ulang_nama_memperbarui_bukan_menduplikat(): void
    {
        $siswa = $this->siswaLama();

        $this->kirim(['name' => 'Ahmad Fauzi', 'hobi' => 'Memancing'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Student::withoutTenant()->where('class_id', $this->class->id)->count());
        $this->assertSame('Memancing', $siswa->fresh()->hobi);
    }

    /**
     * Cara menulis nama berbeda-beda tiap kali diketik. Ketiganya harus
     * mengarah ke siswa yang sama, bukan melahirkan tiga catatan.
     */
    public function test_variasi_penulisan_nama_tetap_dikenali_sebagai_orang_yang_sama(): void
    {
        $this->siswaLama();

        foreach (['  ahmad FAUZI ', 'Ahmad.Fauzi', 'Ahmad  Fauzi', 'AHMAD FAUZI'] as $variasi) {
            $this->kirim(['name' => $variasi])->assertSessionHasNoErrors();
        }

        $this->assertSame(
            1,
            Student::withoutTenant()->where('class_id', $this->class->id)->count(),
            'Variasi penulisan nama tidak boleh melahirkan catatan baru'
        );
    }

    public function test_data_lama_tetap_utuh_saat_dicocokkan_lewat_nama(): void
    {
        $siswa = $this->siswaLama();

        $this->kirim(['name' => 'ahmad fauzi', 'hobi' => 'Berenang'])
            ->assertSessionHasNoErrors();

        $siswa->refresh();

        $this->assertSame('Berenang', $siswa->hobi);
        $this->assertSame('Pak Slamet', $siswa->nama_ayah, 'Isian yang tidak dikirim tetap utuh');
        $this->assertSame('2025001', $siswa->nis);
    }

    /** Hanya kasus benar-benar rancu yang ditolak: dua siswa bernama sama. */
    public function test_nama_kembar_ditolak_karena_tidak_bisa_ditentukan(): void
    {
        $this->siswaLama();
        Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'name' => 'Ahmad Fauzi',
        ]);

        $this->kirim(['name' => 'Ahmad Fauzi'])->assertSessionHasErrors('name');

        $pesan = session('errors')->first('name');
        $this->assertStringContainsString('2 siswa', $pesan);
        $this->assertStringContainsString('Pilih nama Anda', $pesan);

        $this->assertSame(2, Student::withoutTenant()->where('class_id', $this->class->id)->count());
    }

    public function test_siswa_yang_benar_benar_baru_tetap_bisa_mendaftar(): void
    {
        $this->siswaLama();

        $this->kirim(['name' => 'Siti Aminah'])->assertSessionHasNoErrors();

        $this->assertSame(2, Student::withoutTenant()->where('class_id', $this->class->id)->count());
    }

    /** Nama yang sama di KELAS LAIN bukan orang yang sama. */
    public function test_nama_sama_di_kelas_lain_tidak_dicocokkan(): void
    {
        $kelasLain = Classroom::factory()->create(['user_id' => $this->user->id]);
        Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $kelasLain->id, 'name' => 'Ahmad Fauzi',
        ]);

        $this->kirim(['name' => 'Ahmad Fauzi'])->assertSessionHasNoErrors();

        $this->assertSame(1, Student::withoutTenant()->where('class_id', $this->class->id)->count());
    }

    // -- Isian kosong tidak menghapus data lama -----------------------------

    public function test_isian_kosong_tidak_menghapus_biodata_lama(): void
    {
        $siswa = $this->siswaLama();

        $this->kirim([
            'student_id' => $siswa->id,
            'name' => 'Ahmad Fauzi',
            'nama_ayah' => '',
            'alamat_ortu' => '',
            'hobi' => '',
            'cita_cita' => '',
        ])->assertSessionHasNoErrors();

        $siswa->refresh();

        $this->assertSame('Pak Slamet', $siswa->nama_ayah);
        $this->assertSame('Jl. Merdeka 10', $siswa->alamat_ortu);
        $this->assertSame('Membaca', $siswa->hobi);
        $this->assertSame('Dokter', $siswa->cita_cita);
    }

    public function test_isian_yang_diisi_tetap_menimpa(): void
    {
        $siswa = $this->siswaLama();

        $this->kirim([
            'student_id' => $siswa->id,
            'name' => 'Ahmad Fauzi',
            'hobi' => 'Sepak bola',
            'nama_ayah' => '',
        ])->assertSessionHasNoErrors();

        $siswa->refresh();

        $this->assertSame('Sepak bola', $siswa->hobi, 'Isian yang diisi harus tersimpan');
        $this->assertSame('Pak Slamet', $siswa->nama_ayah, 'Yang dikosongkan tetap utuh');
    }

    /** Angka 0 dan false adalah jawaban sah, bukan "kosong". */
    public function test_nol_dan_false_bukan_dianggap_kosong(): void
    {
        $siswa = $this->siswaLama();
        $siswa->update(['jumlah_saudara' => 3, 'penerima_kip' => true]);

        $this->kirim([
            'student_id' => $siswa->id,
            'name' => 'Ahmad Fauzi',
            'jumlah_saudara' => 0,
            'penerima_kip' => '0',
        ])->assertSessionHasNoErrors();

        $siswa->refresh();

        $this->assertSame(0, (int) $siswa->jumlah_saudara);
        $this->assertFalse((bool) $siswa->penerima_kip);
    }

    public function test_memilih_diri_sendiri_tidak_membuat_catatan_baru(): void
    {
        $siswa = $this->siswaLama();

        $this->kirim(['student_id' => $siswa->id, 'name' => 'Ahmad Fauzi', 'hobi' => 'Memancing'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Student::withoutTenant()->where('class_id', $this->class->id)->count());
        $this->assertSame('Memancing', $siswa->fresh()->hobi);
    }

    // -- Galat harus terlihat -----------------------------------------------

    public function test_pesan_galat_tampil_di_halaman(): void
    {
        $this->siswaLama();
        Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id, 'name' => 'Ahmad Fauzi',
        ]);

        $this->kirim(['name' => 'Ahmad Fauzi']);

        $this->get(route('public.biodata.show', $this->class))
            ->assertOk()
            ->assertSee('Isian belum bisa disimpan');
    }

    public function test_isian_dipertahankan_setelah_ditolak(): void
    {
        $this->siswaLama();
        Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id, 'name' => 'Ahmad Fauzi',
        ]);

        $this->kirim(['name' => 'Ahmad Fauzi'])->assertSessionHasInput('name', 'Ahmad Fauzi');
    }
}
