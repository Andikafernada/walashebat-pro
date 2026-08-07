<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Contracts\WhatsAppSessionManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Mematikan balasan otomatis WhatsApp milik guru yang masa langganannya habis.
 *
 * Gerbang di SendWhatsAppMessage hanya menahan pesan yang dikirim APLIKASI.
 * Balasan otomatis berbeda: pengaturannya dititipkan ke gateway, lalu gateway
 * yang menjawab pesan orang tua sendiri tanpa melewati aplikasi sama sekali.
 * Tanpa perintah ini, satu-satunya fitur otomasi yang justru paling terlihat
 * oleh orang tua akan terus berjalan selamanya setelah masa gratis berakhir.
 */
class MatikanOtomasiKedaluwarsa extends Command
{
    protected $signature = 'walikelas:matikan-otomasi-kedaluwarsa';

    protected $description = 'Matikan balasan otomatis WhatsApp untuk langganan yang sudah berakhir';

    public function handle(WhatsAppSessionManager $sessions): int
    {
        /*
         * Hanya menyapu yang baru saja berakhir (14 hari terakhir), bukan semua
         * pengguna kedaluwarsa. Setelah sekali dimatikan, autoreply tidak akan
         * menyala sendiri — menanyai gateway untuk setiap akun lama setiap hari
         * hanya membebani gateway tanpa mengubah apa pun. Jendelanya dibuat
         * lebar supaya beberapa hari kegagalan cron masih tersusul.
         */
        $kandidat = User::query()
            ->where('role', '!=', User::ROLE_ADMIN)
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', now())
            ->where('subscription_ends_at', '>', now()->subDays(14))
            ->where('wa_session_status', 'connected')
            ->get();

        $dimatikan = 0;

        foreach ($kandidat as $pengguna) {
            try {
                $status = $sessions->autoreplyStatus($pengguna);

                if (! ($status['enabled'] ?? false)) {
                    continue;
                }

                if ($sessions->autoreplySave($pengguna, false, [])) {
                    $dimatikan++;
                    Log::info('[langganan] Balasan otomatis dimatikan, masa habis', [
                        'user_id' => $pengguna->id,
                        'berakhir' => $pengguna->subscription_ends_at?->toDateString(),
                    ]);
                }
            } catch (\Throwable $e) {
                /*
                 * Gateway tidak sehat tidak boleh menghentikan sapuan untuk
                 * guru lain; yang gagal akan tersusul pada jalan berikutnya
                 * karena masih berada di dalam jendela 14 hari.
                 */
                Log::warning('[langganan] Gagal mematikan balasan otomatis', [
                    'user_id' => $pengguna->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Diperiksa {$kandidat->count()} akun, {$dimatikan} balasan otomatis dimatikan.");

        return self::SUCCESS;
    }
}
