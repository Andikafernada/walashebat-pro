<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->date('session_date');
            $table->unsignedInteger('meeting_number')->default(1);
            $table->string('subject');
            $table->string('topic');
            $table->text('learning_objective')->nullable();
            $table->text('activity')->nullable();
            $table->text('reflection')->nullable();
            $table->string('p5_dimension')->nullable();
            $table->string('attendance_summary')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'class_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_journals');
    }
};
