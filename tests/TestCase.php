<?php

namespace Tests;

use App\Support\Contracts\NotificationChannel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Tests\Support\PenangkapPesanWhatsApp;

abstract class TestCase extends BaseTestCase
{
    /**
     * Daftarkan seorang guru sampai akunnya benar-benar jadi.
     *
     * Sejak nomor WhatsApp diverifikasi, pendaftaran bukan lagi satu POST:
     * formulirnya menitipkan data ke cache, kodenya dikirim, dan akun baru
     * dibuat setelah kode itu dibalas. Test yang subjeknya BUKAN verifikasi —
     * masa gratis tiga bulan, dimensi karakter, peran yang tidak boleh
     * diselundupkan — tidak perlu tahu urutan itu, dan tidak boleh ikut patah
     * setiap kali urutannya berubah.
     */
    protected function daftarkanGuru(array $isian): TestResponse
    {
        $gateway = new PenangkapPesanWhatsApp;
        $this->app->instance(NotificationChannel::class, $gateway);

        $balasan = $this->post('/register', $isian);

        $kode = $gateway->kodeTerakhir();

        // Sakelar verifikasi mati, atau pendaftarannya memang ditolak.
        if ($kode === null) {
            return $balasan;
        }

        return $this->post('/register/verifikasi', ['otp' => $kode]);
    }

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
