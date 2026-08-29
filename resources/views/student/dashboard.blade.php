@extends('layouts.student')
@section('title', 'Dashboard')

@section('content')
<div class="p-6 lg:p-8 space-y-6 max-w-5xl mx-auto pb-12">
    <!-- Welcome Header -->
    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-800 font-extrabold text-xl shrink-0 shadow-2xs">
                <span>{{ substr($student->name, 0, 2) }}</span>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Selamat datang, {{ $student->name }}!</h1>
                <p class="text-xs text-slate-500 mt-0.5">{{ $class->name }} &bull; Tahun Pelajaran {{ $class->academic_year }}</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Catatan Karakter</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $portfolioStats['records'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Kehadiran</p>
            <p class="mt-1 text-2xl font-extrabold {{ $attendanceStats['rate'] >= 85 ? 'text-emerald-700' : ($attendanceStats['rate'] >= 75 ? 'text-amber-700' : 'text-rose-700') }}">{{ $attendanceStats['rate'] }}%</p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Poin Disiplin</p>
            <p class="mt-1 text-2xl font-extrabold {{ $violationStats['points'] < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $violationStats['points'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Badge Diraih</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $portfolioStats['badges'] }}</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ route('student.portfolio') }}" class="group bg-white rounded-2xl border border-emerald-200 hover:border-emerald-400 shadow-xs p-5 transition-all block">
            <h3 class="text-sm font-extrabold text-slate-900 group-hover:text-emerald-700 transition-colors">Portofolio Karakter</h3>
            <p class="text-xs text-slate-500 mt-0.5">Catat pencapaian dan refleksi dirimu</p>
        </a>

        <a href="{{ route('student.biodata') }}" class="group bg-white rounded-2xl border border-emerald-200 hover:border-emerald-400 shadow-xs p-5 transition-all block">
            <h3 class="text-sm font-extrabold text-slate-900 group-hover:text-emerald-700 transition-colors">Biodata Diri</h3>
            <p class="text-xs text-slate-500 mt-0.5">Lihat dan perbarui data diri serta kontak orang tua</p>
        </a>
    </div>

    <!-- Recent Records -->
    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
        <h2 class="text-sm font-extrabold text-slate-900 mb-4 border-b border-emerald-100 pb-3">Catatan Terbaru</h2>
        @if($records->isEmpty())
            <div class="py-8 text-center text-xs text-slate-400">
                <p class="font-bold text-slate-700">Belum ada catatan</p>
                <a href="{{ route('student.portfolio') }}" class="text-emerald-700 font-bold hover:underline mt-1 inline-block">Mulai catat pencapaian &rarr;</a>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($records->take(5) as $record)
                    <div class="flex items-center gap-3 py-3.5 hover:bg-slate-50/50 transition-colors">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl font-bold shrink-0 {{ $record->type === 'positive' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                            @if($record->type === 'positive')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-900">{{ $record->title }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5 font-mono">{{ $record->dimension?->name }} &bull; {{ $record->record_date->format('d M Y') }}</p>
                        </div>
                        <div class="text-xs font-extrabold font-mono {{ $record->score > 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $record->score > 0 ? '+' : '' }}{{ $record->score }}
                        </div>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('student.portfolio') }}" class="block text-center text-xs font-bold text-emerald-700 hover:underline mt-4 pt-3 border-t border-slate-100">
                Lihat semua catatan &rarr;
            </a>
        @endif
    </div>
</div>
@endsection
