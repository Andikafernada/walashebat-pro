<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limiter untuk endpoint magic link anti-curang.
 * Membaca konfigurasi dari config('walikelas.magic_link_throttle').
 */
class ThrottleMagicLink extends ThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $maxAttempts = null, $decayMinutes = 1, $prefix = ''): Response
    {
        $maxAttempts = $maxAttempts ?? (int) config('walikelas.magic_link_throttle', 30);

        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }
}
