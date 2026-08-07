<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violation_types', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('name');            // cth: "Terlambat", "Tidak pakai seragam"
            $table->string('category')->nullable(); // ringan/sedang/berat
            $table->smallInteger('points');    // poin dikurangi (negatif) atau ditambah
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violation_types');
    }
};
