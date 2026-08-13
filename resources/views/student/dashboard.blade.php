@extends('layouts.student')
@section('title', 'Dashboard')

@section('content')
<div class="p-6 lg:p-8 space-y-6">
    <!-- Welcome Header -->
    <div class="card">
        <div class="flex items-center gap-4">
            <div class="avatar-hero">
                <span class="avatar-hero-initials">{{ substr($student->name, 0, 2) }}</span>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Selamat datang, {{ $student->name }}!</h1>
                <p class="text-slate-500">{{ $class->name }} &bull; Tahun Pelajaran {{ $class->academic_year }}</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <dl class="deret-angka">
        <div>
            <dt class="stat-label">Catatan Karakter</dt>
            <dd class="stat-value">{{ $portfolioStats['records'] }}</dd>
        </div>
        <div>
            <dt class="stat-label">Kehadiran</dt>
            <dd class="stat-value {{ $attendanceStats['rate'] >= 85 ? 'text-emerald-700' : ($attendanceStats['rate'] >= 75 ? 'text-amber-700' : 'text-rose-700') }}">{{ $attendanceStats['rate'] }}%</dd>
        </div>
        <div>
            <dt class="stat-label">Poin Karakter</dt>
            <dd class="stat-value {{ $violationStats['points'] < 0 ? 'text-rose-700' : '' }}">{{ $violationStats['points'] }}</dd>
        </div>
        <div>
            <dt class="stat-label">Badge Diraih</dt>
            <dd class="stat-value">{{ $portfolioStats['badges'] }}</dd>
        </div>
    </dl>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ route('student.portfolio') }}" class="card flex items-center gap-4 transition-colors hover:border-slate-300">
            <div>
                <h3 class="font-semibold text-slate-900">Portofolio Karakter</h3>
                <p class="text-sm text-slate-500">Catat pencapaian dan refleksi</p>
            </div>
        </a>

        <a href="{{ route('student.biodata') }}" class="card flex items-center gap-4 transition-colors hover:border-slate-300">
            <div>
                <h3 class="font-semibold text-slate-900">Biodata Diri</h3>
                <p class="text-sm text-slate-500">Lihat dan perbarui data Anda</p>
            </div>
        </a>
    </div>

    <!-- Recent Records -->
    <div class="card">
        <h2 class="font-semibold text-lg mb-4">Catatan Terbaru</h2>
        @if($records->isEmpty())
            <div class="empty-state empty-state--compact">
                <p class="empty-state__title">Belum ada catatan</p>
                <a href="{{ route('student.portfolio') }}" class="empty-state__description text-indigo-600 hover:underline">Mulai catat pencapaian</a>
            </div>
        @else
            <div class="space-y-3">
                @foreach($records->take(5) as $record)
                    <div class="flex items-center gap-3 p-3 rounded border border-slate-200 bg-slate-50">
                        <div class="stat-icon {{ $record->type === 'positive' ? 'stat-icon--emerald' : 'stat-icon--rose' }}">
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
                        <div class="flex-1">
                            <p class="font-medium text-slate-900">{{ $record->title }}</p>
                            <p class="text-sm text-slate-500">{{ $record->dimension?->name }} &bull; {{ $record->record_date->format('d M Y') }}</p>
                        </div>
                        <div class="text-sm font-semibold {{ $record->score > 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $record->score > 0 ? '+' : '' }}{{ $record->score }}
                        </div>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('student.portfolio') }}" class="block text-center text-sm text-indigo-600 hover:underline mt-4">
                Lihat semua catatan &rarr;
            </a>
        @endif
    </div>
</div>
@endsection
