@extends('layouts.student')
@section('title', 'Portofolio Karakter')

@section('content')
<div class="p-6 lg:p-8 space-y-6">
    <!-- Header -->
    <div class="card">
        <h1 class="text-2xl font-semibold text-slate-900">Portofolio Karakter</h1>
        <p class="text-slate-500 mt-1">Catat pencapaian dan refleksi dirimu</p>
    </div>

    <!-- Quick Add Buttons -->
    <div class="flex gap-3">
        <button onclick="document.getElementById('modal-achievement').classList.remove('hidden')"
                class="btn-primary flex-1">
            Catat Pencapaian
        </button>
        <button onclick="document.getElementById('modal-reflection').classList.remove('hidden')"
                class="btn-success flex-1">
            Refleksi Harian
        </button>
    </div>

    <!-- Badges -->
    @if($badges->where('is_earned', true)->isNotEmpty())
        <div class="card">
            <h2 class="font-semibold mb-4">Badge Diraih</h2>
            <div class="flex flex-wrap gap-3">
                @foreach($badges->where('is_earned', true) as $badge)
                    <div class="badge badge--amber gap-1.5 px-3 py-1.5 text-xs normal-case">
                        <span>{{ $badge->badge->icon ?? '🏆' }}</span>
                        <span class="font-medium">{{ $badge->badge->name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Records -->
    <div class="card">
        <h2 class="font-semibold mb-4">Catatan Terbaru</h2>
        @forelse($records as $record)
            <div class="flex items-start gap-3 py-3 border-b border-slate-200 last:border-0">
                <div class="stat-icon {{ $record->score > 0 ? 'stat-icon--emerald' : 'stat-icon--rose' }}">
                    @if($record->score > 0)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    @endif
                </div>
                <div class="flex-1">
                    <p class="font-medium text-slate-900">{{ $record->title }}</p>
                    <p class="text-xs text-slate-500">{{ $record->dimension?->name }} &bull; {{ $record->record_date->format('d M Y') }}</p>
                </div>
                <div class="text-sm font-semibold {{ $record->score > 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ $record->score > 0 ? '+' : '' }}{{ $record->score }}
                </div>
            </div>
        @empty
            <div class="empty-state empty-state--compact">
                <p class="empty-state__title">Belum ada catatan</p>
                <p class="empty-state__description">Mulai catat pencapaianmu!</p>
            </div>
        @endforelse
    </div>

    {{ $records->links() }}
</div>

<!-- Modal Pencapaian -->
<div id="modal-achievement" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/60" onclick="this.closest('.fixed').classList.add('hidden')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold mb-4">Catat Pencapaian</h3>
            <form action="{{ route('student.portfolio.achievement') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="form-label">Dimensi</label>
                    <select name="character_dimension_id" required class="form-input">
                        <option value="">-- Pilih Dimensi --</option>
                        @foreach($dimensions as $dim)
                            <option value="{{ $dim->id }}" @selected(old('character_dimension_id') == $dim->id)>{{ $dim->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Judul Pencapaian</label>
                    <input type="text" name="title" value="{{ old('title') }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="record_date" value="{{ today()->format('Y-m-d') }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Deskripsi (opsional)</label>
                    <textarea name="description" rows="2" class="form-input"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="document.getElementById('modal-achievement').classList.add('hidden')" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="btn-primary flex-1">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Refleksi -->
<div id="modal-reflection" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/60" onclick="this.closest('.fixed').classList.add('hidden')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4">Refleksi Harian</h3>
            <form action="{{ route('student.reflection.store') }}" method="POST" class="space-y-4">
                @csrf
                {{-- Modal ini khusus refleksi harian: tanggal & periode tidak ditanyakan
                     ke siswa, tapi controller mewajibkan keduanya. Tanpa dua input ini
                     setiap simpan gagal validasi tanpa pesan apa pun. --}}
                <input type="hidden" name="period" value="daily">
                <input type="hidden" name="reflection_date" value="{{ today()->format('Y-m-d') }}">
                <div>
                    <label class="form-label">Apa yang berjalan baik hari ini?</label>
                    <textarea name="what_went_well" rows="3" required class="form-input"></textarea>
                </div>
                <div>
                    <label class="form-label">Apa yang perlu diperbaiki?</label>
                    <textarea name="what_to_improve" rows="3" class="form-input"></textarea>
                </div>
                {{-- Cermin dari luar; boleh dikosongkan. --}}
                <div>
                    <label class="form-label">Menurut temanmu, kamu itu seperti apa? <span class="font-normal text-slate-500 lowercase">(boleh dikosongkan)</span></label>
                    <textarea name="kesan_teman" rows="3" maxlength="1000" placeholder="cth: Kata Rina aku asyik, tapi kadang suka memotong pembicaraan." class="form-input"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="document.getElementById('modal-reflection').classList.add('hidden')" class="btn-secondary flex-1">Batal</button>
                    <button type="submit" class="btn-success flex-1">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
