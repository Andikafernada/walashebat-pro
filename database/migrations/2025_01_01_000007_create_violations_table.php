<?php

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Models\ViolationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Classroom::class, 'class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignIdFor(Student::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ViolationType::class)->nullable()->constrained()->nullOnDelete();
            $table->smallInteger('points');    // snapshot poin saat kejadian
            $table->text('note')->nullable();
            $table->date('occurred_on');
            $table->timestamps();

            $table->index(['user_id', 'class_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violations');
    }
};
