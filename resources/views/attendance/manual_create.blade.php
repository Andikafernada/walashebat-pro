@extends('layouts.app')

@section('title', 'Input Absensi Manual - ' . $class->name)

@section('content')
@php
    $pilihan = [
        'hadir' => 'H',
        'terlambat' => 'T',
        'sakit' => 'S',
        'izin' => 'I',
        'alfa' => 'A',
    ];
@endphp

<div class="space-y-5 pb-12">

    <div class="page-header">
        <div class="min-w-0">
            <nav class="text-xs font-semibold uppercase tracking-wider text-slate-400 flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.attendance.index', $class) }}" class="hover:text-slate-600">Absensi</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Input Manual</span>
            </nav>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Input Absensi Manual / Susulan</h1>
            <p class="mt-1 text-xs text-slate-500">Isi presensi langsung tanpa magic link — untuk tanggal yang sudah lewat.</p>
        </div>

        <a href="{{ route('classes.attendance.index', $class) }}" class="shrink-0 inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Kembali ke Daftar Absensi</a>
    </div>

    @include('partials.class-nav', ['classroom' => $class])

    @include('partials.flash')

    <form method="POST" action="{{ route('classes.attendance.manual.store', $class) }}" class="space-y-4">
        @csrf

        <section class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
            <div class="flex items-center justify-between px-4 pt-4 pb-3 border-b border-emerald-100">
                <h2 class="text-sm font-extrabold text-slate-900">Keterangan Sesi</h2>
            </div>
            <div class="p-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="session_date" class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Absensi <span class="text-rose-500">*</span></label>
                    <input id="session_date" type="date" name="session_date" required value="{{ date('Y-m-d') }}" 
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <p class="mt-1 text-xs text-slate-400">Boleh tanggal lampau.</p>
                </div>

                <div>
                    <label for="title" class="block text-xs font-semibold text-slate-700 mb-1">Judul Sesi <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input id="title" type="text" name="title" value="{{ old('title') }}" placeholder="cth: Absensi Susulan Juli" 
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                @if ($class->kelasAjar())
                    @php $mapelPilihan = $class->mapelDiampu(); @endphp
                    <div>
                        <label for="mapel" class="block text-xs font-semibold text-slate-700 mb-1">Mata Pelajaran</label>
                        @if (count($mapelPilihan) > 1)
                            <select id="mapel" name="mapel" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                @foreach ($mapelPilihan as $m)
                                    <option value="{{ $m }}" @selected(old('mapel') === $m)>{{ $m }}</option>
                                @endforeach
                            </select>
                        @else
                            <input id="mapel" type="text" name="mapel" value="{{ old('mapel', $mapelPilihan[0] ?? '') }}" placeholder="cth: Matematika" 
                                   class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @endif
                        <p class="mt-1 text-xs text-slate-400">Atur daftar mapel di halaman Ubah Kelas.</p>
                    </div>

                    <div>
                        <label for="materi" class="block text-xs font-semibold text-slate-700 mb-1">Materi Hari Ini <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input id="materi" type="text" name="materi" value="{{ old('materi') }}" placeholder="cth: Sistem bilangan biner" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <p class="mt-1 text-xs text-slate-400">Dipakai untuk jurnal mengajar.</p>
                    </div>
                @endif
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
            <div class="flex items-center justify-between px-4 pt-4 pb-3 border-b border-emerald-100">
                <h2 class="text-sm font-extrabold text-slate-900">Roster Siswa</h2>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Bawaan: hadir</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th scope="col" class="py-2.5 px-3 w-10 text-right">No</th>
                            <th scope="col" class="py-2.5 px-3">Nama Siswa</th>
                            <th scope="col" class="py-2.5 px-3 text-center">Status</th>
                            <th scope="col" class="py-2.5 px-3">Catatan / Alasan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($students as $idx => $st)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-2.5 px-3 text-right font-mono text-xs text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3">
                                    <span class="font-bold text-slate-900">{{ $st->name }}</span>
                                    <span class="block font-mono text-[10px] font-normal text-slate-400">{{ $st->nis ?: '—' }}</span>
                                </td>
                                <td class="py-2.5 px-3">
                                    <fieldset class="flex justify-center">
                                        <legend class="sr-only">Status kehadiran {{ $st->name }}</legend>
                                        <div class="inline-flex overflow-hidden rounded-xl border border-slate-200">
                                            @foreach ($pilihan as $nilai => $huruf)
                                                <label class="cursor-pointer border-r border-slate-200 last:border-r-0" title="{{ Str::title($nilai) }}">
                                                    <input type="radio" class="peer sr-only" name="attendance[{{ $st->id }}]" value="{{ $nilai }}" @checked($nilai === 'hadir')>
                                                    <span class="block px-2.5 py-1 font-mono text-xs text-slate-500 transition-colors peer-hover:bg-slate-50 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-checked:bg-slate-900 peer-checked:text-white">{{ $huruf }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                </td>
                                <td class="py-2.5 px-3">
                                    <label for="note-{{ $st->id }}" class="sr-only">Catatan untuk {{ $st->name }}</label>
                                    <input id="note-{{ $st->id }}" type="text" name="notes[{{ $st->id }}]" placeholder="cth: surat dokter" 
                                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('classes.attendance.index', $class) }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Batal</a>
            <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700 transition-colors">Simpan Absensi Manual Tanggal Ini</button>
        </div>
    </form>
</div>
@endsection
