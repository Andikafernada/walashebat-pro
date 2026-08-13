@extends('layouts.guest')
@section('title', 'Lupa Kata Sandi')
@section('content')

    <h2 class="mb-1 text-xl font-semibold text-slate-900">Lupa kata sandi</h2>
    <p class="mb-8 text-sm text-slate-500">
        Masukkan email akun Anda. Kami akan mengirim tautan reset ke email tersebut.
    </p>

    <form method="POST"
          action="{{ route('password.otp.send') }}"
          class="space-y-5"
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
                class="btn-primary w-full justify-center py-3.5"
                :disabled="loading"
                :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
            <span x-show="!loading">Kirim tautan reset</span>
            <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                <span class="spinner spinner--white"></span>
                Mengirim...
            </span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">
            Kembali ke halaman masuk
        </a>
    </p>
@endsection
