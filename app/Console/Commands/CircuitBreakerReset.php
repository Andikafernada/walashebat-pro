<?php

namespace App\Console\Commands;

use App\Support\CircuitBreaker;
use Illuminate\Console\Command;

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
        $gateway = $this->circuit('whatsapp-gateway');
        $session = $this->circuit('whatsapp-session');

        $gatewayStatus = $gateway->getStatus();
        $sessionStatus = $session->getStatus();

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

        $gateway->reset();
        $session->reset();

        $this->info('✓ Circuit breaker berhasil di-reset');

        return Command::SUCCESS;
    }

    /**
     * Perintah ini dulu menyusun sendiri kunci cache `circuit_breaker:...`
     * mentah dan mengulang masa tunggu 60 detik sebagai angka yang dikodekan
     * keras. Akibatnya ia selalu ketinggalan satu langkah dari kelasnya:
     * kunci izin pengintai yang ditambahkan belakangan tidak ikut terhapus,
     * sehingga "reset" meninggalkan sisa yang masih menahan pengintai
     * berikutnya. Dengan mendelegasikan ke CircuitBreaker, kunci apa pun yang
     * ditambahkan nanti otomatis ikut terurus.
     */
    private function circuit(string $name): CircuitBreaker
    {
        return new CircuitBreaker($name);
    }
}
