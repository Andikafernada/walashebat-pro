@extends('layouts.app')

@section('title', 'Cetak Kartu Pelajar & Kartu Ujian QR - ' . $classroom->name)

@section('content')
@php
    $themeColors = [
        'emerald' => 'bg-emerald-600 text-white',
        'navy'    => 'bg-blue-800 text-white',
        'maroon'  => 'bg-rose-800 text-white',
        'gold'    => 'bg-amber-700 text-white',
        'purple'  => 'bg-purple-800 text-white',
    ];

    $cardGradients = [
        'emerald' => 'from-emerald-700 to-teal-700 border-emerald-300',
        'navy'    => 'from-blue-800 to-indigo-800 border-blue-300',
        'maroon'  => 'from-rose-800 to-red-800 border-rose-300',
        'gold'    => 'from-amber-700 to-yellow-700 border-amber-300',
        'purple'  => 'from-purple-800 to-fuchsia-800 border-purple-300',
    ];
@endphp

<div class="space-y-6 pb-12">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 font-semibold" aria-label="Breadcrumb">
                <a href="{{ route('classes.index') }}" class="hover:text-emerald-700">Daftar Kelas</a>
                <span>/</span>
                <a href="{{ route('classes.students.index', $classroom) }}" class="hover:text-emerald-700">{{ $classroom->name }}</a>
                <span>/</span>
                <span class="text-slate-900">Kartu Siswa</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                {{ $mode === 'ujian' ? '📇 Kartu Peserta Ujian / Asesmen' : '📇 Kartu Pelajar & QR Presensi' }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-600">Layout presisi standar A4 (8 kartu per lembar) siap dicetak, digunting, dan dilaminating.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('classes.students.cards.pdf', array_merge([$classroom], request()->query())) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs sm:text-sm font-black text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-700 transition-all hover:scale-105">
                <span>📥</span>
                <span>Unduh PDF Siap Cetak (A4)</span>
            </a>
        </div>
    </div>

    @include('partials.class-nav', ['classroom' => $classroom])
    @include('partials.flash')

    {{-- KONTROL KUSTOMISASI KARTU (TEMA & MODE) --}}
    <div class="bg-white rounded-3xl border border-emerald-200 p-4 sm:p-5 shadow-xs space-y-4">
        <form method="GET" action="{{ route('classes.students.qr-cards', $classroom) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 items-end">
            {{-- 1. Pilihan Mode --}}
            <div>
                <label for="mode" class="block text-xs font-bold text-slate-700 mb-1">Tipe / Format Kartu:</label>
                <select id="mode" name="mode" class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-900 focus:border-emerald-500 focus:outline-none">
                    <option value="pelajar" @selected($mode === 'pelajar')>🏷️ Kartu Pelajar &amp; Presensi QR</option>
                    <option value="ujian" @selected($mode === 'ujian')>📝 Kartu Peserta Ujian / STS / SAS</option>
                </select>
            </div>

            {{-- 2. Pilihan Tema Warna --}}
            <div>
                <label for="theme" class="block text-xs font-bold text-slate-700 mb-1">Tema Warna Kartu:</label>
                <select id="theme" name="theme" class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-900 focus:border-emerald-500 focus:outline-none">
                    <option value="emerald" @selected($theme === 'emerald')>🟢 Hijau Emerald (Default)</option>
                    <option value="navy" @selected($theme === 'navy')>🔵 Biru Navy / Edukasi</option>
                    <option value="maroon" @selected($theme === 'maroon')>🔴 Merah Marun</option>
                    <option value="gold" @selected($theme === 'gold')>🟡 Emas / Kuning Elegan</option>
                    <option value="purple" @selected($theme === 'purple')>🟣 Ungu Royal</option>
                </select>
            </div>

            {{-- 3. Tombol Terapkan --}}
            <div>
                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-slate-900 hover:bg-black text-white text-xs font-black shadow-xs transition-colors">
                    <span>⚡ Terapkan Tampilan</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Kartu Grid Preview --}}
    <div class="bg-slate-100/80 p-4 sm:p-6 rounded-3xl border border-slate-200">
        <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pratinjau Hasil Cetak ({{ $students->count() }} Siswa)</span>
            <span class="text-xs font-extrabold text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-full">
                {{ $mode === 'ujian' ? 'Mode Kartu Ujian' : 'Mode Kartu Pelajar' }}
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($students as $st)
                <div class="bg-white rounded-2xl border-2 {{ $cardGradients[$theme] ?? $cardGradients['emerald'] }} shadow-sm overflow-hidden flex flex-col justify-between" style="min-height: 190px;">
                    {{-- Header Kartu --}}
                    <div class="bg-gradient-to-r {{ $cardGradients[$theme] ?? $cardGradients['emerald'] }} text-white p-2.5 flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="text-[9px] uppercase tracking-wider font-extrabold opacity-80 truncate">
                                {{ $schoolName }}
                            </p>
                            <p class="text-[10.5px] font-black tracking-tight truncate">
                                {{ $mode === 'ujian' ? 'KARTU PESERTA ASESMEN' : 'KARTU PRESENSI DIGITAL' }}
                            </p>
                        </div>
                        <span class="text-[9px] bg-black/30 px-2 py-0.5 rounded-md font-mono font-black">{{ $classroom->name }}</span>
                    </div>

                    {{-- Isi Kartu --}}
                    <div class="p-3 flex items-center justify-between gap-2 flex-1">
                        <div class="min-w-0 flex-1 space-y-0.5">
                            <p class="text-xs font-black text-slate-900 leading-snug truncate">{{ $st->name }}</p>
                            <p class="text-[10px] text-slate-500 font-semibold">NIS: <span class="font-bold text-slate-800">{{ $st->nis ?: '—' }}</span></p>
                            <p class="text-[10px] text-slate-500 font-semibold">JK: <span class="font-bold text-slate-800">{{ $st->gender === 'L' ? 'Laki-laki' : ($st->gender === 'P' ? 'Perempuan' : '—') }}</span></p>
                            <p class="text-[10px] text-slate-400 font-medium">TA: {{ date('Y') }}/{{ date('Y')+1 }}</p>
                        </div>

                        <div class="text-center shrink-0">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($st->nis ?: $st->id) }}"
                                 class="w-14 h-14 rounded-lg border border-slate-200 p-0.5 bg-white shadow-2xs"
                                 alt="QR">
                            <span class="block text-[8px] font-bold text-slate-400 uppercase mt-0.5">SCAN QR</span>
                        </div>
                    </div>

                    {{-- Footer Kartu --}}
                    <div class="bg-slate-50 border-t border-slate-100 px-3 py-1 text-[8.5px] text-slate-400 font-bold flex items-center justify-between">
                        <span>WaliKelas Digital Card</span>
                        <span>{{ $classroom->name }}</span>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-slate-400 text-xs">
                    Belum ada siswa aktif di kelas ini.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
