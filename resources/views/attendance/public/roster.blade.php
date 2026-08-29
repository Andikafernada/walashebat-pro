@extends('layouts.public')

@section('title', 'Presensi Sesi '.($session->title ?: $session->formattedDate()))

@section('content')

@php
    $gaya = [
        'hadir'     => ['kode' => 'H', 'label' => 'Hadir',     'teks' => 'text-emerald-700'],
        'terlambat' => ['kode' => 'T', 'label' => 'Terlambat', 'teks' => 'text-amber-700'],
        'sakit'     => ['kode' => 'S', 'label' => 'Sakit',     'teks' => 'text-sky-700'],
        'izin'      => ['kode' => 'I', 'label' => 'Izin',      'teks' => 'text-purple-700'],
        'alfa'      => ['kode' => 'A', 'label' => 'Alfa',      'teks' => 'text-rose-700'],
    ];
@endphp

{{-- Header Kertas --}}
<div class="mb-4 rounded-2xl border border-emerald-200 bg-white p-4 shadow-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">ISIAN REKAP PRESENSI</p>
            <h1 class="mt-0.5 text-lg font-bold text-slate-900">
                {{ $session->classroom->name ?? 'Kelas' }}
            </h1>
            <p class="mt-0.5 text-xs text-slate-600">
                {{ $session->formattedDate() }}
                @if ($session->title) &middot; {{ $session->title }} @endif
            </p>
        </div>
        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 border border-emerald-200">
            Aktif
        </span>
    </div>
</div>

@if ($students->isEmpty())
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs font-bold text-amber-900">
        <p>Belum ada siswa terdaftar di kelas ini.</p>
    </div>
@else

