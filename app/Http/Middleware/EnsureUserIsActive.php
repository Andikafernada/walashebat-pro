<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak akses jika akun wali kelas dinonaktifkan (mis. langganan berakhir).
 * Tenant isolation itu sendiri ditangani oleh TenantScope di level model.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->is_active) {
            Auth::logout();

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi admin.']);
        }

        return $next($request);
    }
}
