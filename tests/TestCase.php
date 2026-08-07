<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pengaman: tolak berjalan bila koneksi test menunjuk database sungguhan.
     *
     * `php artisan config:cache` — kondisi normal server produksi — membuat
     * config dibaca dari berkas cache, sehingga blok <php> di phpunit.xml
     * DIABAIKAN sepenuhnya. Tanpa pengaman ini, menjalankan test di server
     * produksi mengarahkan RefreshDatabase ke database asli dan `migrate:fresh`
     * menghapus seluruh tabelnya.
     *
     * Pemeriksaannya sengaja di sini, bukan di masing-masing test: satu berkas
     * yang lupa memakainya sudah cukup untuk menghilangkan seluruh data.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $koneksi = config('database.default');
        $database = config("database.connections.{$koneksi}.database");

        if ($koneksi !== 'sqlite' || ! in_array($database, [':memory:', ''], true)) {
            $this->fail(
                "Test dihentikan demi keamanan: koneksi mengarah ke `{$koneksi}` "
                ."(database: `{$database}`), bukan sqlite in-memory.\n"
                ."Penyebab tersering adalah config yang ter-cache. Jalankan:\n"
                ."  php artisan config:clear\n"
                .'Jangan jalankan test terhadap database produksi.'
            );
        }
    }
}
