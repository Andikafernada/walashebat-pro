<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Endpoint /health/ready dipakai pemantau uptime dan, kelak, load balancer.
 * Jawaban 503 palsu dari sini akan dibaca sebagai "situs mati" walau situsnya
 * sehat — dan pada load balancer justru mengeluarkan server yang sehat dari
 * rotasi tepat ketika trafik sedang tinggi.
 */
class CekKesehatanTest extends TestCase
{
    use RefreshDatabase;

    public function test_liveness_menjawab_ok(): void
    {
        $this->get('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_readiness_menjawab_ok_saat_semua_dependensi_sehat(): void
    {
        $this->get('/health/ready')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.cache.status', 'ok');
    }

    /**
     * Regresi: kunci cache pemeriksaan dulu dibuat dari time(), sehingga semua
     * request dalam detik yang sama memakai kunci yang sama. Request yang satu
     * menghapus kunci sebelum request lain sempat membacanya, lalu endpoint
     * melaporkan dirinya tidak sehat. Beberapa panggilan beruntun di dalam satu
     * detik yang sama harus tetap 200 semuanya.
     */
    public function test_panggilan_beruntun_dalam_detik_yang_sama_tetap_sehat(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->get('/health/ready')
                ->assertOk()
                ->assertJsonPath('checks.cache.status', 'ok');
        }
    }

    /**
     * Regresi: pemeriksaan antrean dulu menghitung baris tabel `jobs`, padahal
     * antrean produksi berjalan di Redis. Hasilnya selalu 0 — antrean yang
     * menumpuk tidak pernah terlihat. Pemeriksaan harus menyebut koneksi yang
     * benar-benar dipakai dan membaca kedalaman dari sana.
     */
    public function test_pemeriksaan_antrean_membaca_koneksi_yang_dipakai(): void
    {
        $balasan = $this->get('/health/ready');

        $balasan->assertOk()
            ->assertJsonPath('checks.queue.connection', config('queue.default'))
            ->assertJsonPath('checks.queue.pending', Queue::size());
    }
}
