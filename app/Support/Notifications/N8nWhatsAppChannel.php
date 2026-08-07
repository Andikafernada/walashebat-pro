<?php

namespace App\Support\Notifications;

use App\Support\CircuitBreaker;
use App\Support\Contracts\NotificationChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mengirim pesan WhatsApp dengan mem-POST ke webhook n8n.
 *
 * Payload dikonsumsi workflow n8n "WhatsApp Gateway" yang meneruskan ke sesi
 * pengirim. Field "from" menentukan sesi/nomor mana yang dipakai mengirim —
 * untuk absensi diisi nomor wali kelas agar siswa menerima pesan dari nomor
 * gurunya sendiri. Header X-Webhook-Secret dipakai n8n untuk memvalidasi asal
 * request.
 *
 * Circuit breaker mencegah cascade failure: jika gateway mati, sistem fast-fail
 * tanpa menunggu timeout 15 detik, dan berhenti mencoba setelah 3 kegagalan
 * berturut-turut.
 */
class N8nWhatsAppChannel implements NotificationChannel
{
    private CircuitBreaker $circuit;

    private int $timeout;
    private int $fastTimeout;

    public function __construct(
        private readonly string $webhookUrl,
        private readonly ?string $secret = null,
        int $timeout = 8,
        int $fastTimeout = 3,
    ) {
        $this->circuit = new CircuitBreaker('whatsapp-gateway', failureThreshold: 3, resetTimeout: 60);
        $this->timeout = $timeout;
        $this->fastTimeout = $fastTimeout;
    }

    /**
     * Cek apakah gateway tersedia (circuit tidak open).
     */
    public function isHealthy(): bool
    {
        return $this->circuit->isAvailable();
    }

    /**
     * Dapatkan status circuit breaker untuk monitoring.
     */
    public function getCircuitStatus(): array
    {
        return $this->circuit->getStatus();
    }

    public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
    {
        // Fast fail jika circuit sudah open
        if (! $this->circuit->isAvailable()) {
            Log::warning('[WA:n8n] Circuit breaker OPEN - skip pengiriman', [
                'to' => $to,
                'circuit_status' => $this->circuit->getStatus(),
            ]);
            return false;
        }

        try {
            // Pakai timeout lebih pendek untuk cek kesehatan
            $response = Http::timeout($this->fastTimeout)
                ->connectTimeout(2) // Tidak perlu tunggu handshake lama
                ->withHeaders(array_filter([
                    'X-Webhook-Secret' => $this->secret,
                ]))
                ->post($this->webhookUrl, array_filter([
                    'from' => $from,
                    'to' => $to,
                    'message' => $message,
                    'meta' => $meta,
                ], fn ($v) => $v !== null));

            if ($response->failed()) {
                $this->circuit->recordFailure();
                Log::warning('[WA:n8n] Webhook gagal', [
                    'status' => $response->status(),
                    'from' => $from,
                    'to' => $to,
                    'circuit_failures' => $this->circuit->getStatus()['failures'],
                ]);

                return false;
            }

            // Success - reset atau kurangi failure count
            $this->circuit->recordSuccess();
            return true;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Connection refused, timeout, dll - fast fail
            $this->circuit->recordFailure();
            Log::warning('[WA:n8n] Koneksi gagal (fast fail)', [
                'error' => $e->getMessage(),
                'to' => $to,
                'circuit_failures' => $this->circuit->getStatus()['failures'],
            ]);

            return false;

        } catch (\Throwable $e) {
            $this->circuit->recordFailure();
            Log::error('[WA:n8n] Exception saat kirim', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);

            return false;
        }
    }
}
