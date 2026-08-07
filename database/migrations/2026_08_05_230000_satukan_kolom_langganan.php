<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyatukan status langganan menjadi satu sumber kebenaran.
 *
 * Sebelumnya ada dua pasang kolom yang hidup berdampingan dan sudah saling
 * bertentangan di produksi (satu pengguna tercatat tier=pro_trial sekaligus
 * plan=free): `subscription_tier`/`subscription_ends_at` versus
 * `subscription_plan`/`trial_ends_at`. Sisi pembayaran hanya menulis pasangan
 * pertama, sementara pasangan kedua tidak pernah dibaca siapa pun.
 *
 * Yang dipertahankan adalah `subscription_ends_at` sebagai penentu tunggal
 * "otomasi WhatsApp aktif sampai kapan", karena persetujuan pembayaran memang
 * sudah menulis ke sana. `subscription_tier` tinggal menjadi label tampilan.
 *
 * Masa aktif sengaja disimpan sebagai TANGGAL, bukan status seperti 'expired'.
 * Status tersimpan butuh sesuatu yang rajin membaliknya dan akan basi diam-diam
 * bila cron mati; tanggal menjawab sendiri setiap kali dibaca.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * Backfill dilakukan di PHP, bukan lewat DATE_ADD/datetime() di SQL:
         * fungsi tanggal MySQL dan SQLite berbeda nama, dan test suite berjalan
         * di SQLite sementara produksi memakai MySQL. Migrasi yang hanya bisa
         * dijalankan di salah satunya tidak akan pernah teruji sebelum dipakai.
         */
        DB::table('users')
            ->whereNull('subscription_ends_at')
            ->orderBy('id')
            ->chunkById(500, function ($baris) {
                foreach ($baris as $pengguna) {
                    DB::table('users')->where('id', $pengguna->id)->update([
                        // Aturan produk: tiga bulan gratis sejak mendaftar.
                        'subscription_ends_at' => Carbon::parse($pengguna->created_at)->addMonths(3),
                    ]);
                }
            });

        // 'pro_trial' adalah penamaan lama; sekarang cukup 'trial' dan 'pro'.
        DB::table('users')->where('subscription_tier', 'pro_trial')->update(['subscription_tier' => 'trial']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['subscription_plan', 'trial_ends_at']);
        });

        /*
         * Pesan yang tidak dikirim karena masa otomasi habis bukanlah kegagalan
         * teknis. Tanpa nilai tersendiri, ia akan tercatat 'failed' dan wali
         * kelas mengira gateway rusak lalu menghubungi dukungan — padahal yang
         * perlu dilakukan hanyalah memperpanjang langganan.
         */
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->enum('delivery_status', ['pending', 'sent', 'failed', 'skipped'])
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_plan')->default('free');
            $table->timestamp('trial_ends_at')->nullable();
        });

        DB::table('users')->where('subscription_tier', 'trial')->update(['subscription_tier' => 'pro_trial']);

        // Sesi yang sempat berstatus 'skipped' dipetakan ke 'failed' agar muat
        // kembali ke enum lama.
        DB::table('attendance_sessions')->where('delivery_status', 'skipped')->update(['delivery_status' => 'failed']);

        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->enum('delivery_status', ['pending', 'sent', 'failed'])
                ->nullable()
                ->change();
        });
    }
};
