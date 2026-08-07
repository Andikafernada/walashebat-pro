<?php

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DICADANGKAN untuk pengembangan berikutnya — tabel ini dibuat tetapi
     * BELUM dipakai kode mana pun.
     *
     * Model akses saat ini satu peran: wali kelas (classes.user_id) melihat
     * seluruh data kelasnya, dan tidak ada peran guru mapel / co-wali.
     * Tabel dipertahankan agar penambahan peran nanti tidak perlu mengubah
     * skema yang sudah live.
     */
    public function up(): void
    {
        Schema::create('class_user', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Classroom::class, 'class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('role_in_class')->default('guru_mapel');
            $table->timestamps();

            $table->unique(['class_id', 'user_id', 'role_in_class'], 'class_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_user');
    }
};
