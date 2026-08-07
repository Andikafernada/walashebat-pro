@extends('layouts.app')
@section('title', 'Koreksi Absensi')
@section('content')
    @include('partials.class-nav')

@php
    $gaya = [
        'hadir' => ['label' => 'Hadir', 'aktif' => 'peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white peer-focus-visible:ring-emerald-500/40'],
        'terlambat' => ['label' => 'Terlambat', 'aktif' => 'peer-checked:border-orange-500 peer-checked:bg-orange-500 peer-checked:text-white peer-focus-visible:ring-orange-500/40'],
        'sakit' => ['label' => 'Sakit', 'aktif' => 'peer-checked:border-amber-500 peer-checked:bg-amber-500 peer-checked:text-white peer-focus-visible:ring-amber-500/40'],
        'izin' => ['label' => 'Izin', 'aktif' => 'peer-checked:border-sky-600 peer-checked:bg-sky-600 peer-checked:text-white peer-focus-visible:ring-sky-500/40'],
        'alfa' => ['label' => 'Alfa', 'aktif' => 'peer-checked:border-rose-600 peer-checked:bg-rose-600 peer-checked:text-white peer-focus-visible:ring-rose-500/40'],
    ];
@endphp

<div class="page-header">
    <div>
        <h2 class="page-header__title">Koreksi Absensi</h2>
        <p class="page-header__subtitle">
            {{ $session->session_date->translatedFormat('l, d F Y') }}
            @if (($session->sequence ?? 1) > 1) · sesi ke-{{ $session->sequence }} @endif
        </p>
    </div>
    <a href="{{ route('classes.attendance.show', [$classroom, $session]) }}" class="btn-ghost btn-ghost--sm">
        Kembali ke sesi
    </a>
</div>

<div class="alert alert--warning mb-6">
    <p class="alert__title">Setiap perubahan tercatat</p>
    <p class="alert__body">
        Status yang Anda ubah dicatat di riwayat koreksi beserta nama Anda, waktu, dan alasannya.
        Ini yang membedakan koreksi yang sah dari absensi yang diubah diam-diam.
    </p>
</div>

<form method="POST" action="{{ route('classes.attendance.update', [$classroom, $session]) }}"
      x-data="{ mengirim: false }" x-on:submit="mengirim = true">
    @csrf
    @method('PATCH')

    <div class="card-elevated mb-6">
        <div class="form-group mb-0">
            <label for="reason" class="form-label form-label--required">Alasan koreksi</label>
            <input type="text" id="reason" name="reason" value="{{ old('reason') }}"
                   required minlength="3" maxlength="255"
                   placeholder="Contoh: Surat dokter menyusul untuk Fitriani"
                   class="form-input @error('reason') form-input--error @enderror">
            <p class="form-hint">Berlaku untuk semua status yang berubah pada penyimpanan ini.</p>
            @error('reason')
                <p class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <ul class="space-y-2">
        @foreach ($students as $s)
            @php
                $baris = $absensi->get($s->id);
                $terpilih = old('attendance.'.$s->id, $baris->status ?? null);
            @endphp
            <li class="rounded-2xl border border-slate-200 bg-white p-3"
                x-data="{ status: @js($terpilih) }">

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold leading-snug text-slate-900">
                            {{ $loop->iteration }}. {{ $s->name }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-slate-400">
                            @if ($s->nis) NIS {{ $s->nis }} @endif
                            @if ($baris === null)
                                <span class="ml-1 font-semibold text-amber-600">· belum terisi petugas</span>
                            @endif
                        </p>
                    </div>
                    @if ($baris && $baris->revisions_count > 0)
                        <span class="badge badge--amber shrink-0">{{ $baris->revisions_count }}× dikoreksi</span>
                    @endif
                </div>

                <fieldset class="mt-2.5">
                    <legend class="sr-only">Status kehadiran {{ $s->name }}</legend>
                    <div class="grid grid-cols-5 gap-1">
                        @foreach ($gaya as $key => $g)
                            <label class="cursor-pointer">
                                <input type="radio" class="peer sr-only"
                                       name="attendance[{{ $s->id }}]" value="{{ $key }}" required
                                       x-model="status" @checked($terpilih === $key)>
                                <span class="block rounded-xl border border-slate-200 px-0.5 py-2 text-center text-[10px] font-semibold leading-tight text-slate-500 transition peer-hover:border-slate-300 peer-hover:bg-slate-50 peer-focus-visible:ring-2 {{ $g['aktif'] }}">
                                    {{ $g['label'] }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div x-show="status && status !== 'hadir'" x-cloak class="mt-2.5">
                    <label for="note-{{ $s->id }}" class="sr-only">Catatan untuk {{ $s->name }}</label>
                    <input id="note-{{ $s->id }}" type="text" name="notes[{{ $s->id }}]" maxlength="200"
                           value="{{ old('notes.'.$s->id, $baris->note ?? '') }}"
                           placeholder="Keterangan (opsional)"
                           class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/15">
                </div>

                {{-- Riwayat koreksi ditampilkan di tempat, bukan di halaman
                     terpisah: konteksnya justru dibutuhkan saat menimbang
                     apakah perlu dikoreksi lagi. --}}
                @if ($baris && $baris->revisions->isNotEmpty())
                    <details class="mt-2.5">
                        <summary class="cursor-pointer text-[11px] font-medium text-slate-500 hover:text-slate-700">
                            Riwayat koreksi
                        </summary>
                        <ul class="mt-1.5 space-y-1 border-l-2 border-slate-100 pl-3">
                            @foreach ($baris->revisions as $r)
                                <li class="text-[11px] leading-relaxed text-slate-500">
                                    <span class="font-semibold text-slate-700">{{ $r->from_status }} → {{ $r->to_status }}</span>
                                    · {{ $r->created_at->translatedFormat('d M Y, H:i') }}
                                    · {{ $r->user->name ?? '—' }}
                                    <br><span class="italic">{{ $r->reason }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </li>
        @endforeach
    </ul>

    @if ($students->isEmpty())
        <div class="empty-state">
            <p class="empty-state__title">Belum ada siswa aktif</p>
        </div>
    @else
        <div class="sticky bottom-0 z-20 -mx-4 mt-4 border-t border-slate-200 bg-slate-50/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-2xl sm:border sm:px-4">
            <button type="submit" class="btn-primary w-full py-3.5"
                    x-bind:disabled="mengirim" x-bind:class="mengirim && 'pointer-events-none opacity-60'">
                <span x-show="!mengirim" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan koreksi
                </span>
                <span x-show="mengirim" x-cloak class="inline-flex items-center gap-2">
                    <span class="spinner spinner--white"></span> Menyimpan…
                </span>
            </button>
        </div>
    @endif
</form>
@endsection
