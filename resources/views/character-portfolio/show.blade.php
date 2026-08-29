@extends('layouts.app')
@section('title', 'Detail Catatan Karakter')
@section('content')
    @php $classroom = $class; @endphp
    @include('partials.class-nav')

    <div class="max-w-3xl mx-auto pb-12">
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
            <!-- Header -->
            <div class="flex items-start justify-between gap-3 border-b border-emerald-100 pb-4 mb-6">
                <div class="flex items-center gap-3">
                    <a href="{{ route('classes.character-portfolio.student', [$class, $student]) }}"
                       class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Kembali">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-lg font-bold tracking-tight text-slate-900">Detail Catatan Karakter</h1>
                        <p class="text-xs text-slate-500">{{ $record->student->name }} &middot; {{ $record->dimension->name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if(!$record->is_acknowledged)
                        <form method="POST" action="{{ route('classes.character-portfolio.record.acknowledge', [$class, $record]) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Konfirmasi</button>
                        </form>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Dikonfirmasi
                        </span>
                    @endif
                </div>
            </div>

            <!-- Record Info -->
            <div class="grid gap-5">
                <div class="flex items-center gap-4 p-4 rounded-2xl border"
                     style="background-color: {{ $record->dimension->color }}15; border-color: {{ $record->dimension->color }}40;">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl font-bold" style="background-color: {{ $record->dimension->color }}30; color: {{ $record->dimension->color }};">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $record->dimension->icon }}"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">{{ $record->dimension->name }}</p>
                        <p class="text-2xl font-extrabold" style="color: {{ $record->score >= 0 ? '#16a34a' : '#dc2626' }};">
                            {{ $record->getScoreDisplay() }}
                        </p>
                    </div>
                    <div class="ml-auto text-right">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800">{{ ucfirst($record->type) }}</span>
                        <p class="text-xs text-slate-400 mt-1 font-mono">{{ $record->record_date->format('d M Y') }}</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-1">Judul</h3>
                    <p class="text-xs text-slate-800 font-semibold">{{ $record->title }}</p>
                </div>

                @if($record->description)
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-1">Deskripsi</h3>
                    <p class="text-xs text-slate-600 whitespace-pre-wrap leading-relaxed">{{ $record->description }}</p>
                </div>
                @endif

                @if($record->evidence)
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-1">Bukti</h3>
                    <p class="text-xs text-slate-600">{{ $record->evidence }}</p>
                </div>
                @endif

                <div class="grid grid-cols-2 gap-4 text-xs pt-2 border-t border-slate-100">
                    @if($record->context)
                    <div>
                        <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Konteks</h4>
                        <p class="font-bold text-slate-800">{{ ucfirst(str_replace('_', ' ', $record->context)) }}</p>
                    </div>
                    @endif
                    <div>
                        <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Dicatat Oleh</h4>
                        <p class="font-bold text-slate-800">{{ $record->recorder->name ?? 'Sistem' }}</p>
                    </div>
                </div>

                @if($record->is_acknowledged && $record->acknowledged_at)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4">
                    <p class="text-xs font-bold text-emerald-900">Dikonfirmasi pada</p>
                    <p class="text-xs text-emerald-700 mt-0.5">{{ $record->acknowledged_at->format('d M Y, H:i') }} oleh {{ $record->acknowledger->name ?? 'Wali Kelas' }}</p>
                </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 mt-6 pt-5 border-t border-slate-100">
                <form method="POST" action="{{ route('classes.character-portfolio.record.destroy', [$class, $record]) }}"
                      onsubmit="return confirm('Hapus catatan ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors">Hapus Catatan</button>
                </form>
            </div>
        </div>
    </div>
@endsection
