<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp_otp')->nullable()->after('whatsapp_number');
            $table->timestamp('whatsapp_otp_expires_at')->nullable()->after('whatsapp_otp');
            $table->boolean('whatsapp_verified')->default(false)->after('whatsapp_otp_expires_at');
            $table->string('whatsapp_otp_attempts')->default(0)->after('whatsapp_verified');

            // Add pending fields for registration
            $table->string('pending_name')->nullable()->after('whatsapp_verified');
            $table->string('pending_email')->nullable()->after('pending_name');
            $table->string('pending_password')->nullable()->after('pending_email');
            $table->string('pending_otp')->nullable()->after('pending_password');
            $table->timestamp('pending_otp_expires_at')->nullable()->after('pending_otp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_otp',
                'whatsapp_otp_expires_at',
                'whatsapp_verified',
                'whatsapp_otp_attempts',
                'pending_name',
                'pending_email',
                'pending_password',
                'pending_otp',
                'pending_otp_expires_at',
            ]);
        });
    }
};
