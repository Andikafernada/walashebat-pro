@extends('layouts.public')
@section('title', 'Masukkan PIN')
@section('heading', 'Absensi '.$session->classroom->name)
@section('step', '1')
@section('content')

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

        @if ($locked)
            {{-- Tautan mati. Yang dibutuhkan petugas di sini bukan penjelasan
                 teknis, tapi satu langkah berikutnya yang jelas. --}}
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                    <svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>

                @if ($session->status === 'cancelled')
                    <h1 class="text-lg font-bold text-slate-900">Sesi ini dibatalkan</h1>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Wali kelas membatalkan sesi absensi ini. Tunggu tautan baru dari wali kelas.
                    </p>
                @elseif ($session->isExpired())
                    <h1 class="text-lg font-bold text-slate-900">Tautan sudah kedaluwarsa</h1>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Batas waktunya lewat pada {{ $session->expires_at->translatedFormat('d M Y, H:i') }} WIB.
                        Minta wali kelas mengirim tautan baru.
                    </p>
                @else
                    <h1 class="text-lg font-bold text-slate-900">Tautan tidak berlaku</h1>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Minta wali kelas mengirim tautan absensi yang baru.
                    </p>
                @endif
            </div>
        @else
            <div class="mb-6 text-center">
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Masukkan PIN harian</h1>
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
                            'block w-full rounded-2xl border bg-white py-4 text-center text-3xl font-bold tabular-nums tracking-[0.35em] text-slate-900',
                            'placeholder:tracking-[0.35em] placeholder:text-slate-300',
                            'transition focus:outline-none focus:ring-4',
                            'border-rose-300 focus:border-rose-500 focus:ring-rose-500/15' => $errors->has('pin'),
                            'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500/15' => ! $errors->has('pin'),
                        ])
                        @if($errors->has('pin')) aria-invalid="true" aria-describedby="pin-error" @endif
                    >
                    @error('pin')
                        <p id="pin-error" class="mt-2.5 flex items-start justify-center gap-1.5 text-center text-sm font-medium text-rose-600" role="alert">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <button type="submit"
                        class="btn-primary w-full py-4 text-base"
                        x-bind:disabled="loading"
                        x-bind:class="loading && 'pointer-events-none opacity-60'">
                    <span x-show="!loading" class="inline-flex items-center gap-2">
                        Buka daftar siswa
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/>
                        </svg>
                    </span>
                    <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                        <span class="spinner spinner--white"></span> Memeriksa PIN…
                    </span>
                </button>
            </form>

            <dl class="mt-6 space-y-2 border-t border-slate-100 pt-5 text-xs">
                <div class="flex items-baseline justify-between gap-3">
                    <dt class="text-slate-500">Kelas</dt>
                    <dd class="font-semibold text-slate-800">{{ $session->classroom->name }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-3">
                    <dt class="text-slate-500">Tanggal</dt>
                    <dd class="font-semibold text-slate-800">{{ $session->session_date->translatedFormat('l, d M Y') }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-3">
                    <dt class="text-slate-500">Batas waktu</dt>
                    <dd class="font-semibold tabular-nums text-slate-800">{{ $session->expires_at->format('H:i') }} WIB</dd>
                </div>
            </dl>
        @endif
    </div>
@endsection
