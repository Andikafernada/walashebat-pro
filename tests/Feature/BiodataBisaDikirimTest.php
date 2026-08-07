<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Formulir biodata mandiri terbagi empat langkah, dan tombol Simpan hanya
 * muncul di langkah terakhir. Isian wajibnya tersebar di langkah lain yang saat
 * itu tersembunyi (display:none), sehingga peramban membatalkan pengiriman
 * tanpa pesan apa pun: siswa menekan Simpan dan tidak terjadi apa-apa. Dari 82
 * pembukaan halaman kelas XII TKJ D, hanya 13 yang benar-benar sampai ke
 * server.
 */
class BiodataBisaDikirimTest extends TestCase
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

    private function kirim(array $isi): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('public.biodata.store', $this->class), $isi + [
            'gender' => 'L',
            'parent_phone' => '081234567890',
        ]);
    }

    // -- Isian wajib tidak lagi menjebak ------------------------------------

    /**
     * Memilih nama dari daftar adalah jalur yang dianjurkan halaman ini.
     * Siswa yang menurutinya lalu membiarkan kolom nama kosong harus tetap
     * bisa menyimpan.
     */
    public function test_memilih_dari_daftar_tanpa_mengetik_nama_tetap_tersimpan(): void
    {
        $siswa = Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'name' => 'Ahmad Fauzi',
        ]);

        $this->kirim(['student_id' => $siswa->id, 'hobi' => 'Memancing'])
            ->assertSessionHasNoErrors();

        $siswa->refresh();

        $this->assertSame('Memancing', $siswa->hobi);
        $this->assertSame('Ahmad Fauzi', $siswa->name, 'Nama lama tidak boleh terhapus');
        $this->assertSame(1, Student::withoutTenant()->where('class_id', $this->class->id)->count());
    }

    /** Tanpa memilih dari daftar, nama tetap wajib — kalau tidak, siswanya siapa? */
    public function test_tanpa_memilih_dari_daftar_nama_tetap_wajib(): void
    {
        $this->kirim(['hobi' => 'Memancing'])->assertSessionHasErrors('name');

        $this->assertSame(0, Student::withoutTenant()->where('class_id', $this->class->id)->count());
    }

    /** Isian wajib tidak boleh disembunyikan dari validasi peramban. */
    public function test_formulir_mematikan_validasi_bawaan_peramban(): void
    {
        $halaman = $this->get(route('public.biodata.show', $this->class))->assertOk();

        $halaman->assertSee('novalidate', false);
        $halaman->assertSee('data-langkah="2"', false);
        $halaman->assertSee('periksa($event)', false);
    }

    // -- Isian tidak hilang saat ditolak ------------------------------------

    public function test_seluruh_isian_dipertahankan_setelah_ditolak(): void
    {
        Student::factory()->count(2)->create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id, 'name' => 'Ahmad Fauzi',
        ]);

        $isi = [
            'name' => 'Ahmad Fauzi',
            'nis' => '2025001',
            'tempat_lahir' => 'Cimahi',
            'nama_ayah' => 'Dede Januar',
            'rt_rw' => 'RT 01/ RW 28',
            'kelurahan' => 'Melong',
            'hobi' => 'Merakit model kit',
            'cita_cita' => 'Industri game',
        ];

        $this->kirim($isi)->assertSessionHasErrors('name');

        foreach ($isi as $kolom => $nilai) {
            $this->assertSame(session('_old_input')[$kolom] ?? null, $nilai, "Isian $kolom hilang");
        }
    }

    public function test_isian_lama_dirender_kembali_di_halaman(): void
    {
        Student::factory()->count(2)->create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id, 'name' => 'Ahmad Fauzi',
        ]);

        $this->kirim(['name' => 'Ahmad Fauzi', 'nama_ayah' => 'Dede Januar', 'hobi' => 'Merakit model kit']);

        $this->get(route('public.biodata.show', $this->class))
            ->assertOk()
            ->assertSee('Dede Januar', false)
            ->assertSee('Merakit model kit', false);
    }

    // -- Pesan galat berbahasa Indonesia ------------------------------------

    public function test_pesan_galat_berbahasa_indonesia(): void
    {
        $this->post(route('public.biodata.store', $this->class), ['gender' => 'L']);

        $pesan = session('errors')->all();

        $this->assertNotEmpty($pesan);
        $this->assertStringContainsString('wajib diisi', implode(' ', $pesan));
        $this->assertStringContainsString('Nomor WhatsApp Orang Tua', implode(' ', $pesan));
    }

    // -- Duplikat --------------------------------------------------------------

    /** NIS menunjuk orang yang sama walau namanya ditulis berbeda. */
    public function test_nis_yang_sama_memperbarui_bukan_menduplikat(): void
    {
        $siswa = Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'name' => 'Renata Aulia Priyanti',
            'nis' => '242510234',
        ]);

        $this->kirim(['name' => 'Renata Aulia', 'nis' => '242510234', 'hobi' => 'Menari'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Student::withoutTenant()->where('class_id', $this->class->id)->count());
        $this->assertSame('Menari', $siswa->fresh()->hobi);
    }

    public function test_nisn_yang_sama_memperbarui_bukan_menduplikat(): void
    {
        $siswa = Student::factory()->create([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'name' => 'Muhammad Adriel Zhafran',
            'nis' => null,
            'nisn' => '3172020405080005',
        ]);

        $this->kirim(['name' => 'Adriel Zhafran', 'nisn' => '3172020405080005'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Student::withoutTenant()->where('class_id', $this->class->id)->count());
    }

    /** NIS milik siswa kelas lain bukan urusan kelas ini. */
    public function test_nis_sama_di_kelas_lain_tidak_dicocokkan(): void
    {
        $kelasLain = Classroom::factory()->create(['user_id' => $this->user->id]);
        Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $kelasLain->id,
            'name' => 'Renata Aulia Priyanti', 'nis' => '242510234',
        ]);

        $this->kirim(['name' => 'Siswa Baru', 'nis' => '242510234'])->assertSessionHasNoErrors();

        $this->assertSame(1, Student::withoutTenant()->where('class_id', $this->class->id)->count());
        $this->assertSame(1, Student::withoutTenant()->where('class_id', $kelasLain->id)->count());
    }

    /** NIS yang kebetulan ganda di data sekolah tidak boleh jadi tebakan. */
    public function test_nis_ganda_di_kelas_tidak_dipakai_mencocokkan(): void
    {
        foreach (['Renata Aulia Priyanti', 'Rendy Moerdany'] as $nama) {
            Student::factory()->create([
                'user_id' => $this->user->id, 'class_id' => $this->class->id,
                'name' => $nama, 'nis' => '242510234',
            ]);
        }

        $this->kirim(['name' => 'Rendy Moerdany', 'nis' => '242510234', 'hobi' => 'Futsal'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Student::withoutTenant()->where('class_id', $this->class->id)->count());
        $this->assertSame(
            'Futsal',
            Student::withoutTenant()->where('name', 'Rendy Moerdany')->value('hobi'),
            'Pencocokan jatuh ke nama, bukan menebak salah satu pemilik NIS ganda'
        );
    }

    /** Siswa kelas lain tidak boleh disunting lewat id di formulir ini. */
    public function test_id_siswa_kelas_lain_ditolak(): void
    {
        $kelasLain = Classroom::factory()->create(['user_id' => $this->user->id]);
        $siswaLain = Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $kelasLain->id, 'name' => 'Bukan Siswa Sini',
        ]);

        $this->kirim(['student_id' => $siswaLain->id, 'name' => 'Diganti'])
            ->assertSessionHasErrors('student_id');

        $this->assertSame('Bukan Siswa Sini', $siswaLain->fresh()->name);
    }

    /** RT/RW format lazim tidak boleh lagi memicu galat 500. */
    public function test_rt_rw_panjang_wajar_tersimpan(): void
    {
        $this->kirim(['name' => 'Muhammad Adriel Zhafran', 'rt_rw' => 'RT 01/ RW 28'])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'RT 01/ RW 28',
            Student::withoutTenant()->where('class_id', $this->class->id)->value('rt_rw')
        );
    }
}
