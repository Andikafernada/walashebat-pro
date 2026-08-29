<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelemetryAlertService
{
    /**
     * Laporkan error kritis (500) secara instan ke developer webhook (Telegram/WhatsApp).
     */
    public static function reportCriticalException(\Throwable $exception, $request = null): void
    {
        // 1. Abaikan error 404, validasi, auth biasa
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException ||
            $exception instanceof \Illuminate\Validation\ValidationException ||
            $exception instanceof \Illuminate\Auth\AuthenticationException) {
            return;
        }

        // 2. Cegah Alert Spam: throttled 5 menit per exception signature
        $errorHash = md5($exception->getFile() . ':' . $exception->getLine() . ':' . $exception->getMessage());
        $throttleKey = "telemetry_error_throttle_{$errorHash}";

        if (Cache::has($throttleKey)) {
            return;
        }
        Cache::put($throttleKey, true, 300); // 5 menit

        $user = auth()->user();
        $url = $request ? $request->fullUrl() : 'CLI / Background Job';
        $method = $request ? $request->method() : 'CLI';
        $ip = $request ? $request->ip() : '127.0.0.1';

        $payload = [
            'app' => 'WaliKelas Pro',
            'env' => config('app.env'),
            'time' => now()->toDateTimeString(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile() . ':' . $exception->getLine(),
            'url' => "[{$method}] {$url}",
            'ip' => $ip,
            'user' => $user ? "ID {$user->id} ({$user->name})" : 'Tamu / Tanpa Login',
        ];

        Log::critical('[TELEMETRY 500] Critical Exception Captured', $payload);

        // 3. Kirim ke Telegram Developer Webhook jika TELEGRAM_ALERT_BOT_TOKEN / CHAT_ID ada di .env
        $botToken = env('TELEGRAM_ALERT_BOT_TOKEN');
        $chatId = env('TELEGRAM_ALERT_CHAT_ID');

        if ($botToken && $chatId) {
            try {
                $text = "🚨 *[ERROR 500 ALERT]* WaliKelas Pro\n"
                    . "━━━━━━━━━━━━━━━━━━━━\n"
                    . "⚠️ *Error:* `{$payload['message']}`\n"
                    . "📍 *Lokasi:* `{$payload['file']}`\n"
                    . "🌐 *URL:* `{$payload['url']}`\n"
                    . "👤 *User:* `{$payload['user']}`\n"
                    . "🕒 *Waktu:* `{$payload['time']}`\n";

                Http::timeout(3)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'Markdown',
                ]);
            } catch (\Throwable $e) {
                // Jangan lempar error di dalam exception reporter
            }
        }
    }
}
