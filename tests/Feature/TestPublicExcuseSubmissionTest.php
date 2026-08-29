<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentExcuse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TestPublicExcuseSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_submit_excuse_publicly(): void
    {
        Queue::fake();

        $teacher = User::factory()->create([
            'role' => 'teacher',
            'whatsapp_number' => '628123456789',
        ]);
        $classroom = Classroom::factory()->create([
            'user_id' => $teacher->id,
            'public_token' => 'test-token-excuse-12345',
        ]);
        $student = Student::factory()->create([
            'user_id' => $teacher->id,
            'class_id' => $classroom->id,
            'parent_phone' => '081234567890',
        ]);

        $response = $this->post(route('public.excuse.store', $classroom->public_token), [
            'student_id' => $student->id,
            'tanggal' => now()->toDateString(),
            'jenis' => 'izin',
            'keterangan' => 'Mengantar nenek ke rumah sakit',
            'parent_phone_last4' => '7890',
        ]);

        $response->assertSessionHas('success_public');
        $response->assertStatus(302);

        $this->assertDatabaseHas('student_excuses', [
            'user_id' => $teacher->id,
            'class_id' => $classroom->id,
            'student_id' => $student->id,
            'jenis' => 'izin',
            'keterangan' => 'Mengantar nenek ke rumah sakit',
        ]);

        Queue::assertPushed(SendWhatsAppMessage::class, function ($job) use ($teacher) {
            return $job->to === '628123456789' && $job->userId === $teacher->id;
        });
    }
}
