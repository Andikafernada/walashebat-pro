@extends('layouts.guest')
@section('title', 'Lupa Kata Sandi')
@section('page-title', 'Lupa kata sandi')
@section('page-subtitle', 'Masukkan email akun Anda untuk menerima tautan reset kata sandi')

@section('content')
    <form method="POST"
          action="{{ route('password.otp.send') }}"
          class="space-y-4"
          x-data="{ loading: false }"
          @submit="loading = true">

        @csrf

        <div class="form-group">
            <label for="email" class="form-label form-label--required">Email akun</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                placeholder="guru@sekolah.sch.id"
                class="form-input"
            >
            @error('email')
                <p class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="btn-primary"
                :disabled="loading"
                :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
            <span x-show="!loading">Kirim tautan reset</span>
            <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                <span class="spinner"></span>
                Mengirim...
            </span>
        </button>
    </form>

    <p class="mt-6 text-center text-xs sm:text-sm text-slate-600">
        <a href="{{ route('login') }}" class="font-bold text-emerald-800 hover:underline">
            ← Kembali ke halaman masuk
        </a>
    </p>
@endsection
