<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Reset circuit breaker WhatsApp Gateway.
 *
 * Digunakan jika gateway sudah pulih tapi circuit breaker masih terbuka.
 *
 * Usage:
 *   php artisan walikelas:circuit-reset
 *   php artisan walikelas:circuit-reset --show
 */
class CircuitBreakerReset extends Command
{
    protected $signature = 'walikelas:circuit-reset
                            {--show : Tampilkan status saja tanpa reset}';

    protected $description = 'Reset circuit breaker WhatsApp Gateway';

    public function handle(): int
    {
        $gatewayStatus = $this->getCircuitStatus('whatsapp-gateway');
        $sessionStatus = $this->getCircuitStatus('whatsapp-session');

        $this->info('=== Circuit Breaker Status ===');
        $this->table(
            ['Circuit', 'State', 'Failures', 'Time Until Retry'],
            [
                ['whatsapp-gateway', $gatewayStatus['state'], $gatewayStatus['failures'], $gatewayStatus['time_until_retry'] . 's'],
                ['whatsapp-session', $sessionStatus['state'], $sessionStatus['failures'], $sessionStatus['time_until_retry'] . 's'],
            ]
        );

        if ($this->option('show')) {
            return Command::SUCCESS;
        }

        if ($gatewayStatus['state'] === 'closed' && $sessionStatus['state'] === 'closed') {
            $this->info('✓ Semua circuit breaker sudah tertutup (closed)');
            return Command::SUCCESS;
        }

        if (! $this->confirm('Reset semua circuit breaker?')) {
            $this->warn('Dibatalkan.');
            return Command::FAILURE;
        }

        $this->resetCircuit('whatsapp-gateway');
        $this->resetCircuit('whatsapp-session');

        $this->info('✓ Circuit breaker berhasil di-reset');

        return Command::SUCCESS;
    }

    private function getCircuitStatus(string $name): array
    {
        return [
            'state' => Cache::get("circuit_breaker:{$name}:state", 'closed'),
            'failures' => Cache::get("circuit_breaker:{$name}:failures", 0),
            'time_until_retry' => Cache::has("circuit_breaker:{$name}:opened_at")
                ? max(0, 60 - (time() - (int) Cache::get("circuit_breaker:{$name}:opened_at")))
                : 0,
        ];
    }

    private function resetCircuit(string $name): void
    {
        Cache::forget("circuit_breaker:{$name}:state");
        Cache::forget("circuit_breaker:{$name}:failures");
        Cache::forget("circuit_breaker:{$name}:opened_at");
        Cache::forget("circuit_breaker:{$name}:half_open_successes");
    }
}
