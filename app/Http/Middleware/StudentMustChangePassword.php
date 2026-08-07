<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentMustChangePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $student = Auth::guard('student')->user();

        /*
         * Formulir DAN pengirimannya sama-sama harus lolos. Sebelumnya hanya
         * route GET yang dikecualikan, sehingga POST-nya ikut dialihkan balik
         * ke formulir: siswa yang wajib mengganti kata sandi tidak akan pernah
         * bisa menyimpannya. Karena must_change_password bernilai true secara
         * bawaan, seluruh akun siswa baru terkena.
         */
        $dikecualikan = ['student.password.change', 'student.password.update'];

        if ($student && $student->must_change_password && ! $request->routeIs(...$dikecualikan)) {
            return redirect()->route('student.password.change');
        }

        return $next($request);
    }
}
