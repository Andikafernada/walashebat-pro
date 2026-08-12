@extends('layouts.student')
@section('title', 'Dashboard')

@section('content')
<div class="p-6 lg:p-8 space-y-6">
    <!-- Welcome Header -->
    <div class="glass-card">
        <div class="flex items-center gap-4">
            <div class="avatar avatar--2xl avatar--primary">
                <span class="avatar-initials">{{ substr($student->name, 0, 2) }}</span>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Selamat datang, {{ $student->name }}!</h1>
                <p class="text-slate-500">{{ $class->name }} • Tahun Pelajaran {{ $class->academic_year }}</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card text-center">
            <div class="text-3xl font-semibold text-indigo-600">{{ $portfolioStats['records'] }}</div>
            <div class="text-sm text-slate-500">Catatan Karakter</div>
        </div>
        <div class="glass-card text-center">
            <div class="text-3xl font-semibold text-emerald-600">{{ $attendanceStats['rate'] }}%</div>
            <div class="text-sm text-slate-500">Kehadiran</div>
        </div>
        <div class="glass-card text-center">
            <div class="text-3xl font-semibold {{ $violationStats['points'] < 0 ? 'text-red-500' : 'text-slate-500' }}">
                {{ $violationStats['points'] }}
            </div>
            <div class="text-sm text-slate-500">Poin Karakter</div>
        </div>
        <div class="glass-card text-center">
            <div class="text-3xl font-semibold text-amber-500">{{ $portfolioStats['badges'] }}</div>
            <div class="text-sm text-slate-500">Badge Diraih</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ route('student.portfolio') }}" class="glass-card hover-lift transition-colors">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded bg-indigo-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13.668m7.088-7.218c2.103-2.048 5.802-3.206 9.912-3.206H21v21m-7.088-7.218c-2.103 2.048-5.802 3.206-9.912 3.206H3m18-17.574V21M12 6.253V3"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">Portofolio Karakter</h3>
                    <p class="text-sm text-slate-500">Catat pencapaian dan refleksi</p>
                </div>
                <svg class="w-5 h-5 text-slate-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7 7-7"/>
                </svg>
            </div>
        </a>

        <a href="{{ route('student.biodata') }}" class="glass-card hover-lift transition-colors">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded bg-emerald-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0a4 4 0 018 0zM12 14a9 9 0 100-18 9 9 0 0018 0zm9-4.5v4.5l3 3l-3 3m-4.5-10.5h-4.5"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">Biodata Diri</h3>
                    <p class="text-sm text-slate-500">Lihat dan perbarui data Anda</p>
                </div>
                <svg class="w-5 h-5 text-slate-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7 7-7"/>
                </svg>
            </div>
        </a>
    </div>

    <!-- Recent Records -->
    <div class="glass-card">
        <h2 class="font-semibold text-lg mb-4">Catatan Terbaru</h2>
        @if($records->isEmpty())
            <div class="text-center py-8 text-slate-500">
                <p>Belum ada catatan</p>
                <a href="{{ route('student.portfolio') }}" class="text-indigo-600 hover:underline">Mulai catat pencapaian</a>
            </div>
        @else
            <div class="space-y-3">
                @foreach($records->take(5) as $record)
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-50">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $record->type === 'positive' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' }}">
                            @if($record->type === 'positive')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-slate-900">{{ $record->title }}</p>
                            <p class="text-sm text-slate-500">{{ $record->dimension?->name }} • {{ $record->record_date->format('d M Y') }}</p>
                        </div>
                        <div class="text-sm font-semibold {{ $record->score > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $record->score > 0 ? '+' : '' }}{{ $record->score }}
                        </div>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('student.portfolio') }}" class="block text-center text-sm text-indigo-600 hover:underline mt-4">
                Lihat semua catatan →
            </a>
        @endif
    </div>
</div>
@endsection
