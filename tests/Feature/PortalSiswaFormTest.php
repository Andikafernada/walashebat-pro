<?php

namespace Tests\Feature;

use App\Models\CharacterDimension;
use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationType;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tiga formulir ini pernah gagal senyap: markup dan aturan validasinya tidak
 * sepakat, jadi tombol Simpan terlihat bekerja padahal tidak ada yang tersimpan.
 * Audit05ViewFormTest hanya mencatat gejalanya tanpa assertion; test ini yang
 * mengunci perilaku benarnya.
 */
class PortalSiswaFormTest extends TestCase
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

    private function siswa(array $atribut = []): Student
    {
        return Student::factory()->create($atribut + [
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
        ]);
    }

    // -- Modal "Refleksi Harian" di portal siswa ---------------------------

    public function test_modal_refleksi_harian_merender_tanggal_dan_periode(): void
    {
        $siswa = $this->siswa(['must_change_password' => false]);
        CharacterDimension::create([
            'user_id' => $this->user->id, 'code' => 'iman', 'name' => 'Keimanan',
            'is_active' => true, 'sort_order' => 1,
        ]);

        $html = $this->actingAs($siswa, 'student')
            ->get(route('student.portfolio'))->assertOk()->getContent();

        $this->assertStringContainsString('name="reflection_date"', $html);
        $this->assertStringContainsString('name="period"', $html);
    }

    public function test_refleksi_harian_tersimpan_dari_payload_modal(): void
    {
        $siswa = $this->siswa(['must_change_password' => false]);

        $this->actingAs($siswa, 'student')
            ->post(route('student.reflection.store'), [
                'period' => 'daily',
                'reflection_date' => today()->format('Y-m-d'),
                'what_went_well' => 'Hari ini saya piket kelas',
                'what_to_improve' => 'Kurang teliti mengerjakan PR',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('student.portfolio'));

        $refleksi = CharacterReflection::withoutTenant()->sole();

        $this->assertSame($siswa->id, $refleksi->student_id);
        $this->assertSame($this->class->id, $refleksi->class_id);
        $this->assertSame('daily', $refleksi->period);
        $this->assertSame(CharacterReflection::STATUS_SUBMITTED, $refleksi->status);
        $this->assertSame('Hari ini saya piket kelas', $refleksi->what_went_well);
    }

    public function test_kegagalan_validasi_di_portal_siswa_terlihat_oleh_siswa(): void
    {
        $siswa = $this->siswa(['must_change_password' => false]);

        $this->actingAs($siswa, 'student')
            ->from(route('student.portfolio'))
            ->post(route('student.reflection.store'), ['what_went_well' => 'tanpa tanggal'])
            ->assertSessionHasErrors('reflection_date');

        // Layout siswa harus memunculkan pesan galat, bukan memuat ulang diam-diam.
        $html = $this->actingAs($siswa, 'student')
            ->get(route('student.portfolio'))->assertOk()->getContent();

        $this->assertStringContainsString('reflection date', strtolower($html));
    }

    // -- Formulir biodata publik -------------------------------------------

    public function test_biodata_publik_menyimpan_lima_field_keluarga(): void
    {
        $this->post(route('public.biodata.store', $this->class), [
            'name' => 'Budi Santoso',
            'gender' => 'L',
            'parent_phone' => '081234567890',
            'nis' => '12345',
            'tahun_masuk' => 2024,
            'nama_wali' => 'Pak Karto',
            'pekerjaan_wali' => 'Petani',
            'anak_ke' => 2,
            'jumlah_saudara' => 3,
            'hobi' => 'Sepak bola',
        ])->assertSessionHasNoErrors();

        $siswa = Student::withoutTenant()->where('name', 'Budi Santoso')->sole();

        $this->assertSame(2024, (int) $siswa->tahun_masuk);
        $this->assertSame('Pak Karto', $siswa->nama_wali);
        $this->assertSame('Petani', $siswa->pekerjaan_wali);
        $this->assertSame(2, (int) $siswa->anak_ke);
        $this->assertSame(3, (int) $siswa->jumlah_saudara);
    }

    public function test_biodata_publik_menolak_tahun_masuk_di_luar_nalar(): void
    {
        $this->post(route('public.biodata.store', $this->class), [
            'name' => 'Siti Aminah',
            'gender' => 'P',
            'parent_phone' => '081234567891',
            'tahun_masuk' => 3025,
        ])->assertSessionHasErrors('tahun_masuk');

        $this->assertSame(0, Student::withoutTenant()->where('name', 'Siti Aminah')->count());
    }

    // -- Formulir catat pelanggaran ----------------------------------------

    private function jenisPelanggaran(int $poin = -5, ?User $pemilik = null): ViolationType
    {
        return ViolationType::create([
            'user_id' => ($pemilik ?? $this->user)->id,
            'name' => 'Terlambat',
            'category' => 'ringan',
            'points' => $poin,
        ]);
    }

    public function test_pelanggaran_dengan_jenis_memakai_poin_dari_jenis(): void
    {
        $siswa = $this->siswa(['discipline_points' => 100]);
        $jenis = $this->jenisPelanggaran(-5);

        $this->actingAs($this->user)
            ->post(route('classes.violations.store', $this->class), [
                'student_id' => $siswa->id,
                'violation_type_id' => $jenis->id,
                'occurred_on' => '2026-08-04',
                'note' => 'Datang jam 08.15',
            ])
            ->assertSessionHasNoErrors();

        $pelanggaran = Violation::withoutTenant()->sole();

        $this->assertSame(-5, $pelanggaran->points);
        $this->assertSame(95, $siswa->fresh()->discipline_points);
    }

    public function test_poin_dari_klien_diabaikan_bila_jenis_dipilih(): void
    {
        $siswa = $this->siswa(['discipline_points' => 100]);
        $jenis = $this->jenisPelanggaran(-5);

        $this->actingAs($this->user)
            ->post(route('classes.violations.store', $this->class), [
                'student_id' => $siswa->id,
                'violation_type_id' => $jenis->id,
                'occurred_on' => '2026-08-04',
                'points' => 90, // usaha menaikkan poin lewat request yang dipalsukan
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(-5, Violation::withoutTenant()->sole()->points);
        $this->assertSame(95, $siswa->fresh()->discipline_points);
    }

    public function test_pelanggaran_custom_wajib_mengisi_poin(): void
    {
        $siswa = $this->siswa(['discipline_points' => 100]);

        $this->actingAs($this->user)
            ->post(route('classes.violations.store', $this->class), [
                'student_id' => $siswa->id,
                'occurred_on' => '2026-08-04',
                'note' => 'Tanpa jenis dan tanpa poin',
            ])
            ->assertSessionHasErrors('points');

        $this->assertSame(0, Violation::withoutTenant()->count());
        $this->assertSame(100, $siswa->fresh()->discipline_points);
    }

    public function test_pelanggaran_custom_tersimpan_dengan_poin_sendiri(): void
    {
        $siswa = $this->siswa(['discipline_points' => 100]);

        $this->actingAs($this->user)
            ->post(route('classes.violations.store', $this->class), [
                'student_id' => $siswa->id,
                'occurred_on' => '2026-08-04',
                'points' => -20,
                'note' => 'Merusak fasilitas',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(-20, Violation::withoutTenant()->sole()->points);
        $this->assertSame(80, $siswa->fresh()->discipline_points);
    }

    public function test_form_pelanggaran_merender_input_poin(): void
    {
        $this->siswa();

        $html = $this->actingAs($this->user)
            ->get(route('classes.violations.index', $this->class))->assertOk()->getContent();

        $this->assertStringContainsString('name="points"', $html);
    }

    public function test_jenis_pelanggaran_milik_sekolah_lain_ditolak(): void
    {
        $siswa = $this->siswa(['discipline_points' => 100]);
        $jenisAsing = $this->jenisPelanggaran(-50, User::factory()->create());

        $this->actingAs($this->user)
            ->post(route('classes.violations.store', $this->class), [
                'student_id' => $siswa->id,
                'violation_type_id' => $jenisAsing->id,
                'occurred_on' => '2026-08-04',
            ])
            ->assertSessionHasErrors('violation_type_id');

        $this->assertSame(0, Violation::withoutTenant()->count());
        $this->assertSame(100, $siswa->fresh()->discipline_points);
    }
}
