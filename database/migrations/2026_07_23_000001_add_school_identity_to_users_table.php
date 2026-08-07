<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('school_address')->nullable()->after('school_name');
            $table->string('school_npsn', 20)->nullable()->after('school_address');
            $table->string('principal_name')->nullable()->after('school_npsn');
            $table->string('principal_nip', 30)->nullable()->after('principal_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['school_address', 'school_npsn', 'principal_name', 'principal_nip']);
        });
    }
};
