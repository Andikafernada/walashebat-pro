<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TestAutomatedParentRecapTest extends TestCase
{
    use RefreshDatabase;

    public function test_automatic_parent_recap_is_dispatched_on_attendance_submit(): void
    {
        Queue::fake();

        $teacher = User::factory()->create([
            'role' => 'teacher',
            'whatsapp_number' => '6283817203455',
            'subscription_ends_at' => now()->addMonth(),
        ]);

        $classroom = Classroom::factory()->create([
            'user_id' => $teacher->id,
            'name' => 'XII TKJ D',
            'parent_group_wa' => '120363321166050533@g.us',
        ]);

        $student1 = Student::factory()->create(['user_id' => $teacher->id, 'class_id' => $classroom->id, 'name' => 'Budi Santoso']);
        $student2 = Student::factory()->create(['user_id' => $teacher->id, 'class_id' => $classroom->id, 'name' => 'Rendy Moerdany']);

        $service = app(AttendanceSessionService::class);
        $result = $service->create($classroom);
        $session = $result['session'];

        Attendance::create([
            'attendance_session_id' => $session->id,
            'student_id' => $student1->id,
            'user_id' => $teacher->id,
            'status' => 'hadir',
        ]);

        Attendance::create([
            'attendance_session_id' => $session->id,
            'student_id' => $student2->id,
            'user_id' => $teacher->id,
            'status' => 'terlambat',
        ]);

        $dispatched = $service->dispatchParentRecap($session);
        $this->assertTrue($dispatched);

        Queue::assertPushed(SendWhatsAppMessage::class, function ($job) use ($classroom, $teacher) {
            return $job->to === '120363321166050533@g.us'
                && $job->userId === $teacher->id
                && str_contains($job->message, 'Rekap Absensi XII TKJ D')
                && str_contains($job->message, 'Rendy Moerdany — Terlambat');
        });
    }
}
