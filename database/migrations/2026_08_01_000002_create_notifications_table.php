<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // App\Notifications\AttendanceReminder, etc.
            $table->string('title');
            $table->text('body');
            $table->string('icon')->default('bell');
            $table->string('color')->default('indigo'); // indigo, emerald, rose, amber
            $table->string('action_url')->nullable(); // URL to redirect when clicked
            $table->json('data')->nullable(); // Additional payload
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Push notification settings
            $table->boolean('push_enabled')->default(true);
            $table->boolean('push_attendance_reminder')->default(true);
            $table->boolean('push_new_violation')->default(true);
            $table->boolean('push_low_cashbook')->default(true);
            $table->boolean('push_daily_summary')->default(false);
            // Web push subscription
            $table->text('push_subscription')->nullable(); // JSON with endpoint, keys, etc.
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
        Schema::dropIfExists('notifications');
    }
};
