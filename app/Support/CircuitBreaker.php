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
 *
 * Keadaannya hidup di cache bersama, bukan di dalam objek: tiap request web dan
 * tiap worker antrian punya instance sendiri tapi harus melihat circuit yang
 * sama.
 */
class CircuitBreaker
{
    public const STATE_CLOSED = 'closed';
    public const STATE_OPEN = 'open';
    public const STATE_HALF_OPEN = 'half_open';

    private const STORE_PREFIX = 'circuit_breaker:';

    /**
     * Umur izin pengintai. Harus lebih panjang dari timeout HTTP terpanjang ke
     * gateway (aksi 'pair' 25 detik) supaya pengintai sempat melapor, tapi tetap
     * pendek: kalau worker pemegang izin mati sebelum melapor, izinnya wajib
     * kedaluwarsa sendiri — kalau tidak, circuit terkunci di half_open tanpa ada
     * yang boleh mencoba lagi.
     */
    private const PROBE_TTL = 30;

    private int $failureThreshold;
    private int $resetTimeout;
    private int $successThreshold;
    private string $name;

    /** Token izin pengintai milik instance ini, bila ia yang memenangkannya. */
    private ?string $probeToken = null;

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
     * Apakah circuit mengizinkan request SEKARANG?
     *
     * Punya efek samping: di half_open pemanggilan ini mengambil izin pengintai.
     * Yang mendapat true WAJIB melapor lewat recordSuccess()/recordFailure().
     * Untuk sekadar membaca keadaan (dasbor, monitoring) pakai isHealthy().
     */
    public function isAvailable(): bool
    {
        $state = $this->getState();

        if ($state === self::STATE_CLOSED) {
            return true;
        }

        if ($state === self::STATE_OPEN) {
            if (! $this->waktunyaMencobaLagi()) {
                return false;
            }

            // Yang menang izin sekaligus yang memindahkan state; sisanya tetap
            // melihat OPEN dan ditolak tanpa menyentuh gateway.
            if (! $this->ambilIzinPengintai()) {
                return false;
            }

            $this->setState(self::STATE_HALF_OPEN);

            return true;
        }

        // HALF_OPEN: hanya satu pengintai yang boleh lewat sampai ia melapor.
        // Kalau semua diloloskan, seluruh antrian menyerbu gateway yang baru
        // pulih dan menjatuhkannya lagi.
        return $this->ambilIzinPengintai();
    }

    /**
     * Bacaan keadaan tanpa efek samping, untuk dasbor dan penjaga kasar.
     *
     * Memakai isAvailable() untuk ini akan membakar satu-satunya jatah pengintai
     * hanya demi menggambar halaman status, sehingga pemulihan tertunda sampai
     * izinnya kedaluwarsa.
     */
    public function isHealthy(): bool
    {
        if ($this->getState() !== self::STATE_OPEN) {
            return true;
        }

        return $this->waktunyaMencobaLagi();
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

                return;
            }

            // Belum cukup menutup circuit: lepaskan izin supaya pengintai
            // berikutnya boleh jalan, bukan menunggu izin kedaluwarsa sendiri.
            $this->lepasIzinPengintai();
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
        // Penanda waktu ditulis lebih dulu: jangan sampai ada celah di mana
        // state sudah open tapi belum ada 'opened_at' yang bisa dibaca.
        $this->setStore('opened_at', time());
        $this->setState(self::STATE_OPEN);
        $this->setStore('half_open_successes', 0);
        $this->buangIzinPengintai();
    }

    /**
     * Reset circuit ke CLOSED.
     */
    public function reset(): void
    {
        $this->setState(self::STATE_CLOSED);
        $this->setStore('failures', 0);
        $this->forgetStore('opened_at');
        $this->setStore('half_open_successes', 0);
        $this->buangIzinPengintai();
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

    /**
     * Sudah lewatkah masa tunggu sejak circuit terbuka?
     *
     * Penanda waktu yang hilang (kunci cache-nya kedaluwarsa atau ditendang
     * karena memori penuh) dihitung sebagai "sudah lewat", bukan "belum pernah".
     * Keadaan yang hilang sebagian tidak boleh berarti terkunci selamanya:
     * gateway yang sudah sehat akan tetap dianggap mati sampai ada yang
     * membersihkan cache dengan tangan. Risikonya cuma satu pengintai lebih awal.
     */
    private function waktunyaMencobaLagi(): bool
    {
        $openedAt = $this->getStore('opened_at');

        return $openedAt === null
            || (time() - (int) $openedAt) >= $this->resetTimeout;
    }

    /**
     * Rebut izin pengintai secara atomik.
     *
     * add() = "tulis hanya bila belum ada" dan dijalankan sebagai satu operasi
     * oleh Redis, jadi dari sepuluh worker yang berebut di detik yang sama tepat
     * satu mendapat true. Cek-lalu-tulis biasa akan meloloskan semuanya.
     */
    private function ambilIzinPengintai(): bool
    {
        $token = bin2hex(random_bytes(8));

        if (! cache()->add($this->cacheKey('half_open_probe'), $token, self::PROBE_TTL)) {
            return false;
        }

        $this->probeToken = $token;

        return true;
    }

    /**
     * Lepaskan izin, tapi hanya bila instance ini memang pemegangnya — laporan
     * yang telat dari proses lain tidak boleh membebaskan pengintai yang masih
     * berjalan.
     */
    private function lepasIzinPengintai(): void
    {
        $key = $this->cacheKey('half_open_probe');

        if ($this->probeToken !== null && cache()->get($key) === $this->probeToken) {
            cache()->forget($key);
        }

        $this->probeToken = null;
    }

    /** Buang izin siapa pun pemegangnya; dipakai saat state berpindah. */
    private function buangIzinPengintai(): void
    {
        cache()->forget($this->cacheKey('half_open_probe'));
        $this->probeToken = null;
    }

    private function getState(): string
    {
        return $this->getStore('state', self::STATE_CLOSED);
    }

    private function setState(string $state): void
    {
        $this->setStore('state', $state);
    }

    private function cacheKey(string $key): string
    {
        return self::STORE_PREFIX . $this->name . ':' . $key;
    }

    private function getStore(string $key, mixed $default = null): mixed
    {
        return cache()->get($this->cacheKey($key), $default);
    }

    private function setStore(string $key, mixed $value): void
    {
        // Seluruh kunci keadaan berbagi satu TTL supaya umurnya tidak melar
        // sendiri-sendiri: 'half_open_successes' yang dulu setengah umur 'state'
        // membuat hitungan sukses bisa lenyap di tengah pemulihan. Kalaupun
        // salah satu kunci tetap hilang duluan (Redis kehabisan memori), yang
        // menjaga circuit tidak macet adalah waktunyaMencobaLagi(), bukan TTL ini.
        $ttl = match ($key) {
            'state', 'opened_at', 'half_open_successes' => $this->resetTimeout * 2,
            default => 300,
        };
        cache()->put($this->cacheKey($key), $value, $ttl);
    }

    private function forgetStore(string $key): void
    {
        cache()->forget($this->cacheKey($key));
    }
}
