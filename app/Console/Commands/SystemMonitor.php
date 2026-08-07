<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Monitoring kesehatan sistem untuk production.
 *
 * Jalankan via cron untuk deteksi dini masalah:
 *   * * * * * php artisan walikelas:monitor >> /dev/null 2>&1
 *
 * Atau untuk testing:
 *   php artisan walikelas:monitor --output
 *
 * Alert dikirim ke log dengan level warning/error, dapat dikumpulkan
 * oleh monitoring tools (Sentry, Datadog, dll).
 */
class SystemMonitor extends Command
{
    protected $signature = "walikelas:monitor
                            {--output : Tampilkan output detail ke terminal}
                            {--alert : Kirim alert via Slack/webhook (jika dikonfigurasi)}";

    protected $description = 'Monitor kesehatan sistem WaliKelas Pro';

    private array $alerts = [];
    private bool $hasErrors = false;

    public function handle(): int
    {
        $this->info('Memulai monitoring sistem...');

        $this->checkFailedJobs();
        $this->checkQueueDepth();
        $this->checkDatabaseSize();
        $this->checkStorageSpace();
        $this->checkCacheHealth();
        $this->checkWhatsAppGateway();

        // Tampilkan ringkasan
        $this->showSummary();

        // Log hasil
        $this->logResults();

        return $this->hasErrors ? Command::FAILURE : Command::SUCCESS;
    }

    private function checkFailedJobs(): void
    {
        try {
            $count = DB::table('failed_jobs')->count();

            if ($count === 0) {
                $this->line('✓ Failed jobs: 0');
                return;
            }

            $this->warn("⚠ Failed jobs: {$count}");

            // Ambil detail failed jobs
            $recent = DB::table('failed_jobs')
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'queue', 'exception', 'failed_at']);

            $this->alerts[] = [
                'type' => 'warning',
                'title' => 'Failed Jobs Ditemukan',
                'message' => "Terdapat {$count} failed job yang perlu ditangani.",
                'details' => $recent->map(fn ($job) => [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                ])->toArray(),
            ];

