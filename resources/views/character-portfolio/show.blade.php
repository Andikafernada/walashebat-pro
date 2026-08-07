@extends('layouts.app')
@section('title', 'Detail Catatan Karakter')
@section('content')
    @php $classroom = $class; @endphp
    @include('partials.class-nav')

    <div class="max-w-3xl mx-auto">
        <div class="glass-card animate-in">
            <!-- Header -->
            <div class="flex items-start justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('classes.character-portfolio.student', [$class, $student]) }}"
                       class="p-2 rounded-xl hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Detail Catatan Karakter</h2>
                        <p class="text-sm text-slate-500">{{ $record->student->name }} - {{ $record->dimension->name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if(!$record->is_acknowledged)
                        <form method="POST" action="{{ route('classes.character-portfolio.record.acknowledge', [$class, $record]) }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-secondary--sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" strokeBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Konfirmasi
                            </button>
                        </form>
                    @else
                        <span class="badge badge--success">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Dikonfirmasi
                        </span>
                    @endif
                </div>
            </div>

            <!-- Record Info -->
            <div class="grid gap-6">
                <div class="flex items-center gap-4 p-4 rounded-xl"
                     style="background-color: {{ $record->dimension->color }}15; border-left: 4px solid {{ $record->dimension->color }};">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                         style="background-color: {{ $record->dimension->color }}30; color: {{ $record->dimension->color }};">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $record->dimension->icon }}"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">{{ $record->dimension->name }}</p>
                        <p class="text-2xl font-bold" style="color: {{ $record->score >= 0 ? '#16a34a' : '#dc2626' }};">
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
                <div class="p-4 bg-emerald-50 rounded-xl">
                    <h4 class="font-medium text-emerald-800 mb-1">Dikonfirmasi pada</h4>
                    <p class="text-emerald-700">{{ $record->acknowledged_at->format('d M Y, H:i') }} oleh {{ $record->acknowledger->name ?? 'Wali Kelas' }}</p>
                </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 mt-8 pt-6 border-t border-slate-100">
                <form method="POST" action="{{ route('classes.character-portfolio.record.destroy', [$class, $record]) }}"
                      onsubmit="return confirm('Hapus catatan ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger-ghost">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
