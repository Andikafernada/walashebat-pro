<?php

use App\Models\AttendanceSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AttendanceSession::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Student::class)->constrained()->cascadeOnDelete();
            // Default 'alfa': siswa dianggap tidak hadir sampai ditandai hadir.
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alfa'])->default('alfa');
            $table->string('note')->nullable();
            $table->timestamps();

            // Satu siswa satu record per sesi.
            $table->unique(['attendance_session_id', 'student_id']);
            $table->index(['user_id', 'student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
