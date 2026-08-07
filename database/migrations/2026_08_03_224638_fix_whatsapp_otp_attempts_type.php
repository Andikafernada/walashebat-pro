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
            // Change whatsapp_otp_attempts from varchar to unsigned integer
            $table->unsignedInteger('whatsapp_otp_attempts')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert back to varchar
            $table->string('whatsapp_otp_attempts', 191)->default('0')->change();
        });
    }
};
