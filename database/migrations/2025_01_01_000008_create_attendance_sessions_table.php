<?php

use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inti fitur anti-curang: token dinamis + PIN harian + waktu kedaluwarsa.
     * Magic link = /a/{token}, lalu petugas memasukkan PIN untuk membuka roster.
     */
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Classroom::class, 'class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignIdFor(Schedule::class)->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();       // cth: "Absen Pagi - Senin"
            $table->date('session_date');
            $table->string('token', 80)->unique();     // dipakai di URL magic link
            $table->string('pin_hash');                // hash PIN harian (tak disimpan plaintext)
            $table->timestamp('expires_at');
            $table->enum('status', ['open', 'submitted', 'expired', 'cancelled'])->default('open');
            $table->timestamp('submitted_at')->nullable();
            $table->string('submitted_ip', 45)->nullable();

            // Jejak pengiriman WhatsApp — supaya kegagalan tidak senyap.
            // null = sesi manual yang memang tidak dikirim.
            $table->enum('delivery_status', ['pending', 'sent', 'failed'])->nullable();
            $table->string('delivery_target', 20)->nullable();
            $table->string('delivery_error')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            /*
             * SATU sesi absensi per kelas per hari — ditegakkan di level database
             * sehingga scheduler yang berjalan tiap menit tidak mungkin membuat
             * duplikat, bahkan bila dua proses berjalan bersamaan.
             *
             * schedule_id hanya mencatat jadwal mana yang jadi penanda waktu
             * (jam pelajaran pertama hari itu), bukan kunci keunikan.
             */
            $table->unique(['class_id', 'session_date']);
            $table->index(['user_id', 'class_id', 'session_date']);
            $table->index(['token', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
