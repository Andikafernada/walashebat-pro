<?php

namespace Tests\Feature;

use App\Models\CharacterDimension;
use App\Models\CharacterRecord;
use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\CharacterDimensionProvisioner;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rute portofolio karakter berada di dalam grup ->scopeBindings(), yang
 * mencarikan {record} dan {reflection} lewat relasi bernama records() dan
 * reflections() pada induknya. Relasi itu tidak pernah ada, sehingga setiap
 * pembukaan detail catatan, penyuntingan, penghapusan, konfirmasi, dan umpan
 * balik berakhir "Call to undefined method" — galat 500. Tidak ketahuan selama
 * daftar dimensi karakter kosong, karena tidak ada catatan yang bisa dibuat.
 */
class RutePortofolioKarakterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    private Student $student;

    private CharacterDimension $dimensi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->user = User::factory()->create();
        (new CharacterDimensionProvisioner)->provisionFor($this->user->id);

        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);
        $this->student = Student::factory()->create([
            'user_id' => $this->user->id, 'class_id' => $this->class->id,
        ]);
        $this->dimensi = CharacterDimension::forOwner($this->user->id)->active()->first();
    }

    private function catatan(array $ubah = []): CharacterRecord
    {
        return CharacterRecord::create($ubah + [
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'character_dimension_id' => $this->dimensi->id,
            'type' => 'positive',
            'score' => 5,
            'title' => 'Membantu piket kelas',
            'record_date' => now()->toDateString(),
        ]);
    }

    public function test_detail_catatan_bisa_dibuka(): void
    {
        $catatan = $this->catatan();

        $this->actingAs($this->user)
            ->get(route('classes.character-portfolio.record.show', [$this->class, $this->student, $catatan]))
            ->assertOk()
            ->assertSee('Membantu piket kelas');
    }

    public function test_catatan_bisa_disunting(): void
    {
        $catatan = $this->catatan();

        $this->actingAs($this->user)
            ->patch(route('classes.character-portfolio.record.update', [$this->class, $catatan]), [
                'character_dimension_id' => $this->dimensi->id,
                'type' => 'positive',
                'score' => 8,
                'title' => 'Membantu piket kelas sepekan penuh',
                'record_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame('Membantu piket kelas sepekan penuh', $catatan->fresh()->title);
    }

    public function test_catatan_bisa_dikonfirmasi(): void
    {
        $catatan = $this->catatan();

        $this->actingAs($this->user)
            ->post(route('classes.character-portfolio.record.acknowledge', [$this->class, $catatan]))
            ->assertRedirect();

        $this->assertTrue((bool) $catatan->fresh()->is_acknowledged);
    }

    public function test_catatan_bisa_dihapus(): void
    {
        $catatan = $this->catatan();

        $this->actingAs($this->user)
            ->delete(route('classes.character-portfolio.record.destroy', [$this->class, $catatan]))
            ->assertRedirect();

        $this->assertNull(CharacterRecord::find($catatan->id));
    }

    public function test_umpan_balik_refleksi_bisa_disimpan(): void
    {
        $refleksi = (new CharacterReflection)->forceFill([
            'user_id' => $this->user->id,
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'character_dimension_id' => $this->dimensi->id,
            'period' => CharacterReflection::PERIOD_MONTHLY,
            'reflection_date' => now()->toDateString(),
            'what_went_well' => 'Rajin piket.',
            'what_to_improve' => 'Kurangi mengobrol.',
            'action_plan' => 'Duduk di depan.',
            'status' => 'submitted',
        ]);
        $refleksi->save();

        $this->actingAs($this->user)
            ->post(route('classes.character-portfolio.reflection.feedback', [$this->class, $refleksi]), [
                'teacher_feedback' => 'Pertahankan, Nak. Bapak bangga.',
                'teacher_rating' => 5,
            ])
            ->assertRedirect();

        $this->assertSame('Pertahankan, Nak. Bapak bangga.', $refleksi->fresh()->teacher_feedback);
    }

    /** scopeBindings tetap harus menolak catatan milik kelas lain. */
    public function test_catatan_kelas_lain_tidak_bisa_dibuka(): void
    {
        $kelasLain = Classroom::factory()->create(['user_id' => $this->user->id]);
        $catatan = $this->catatan();

        $this->actingAs($this->user)
            ->delete(route('classes.character-portfolio.record.destroy', [$kelasLain, $catatan]))
            ->assertNotFound();

        $this->assertNotNull(CharacterRecord::find($catatan->id));
    }
}
