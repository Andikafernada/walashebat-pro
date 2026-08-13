@extends('layouts.app')
@section('title', 'Detail Catatan Karakter')
@section('content')
    @php $classroom = $class; @endphp
    @include('partials.class-nav')

    <div class="max-w-3xl mx-auto">
        <div class="card">
            <!-- Header -->
            <div class="flex items-start justify-between gap-3 border-b border-slate-200 pb-4 mb-6">
                <div class="flex items-center gap-3">
                    <a href="{{ route('classes.character-portfolio.student', [$class, $student]) }}"
                       class="btn-icon" aria-label="Kembali">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-semibold tracking-tight text-slate-900">Detail Catatan Karakter</h1>
                        <p class="mt-1 text-sm text-slate-500">{{ $record->student->name }} &middot; {{ $record->dimension->name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if(!$record->is_acknowledged)
                        <form method="POST" action="{{ route('classes.character-portfolio.record.acknowledge', [$class, $record]) }}">
                            @csrf
                            <button type="submit" class="btn-secondary btn-secondary--sm">Konfirmasi</button>
                        </form>
                    @else
                        <span class="badge badge--emerald">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Dikonfirmasi
                        </span>
                    @endif
                </div>
            </div>

            <!-- Record Info -->
            <div class="grid gap-6">
                <div class="flex items-center gap-4 p-4 rounded"
                     style="background-color: {{ $record->dimension->color }}15; border-left: 4px solid {{ $record->dimension->color }};">
                    <div class="stat-icon" style="background-color: {{ $record->dimension->color }}30; color: {{ $record->dimension->color }};">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $record->dimension->icon }}"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">{{ $record->dimension->name }}</p>
                        <p class="text-2xl font-semibold" style="color: {{ $record->score >= 0 ? '#16a34a' : '#dc2626' }};">
                            {{ $record->getScoreDisplay() }}
                        </p>
                    </div>
                    <div class="ml-auto text-right">
                        <span class="{{ $record->getTypeBadgeClass() }}">{{ ucfirst($record->type) }}</span>
                        <p class="text-sm text-slate-500 mt-1">{{ $record->record_date->format('d M Y') }}</p>
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold text-slate-900 mb-2">Judul</h3>
                    <p class="text-slate-700">{{ $record->title }}</p>
                </div>

                @if($record->description)
                <div>
                    <h3 class="font-semibold text-slate-900 mb-2">Deskripsi</h3>
                    <p class="text-slate-700 whitespace-pre-wrap">{{ $record->description }}</p>
                </div>
                @endif

                @if($record->evidence)
                <div>
                    <h3 class="font-semibold text-slate-900 mb-2">Bukti</h3>
                    <p class="text-slate-700">{{ $record->evidence }}</p>
                </div>
                @endif

                <div class="grid grid-cols-2 gap-4 text-sm">
                    @if($record->context)
                    <div>
                        <h4 class="font-medium text-slate-500 mb-1">Konteks</h4>
                        <p class="text-slate-700">{{ ucfirst(str_replace('_', ' ', $record->context)) }}</p>
                    </div>
                    @endif
                    <div>
                        <h4 class="font-medium text-slate-500 mb-1">Dicatat oleh</h4>
                        <p class="text-slate-700">{{ $record->recorder->name ?? 'Sistem' }}</p>
                    </div>
                </div>

                @if($record->is_acknowledged && $record->acknowledged_at)
                <div class="alert alert--success">
                    <div>
                        <p class="alert__title">Dikonfirmasi pada</p>
                        <p class="alert__body">{{ $record->acknowledged_at->format('d M Y, H:i') }} oleh {{ $record->acknowledger->name ?? 'Wali Kelas' }}</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-slate-200">
                <form method="POST" action="{{ route('classes.character-portfolio.record.destroy', [$class, $record]) }}"
                      onsubmit="return confirm('Hapus catatan ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger-ghost">Hapus</button>
                </form>
            </div>
        </div>
    </div>
@endsection