<form method="POST"
      action="{{ route('magic.submit', $session->token) }}"
      x-data="rosterAbsensi({{ $students->count() }})"
      x-on:change="hitung()"
      x-on:submit="mengirim = true">
    @csrf

    <div class="sticky top-0 z-20 -mx-4 mb-3 border-b border-emerald-100 bg-slate-50/95 backdrop-blur-xs px-4 py-3">
        <div class="grid grid-cols-5 gap-1.5 text-center">
            @foreach ($gaya as $key => $g)
                <div class="rounded-xl border border-emerald-100 bg-white py-1.5 shadow-2xs">
                    <p class="angka text-base font-extrabold leading-none {{ $g['teks'] }}" x-text="jumlah.{{ $key }}">0</p>
                    <p class="mt-1 font-mono text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ $g['kode'] }}</p>
                </div>
            @endforeach
        </div>

        <p class="mt-2 text-center text-xs" x-cloak>
            <span x-show="sisa > 0" class="font-semibold text-slate-600">
                <span x-text="sisa"></span> siswa belum ditandai
            </span>
            <span x-show="sisa === 0" class="font-bold text-emerald-700">
                Semua {{ $students->count() }} siswa sudah ditandai
            </span>
        </p>
    </div>

    <div class="mb-3 flex items-center gap-2 rounded-2xl border border-emerald-200 bg-white p-2.5 shadow-xs">
        <button type="button"
                x-on:click="tandaiSemuaHadir()"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors h-10 flex-1">Tandai Semua Hadir</button>
        <button type="button"
                x-on:click="kosongkan()"
                class="inline-flex items-center justify-center rounded-xl border border-transparent px-3 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-100 transition-colors h-10">Kosongkan</button>
    </div>

    @error('attendance')
        <p class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs font-semibold text-rose-700 mb-3" role="alert">{{ $message }}</p>
    @enderror

    <ul class="space-y-2.5">
        @foreach ($students as $s)
            @php
            $terpilih = old('attendance.'.$s->id);
            $presetExcuse = $excuses->has($s->id) ? $excuses->get($s->id) : null;
            $dariOrangTua = $presetExcuse ? $presetExcuse->jenis : null;
            if ($terpilih === null && $dariOrangTua) {
                $terpilih = $dariOrangTua;
            }
        @endphp
            <li class="rounded-2xl border border-emerald-200 bg-white p-3.5 shadow-xs"
                x-data="{ status: @js($terpilih) }"
                x-on:roster-isi.window="status = $event.detail">

                <div class="flex items-start gap-3">
                    <span class="mt-0.5 w-6 shrink-0 text-right font-mono text-xs text-slate-400">{{ $loop->iteration }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold leading-snug text-slate-900">{{ $s->name }}</p>
                        @if ($s->nis)
                            <p class="mt-0.5 font-mono text-[10px] text-slate-400">NIS {{ $s->nis }}</p>
                        @endif
                    </div>
                </div>

                @if ($presetExcuse)
                    @php $lapor = $presetExcuse; @endphp
                    <div class="mt-2.5 rounded-xl p-2.5 text-[11px] space-y-1 border"
                       :class="status === '{{ $dariOrangTua }}' ? 'bg-emerald-50 border-emerald-200 text-emerald-950' : 'bg-amber-50 border-amber-200 text-amber-950'">
                        <div class="flex items-center justify-between flex-wrap gap-1">
                            <div class="flex items-center gap-1 font-bold">
                                <span>📩</span>
                                <span>Laporan Ortu: <strong>{{ $lapor->jenis === 'sakit' ? 'Sakit' : 'Izin' }}</strong></span>
                            </div>
                            <div class="flex items-center gap-1">
                                @if ($lapor->parent_phone_verified)
                                    <span class="px-1.5 py-0.5 rounded-md bg-emerald-200 text-emerald-950 font-bold text-[10px] border border-emerald-300">
                                        🔒 Verif No. HP
                                    </span>
                                @endif
                                @if ($lapor->attachment_path)
                                    <a href="{{ asset('storage/' . $lapor->attachment_path) }}" target="_blank" class="px-2 py-0.5 rounded-md bg-white border border-emerald-300 text-emerald-800 font-bold text-[10px] hover:bg-emerald-100 transition-colors shadow-2xs">
                                        📸 lihat Foto Surat
                                    </a>
                                @endif
                            </div>
                        </div>
                        @if ($lapor->keterangan)
                            <p class="text-slate-600 italic leading-tight">&ldquo;{{ $lapor->keterangan }}&rdquo;</p>
                        @endif
                    </div>
                @endif

                <fieldset class="mt-2.5">
                    <legend class="sr-only">Status kehadiran {{ $s->name }}</legend>
                    <div class="grid grid-cols-5 gap-1.5">
                        @foreach ($gaya as $key => $g)
                            <label class="cursor-pointer">
                                <input type="radio"
                                       class="peer sr-only"
                                       name="attendance[{{ $s->id }}]"
                                       value="{{ $key }}"
                                       required
                                       x-model="status"
                                       :checked="status === '{{ $key }}'">
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
                    <input id="note-{{ $s->id }}"
                           type="text"
                           name="notes[{{ $s->id }}]"
                           maxlength="200"
                           value="{{ old('notes.'.$s->id) }}"
                           placeholder="Keterangan (opsional) — cth: surat dokter"
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
            </li>
        @endforeach
    </ul>

    <div class="sticky bottom-0 z-20 -mx-4 mt-4 border-t border-emerald-100 bg-slate-50/95 backdrop-blur-xs px-4 py-3">
        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-xs font-bold text-white hover:bg-emerald-700 transition-colors shadow-xs"
                x-bind:disabled="mengirim"
                x-bind:class="mengirim && 'pointer-events-none opacity-60'">
            <span x-show="!mengirim">Kirim Absensi</span>
            <span x-show="mengirim" x-cloak class="inline-flex items-center gap-2">
                <span class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"></span> Menyimpan…
            </span>
        </button>
        <p class="mt-2 text-center text-[11px] text-slate-400 font-medium">
            Sekali terkirim, absensi tidak bisa diubah dari tautan ini.
        </p>
    </div>
</form>
@endif
@endsection
