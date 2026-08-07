<?php

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Classroom::class, 'class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignIdFor(Student::class)->nullable()->constrained()->nullOnDelete();
            // Salah satu dari config('walikelas.student_roles'): ketua, seksi_absensi, dst.
            $table->string('role');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_structures');
    }
};
