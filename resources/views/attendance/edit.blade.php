@extends('layouts.app')
@section('title', 'Koreksi Absensi')
@section('content')
    @include('partials.class-nav')

@php
    $gaya = [
        'hadir' => ['label' => 'Hadir', 'kode' => 'H'],
        'terlambat' => ['label' => 'Terlambat', 'kode' => 'T'],
        'sakit' => ['label' => 'Sakit', 'kode' => 'S'],
        'izin' => ['label' => 'Izin', 'kode' => 'I'],
        'alfa' => ['label' => 'Alfa', 'kode' => 'A'],
    ];
@endphp

<div class="page-header mb-5 flex-wrap">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Koreksi Absensi</h1>
        <p class="text-xs text-slate-500 mt-0.5">
            {{ $session->session_date->translatedFormat('l, d F Y') }}
            @if (($session->sequence ?? 1) > 1) · sesi ke-{{ $session->sequence }} @endif
        </p>
    </div>
    <a href="{{ route('classes.attendance.show', [$classroom, $session]) }}" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
        Kembali ke sesi
    </a>
</div>

<div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 mb-6 flex gap-3">
    <div class="text-amber-600 mt-0.5">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    </div>
    <div>
        <p class="text-xs font-bold text-slate-900">Setiap perubahan tercatat</p>
        <p class="text-xs text-slate-600 mt-0.5">
            Status yang Anda ubah dicatat di riwayat koreksi beserta nama Anda, waktu, dan alasannya.
            Ini yang membedakan koreksi yang sah dari absensi yang diubah diam-diam.
        </p>
    </div>
</div>

<form method="POST" action="{{ route('classes.attendance.update', [$classroom, $session]) }}"
      x-data="{ mengirim: false }" x-on:submit="mengirim = true">
    @csrf
    @method('PATCH')

    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4 mb-6">
        <div>
            <label for="reason" class="block text-xs font-semibold text-slate-700 mb-1">Alasan koreksi <span class="text-rose-500">*</span></label>
            <input type="text" id="reason" name="reason" value="{{ old('reason') }}"
                   required minlength="3" maxlength="255"
                   placeholder="Contoh: Surat dokter menyusul untuk Fitriani"
                   class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('reason') border-rose-400 @enderror">
            <p class="mt-1 text-[11px] text-slate-400">Berlaku untuk semua status yang berubah pada penyimpanan ini.</p>
            @error('reason')
                <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <ul class="space-y-2">
        @foreach ($students as $s)
            @php
                $baris = $absensi->get($s->id);
                $terpilih = old('attendance.'.$s->id, $baris->status ?? null);
            @endphp
            <li class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-xs"
                x-data="{ status: @js($terpilih) }">

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold leading-snug text-slate-900">
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
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 shrink-0">{{ $baris->revisions_count }}× dikoreksi</span>
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
                                <span class="flex flex-col items-center gap-0.5 rounded-xl border border-slate-200 px-0.5 py-1.5 text-center leading-tight text-slate-500 transition-colors peer-hover:bg-slate-50 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-checked:border-slate-900 peer-checked:bg-slate-900 peer-checked:text-white">
                                    <span class="font-mono text-xs font-bold">{{ $g['kode'] }}</span>
                                    <span class="text-[10px] font-semibold">{{ $g['label'] }}</span>
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
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                @if ($baris && $baris->revisions->isNotEmpty())
                    <details class="mt-2.5">
                        <summary class="cursor-pointer text-[11px] font-semibold text-slate-500 hover:text-slate-700">
                            Riwayat koreksi
                        </summary>
                        <ul class="mt-1.5 space-y-1 border-l border-slate-200 pl-3">
                            @foreach ($baris->revisions as $r)
                                <li class="text-[11px] leading-relaxed text-slate-500">
                                    <span class="font-bold text-slate-700">{{ $r->from_status }} → {{ $r->to_status }}</span>
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
        <div class="my-10 text-center">
            <p class="text-sm font-semibold text-slate-800">Belum ada siswa aktif</p>
        </div>
    @else
        <div class="sticky bottom-0 z-20 -mx-4 mt-4 border-t border-slate-200 bg-slate-50/95 px-4 py-3 sm:mx-0 sm:rounded-2xl sm:border sm:px-4 backdrop-blur">
            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-xs font-bold text-white hover:bg-emerald-700 transition-colors disabled:opacity-60"
                    x-bind:disabled="mengirim">
                <span x-show="!mengirim">Simpan Koreksi</span>
                <span x-show="mengirim" x-cloak class="inline-flex items-center gap-2">
                    <span class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span> Menyimpan…
                </span>
            </button>
        </div>
    @endif
</form>
@endsection
