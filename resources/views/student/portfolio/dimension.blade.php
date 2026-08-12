@extends('layouts.student')
@section('title', $dimension->name)

@section('content')
<div class="p-6 lg:p-8 space-y-6">
    <!-- Header -->
    <div class="glass-card flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">{{ $dimension->name }}</h1>
            <p class="text-slate-500 mt-1">Riwayat catatan pada dimensi karakter ini</p>
        </div>
        <a href="{{ route('student.portfolio') }}" class="btn btn-secondary">Kembali</a>
    </div>

    <!-- Records -->
    <div class="glass-card">
        <h2 class="font-semibold mb-4">Catatan</h2>
        @forelse ($records as $record)
            <div class="flex items-start gap-3 py-3 border-b border-slate-200 last:border-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $record->score > 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' }}">
                    @if ($record->score > 0)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    @endif
                </div>
                <div class="flex-1">
                    <p class="font-medium text-slate-900">{{ $record->title }}</p>
                    @if ($record->description)
                        <p class="text-sm text-slate-500 mt-0.5">{{ $record->description }}</p>
                    @endif
                    <p class="text-xs text-slate-400 mt-1">{{ $record->record_date->format('d M Y') }}</p>
                </div>
                <div class="text-sm font-semibold {{ $record->score > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $record->score > 0 ? '+' : '' }}{{ $record->score }}
                </div>
            </div>
        @empty
            <p class="text-center py-8 text-slate-500">
                Belum ada catatan pada dimensi ini.
            </p>
        @endforelse
    </div>

    {{ $records->links() }}
</div>
@endsection
