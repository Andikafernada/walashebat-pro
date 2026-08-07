<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom langganan pada tabel `users`.
 *
 * Kolom-kolom ini sudah ada di basis data produksi tetapi TIDAK PERNAH ada
 * migrasinya — agaknya dulu ditambahkan langsung lewat SQL. Akibatnya basis
 * data mana pun yang dibangun ulang dari migrasi (server baru, lingkungan uji,
 * pemulihan dari nol) tidak memilikinya sama sekali, dan persetujuan pembayaran
 * langsung gagal: "no such column: subscription_tier" — galat 500 tepat setelah
 * admin menekan Setujui, dengan pembayaran yang sudah diterima.
 *
 * Ditulis idempoten: pada produksi yang kolomnya sudah ada, migrasi ini tidak
 * mengubah apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'subscription_tier')) {
                $table->string('subscription_tier')->default('pro_trial');
            }

            if (! Schema::hasColumn('users', 'subscription_plan')) {
                $table->string('subscription_plan')->default('free');
            }

            if (! Schema::hasColumn('users', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(
                ['subscription_tier', 'subscription_plan', 'subscription_ends_at', 'trial_ends_at'],
                fn (string $kolom) => Schema::hasColumn('users', $kolom)
            )));
        });
    }
};
