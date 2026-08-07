<?php

namespace App\Support;

/**
 * Circuit Breaker untuk mendeteksi gateway WhatsApp yang mati.
 *
 * State machine:
 *   CLOSED (normal) → OPEN (gateway gagal) → HALF_OPEN (coba lagi)
 *
 * OPEN → HALF_OPEN setelah $resetTimeout detik.
 * HALF_OPEN → CLOSED jika request berhasil, OPEN jika gagal.
 */
class CircuitBreaker
{
    public const STATE_CLOSED = 'closed';
    public const STATE_OPEN = 'open';
    public const STATE_HALF_OPEN = 'half_open';

    private const STORE_PREFIX = 'circuit_breaker:';

    private int $failureThreshold;
    private int $resetTimeout;
    private int $successThreshold;
    private string $name;

    public function __construct(
        string $name,
        int $failureThreshold = 3,
        int $resetTimeout = 60,
        int $successThreshold = 2
    ) {
        $this->name = $name;
        $this->failureThreshold = $failureThreshold;
        $this->resetTimeout = $resetTimeout;
        $this->successThreshold = $successThreshold;
    }

    /**
     * Apakah circuit允许 request?
     */
    public function isAvailable(): bool
    {
        $state = $this->getState();

        if ($state === self::STATE_CLOSED) {
            return true;
        }

        if ($state === self::STATE_OPEN) {
            // Cek apakah sudah waktunya coba lagi
            $openedAt = $this->getStore('opened_at');
            if ($openedAt && (time() - (int) $openedAt) >= $this->resetTimeout) {
                $this->setState(self::STATE_HALF_OPEN);
                return true;
            }
            return false;
        }

        // HALF_OPEN - izinkan 1 request untuk test
        return true;
    }

    /**
     * Catat keberhasilan.
     */
    public function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            $successes = (int) $this->getStore('half_open_successes', 0) + 1;
            $this->setStore('half_open_successes', $successes);

            if ($successes >= $this->successThreshold) {
                $this->reset();
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure count on success
            $this->setStore('failures', 0);
        }
    }

    /**
     * Catat kegagalan.
     */
    public function recordFailure(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            // Gagal di half_open = langsung open lagi
            $this->trip();
        } else {
            $failures = (int) $this->getStore('failures', 0) + 1;
            $this->setStore('failures', $failures);

            if ($failures >= $this->failureThreshold) {
                $this->trip();
            }
        }
    }

    /**
     * Trip circuit ke OPEN state.
     */
    public function trip(): void
    {
        $this->setState(self::STATE_OPEN);
        $this->setStore('opened_at', time());
        $this->setStore('half_open_successes', 0);
    }

    /**
     * Reset circuit ke CLOSED.
     */
    public function reset(): void
    {
        $this->setState(self::STATE_CLOSED);
        $this->setStore('failures', 0);
        $this->setStore('opened_at', null);
        $this->setStore('half_open_successes', 0);
    }

    /**
     * Dapatkan status circuit breaker.
     */
    public function getStatus(): array
    {
        $state = $this->getState();
        $openedAt = $this->getStore('opened_at');
        $timeUntilRetry = 0;

        if ($state === self::STATE_OPEN && $openedAt) {
            $timeUntilRetry = max(0, $this->resetTimeout - (time() - (int) $openedAt));
        }

        return [
            'name' => $this->name,
            'state' => $state,
            'failures' => (int) $this->getStore('failures', 0),
            'threshold' => $this->failureThreshold,
            'time_until_retry' => $timeUntilRetry,
        ];
    }

    private function getState(): string
    {
        return $this->getStore('state', self::STATE_CLOSED);
    }

    private function setState(string $state): void
    {
        $this->setStore('state', $state);
    }

    private function getStore(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::STORE_PREFIX . $this->name . ':' . $key;
        return cache()->get($cacheKey, $default);
    }

    private function setStore(string $key, mixed $value): void
    {
        $cacheKey = self::STORE_PREFIX . $this->name . ':' . $key;
        // OPEN state bertahan lebih lama, CLOSED lebih pendek
        $ttl = match ($key) {
            'state' => $this->resetTimeout * 2,
            'opened_at' => $this->resetTimeout * 2,
            'half_open_successes' => $this->resetTimeout,
            default => 300,
        };
        cache()->put($cacheKey, $value, $ttl);
    }
}
