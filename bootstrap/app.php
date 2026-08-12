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
        /*
         * Tab yang ditinggal semalaman punya token CSRF yang sudah mati bersama
         * sesinya. Halaman "419 Page Expired" bawaan Laravel adalah jalan buntu:
         * tidak menjelaskan apa pun dan tidak menawarkan langkah berikutnya.
         *
         * Untuk "Keluar" tujuannya sudah jelas: sesinya memang sudah mati, jadi
         * antar saja ke halaman masuk sambil menyebutkan sebabnya. Untuk form
         * lain — termasuk tautan publik wali murid — pantulkan ke halaman asal
         * beserta isian yang sudah diketik, supaya dirender ulang dengan token
         * segar dan kiriman kedua berhasil tanpa mengetik ulang.
         */
        /*
         * Type-hint-nya HttpException, bukan TokenMismatchException: Handler
         * memetakan yang kedua menjadi HttpException(419) di prepareException()
         * sebelum callback render dipanggil, sehingga type-hint aslinya tidak
         * pernah cocok.
         */
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            $pesan = 'Sesi Anda sudah berakhir karena halaman terlalu lama dibiarkan terbuka. Silakan masuk lagi.';

            if ($request->routeIs('logout', 'student.logout')) {
                return redirect()
                    ->route($request->routeIs('student.logout') ? 'student.login' : 'login')
                    ->with('error', $pesan);
            }

            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation', 'pin']))
                ->with('error', $pesan);
        });
    })->create();

return $app;
