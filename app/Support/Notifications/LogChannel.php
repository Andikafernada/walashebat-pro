<?php

namespace App\Support\Notifications;

use App\Support\Contracts\NotificationChannel;
use Illuminate\Support\Facades\Log;

/** Channel dev: mencatat pesan ke log alih-alih mengirim sungguhan. */
class LogChannel implements NotificationChannel
{
    public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
    {
        Log::info('[WA:LOG] Pesan tidak dikirim (driver=log)', [
            'from' => $from,
            'to' => $to,
            'message' => $message,
            'meta' => $meta,
        ]);

        return true;
    }

    /**
     * Log channel selalu sehat (tidak ada gateway yang bisa down).
     */
    public function isHealthy(): bool
    {
        return true;
    }

    /**
     * Log channel tidak punya circuit breaker.
     */
    public function getCircuitStatus(): array
    {
        return [
            'name' => 'log-channel',
            'state' => 'closed',
            'failures' => 0,
            'threshold' => 0,
            'time_until_retry' => 0,
        ];
    }
}
