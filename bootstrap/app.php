<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.tenant' => \App\Http\Middleware\EnsureUserIsActive::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'student.auth' => \App\Http\Middleware\StudentAuth::class,
            'student.must.change.password' => \App\Http\Middleware\StudentMustChangePassword::class,
            'magic-link' => \App\Http\Middleware\ThrottleMagicLink::class,
        ]);

        $middleware->statefulApi();

        /*
         * cloudflared menyambung ke nginx lewat 127.0.0.1, bukan 172.16.0.183,
         * sehingga daftar lama tidak pernah cocok dan seluruh header X-Forwarded-*
         * diabaikan diam-diam.
         *
         * Pekerjaannya kini dilakukan nginx: `real_ip_header CF-Connecting-IP`
         * menulis ulang alamat asal menjadi IP pengunjung asli, dan skema https
         * dikirim sebagai $_SERVER['HTTPS']. Laravel tetap mempercayai loopback
         * untuk sisa header proxy, dan hanya loopback — mempercayai rentang LAN
         * akan membuka jalan pemalsuan alamat untuk melewati rate limit.
         */
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
    })
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        /*
         * Parameter `health` menerima URI, bukan path berkas. Yang lama
         * mendaftarkan rute dengan URI harfiah
         * "var/www/walikelas-pro/bootstrap/../routes/health.php" — tidak bisa
         * diakses siapa pun (404) sekaligus menambah rute sampah di tabel rute.
         * Berkas yang ditunjuknya pun tidak ada. Pemeriksaan kesehatan yang
         * sungguh dipakai adalah /health dan /health/ready dari HealthController.
         */
    )
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

return $app;
