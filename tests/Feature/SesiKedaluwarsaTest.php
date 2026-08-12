<?php

namespace Tests\Feature;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Tab yang ditinggal semalaman memakai token CSRF yang mati bersama sesinya.
 * Dulu hasilnya tembok "419 Page Expired" tanpa jalan keluar.
 */
class SesiKedaluwarsaTest extends TestCase
{
    private function render(string $uri, string $namaRute)
    {
        $request = Request::create($uri, 'POST', ['catatan' => 'sudah diketik', 'password' => 'rahasia']);
        $request->headers->set('referer', 'https://walas.my.id/asal');
        $request->setRouteResolver(fn () => Route::getRoutes()->getByName($namaRute));

        // back() membaca referer lewat UrlGenerator, bukan lewat $request kita.
        app('url')->setRequest($request);

        return app(ExceptionHandler::class)->render($request, new TokenMismatchException);
    }

    public function test_keluar_dengan_sesi_mati_diantar_ke_halaman_masuk(): void
    {
        $response = $this->render('/logout', 'logout');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertNotEmpty($response->getSession()->get('error'));
    }

    public function test_form_lain_dipantulkan_balik_dengan_isian_utuh(): void
    {
        $response = $this->render('/classes/1/nilai', 'classes.nilai.store');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://walas.my.id/asal', $response->getTargetUrl());

        $input = $response->getSession()->get('_old_input');
        $this->assertSame('sudah diketik', $input['catatan']);
        $this->assertArrayNotHasKey('password', $input, 'Kata sandi tidak boleh ikut dipantulkan.');
    }
}
