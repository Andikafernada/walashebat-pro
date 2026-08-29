@extends('layouts.app')

@section('title', 'Cetak Kartu Pelajar & QR Presensi - ' . $classroom->name)

@section('content')
<div class="space-y-6 pb-12">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 font-semibold" aria-label="Breadcrumb">
                <a href="{{ route('classes.index') }}" class="hover:text-emerald-700">Daftar Kelas</a>
                <span>/</span>
                <a href="{{ route('classes.students.index', $classroom) }}" class="hover:text-emerald-700">{{ $classroom->name }}</a>
                <span>/</span>
                <span class="text-slate-900">Kartu Pelajar &amp; QR</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Kartu Pelajar &amp; QR Presensi Digital</h1>
            <p class="text-xs sm:text-sm text-slate-600">Layout standar A4 (8 kartu per lembar) siap dicetak, digunting, atau dilaminasi.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('classes.students.cards.pdf', $classroom) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs sm:text-sm font-bold text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-700 transition-colors">
                <span>📥</span>
                <span>Unduh PDF Siap Cetak (A4)</span>
            </a>
        </div>
    </div>

    @include('partials.class-nav', ['classroom' => $classroom])
    @include('partials.flash')

    {{-- Kartu Grid Preview --}}
    <div class="bg-slate-100 p-6 rounded-3xl border border-slate-200">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($students as $st)
                <div class="bg-white rounded-2xl border-2 border-emerald-300 shadow-sm overflow-hidden flex flex-col justify-between" style="aspect-ratio: 85/54;">
                    {{-- Header Kartu --}}
                    <div class="bg-gradient-to-r from-emerald-700 to-teal-700 text-white p-2.5 flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="text-[9px] uppercase tracking-wider font-extrabold text-emerald-200 truncate">
                                {{ $user->school_name ?: 'WaliKelas Pro' }}
                            </p>
                            <p class="text-[10px] font-black tracking-tight truncate">KARTU PRESENSI SISWA</p>
                        </div>
                        <span class="text-[9px] bg-emerald-800/80 px-1.5 py-0.5 rounded font-mono font-bold">{{ $classroom->name }}</span>
                    </div>

                    {{-- Isi Kartu --}}
                    <div class="p-3 flex items-center justify-between gap-2 flex-1">
                        <div class="min-w-0 flex-1 space-y-1">
                            <p class="text-xs font-black text-slate-900 leading-tight line-clamp-2">{{ $st->name }}</p>
                            <p class="text-[10px] text-slate-500 font-mono font-semibold">NIS: {{ $st->nis ?: '—' }}</p>
                            <p class="text-[10px] text-slate-500 font-semibold capitalize">JK: {{ $st->gender === 'L' ? 'Laki-laki' : ($st->gender === 'P' ? 'Perempuan' : '—') }}</p>
                        </div>

                        {{-- QR Code --}}
                        <div class="shrink-0 p-1 bg-white border border-slate-200 rounded-lg shadow-2xs text-center">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($st->nis ?: $st->id) }}"
                                 alt="QR {{ $st->name }}"
                                 class="w-14 h-14 mx-auto object-contain">
                            <span class="text-[8px] font-mono font-bold text-slate-500 block mt-0.5">SCAN ME</span>
                        </div>
                    </div>

                    {{-- Footer Kartu --}}
                    <div class="bg-slate-50 border-t border-slate-100 px-3 py-1 text-[8px] text-slate-400 font-semibold flex items-center justify-between">
                        <span>WaliKelas Digital ID</span>
                        <span class="font-mono">{{ date('Y') }}</span>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-slate-400 bg-white rounded-2xl border border-dashed border-slate-300">
                    <p class="text-2xl">👥</p>
                    <p class="text-sm font-bold text-slate-700 mt-2">Belum ada siswa di kelas ini</p>
                    <p class="text-xs text-slate-400">Tambahkan atau impor siswa terlebih dahulu.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
