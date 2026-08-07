<?php

namespace Tests\Feature;

use App\Models\CharacterDimension;
use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Formulir dan halaman yang isian-isiannya pernah hilang di tengah jalan.
 *
 * Nama-nama test di berkas ini semula menyebut kerusakan — "gagal senyap",
 * "selalu gagal", "diabaikan", "membuang lima field". Semuanya sudah
 * diperbaiki, tetapi tidak satu pun perbaikan itu pernah dikunci: isinya cuma
 * dump() ke layar lalu assertTrue(true). Kerusakan yang sama bisa kembali
 * kapan saja tanpa satu pun test berubah warna.
 *
 * Kelas yang dijaga di sini semuanya sejenis: data yang diketik pengguna harus
 * SAMPAI ke basis data, dan bila tidak, kegagalannya harus terlihat — bukan
 * diam-diam hilang. Kegagalan senyap pada formulir adalah yang paling mahal,
 * karena yang mengetik baru menyadarinya berminggu-minggu kemudian.
 */
class Audit05ViewFormTest extends TestCase
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

    public function test_portofolio_siswa_terbuka_saat_sudah_ada_refleksi(): void
    {
        $s = Student::factory()->create(['user_id' => $this->user->id, 'class_id' => $this->class->id]);
        $d = CharacterDimension::create([
            'user_id' => $this->user->id, 'code' => 'iman', 'name' => 'Keimanan',
            'is_active' => true, 'sort_order' => 1,
        ]);
        CharacterReflection::create([
            'user_id' => $this->user->id,
            'student_id' => $s->id, 'class_id' => $this->class->id,
            'character_dimension_id' => $d->id, 'period' => 'weekly',
            'reflection_date' => now()->toDateString(), 'self_rating' => 4,
            'what_went_well' => 'baik', 'what_to_improve' => 'lebih rajin',
            'action_plan' => 'belajar', 'status' => 'submitted', 'submitted_at' => now(),
        ]);

        $this->get(route('classes.character-portfolio.student', [$this->class, $s]))
            ->assertOk()
            ->assertSee('Keimanan');
    }

    /**
     * PATCH tanpa satu pun isian harus DITOLAK, bukan dijawab "berhasil".
     *
     * Bila permintaan kosong dijawab 200, pengguna melihat "tersimpan" padahal
     * tidak ada yang berubah — dan preferensi yang ia kira sudah dimatikan
     * tetap menyala.
     */
    public function test_preferensi_notifikasi_menolak_patch_kosong(): void
    {
        $pref = \App\Models\UserNotificationPreference::create([
            'user_id' => $this->user->id,
            'push_enabled' => true, 'push_attendance_reminder' => true,
            'push_new_violation' => true, 'push_low_cashbook' => true,
            'push_daily_summary' => true,
        ]);

        $this->patchJson(route('notifications.settings.update'), [])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertTrue($pref->refresh()->push_enabled, 'yang ditolak tidak boleh mengubah apa pun');
    }

    /**
     * Kas per siswa: student_id yang dipilih di formulir harus benar-benar
     * tersimpan. Kolomnya sempat tidak ada, sehingga nilainya dibuang diam-diam
     * dan setiap setoran tercatat tanpa penyetornya.
     */
    public function test_setoran_kas_menyimpan_siswa_penyetor(): void
    {
        $st = Student::factory()->create(['user_id' => $this->user->id, 'class_id' => $this->class->id]);

        $this->get(route('classes.cashbook.index', $this->class))
            ->assertOk()
            ->assertSee('name="student_id"', false);

        $this->post(route('classes.cashbook.store', $this->class), [
            'transaction_date' => '2026-08-04', 'type' => 'in', 'amount' => 5000,
            'description' => 'Kas mingguan', 'student_id' => $st->id,
        ])->assertSessionHasNoErrors();

        $baris = \App\Models\CashBook::withoutTenant()->latest('id')->firstOrFail();

        $this->assertSame($st->id, $baris->student_id, 'penyetornya harus ikut tercatat');
        $this->assertSame(5000, (int) $baris->amount);
    }

    /**
     * Lima isian biodata sempat dirender oleh Blade tetapi tidak pernah sampai
     * ke basis data. Orang tua mengisinya dari HP, menekan kirim, melihat
     * "terima kasih" — dan isinya menguap.
     */
    public function test_biodata_publik_menyimpan_seluruh_isian(): void
    {
        $html = $this->get(route('public.biodata.show', $this->class->tokenPublik()))
            ->assertOk()
            ->getContent();

        foreach (['tahun_masuk', 'nama_wali', 'pekerjaan_wali', 'anak_ke', 'jumlah_saudara'] as $f) {
            $this->assertStringContainsString('name="'.$f.'"', $html, "formulir tidak merender {$f}");
        }

        $this->post(route('public.biodata.store', $this->class->tokenPublik()), [
            'name' => 'Budi Santoso', 'gender' => 'L', 'parent_phone' => '081234567890',
            'nis' => '12345', 'tahun_masuk' => 2024, 'nama_wali' => 'Pak Karto',
            'pekerjaan_wali' => 'Petani', 'anak_ke' => 2, 'jumlah_saudara' => 3,
            'hobi' => 'Sepak bola',
        ])->assertSessionHasNoErrors();

        $s = Student::withoutTenant()->where('name', 'Budi Santoso')->firstOrFail();

        $this->assertSame('12345', $s->nis);
        $this->assertSame('Sepak bola', $s->hobi);
        $this->assertSame(2024, (int) $s->tahun_masuk);
        $this->assertSame('Pak Karto', $s->nama_wali);
        $this->assertSame('Petani', $s->pekerjaan_wali);
        $this->assertSame(2, (int) $s->anak_ke);
        $this->assertSame(3, (int) $s->jumlah_saudara);
    }

    /**
     * Modal "Refleksi Harian" di portal siswa: isian wajibnya harus ADA di
     * formulir, dan bila toh tidak terkirim, kegagalannya harus dilaporkan.
     *
     * Dulu modal itu tidak memuat reflection_date dan period sama sekali,
     * sehingga setiap kiriman ditolak validasi lalu dibuang tanpa pesan. Siswa
     * menulis refleksinya, menekan kirim, dan tidak terjadi apa-apa.
     */
    public function test_modal_refleksi_siswa_melaporkan_kegagalan_alih_alih_diam(): void
    {
        $siswa = Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id,
            'must_change_password' => false,
        ]);
        CharacterDimension::create([
            'user_id' => $this->user->id, 'code' => 'iman', 'name' => 'Keimanan',
            'is_active' => true, 'sort_order' => 1,
        ]);

        $html = $this->actingAs($siswa, 'student')
            ->get(route('student.portfolio'))->assertOk()->getContent();

        $this->assertStringContainsString('name="reflection_date"', $html);
        $this->assertStringContainsString('name="period"', $html);

        // Kiriman tanpa isian wajib harus mengembalikan galat yang terbaca.
        $this->actingAs($siswa, 'student')
            ->post(route('student.reflection.store'), [
                'what_went_well' => 'Hari ini saya piket kelas',
                'what_to_improve' => 'Kurang teliti mengerjakan PR',
            ])
            ->assertSessionHasErrors(['reflection_date', 'period']);

        $this->assertSame(0, CharacterReflection::withoutTenant()->count());
    }

    /**
     * Formulir "Catat Pelanggaran" tidak mengirim `points` — poinnya diambil
     * dari jenis pelanggaran. Dulu kolom itu wajib di sisi server, sehingga
     * setiap pencatatan ditolak dan buku poin tidak pernah terisi.
     */
    public function test_catat_pelanggaran_mengambil_poin_dari_jenisnya(): void
    {
        $st = Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id, 'discipline_points' => 100,
        ]);
        $vt = \App\Models\ViolationType::create([
            'user_id' => $this->user->id, 'name' => 'Terlambat', 'category' => 'ringan', 'points' => -5,
        ]);

        $this->post(route('classes.violations.store', $this->class), [
            'student_id' => $st->id, 'violation_type_id' => $vt->id,
            'occurred_on' => '2026-08-04', 'note' => 'Datang jam 08.15',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, \App\Models\Violation::withoutTenant()->count());
        $this->assertSame(95, (int) $st->fresh()->discipline_points, 'poin siswa harus ikut berkurang');
    }
}
