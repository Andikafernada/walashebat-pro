@extends('layouts.student')
@section('title', 'Portofolio Karakter')

@section('content')
<div class="p-6 lg:p-8 space-y-6 max-w-4xl mx-auto pb-12">
    <!-- Header -->
    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
        <h1 class="text-xl font-bold tracking-tight text-slate-900">Portofolio Karakter</h1>
        <p class="text-xs text-slate-500 mt-0.5">Catat pencapaian dan refleksi dirimu</p>
    </div>

    <!-- Quick Add Buttons -->
    <div class="flex gap-3">
        <button onclick="document.getElementById('modal-achievement').classList.remove('hidden')"
                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-3 text-xs font-bold text-white hover:bg-emerald-700 transition-colors shadow-xs">
            Catat Pencapaian
        </button>
        <button onclick="document.getElementById('modal-reflection').classList.remove('hidden')"
                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition-colors">
            Refleksi Harian
        </button>
    </div>

    <!-- Badges -->
    @if($badges->where('is_earned', true)->isNotEmpty())
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
            <h2 class="text-sm font-extrabold text-slate-900 mb-4">Badge Diraih</h2>
            <div class="flex flex-wrap gap-2.5">
                @foreach($badges->where('is_earned', true) as $badge)
                    <div class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 border border-amber-200 px-3 py-1.5 text-xs text-amber-900 font-bold">
                        <span>{{ $badge->badge->icon ?? '🏆' }}</span>
                        <span>{{ $badge->badge->name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Records -->
    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
        <h2 class="text-sm font-extrabold text-slate-900 mb-4 border-b border-emerald-100 pb-3">Catatan Terbaru</h2>
        <div class="divide-y divide-slate-100">
            @forelse($records as $record)
                <div class="flex items-start gap-3 py-3.5 hover:bg-slate-50/50 transition-colors">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl font-bold shrink-0 {{ $record->score > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                        @if($record->score > 0)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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
            @empty
                <div class="py-8 text-center text-xs text-slate-400">
                    <p class="font-bold text-slate-700">Belum ada catatan</p>
                    <p class="mt-1">Mulai catat pencapaianmu!</p>
                </div>
            @endforelse
        </div>
    </div>

    {{ $records->links() }}
</div>

<!-- Modal Pencapaian -->
<div id="modal-achievement" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/60" onclick="this.closest('.fixed').classList.add('hidden')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl relative z-10">
            <h3 class="text-sm font-extrabold text-slate-900 mb-4 border-b border-emerald-100 pb-3">Catat Pencapaian</h3>
            <form action="{{ route('student.portfolio.achievement') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Dimensi</label>
                    <select name="character_dimension_id" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <option value="">-- Pilih Dimensi --</option>
                        @foreach($dimensions as $dim)
                            <option value="{{ $dim->id }}" @selected(old('character_dimension_id') == $dim->id)>{{ $dim->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Pencapaian</label>
                    <input type="text" name="title" value="{{ old('title') }}" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal</label>
                    <input type="date" name="record_date" value="{{ today()->format('Y-m-d') }}" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi (opsional)</label>
                    <textarea name="description" rows="2" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>
                <div class="flex gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modal-achievement').classList.add('hidden')" class="flex-1 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Batal</button>
                    <button type="submit" class="flex-1 inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Refleksi -->
<div id="modal-reflection" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/60" onclick="this.closest('.fixed').classList.add('hidden')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-xl relative z-10">
            <h3 class="text-sm font-extrabold text-slate-900 mb-4 border-b border-emerald-100 pb-3">Refleksi Harian</h3>
            <form action="{{ route('student.reflection.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="period" value="daily">
                <input type="hidden" name="reflection_date" value="{{ today()->format('Y-m-d') }}">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Apa yang berjalan baik hari ini?</label>
                    <textarea name="what_went_well" rows="3" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Apa yang perlu diperbaiki?</label>
                    <textarea name="what_to_improve" rows="3" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Menurut temanmu, kamu itu seperti apa? <span class="font-normal text-slate-400 lowercase">(opsional)</span></label>
                    <textarea name="kesan_teman" rows="3" maxlength="1000" placeholder="cth: Kata Rina aku asyik, tapi kadang suka memotong pembicaraan." class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>
                <div class="flex gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modal-reflection').classList.add('hidden')" class="flex-1 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Batal</button>
                    <button type="submit" class="flex-1 inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
