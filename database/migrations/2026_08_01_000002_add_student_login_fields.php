<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Password fields for student login
            $table->string('password')->nullable()->after('discipline_points');
            $table->boolean('must_change_password')->default(true)->after('password');

            // Parent phone verification
            $table->timestamp('parent_phone_verified_at')->nullable()->after('must_change_password');
            $table->string('phone_verified_at')->nullable()->after('parent_phone_verified_at');

            // Student activation
            $table->timestamp('activated_at')->nullable()->after('phone_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'password',
                'must_change_password',
                'parent_phone_verified_at',
                'phone_verified_at',
                'activated_at',
            ]);
        });
    }
};
