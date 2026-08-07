<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Uji asap: setiap halaman GET wali kelas harus benar-benar terbuka.
 *
 * Layout dan navigasi kelas dipakai bersama oleh hampir semua halaman, jadi
 * satu kesalahan kecil di sana menjatuhkan belasan halaman sekaligus tanpa ada
 * pengujian lain yang menyadarinya.
 */
class HalamanTerbukaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_semua_halaman_wali_kelas_terbuka(): void
    {
        $user = User::factory()->create();
        $class = Classroom::factory()->create(['user_id' => $user->id]);
        Student::factory()->count(2)->create([
            'user_id' => $user->id,
            'class_id' => $class->id,
        ]);

        $this->actingAs($user);

        $global = [
            'dashboard', 'profile.edit', 'whatsapp.index',
            'holidays.index', 'violation-types.index',
            'classes.index', 'classes.create', 'classes.trashed',
        ];

        foreach ($global as $name) {
            $this->get(route($name))->assertOk("Halaman {$name} gagal dibuka");
        }

        $perKelas = [
            'classes.show', 'classes.edit',
            'classes.students.index', 'classes.students.create',
            'classes.students.trashed', 'classes.students.import.form',
            'classes.schedules.index', 'classes.organization.index',
            'classes.violations.index', 'classes.cashbook.index',
            'classes.seating.index', 'classes.attendance.index',
            'classes.reports.attendance', 'classes.reports.full',
        ];

        foreach ($perKelas as $name) {
            $this->get(route($name, $class))->assertOk("Halaman {$name} gagal dibuka");
        }
    }

    public function test_halaman_tamu_terbuka(): void
    {
        foreach (['landing', 'login', 'register', 'password.request'] as $name) {
            $this->get(route($name))->assertOk("Halaman {$name} gagal dibuka");
        }
    }
}
