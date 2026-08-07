<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('character_dimension_id');

            // Record details
            $table->enum('type', ['positive', 'negative', 'observation', 'achievement'])->default('observation');
            $table->integer('score')->default(0); // -10 to +10
            $table->string('title'); // Short title
            $table->text('description')->nullable();
            $table->text('evidence')->nullable(); // Evidence or context

            // Context
            $table->string('context', 100)->nullable(); // e.g., 'in_class', 'extracurricular', 'break_time', 'exam'
            $table->date('record_date');
            $table->unsignedBigInteger('recorded_by')->nullable();

            // Status
            $table->boolean('is_acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();

            // Notification
            $table->boolean('notify_parent')->default(false);
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
            $table->foreign('character_dimension_id')->references('id')->on('character_dimensions')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('acknowledged_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index(['student_id', 'record_date']);
            $table->index(['character_dimension_id', 'record_date']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_records');
    }
};
