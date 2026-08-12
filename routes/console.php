<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Penjadwalan WaliKelas Pro
|--------------------------------------------------------------------------
| Aktifkan dengan satu entri cron di server:
|   * * * * * cd /path/ke/app && php artisan schedule:run >> /dev/null 2>&1
*/

/*
 * Detak jantung penjadwal.
 *
 * Satu-satunya bukti bahwa `schedule:run` benar-benar berjalan. Tanpa ini
 * matinya penjadwal tidak menimbulkan gejala apa pun yang bisa dilihat dari
 * luar: situs tetap membalas 200, semua layanan tetap "active", dan pemantau
 * tetap menulis "OK" — sementara sesi absensi pagi tidak pernah dibuat dan
 * magic link tidak pernah terkirim. Sudah terjadi pada 2026-08-06 08:06-08:12:
 * penjadwal mati tujuh menit dan pemantau menulis "OK" tiga kali berturut-turut.
 *
 * Sengaja tugas paling sederhana yang bisa dibuat — hanya menyentuh berkas.
 * Detak yang ikut memanggil basis data atau antrean akan ikut mati oleh
 * gangguan yang bukan salah penjadwal, lalu menghasilkan alarm palsu.
 *
 * Dibaca oleh pantau-walikelas.sh lewat umur berkasnya.
 */
Schedule::call(function () {
    $berkas = storage_path('framework/scheduler-heartbeat');

    // touch() tidak membuat berkas bila direktorinya hilang; file_put_contents
    // memberi isi yang bisa dibaca manusia saat memeriksa manual.
    @file_put_contents($berkas, now()->toDateTimeString().PHP_EOL);
})->everyMinute()->name('detak-penjadwal')->withoutOverlapping();

// Buat sesi absensi dari jadwal & kirim magic link.
// withoutOverlapping(): cegah dua proses berjalan bersamaan bila eksekusi lambat.
Schedule::command('walikelas:generate-attendance')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();

// Rapikan status sesi yang sudah lewat waktu.
Schedule::command('walikelas:expire-attendance')
    ->everyTenMinutes();

/*
 * Matikan balasan otomatis milik langganan yang sudah berakhir.
 *
 * Dijalankan pagi hari, bukan tengah malam: bila gateway sedang bermasalah,
 * kegagalannya terlihat pada jam kerja dan masih bisa ditangani hari itu juga.
 */
Schedule::command('walikelas:matikan-otomasi-kedaluwarsa')
    ->dailyAt('06:00')
    ->withoutOverlapping(10);

/*
 * Pengingat iuran bulanan ke grup WhatsApp orang tua.
 *
 * Jam 07:00, bukan tengah malam: pesan yang masuk grup orang tua pukul 00:00
 * membangunkan orang, dan kegagalan gateway pada jam itu baru ketahuan siang.
 * Perintahnya sendiri yang memutuskan kelas mana yang jatuh tempo hari ini,
 * karena tiap kelas boleh memilih tanggalnya sendiri.
 */
Schedule::command('walikelas:kirim-pengingat-spp')
    ->dailyAt('07:00')
    ->withoutOverlapping(10);
