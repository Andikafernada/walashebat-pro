@extends('layouts.guest')

@section('title', 'Masuk')
@section('page-title', 'Selamat datang kembali')
@section('page-subtitle', 'Masuk untuk mengelola administrasi kelas Anda')

@section('content')

<form method="POST" action="{{ route('login') }}" x-data="{
    loading: false,
    showPassword: false,
}" @submit="loading = true">
    @csrf

    {{-- Tombol Google & Akun Belajar.id --}}
    <div class="mb-5">
        <a href="{{ route('auth.google') }}"
           class="w-full flex items-center justify-center gap-3 px-4 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-800 text-xs sm:text-sm font-bold shadow-xs transition-all hover:border-slate-400">
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Masuk dengan Akun Belajar.id / Google</span>
        </a>

        <div class="relative my-4 flex items-center justify-center">
            <div class="border-t border-slate-200 w-full"></div>
            <span class="bg-white px-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider absolute">atau</span>
        </div>
    </div>


    <!-- Email -->
    <div class="form-group">
        <label for="email" class="form-label form-label--required">Email</label>
        <input
            id="email"
            name="email"
            type="email"
            value="{{ old('email') }}"
            required
            autofocus
            autocomplete="email"
            placeholder="guru@sekolah.sch.id"
            class="form-input @error('email') form-input--error @enderror"
            aria-describedby="email-error"
        >
        @error('email')
            <p id="email-error" class="form-error">{{ $message }}</p>
        @enderror
    </div>

    <!-- Password -->
    <div class="form-group">
        <div class="flex items-center justify-between mb-2">
            <label for="password" class="form-label form-label--required mb-0">Kata sandi</label>
            <a href="{{ route('password.request') }}" class="forgot-link">Lupa kata sandi?</a>
        </div>
        <div class="relative">
            <input
                id="password"
                name="password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                placeholder="Masukkan kata sandi"
                class="form-input pr-12 @error('password') form-input--error @enderror"
            >
            <button type="button" @click="showPassword = !showPassword" class="password-toggle" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
            </button>
        </div>
        @error('password')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>

    <!-- Remember me -->
    <div class="flex items-center gap-2.5 mb-6">
        <input type="checkbox" id="remember" name="remember" class="form-checkbox">
        <label for="remember" class="text-xs sm:text-sm font-semibold text-slate-700">Ingat saya selama 30 hari</label>
    </div>

    <!-- Submit -->
    <button type="submit" class="btn-primary" :disabled="loading" :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
        <span x-show="!loading">Masuk ke akun</span>
        <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
            <span class="spinner"></span>
            Memproses...
        </span>
    </button>

    <p class="mt-6 text-center text-xs sm:text-sm text-slate-600">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-bold text-emerald-800 hover:underline">Daftar gratis</a>
    </p>
</form>
@endsection

@section('footer')
<a href="/" class="text-emerald-800 font-bold hover:underline">← Kembali ke Beranda</a>
@endsection
