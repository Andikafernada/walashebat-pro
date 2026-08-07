<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_hapus_kelas_mengarsipkan_siswanya_dan_bisa_dipulihkan(): void
    {
        $user = User::factory()->create();
        $class = Classroom::factory()->create(['user_id' => $user->id]);
        Student::factory()->count(3)->create([
            'user_id' => $user->id,
            'class_id' => $class->id,
        ]);

        $this->actingAs($user);

        $this->delete(route('classes.destroy', $class))->assertRedirect();

        // Kelas dan siswanya hilang dari query normal.
        $this->assertSame(0, Classroom::count());
        $this->assertSame(0, Student::count());

        // Tapi masih ada di arsip.
        $this->assertSame(1, Classroom::onlyTrashed()->count());
        $this->assertSame(3, Student::onlyTrashed()->count());

        $this->patch(route('classes.restore', $class->id))->assertRedirect();

        $this->assertSame(1, Classroom::count());
        $this->assertSame(3, Student::count());
    }

    public function test_arsip_hanya_menampilkan_kelas_sendiri(): void
    {
        $budi = User::factory()->create();
        $kelasBudi = Classroom::factory()->create(['user_id' => $budi->id]);
        $kelasBudi->delete();

        $this->actingAs(User::factory()->create());

        // Wali kelas lain tidak boleh memulihkan kelas milik orang.
        $this->patch(route('classes.restore', $kelasBudi->id))->assertNotFound();
    }
}
