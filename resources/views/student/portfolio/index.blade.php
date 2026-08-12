@extends('layouts.student')
@section('title', 'Portofolio Karakter')

@section('content')
<div class="p-6 lg:p-8 space-y-6">
    <!-- Header -->
    <div class="glass-card">
        <h1 class="text-2xl font-bold text-slate-900">Portofolio Karakter</h1>
        <p class="text-slate-500 mt-1">Catat pencapaian dan refleksi dirimu</p>
    </div>

    <!-- Quick Add Buttons -->
    <div class="flex gap-3">
        <button onclick="document.getElementById('modal-achievement').classList.remove('hidden')"
                class="flex-1 glass-card flex items-center justify-center gap-2 py-4 hover:bg-indigo-50 cursor-pointer transition-colors">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0V6m-6 6a6 6 0 100-12 6a6 6 0 0012 12z"/>
            </svg>
            <span class="font-medium">Catat Pencapaian</span>
        </button>
        <button onclick="document.getElementById('modal-reflection').classList.remove('hidden')"
                class="flex-1 glass-card flex items-center justify-center gap-2 py-4 hover:bg-emerald-50 cursor-pointer transition-colors">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v10a2 2 0 002 2h5m5-16l-2.09 2.09m-2.09-2.09l-2.09 2.09m2.09-2.09h9m-9-2.09"/>
            </svg>
            <span class="font-medium">Refleksi Harian</span>
        </button>
    </div>

    <!-- Badges -->
    @if($badges->where('is_earned', true)->isNotEmpty())
        <div class="glass-card">
            <h2 class="font-bold mb-4">Badge Diraih</h2>
            <div class="flex flex-wrap gap-3">
                @foreach($badges->where('is_earned', true) as $badge)
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200">
                        <span class="text-xl">{{ $badge->badge->icon ?? '🏆' }}</span>
                        <span class="text-sm font-medium text-amber-700">{{ $badge->badge->name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Records -->
    <div class="glass-card">
        <h2 class="font-bold mb-4">Catatan Terbaru</h2>
        @forelse($records as $record)
            <div class="flex items-start gap-3 py-3 border-b border-slate-100 last:border-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $record->score > 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' }}">
                    @if($record->score > 0)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    @endif
                </div>
                <div class="flex-1">
                    <p class="font-medium text-slate-900">{{ $record->title }}</p>
                    <p class="text-xs text-slate-500">{{ $record->dimension?->name }} • {{ $record->record_date->format('d M Y') }}</p>
                </div>
                <div class="text-sm font-semibold {{ $record->score > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $record->score > 0 ? '+' : '' }}{{ $record->score }}
                </div>
            </div>
        @empty
            <p class="text-center py-8 text-slate-500">
                Belum ada catatan. Mulai catat pencapaianmu!
            </p>
        @endforelse
    </div>

    {{ $records->links() }}
</div>

<!-- Modal Pencapaian -->
<div id="modal-achievement" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="this.closest('.fixed').classList.add('hidden')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold mb-4">Catat Pencapaian</h3>
            <form action="{{ route('student.portfolio.achievement') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Dimensi</label>
                    <select name="character_dimension_id" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="">-- Pilih Dimensi --</option>
                        @foreach($dimensions as $dim)
                            <option value="{{ $dim->id }}" @selected(old('character_dimension_id') == $dim->id)>{{ $dim->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Judul Pencapaian</label>
                    <input type="text" name="title" value="{{ old('title') }}" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Tanggal</label>
                    <input type="date" name="record_date" value="{{ today()->format('Y-m-d') }}" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Deskripsi (opsional)</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="document.getElementById('modal-achievement').classList.add('hidden')" class="flex-1 px-4 py-2 border rounded-lg">Batal</button>
                    <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Refleksi -->
<div id="modal-reflection" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="this.closest('.fixed').classList.add('hidden')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold mb-4">Refleksi Harian</h3>
            <form action="{{ route('student.reflection.store') }}" method="POST" class="space-y-4">
                @csrf
                {{-- Modal ini khusus refleksi harian: tanggal & periode tidak ditanyakan
                     ke siswa, tapi controller mewajibkan keduanya. Tanpa dua input ini
                     setiap simpan gagal validasi tanpa pesan apa pun. --}}
                <input type="hidden" name="period" value="daily">
                <input type="hidden" name="reflection_date" value="{{ today()->format('Y-m-d') }}">
                <div>
                    <label class="block text-sm font-medium mb-1">Apa yang berjalan baik hari ini?</label>
                    <textarea name="what_went_well" rows="3" required class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Apa yang perlu diperbaiki?</label>
                    <textarea name="what_to_improve" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
                {{-- Cermin dari luar; boleh dikosongkan. --}}
                <div>
                    <label class="block text-sm font-medium mb-1">Menurut temanmu, kamu itu seperti apa? <span class="font-normal text-gray-500">(boleh dikosongkan)</span></label>
                    <textarea name="kesan_teman" rows="3" maxlength="1000" placeholder="cth: Kata Rina aku asyik, tapi kadang suka memotong pembicaraan." class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="document.getElementById('modal-reflection').classList.add('hidden')" class="flex-1 px-4 py-2 border rounded-lg">Batal</button>
                    <button type="submit" class="flex-1 bg-emerald-600 text-white py-2 rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
