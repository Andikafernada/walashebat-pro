<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Super admin bisa akses semua halaman
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // If no roles specified, allow any authenticated user
        if (empty($roles)) {
            return $next($request);
        }

        // Check if user has one of the required roles
        if (! in_array($user->role, $roles)) {
            // User doesn't have required role - redirect to dashboard with error
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
