<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_badges', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->unsignedBigInteger('character_dimension_id');

            $table->string('name');
            $table->string('icon', 50);
            $table->string('color', 20)->default('#6366f1');
            $table->string('level', 20)->default('bronze');

            $table->text('description');
            $table->integer('min_score')->default(0);
            $table->integer('min_occurrences')->default(1);
            $table->string('criteria_type', 50)->default('score');
            $table->json('requirements')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('character_dimension_id')->references('id')->on('character_dimensions')->cascadeOnDelete();
        });

        Schema::create('student_badges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('character_badge_id');
            $table->integer('current_progress')->default(0);
            $table->boolean('is_earned')->default(false);
            $table->timestamp('earned_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('character_badge_id')->references('id')->on('character_badges')->cascadeOnDelete();
            $table->foreign('granted_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['student_id', 'character_badge_id']);
            $table->index(['student_id', 'is_earned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_badges');
        Schema::dropIfExists('character_badges');
    }
};
