<?php

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Denah tempat duduk: grid baris x kolom, tiap sel opsional berisi siswa. */
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Classroom::class, 'class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignIdFor(Student::class)->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('row_index');
            $table->unsignedSmallInteger('col_index');
            $table->string('label')->nullable(); // cth "Meja Guru", atau catatan
            $table->timestamps();

            $table->unique(['class_id', 'row_index', 'col_index']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