            if ($count >= 10) {
                $this->hasErrors = true;
                $this->error("ERROR: Terlalu banyak failed jobs ({$count}). Perlu perhatian segera!");
            }
        } catch (\Throwable $e) {
            $this->error("Error cek failed jobs: {$e->getMessage()}");
        }
    }

    private function checkQueueDepth(): void
    {
        try {
            $pending = DB::table('jobs')->count();
            $pending24h = DB::table('jobs')
                ->where('created_at', '<', now()->subDay())
                ->count();

            if ($pending === 0) {
                $this->line("✓ Queue depth: 0");
                return;
            }

            $this->warn("⚠ Queue depth: {$pending} ({$pending24h} older than 24h)");

            $this->alerts[] = [
                'type' => 'info',
                'title' => 'Queue Depth Tinggi',
                'message' => "{$pending} jobs pending ({$pending24h} older than 24h)",
            ];

            if ($pending > 1000) {
                $this->hasErrors = true;
                $this->error("ERROR: Queue depth sangat tinggi ({$pending}). Worker mungkin tidak berjalan!");
            }
        } catch (\Throwable $e) {
            $this->error("Error cek queue: {$e->getMessage()}");
        }
    }

    private function checkDatabaseSize(): void
    {
        try {
            $tables = collect(DB::select("
                SELECT table_name, (data_length + index_length) as size, table_rows
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
            "));

            // Cek tabel yang mungkin membesar太快
            $bigTables = $tables->filter(fn ($t) => $t->size > 100 * 1024 * 1024) // > 100MB
                ->sortByDesc('size');

            if ($bigTables->isEmpty()) {
                $this->line('✓ Database size: normal');
                return;
            }

            foreach ($bigTables as $table) {
                $sizeMB = round($table->size / 1024 / 1024, 2);
                $this->warn("⚠ Tabel {$table->table_name}: {$sizeMB} MB ({$table->table_rows} rows)");
            }

            $this->alerts[] = [
                'type' => 'info',
                'title' => 'Tabel Besar Terdeteksi',
                'message' => $bigTables->map(fn ($t) => "{$t->table_name}: " . round($t->size / 1024 / 1024, 1) . "MB")->join(', '),
            ];
        } catch (\Throwable $e) {
            $this->error("Error cek database size: {$e->getMessage()}");
        }
    }

    private function checkStorageSpace(): void
    {
        try {
            $path = base_path();
            $free = disk_free_space($path);
            $total = disk_total_space($path);
            $used = $total - $free;
            $percent = round($used / $total * 100, 1);

            $this->line("Storage: {$percent}% used (" . round($free / 1024 / 1024 / 1024, 1) . " GB free)");

            if ($percent > 90) {
                $this->hasErrors = true;
                $this->error("ERROR: Disk space hampir penuh ({$percent}% used)!");
                $this->alerts[] = [
                    'type' => 'error',
                    'title' => 'Disk Space Rendah',
                    'message' => "Hanya " . round($free / 1024 / 1024 / 1024, 1) . " GB tersisa ({$percent}% used)",
                ];
            } elseif ($percent > 80) {
                $this->warn("⚠ Disk space: {$percent}% used");
                $this->alerts[] = [
                    'type' => 'warning',
                    'title' => 'Disk Space Menipis',
                    'message' => "{$percent}% used, " . round($free / 1024 / 1024 / 1024, 1) . " GB free",
                ];
            }
        } catch (\Throwable $e) {
            $this->error("Error cek storage: {$e->getMessage()}");
        }
    }

    private function checkCacheHealth(): void
    {
        try {
            $key = 'monitor_cache_test_' . time();
            Cache::put($key, 'ok', 60);

            if (Cache::get($key) === 'ok') {
                Cache::forget($key);
                $this->line('✓ Cache: healthy');
            } else {
                $this->warn('⚠ Cache: read/write test failed');
                $this->alerts[] = [
                    'type' => 'warning',
                    'title' => 'Cache Health Issue',
                    'message' => 'Cache read/write test failed',
                ];
            }
        } catch (\Throwable $e) {
            $this->warn("⚠ Cache: unavailable - {$e->getMessage()}");
            $this->alerts[] = [
                'type' => 'warning',
                'title' => 'Cache Unavailable',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function checkWhatsAppGateway(): void
    {
        // Cek circuit breaker status via cache
        try {
            $gatewayCircuit = Cache::get('circuit_breaker:whatsapp-gateway:state');
            $sessionCircuit = Cache::get('circuit_breaker:whatsapp-session:state');

            if ($gatewayCircuit === 'open' || $sessionCircuit === 'open') {
                $this->warn('⚠ WhatsApp Gateway: Circuit breaker OPEN');

                $this->alerts[] = [
                    'type' => 'warning',
                    'title' => 'WhatsApp Gateway Down',
                    'message' => 'Circuit breaker aktif - gateway tidak merespons',
                ];
            }
        } catch (\Throwable $e) {
            // Circuit breaker tidak tersedia, skip
        }
    }

    private function showSummary(): void
    {
        $this->newLine();
        $this->info('=== Ringkasan Monitoring ===');

        if (empty($this->alerts)) {
            $this->info('✓ Tidak ada masalah terdeteksi');
            return;
        }

        foreach ($this->alerts as $alert) {
            $icon = match ($alert['type']) {
                'error' => '🔴',
                'warning' => '🟡',
                default => '🔵',
            };
            $this->line("{$icon} {$alert['title']}: {$alert['message']}");
        }
    }

    private function logResults(): void
    {
        if (empty($this->alerts)) {
            Log::info('[Monitor] Health check passed - no issues detected');
            return;
        }

        foreach ($this->alerts as $alert) {
            $logMethod = match ($alert['type']) {
                'error' => 'error',
                'warning' => 'warning',
                default => 'info',
            };

            Log::$logMethod("[Monitor] {$alert['title']}", [
                'message' => $alert['message'],
                'details' => $alert['details'] ?? null,
            ]);
        }

        // Log summary
        if ($this->hasErrors) {
            Log::critical('[Monitor] Critical issues detected', [
                'alert_count' => count($this->alerts),
                'has_errors' => true,
            ]);
        }
    }
}
