@extends('layouts.guest')
@section('title', 'Atur Ulang Kata Sandi')
@section('page-title', 'Buat kata sandi baru')
@section('page-subtitle', 'Cek WhatsApp Anda untuk kode OTP, lalu masukkan kata sandi baru')

@section('content')
    <form method="POST"
          action="{{ route('password.update') }}"
          class="space-y-4"
          x-data="{ loading: false }"
          @submit="loading = true">

        @csrf

        <input type="hidden" name="email" value="{{ $email }}">

        <!-- OTP -->
        <div class="form-group">
            <label for="otp" class="form-label form-label--required">Kode OTP dari WhatsApp</label>
            <input
                id="otp"
                name="otp"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
                maxlength="6"
                placeholder="123456"
                class="form-input text-center text-xl font-bold tracking-[0.3em]"
                style="letter-spacing: 0.3em;"
            >
            <p class="form-hint">Kode 6 digit dari pesan WhatsApp yang dikirim ke nomor Anda</p>
            @error('otp')
                <p class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <!-- New Password -->
        <div class="form-group">
            <label for="password" class="form-label form-label--required">Kata sandi baru</label>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Minimal 8 karakter"
                minlength="8"
                class="form-input"
            >
            @error('password')
                <p class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
            <label for="password_confirmation" class="form-label form-label--required">Konfirmasi kata sandi baru</label>
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Ulangi kata sandi baru"
                class="form-input"
            >
        </div>

        <button type="submit"
                class="btn-primary"
                :disabled="loading"
                :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
            <span x-show="!loading">Simpan kata sandi baru</span>
            <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                <span class="spinner"></span>
                Menyimpan...
            </span>
        </button>
    </form>

    <p class="mt-6 text-center text-xs sm:text-sm text-slate-600">
        Tidak menerima kode?
        <a href="{{ route('password.request') }}" class="font-bold text-emerald-800 hover:underline">
            Kirim ulang
        </a>
    </p>
@endsection
