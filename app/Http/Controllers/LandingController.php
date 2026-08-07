<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Halaman muka.
 *
 * Dijadikan controller, bukan closure di routes/web.php, karena closure tidak
 * bisa diserialkan: satu saja membuat `route:cache` gagal untuk SELURUH rute
 * aplikasi.
 */
class LandingController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        return Auth::check()
            ? redirect()->route('dashboard')
            : view('landing');
    }
}
