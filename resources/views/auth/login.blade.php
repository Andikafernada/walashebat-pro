@extends('layouts.guest')
@section('title', 'Masuk')
@push('styles')
<style>
/* Custom animations for login page */
.login-float {
    animation: loginFloat 6s ease-in-out infinite;
}
@keyframes loginFloat {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(3deg); }
}

.login-float-delay {
    animation: loginFloatDelay 8s ease-in-out infinite;
}
@keyframes loginFloatDelay {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-15px) rotate(-2deg); }
}

/* Password visibility toggle */
.password-toggle {
    transition: all 0.2s ease;
}
.password-toggle:hover {
    background-color: #f1f5f9;
}

/* Input focus animation */
.input-animate {
    transition: all 0.2s ease;
}
.input-animate:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

/* Button hover effect */
.btn-login {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}
.btn-login::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
}
.btn-login:hover::before {
    left: 100%;
}
.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px -5px rgba(79, 70, 229, 0.4);
}

/* Social login buttons */
.social-btn {
    transition: all 0.2s ease;
}
.social-btn:hover {
    background-color: #f8fafc;
    border-color: #c7d2fe;
    transform: translateY(-2px);
}
</style>
@endpush
@section('content')

    <!-- Header -->
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-slate-900">Selamat datang kembali</h2>
        <p class="mt-2 text-sm text-slate-500">Masuk untuk mengelola administrasi kelas Anda</p>
    </div>

    <!-- Decorative badges -->
    <div class="flex justify-center gap-3 mb-6">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-600 border border-emerald-100">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            100% Gratis
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600 border border-indigo-100">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
            </svg>
            Data Aman
        </span>
    </div>

    @if (session('error'))
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3" role="alert">
            <p class="text-sm font-medium text-amber-900">{{ session('error') }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{
        loading: false,
        showPassword: false,
    }" @submit="loading = true">
        @csrf

        <!-- Email -->
        <div class="form-group">
            <label for="email" class="form-label form-label--required">Email</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="guru@sekolah.sch.id"
                    class="input-animate pl-12 pr-4 py-3.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white @error('email') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    aria-describedby="email-error"
                >
            </div>
            @error('email')
                <p id="email-error" class="form-error mt-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Password -->
        <div class="form-group">
            <div class="flex items-center justify-between mb-2">
                <label for="password" class="form-label form-label--required">Kata sandi</label>
                <a href="{{ route('password.request') }}"
                   class="text-xs font-medium text-indigo-600 hover:text-indigo-700 transition-colors">
                    Lupa kata sandi?
                </a>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <input
                    id="password"
                    name="password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi"
                    class="input-animate pl-12 pr-12 py-3.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white @error('password') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    aria-describedby="password-error"
                >
                <button type="button" @click="showPassword = !showPassword" class="password-toggle absolute inset-y-0 right-0 pr-4 flex items-center">
                    <svg x-show="!showPassword" class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <p id="password-error" class="form-error mt-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Remember me -->
        <label class="flex items-center gap-3 cursor-pointer group">
            <input type="checkbox" name="remember" class="form-checkbox w-4.5 h-4.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 transition-colors">
            <span class="text-sm text-slate-600 group-hover:text-slate-900 transition-colors">
                Ingat saya selama 30 hari
            </span>
        </label>

        <!-- Submit -->
        <button
            type="submit"
            class="btn-login btn-primary w-full justify-center py-4 text-base font-semibold"
            :disabled="loading"
            :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
        >
            <span x-show="!loading" class="inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Masuk ke akun
            </span>
            <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                <span class="spinner spinner--white"></span>
                Memasuki...
            </span>
        </button>
    </form>

    <!-- Divider -->
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-slate-200"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="bg-white px-4 text-slate-500">atau</span>
        </div>
    </div>

    <!-- Social login hint -->
    <div class="text-center">
        <p class="text-sm text-slate-500 mb-4">
            Belum punya akun?
            <a href="{{ route('register') }}"
               class="font-semibold text-indigo-600 hover:text-indigo-700 hover:underline transition-colors">
                Daftar gratis &rarr;
            </a>
        </p>
    </div>

    <!-- Benefits -->
    <div class="mt-6 pt-6 border-t border-slate-100">
        <div class="grid grid-cols-3 gap-2 text-center">
            <div class="p-3 rounded-xl bg-slate-50">
                <svg class="w-6 h-6 mx-auto mb-1 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-slate-600 font-medium">Gratis</p>
            </div>
            <div class="p-3 rounded-xl bg-slate-50">
                <svg class="w-6 h-6 mx-auto mb-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <p class="text-xs text-slate-600 font-medium">Mobile</p>
            </div>
            <div class="p-3 rounded-xl bg-slate-50">
                <svg class="w-6 h-6 mx-auto mb-1 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <p class="text-xs text-slate-600 font-medium">Aman</p>
            </div>
        </div>
    </div>
@endsection
