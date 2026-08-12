<?php

namespace Tests\Feature;

use App\Models\CharacterDimension;
use App\Models\CharacterRecord;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\CharacterDimensionProvisioner;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `positive_score` dipanggil Student\PortfolioController sejak awal, tetapi
 * kolomnya tidak pernah ada di tabel dimensi: nilainya selalu null sehingga
 * setiap pencapaian bernilai 5 poin dan wali kelas tidak punya cara membedakan
 * bobot antar dimensi.
 *
 * Di jalur catatan mandiri masalahnya lebih jauh: poin diambil apa adanya dari
 * request, padahal catatan itu ditulis siswa untuk dirinya sendiri dan langsung
 * menambah discipline_points.
 */
class BobotPoinDimensiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Student $student;

    private CharacterDimension $dimensi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create();
        (new CharacterDimensionProvisioner)->provisionFor($this->user->id);

        $class = Classroom::factory()->create(['user_id' => $this->user->id]);
        $this->student = Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $class->id, 'discipline_points' => 0,
            // Seluruh portal siswa ada di balik gerbang wajib-ganti-sandi;
            // tanpa ini setiap POST dialihkan ke formulir sandi, bukan ditolak
            // — dan `assertRedirect()` tetap hijau sambil tidak menguji apa pun.
            'must_change_password' => false,
        ]);
        $this->dimensi = CharacterDimension::forOwner($this->user->id)->active()->first();
    }

    public function test_dimensi_bawaan_punya_bobot_poin(): void
    {
        foreach (CharacterDimension::forOwner($this->user->id)->get() as $dimensi) {
            $this->assertSame(5, $dimensi->positive_score, "Bobot positif {$dimensi->code} kosong");
            $this->assertSame(-5, $dimensi->negative_score, "Bobot negatif {$dimensi->code} kosong");
        }
    }

    /** Bobot yang disesuaikan wali kelas benar-benar dipakai. */
    public function test_pencapaian_memakai_bobot_dimensinya(): void
    {
        $this->dimensi->update(['positive_score' => 12]);

        $this->actingAs($this->student, 'student')
            ->post(route('student.portfolio.achievement'), [
                'character_dimension_id' => $this->dimensi->id,
                'record_date' => now()->toDateString(),
                'title' => 'Juara lomba kebersihan kelas',
            ])->assertRedirect();

        $this->assertSame(12, (int) CharacterRecord::first()->score);
        $this->assertSame(12, (int) $this->student->fresh()->discipline_points);
    }

    // -- Catatan mandiri tidak lagi bisa diberi poin sendiri ----------------

    public function test_poin_catatan_mandiri_diabaikan_dari_kiriman(): void
    {
        $this->dimensi->update(['positive_score' => 3]);

        $this->actingAs($this->student, 'student')
            ->post(route('student.portfolio.observation'), [
                'character_dimension_id' => $this->dimensi->id,
                'record_date' => now()->toDateString(),
                'type' => 'positive',
                'score' => 10, // dikirim siswa, harus diabaikan
                'title' => 'Membantu teman',
            ])->assertRedirect();

        $this->assertSame(3, (int) CharacterRecord::first()->score, 'Poin harus mengikuti bobot dimensi');
        $this->assertSame(3, (int) $this->student->fresh()->discipline_points);
    }

    public function test_catatan_negatif_memakai_bobot_negatif(): void
    {
        // Poin awal 50, bukan 0: mutator disciplinePoints() mengurung di 0-100,
        // jadi dari 0 pengurangan apa pun tidak akan terlihat.
        $this->student->update(['discipline_points' => 50]);
        $this->dimensi->update(['negative_score' => -7]);

        $this->actingAs($this->student, 'student')
            ->post(route('student.portfolio.observation'), [
                'character_dimension_id' => $this->dimensi->id,
                'record_date' => now()->toDateString(),
                'type' => 'negative',
                'score' => -1,
                'title' => 'Terlambat masuk kelas',
            ])->assertRedirect();

        $this->assertSame(-7, (int) CharacterRecord::first()->score);
        $this->assertSame(43, (int) $this->student->fresh()->discipline_points);
    }

    /** Pengamatan bersifat netral: dicatat, tidak menggeser poin. */
    public function test_pengamatan_tidak_berpoin(): void
    {
        $this->actingAs($this->student, 'student')
            ->post(route('student.portfolio.observation'), [
                'character_dimension_id' => $this->dimensi->id,
                'record_date' => now()->toDateString(),
                'type' => 'observation',
                'score' => 9,
                'title' => 'Lebih banyak diam hari ini',
            ])->assertRedirect();

        $this->assertSame(0, (int) CharacterRecord::first()->score);
        $this->assertSame(0, (int) $this->student->fresh()->discipline_points);
    }

    /** Dimensi sekolah lain tetap ditolak, bobotnya pun tidak terpakai. */
    public function test_dimensi_sekolah_lain_ditolak(): void
    {
        $lain = User::factory()->create();
        (new CharacterDimensionProvisioner)->provisionFor($lain->id);
        $dimensiLain = CharacterDimension::forOwner($lain->id)->first();

        $this->actingAs($this->student, 'student')
            ->post(route('student.portfolio.achievement'), [
                'character_dimension_id' => $dimensiLain->id,
                'record_date' => now()->toDateString(),
                'title' => 'Percobaan lintas sekolah',
            ])->assertSessionHasErrors('character_dimension_id');

        $this->assertSame(0, CharacterRecord::count());
    }

    // -- Poin selalu berada di rentang 0-100 -------------------------------

    /**
     * Catatan negatif pada siswa berpoin rendah dulu memakai decrement(), yang
     * menulis langsung ke kolom UNSIGNED. Dengan STRICT_TRANS_TABLES kuerinya
     * gagal: halaman galat 500, catatan sudah tersimpan, poin tidak berubah.
     */
    public function test_catatan_negatif_pada_poin_rendah_tidak_membuat_galat(): void
    {
        $this->student->update(['discipline_points' => 3]);
        $this->dimensi->update(['negative_score' => -10]);

        $this->actingAs($this->student, 'student')
            ->post(route('student.portfolio.observation'), [
                'character_dimension_id' => $this->dimensi->id,
                'record_date' => now()->toDateString(),
                'type' => 'negative',
                'title' => 'Tidak mengerjakan tugas',
            ])->assertRedirect();

        $this->assertSame(0, (int) $this->student->fresh()->discipline_points);
    }

    /** Pencapaian tidak boleh mendorong poin melewati batas atas 100. */
    public function test_pencapaian_tidak_melewati_seratus(): void
    {
        $this->student->update(['discipline_points' => 95]);
        $this->dimensi->update(['positive_score' => 20]);

        $this->actingAs($this->student, 'student')
            ->post(route('student.portfolio.achievement'), [
                'character_dimension_id' => $this->dimensi->id,
                'record_date' => now()->toDateString(),
                'title' => 'Juara lomba kebersihan',
            ])->assertRedirect();

        $this->assertSame(100, (int) $this->student->fresh()->discipline_points);
    }
}
