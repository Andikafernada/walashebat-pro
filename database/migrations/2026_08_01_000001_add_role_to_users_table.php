<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Role field: 'teacher' (default) or 'admin' (kepala sekolah)
            $table->string('role', 20)->default('teacher')->after('is_active');
            $table->index('role');

            // School details for admin
            $table->string('school_address')->nullable()->change();
            $table->string('school_city')->nullable()->change();
            $table->string('school_npsn', 10)->nullable()->change();
            $table->string('principal_name')->nullable()->change();
            $table->string('principal_nip', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
