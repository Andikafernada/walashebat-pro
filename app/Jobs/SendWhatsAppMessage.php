<?php

namespace App\Jobs;

use App\Models\AttendanceSession;
use App\Models\User;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Mengirim pesan WhatsApp di luar siklus request dengan Rate Limiting & Auto-Retry.
 *
 * Dilengkapi:
 * - Exponential backoff: [15, 60, 300] detik
 * - Sender Rate Throttling: jeda minimum 2.5 detik antar pesan per nomor gateway/sender
 * - Anti-ban & Gateway overload protection saat broadcast massal (misal: 36 siswa jam 07:00).
 */
class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [15, 60, 300];

    public int $timeout = 60;

    /**
     * @param  int  $userId  Wali kelas pemilik pesan ini.
     */
    public function __construct(
        public readonly string $to,
        public readonly string $message,
        public readonly int $userId,
        public readonly array $meta = [],
        public readonly ?int $attendanceSessionId = null,
        public readonly ?string $from = null,
    ) {}

    public function handle(NotificationChannel $notifier): void
    {
        if (! $this->otomasiDiizinkan()) {
            return;
        }

        // 1. RATE LIMITING & GATEWAY PROTECTION
        // Pastikan ada jeda minimal 2-3 detik antar pesan dari sender yang sama
        $senderKey = 'wa_throttle_' . ($this->from ?: 'default');
        $lastSent = Cache::get($senderKey);

        if ($lastSent) {
            $elapsedMs = (int) ((microtime(true) - (float) $lastSent) * 1000);
            $minIntervalMs = 2500; // 2.5 detik per pesan
            if ($elapsedMs < $minIntervalMs) {
                usleep(($minIntervalMs - $elapsedMs) * 1000);
            }
        }

        // Catat timestamp pengiriman terakhir
        Cache::put($senderKey, microtime(true), 30);

        // 2. KIRIM PESAN KE GATEWAY
        if (! $notifier->send($this->to, $this->message, $this->meta, $this->from)) {
            throw new \RuntimeException('Gateway WhatsApp menolak pesan atau sedang sibuk.');
        }

        $this->markSession([
            'delivery_status' => 'sent',
            'delivery_error' => null,
            'delivered_at' => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[WA] Pesan gagal setelah 3x percobaan ulang', [
            'from' => $this->from,
            'to' => $this->to,
            'meta' => $this->meta,
            'error' => $e->getMessage(),
        ]);

        $this->markSession([
            'delivery_status' => 'failed',
            'delivery_error' => mb_substr($e->getMessage(), 0, 200),
        ]);
    }

    /**
     * Gerbang masa aktif otomasi WhatsApp.
     */
    private function otomasiDiizinkan(): bool
    {
        $pemilik = User::find($this->userId);

        if ($pemilik === null) {
            $this->markSession([
                'delivery_status' => 'skipped',
                'delivery_error' => 'Pemilik pesan sudah tidak ada.',
            ]);

            return false;
        }

        if ($pemilik->otomasiWhatsAppAktif()) {
            return true;
        }

        Log::info('[WA] Otomasi dilewati, masa langganan habis', [
            'user_id' => $pemilik->id,
            'berakhir' => $pemilik->subscription_ends_at?->toDateString(),
            'type' => $this->meta['type'] ?? null,
        ]);

        $this->markSession([
            'delivery_status' => 'skipped',
            'delivery_error' => 'Masa otomasi WhatsApp sudah berakhir. Perpanjang langganan untuk mengaktifkan kembali.',
        ]);

        return false;
    }

    private function markSession(array $attributes): void
    {
        if (! $this->attendanceSessionId) {
            return;
        }

        AttendanceSession::withoutTenant()
            ->whereKey($this->attendanceSessionId)
            ->update($attributes);
    }
}
