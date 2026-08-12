@extends('layouts.guest')
@section('title', 'Atur Ulang Kata Sandi')
@section('content')

    <h2 class="mb-1 text-xl font-semibold text-slate-900">Buat kata sandi baru</h2>
    <p class="mb-8 text-sm text-slate-500">
        Cek WhatsApp Anda untuk kode OTP, lalu masukkan kata sandi baru.
    </p>

    <form method="POST"
          action="{{ route('password.update') }}"
          class="space-y-5"
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
                placeholder="cth: 123456"
                class="form-input text-center text-2xl font-semibold tracking-[0.5em]"
                style="letter-spacing: 0.5em;"
            >
            <p class="form-hint mt-1.5">Kode 6 digit dari pesan WhatsApp yang dikirim ke nomor Anda.</p>
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
                class="btn-primary w-full justify-center py-3.5"
                :disabled="loading"
                :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
            <span x-show="!loading" class="inline-flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Simpan kata sandi baru
            </span>
            <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                <span class="spinner spinner--white"></span>
                Menyimpan...
            </span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Tidak menerima kode?
        <a href="{{ route('password.request') }}" class="font-semibold text-indigo-600 hover:underline">
            Kirim ulang
        </a>
    </p>
@endsection
