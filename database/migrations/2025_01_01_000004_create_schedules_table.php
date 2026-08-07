<?php

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Classroom::class, 'class_id')->constrained('classes')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1=Senin ... 7=Minggu
            $table->string('subject');
            $table->string('teacher_name')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index(['user_id', 'class_id', 'day_of_week']);
            // Mendukung query scheduler: cari jadwal hari ini yang jam mulainya
            // jatuh di jendela kirim, lintas seluruh tenant.
            $table->index(['day_of_week', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
