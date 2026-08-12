@extends('layouts.public')
@section('title', 'Masukkan PIN')
@section('heading', 'Absensi '.$session->classroom->name)
@section('step', '1')
@section('content')

    <div class="rounded-lg border border-slate-200 bg-white p-5">

        @if ($locked)
            {{-- Tautan mati. Yang dibutuhkan petugas di sini bukan penjelasan
                 teknis, tapi satu langkah berikutnya yang jelas. --}}
            <div class="text-center">
                @if ($session->status === 'cancelled')
                    <h1 class="text-lg font-semibold tracking-tight text-slate-900">Sesi ini dibatalkan</h1>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Wali kelas membatalkan sesi absensi ini. Tunggu tautan baru dari wali kelas.
                    </p>
                @elseif ($session->isExpired())
                    <h1 class="text-lg font-semibold tracking-tight text-slate-900">Tautan sudah kedaluwarsa</h1>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Batas waktunya lewat pada {{ $session->expires_at->translatedFormat('d M Y, H:i') }} WIB.
                        Minta wali kelas mengirim tautan baru.
                    </p>
                @else
                    <h1 class="text-lg font-semibold tracking-tight text-slate-900">Tautan tidak berlaku</h1>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Minta wali kelas mengirim tautan absensi yang baru.
                    </p>
                @endif
            </div>
        @else
            <div class="mb-6 text-center">
                <h1 class="text-lg font-semibold tracking-tight text-slate-900">Masukkan PIN harian</h1>
                <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-slate-500">
                    PIN {{ config('walikelas.pin_length', 6) }} angka ada di pesan WhatsApp yang sama dengan tautan ini.
                </p>
            </div>

            <form method="POST"
                  action="{{ route('magic.verify', $session->token) }}"
                  class="space-y-5"
                  x-data="{ loading: false }"
                  x-on:submit="loading = true">
                @csrf

                <div>
                    <label for="pin" class="sr-only">PIN harian</label>
                    <input
                        id="pin"
                        name="pin"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        required
                        autofocus
                        maxlength="12"
                        placeholder="{{ str_repeat('•', (int) config('walikelas.pin_length', 6)) }}"
                        @class([
                            'block w-full rounded border bg-white py-3.5 text-center font-mono text-3xl tabular-nums tracking-[0.3em] text-slate-900',
                            'placeholder:tracking-[0.3em] placeholder:text-slate-300',
                            'transition-colors focus:outline-none focus:ring-1',
                            'border-rose-400 focus:border-rose-600 focus:ring-rose-600' => $errors->has('pin'),
                            'border-slate-300 focus:border-indigo-600 focus:ring-indigo-600' => ! $errors->has('pin'),
                        ])
                        @if($errors->has('pin')) aria-invalid="true" aria-describedby="pin-error" @endif
                    >
                    @error('pin')
                        <p id="pin-error" class="form-error mt-2 text-center" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="btn-primary h-11 w-full"
                        x-bind:disabled="loading"
                        x-bind:class="loading && 'pointer-events-none opacity-60'">
                    <span x-show="!loading">Buka daftar siswa</span>
                    <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                        <span class="spinner spinner--white"></span> Memeriksa PIN…
                    </span>
                </button>
            </form>

            <dl class="mt-5 space-y-1.5 border-t border-slate-200 pt-4 text-xs">
                <div class="flex items-baseline justify-between gap-3">
                    <dt class="text-slate-500">Kelas</dt>
                    <dd class="font-medium text-slate-800">{{ $session->classroom->name }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-3">
                    <dt class="text-slate-500">Tanggal</dt>
                    <dd class="font-medium text-slate-800">{{ $session->session_date->translatedFormat('l, d M Y') }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-3">
                    <dt class="text-slate-500">Batas waktu</dt>
                    <dd class="angka font-medium text-slate-800">{{ $session->expires_at->format('H:i') }} WIB</dd>
                </div>
            </dl>
        @endif
    </div>
@endsection
