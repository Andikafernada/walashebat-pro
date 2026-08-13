@extends('layouts.guest')
@section('title', 'Masuk')
@section('content')

    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-xl font-semibold tracking-tight text-slate-900">Selamat datang kembali</h2>
        <p class="mt-1 text-sm text-slate-500">Masuk untuk mengelola administrasi kelas Anda</p>
    </div>

    @if (session('error'))
        <div class="alert alert--warning mb-5" role="alert">
            <p class="alert__body">{{ session('error') }}</p>
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
            <div class="flex items-center justify-between">
                <label for="password" class="form-label form-label--required">Kata sandi</label>
                <a href="{{ route('password.request') }}"
                   class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                    Lupa kata sandi?
                </a>
            </div>
            <div class="relative">
                <input
                    id="password"
                    name="password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi"
                    class="form-input pr-11 @error('password') form-input--error @enderror"
                    aria-describedby="password-error"
                >
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                    <svg x-show="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <p id="password-error" class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember me -->
        <label class="flex items-center gap-2.5">
            <input type="checkbox" name="remember" class="form-checkbox">
            <span class="text-sm text-slate-600">Ingat saya selama 30 hari</span>
        </label>

        <!-- Submit -->
        <button
            type="submit"
            class="btn-primary w-full justify-center"
            :disabled="loading"
            :class="loading ? 'opacity-70 cursor-not-allowed' : ''"
        >
            <span x-show="!loading">Masuk ke akun</span>
            <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                <span class="spinner spinner--white"></span>
                Memasuki...
            </span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:underline">
            Daftar gratis
        </a>
    </p>
@endsection
