<?php

namespace Tests\Feature;

use App\Models\CharacterDimension;
use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Audit04RelasiTest extends TestCase
{
    use RefreshDatabase;

    private function bikinRefleksi(): array
    {
        $user = User::factory()->create();
        $class = Classroom::factory()->create(['user_id' => $user->id]);
        $student = Student::factory()->create(['user_id' => $user->id, 'class_id' => $class->id]);

        $dimId = DB::table('character_dimensions')->insertGetId([
            'user_id' => $user->id,
            'code' => 'gotong_royong',
            'name' => 'Gotong Royong',
            'icon' => 'users',
            'color' => '#6366f1',
            'sort_order' => 1,
            'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $refId = DB::table('character_reflections')->insertGetId([
            'user_id' => $user->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'character_dimension_id' => $dimId,
            'period' => 'weekly',
            'reflection_date' => now()->toDateString(),
            'self_rating' => 4,
            'status' => 'submitted',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$user, $student, $dimId, $refId];
    }

    public function test_relasi_dimension_pada_character_reflection(): void
    {
        [$user, $student, $dimId, $refId] = $this->bikinRefleksi();
        $this->actingAs($user);

        $ref = CharacterReflection::find($refId);

        fwrite(STDERR, "\n[AUDIT04] character_reflections.character_dimension_id di DB = "
            . var_export(DB::table('character_reflections')->where('id', $refId)->value('character_dimension_id'), true) . "\n");

        $rel = $ref->dimension();
        fwrite(STDERR, "[AUDIT04] CharacterReflection::dimension() foreignKeyName = "
            . $rel->getForeignKeyName() . " (kolom yang ADA: character_dimension_id)\n");
        fwrite(STDERR, "[AUDIT04] \$ref->dimension = " . var_export($ref->dimension?->name, true) . "\n");

        // Eager loading yang dipakai controller
        $eager = CharacterReflection::with('dimension')->find($refId);
        fwrite(STDERR, "[AUDIT04] with('dimension') -> " . var_export($eager->dimension?->name, true) . "\n");

        // whereHas seperti yang biasa dipakai laporan
        try {
            $n = CharacterReflection::whereHas('dimension')->count();
            fwrite(STDERR, "[AUDIT04] whereHas('dimension')->count() = $n\n");
        } catch (\Throwable $e) {
            fwrite(STDERR, "[AUDIT04] whereHas('dimension') THROW: " . get_class($e) . ' :: '
                . substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 250) . "\n");
        }

        $this->assertSame('Gotong Royong', $ref->dimension?->name,
            'CharacterReflection::dimension() TIDAK mengembalikan dimensi yang benar');
    }

    public function test_relasi_notifikasi_bawaan_notifiable_pada_user(): void
    {
        $user = User::factory()->create();

        foreach (['notifications', 'readNotifications'] as $rel) {
            try {
                $n = $user->{$rel}()->count();
                fwrite(STDERR, "[AUDIT04] \$user->$rel()->count() = $n\n");
            } catch (\Throwable $e) {
                fwrite(STDERR, "[AUDIT04] \$user->$rel() THROW: " . get_class($e) . ' :: '
                    . substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 250) . "\n");
            }
        }

        $this->assertSame(0, $user->notifications()->count(),
            'Relasi Notifiable::notifications() rusak terhadap tabel notifications kustom');
    }
}
