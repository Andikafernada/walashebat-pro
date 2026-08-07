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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            // user_id didenormalisasi untuk tenant scope yang seragam & cepat.
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Classroom::class, 'class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('nis')->nullable();      // Nomor Induk Siswa
            $table->string('nisn')->nullable();
            $table->string('name');
            $table->enum('gender', ['L', 'P'])->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('parent_phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->unsignedInteger('discipline_points')->default(100); // poin awal
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // hapus siswa bisa dibatalkan

            $table->index(['user_id', 'class_id']);
            /*
             * Sengaja BUKAN unique(class_id, nis): dengan soft delete, siswa
             * yang dihapus masih menempati NIS-nya sehingga NIS tidak bisa
             * dipakai ulang. Keunikan ditegakkan di StudentRequest, dibatasi
             * pada baris yang belum dihapus.
             */
            $table->index(['class_id', 'nis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
