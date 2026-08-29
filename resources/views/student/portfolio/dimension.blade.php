@extends('layouts.student')
@section('title', $dimension->name)

@section('content')
<div class="p-6 lg:p-8 space-y-6 max-w-4xl mx-auto pb-12">
    <!-- Header -->
    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ $dimension->name }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Riwayat catatan pada dimensi karakter ini</p>
        </div>
        <a href="{{ route('student.portfolio') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Kembali</a>
    </div>

    <!-- Records -->
    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
        <h2 class="text-sm font-extrabold text-slate-900 mb-4 border-b border-emerald-100 pb-3">Catatan</h2>
        <div class="divide-y divide-slate-100">
            @forelse ($records as $record)
                <div class="flex items-start gap-3 py-3.5 hover:bg-slate-50/50 transition-colors">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl font-bold shrink-0 {{ $record->score > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                        @if ($record->score > 0)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-900">{{ $record->title }}</p>
                        @if ($record->description)
                            <p class="text-xs text-slate-500 mt-0.5">{{ $record->description }}</p>
                        @endif
                        <p class="text-[11px] text-slate-400 mt-1 font-mono">{{ $record->record_date->format('d M Y') }}</p>
                    </div>
                    <div class="text-xs font-extrabold font-mono {{ $record->score > 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ $record->score > 0 ? '+' : '' }}{{ $record->score }}
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-xs text-slate-400">
                    <p class="font-bold text-slate-700">Belum ada catatan pada dimensi ini</p>
                </div>
            @endforelse
        </div>
    </div>

    {{ $records->links() }}
</div>
@endsection
