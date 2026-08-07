<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_reflections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('character_dimension_id')->nullable();

            $table->string('period', 50);
            $table->date('reflection_date');

            $table->integer('self_rating')->nullable();
            $table->text('what_went_well')->nullable();
            $table->text('what_to_improve')->nullable();
            $table->text('action_plan')->nullable();

            $table->text('teacher_feedback')->nullable();
            $table->integer('teacher_rating')->nullable();
            $table->unsignedBigInteger('feedback_by')->nullable();
            $table->timestamp('feedback_at')->nullable();

            $table->enum('status', ['draft', 'submitted', 'reviewed'])->default('draft');
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
            $table->foreign('character_dimension_id')->references('id')->on('character_dimensions')->nullOnDelete();
            $table->foreign('feedback_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['student_id', 'period', 'reflection_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_reflections');
    }
};
