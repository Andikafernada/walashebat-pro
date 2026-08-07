<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * Health check endpoint untuk monitoring.
 *
 * GET /health - Basic liveness check (aplikasi hidup?)
 * GET /health/ready - Readiness check (siap menerima traffic?)
 */
class HealthController extends Controller
{
    /**
     * Basic liveness check.
     * Harusnya selalu 200 selama PHP masih berjalan.
     */
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'app' => config('app.name'),
        ]);
    }

    /**
     * Readiness check - cek semua dependensi.
     * Untuk load balancer, deployment hooks, dll.
     */
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'failed_jobs' => $this->checkFailedJobs(),
            'storage' => $this->checkStorage(),
        ];

        $allHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            /*
             * `SELECT 1` menggantikan `SHOW TABLES`. Dua alasan: SHOW TABLES
             * hanya dikenal MySQL sehingga pemeriksaan ini tidak bisa diuji,
             * dan ia mendaftar seluruh tabel pada setiap polling — pekerjaan
             * sia-sia untuk endpoint yang dipanggil tiap beberapa detik.
             * Yang perlu dijawab hanyalah: koneksi hidup atau tidak.
             */
            DB::connection()->getPdo();
            DB::select('SELECT 1');

            return [
                'status' => 'ok',
                'driver' => DB::connection()->getDriverName(),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'error' => 'Cannot connect to database',
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            /*
             * Kunci harus unik per request. Versi sebelumnya memakai time(),
             * sehingga semua request dalam detik yang sama berbagi satu kunci:
             * request yang satu memanggil forget() tepat sebelum request lain
             * memanggil get(), lalu endpoint ini melaporkan dirinya tidak sehat
             * dan membalas 503. Di bawah beban, sekitar 1 dari 10 pemeriksaan
             * gagal seperti itu — cukup untuk membuat pemantau uptime mengira
             * situs mati, dan membuat load balancer mengeluarkan server sehat
             * dari rotasi justru saat trafik sedang tinggi.
             */
            $key = 'health_check_'.bin2hex(random_bytes(8));
            Cache::put($key, 'ok', 10);

            if (Cache::get($key) === 'ok') {
                Cache::forget($key);
                return [
                    'status' => 'ok',
                    'driver' => config('cache.default'),
                ];
            }

            return [
                'status' => 'warning',
                'error' => 'Cache read/write failed',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'error' => 'Cache unavailable: ' . $e->getMessage(),
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            /*
             * Antrean berjalan di Redis, bukan di tabel `jobs`. Membaca tabel
             * itu selalu menghasilkan 0 berapa pun isi antrean sebenarnya,
             * sehingga pemeriksaan ini dulu selalu menjawab "sehat" — termasuk
             * saat ribuan pesan WhatsApp untuk orang tua menumpuk tak terkirim.
             * Queue::size() menanyakan koneksi yang benar-benar dipakai.
             */
            $pending = Queue::size();

            return [
                'status' => $pending > 1000 ? 'warning' : 'ok',
                'connection' => config('queue.default'),
                'pending' => $pending,
                'message' => $pending > 1000
                    ? 'Queue depth tinggi, pertimbangkan menambah worker'
                    : null,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'error' => 'Cannot read queue',
            ];
        }
    }

    private function checkFailedJobs(): array
    {
        try {
            $count = DB::table('failed_jobs')->count();

            return [
                'status' => $count > 10 ? 'warning' : 'ok',
                'count' => $count,
                'message' => $count > 0
                    ? "Terdapat {$count} failed job yang perlu ditangani"
                    : null,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'error' => 'Cannot read failed_jobs table',
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $path = storage_path('framework/cache');
            $writable = is_writable($path);

            return [
                'status' => $writable ? 'ok' : 'error',
                'cache_writable' => $writable,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'error' => 'Storage check failed',
            ];
        }
    }
}
