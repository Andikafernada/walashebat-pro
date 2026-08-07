<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentAuth
{
    public function handle(Request $request, Closure $next, string $guard = 'student'): Response
    {
        if (! Auth::guard($guard)->check()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthorized'], 401)
                : redirect()->route('student.login');
        }

        return $next($request);
    }
}
