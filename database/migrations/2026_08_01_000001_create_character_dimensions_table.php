<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_dimensions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // e.g., 'imtak', 'kebinekaan', 'gotong_royong', 'mandiri', 'nalar_kritis', 'kreatif'
            $table->string('name'); // Indonesian name
            $table->string('name_en')->nullable(); // English name
            $table->text('description')->nullable();
            $table->text('indicators')->nullable(); // JSON array of indicators
            $table->string('icon', 100)->nullable(); // Icon class
            $table->string('color', 20)->default('#6366f1'); // Default color
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_dimensions');
    }
};
