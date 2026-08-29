@extends('layouts.public')
@section('title', 'Masukkan PIN')
@section('heading', 'Absensi '.$session->classroom->name)
@section('step', '1')
@section('content')

    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">

        @if ($locked)
            <div class="text-center">
                @if ($session->status === 'cancelled')
                    <h1 class="text-lg font-bold tracking-tight text-slate-900">Sesi Ini Dibatalkan</h1>
                    <p class="mt-2 text-xs leading-relaxed text-slate-600">
                        Wali kelas membatalkan sesi absensi ini. Tunggu tautan baru dari wali kelas.
                    </p>
                @elseif ($session->isExpired())
                    <h1 class="text-lg font-bold tracking-tight text-slate-900">Tautan Sudah Kedaluwarsa</h1>
                    <p class="mt-2 text-xs leading-relaxed text-slate-600">
                        Batas waktunya lewat pada {{ $session->expires_at->translatedFormat('d M Y, H:i') }} WIB.
                        Minta wali kelas mengirim tautan baru.
                    </p>
                @else
                    <h1 class="text-lg font-bold tracking-tight text-slate-900">Tautan Tidak Berlaku</h1>
                    <p class="mt-2 text-xs leading-relaxed text-slate-600">
                        Minta wali kelas mengirim tautan absensi yang baru.
                    </p>
                @endif
            </div>
        @else
            <div class="mb-6 text-center">
                <h1 class="text-lg font-extrabold tracking-tight text-slate-900">Masukkan PIN Harian</h1>
                <p class="mx-auto mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
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
                            'block w-full rounded-xl bg-white py-3.5 text-center font-mono text-3xl tabular-nums tracking-[0.3em] text-slate-900',
                            'placeholder:tracking-[0.3em] placeholder:text-slate-300',
                            'transition-colors focus:outline-none focus:ring-2',
                            'border border-rose-400 focus:border-rose-600 focus:ring-rose-200' => $errors->has('pin'),
                            'border border-slate-200 focus:border-emerald-600 focus:ring-emerald-200' => ! $errors->has('pin'),
                        ])
                        @if($errors->has('pin')) aria-invalid="true" aria-describedby="pin-error" @endif
                    >
                    @error('pin')
                        <p id="pin-error" class="mt-2 text-center text-xs font-semibold text-rose-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-3 text-xs font-bold text-white hover:bg-emerald-700 transition-colors disabled:opacity-50"
                        x-bind:disabled="loading"
                        x-bind:class="loading && 'pointer-events-none opacity-60'">
                    <span x-show="!loading">Buka Daftar Siswa</span>
                    <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                        <span class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span> Memeriksa PIN…
                    </span>
                </button>
            </form>

            <dl class="mt-5 space-y-2 border-t border-slate-100 pt-4 text-xs">
                <div class="flex items-baseline justify-between gap-3">
                    <dt class="text-slate-400">Kelas</dt>
                    <dd class="font-bold text-slate-800">{{ $session->classroom->name }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-3">
                    <dt class="text-slate-400">Tanggal</dt>
                    <dd class="font-semibold text-slate-800">{{ $session->session_date->translatedFormat('l, d M Y') }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-3">
                    <dt class="text-slate-400">Batas Waktu</dt>
                    <dd class="font-mono font-semibold text-slate-800">{{ $session->expires_at->format('H:i') }} WIB</dd>
                </div>
            </dl>
        @endif
    </div>
@endsection
