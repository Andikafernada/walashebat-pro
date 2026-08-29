@extends('layouts.app')
@section('title', 'Tambah Catatan Karakter')
@section('content')
    @php $classroom = $class; @endphp
    @include('partials.class-nav')

    <div class="max-w-2xl mx-auto pb-12">
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
            <div class="flex items-center gap-3 border-b border-emerald-100 pb-4 mb-6">
                <a href="{{ route('classes.character-portfolio.student', [$class, $student]) }}"
                   class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Kembali">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-lg font-bold tracking-tight text-slate-900">Tambah Catatan Karakter</h1>
                    <p class="text-xs text-slate-500">{{ $student->name }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('classes.character-portfolio.record.store', $class) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="student_id" value="{{ $student->id }}">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe <span class="text-rose-500">*</span></label>
                        <select name="type" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="positive">Positif</option>
                            <option value="negative">Negatif</option>
                            <option value="observation">Observasi</option>
                            <option value="achievement">Prestasi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Skor (-10 s/d +10) <span class="text-rose-500">*</span></label>
                        <input type="number" name="score" required min="-10" max="10" value="1" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <p class="text-[11px] text-slate-400 mt-1">Positif: +1 s/d +10, Negatif: -1 s/d -10</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Dimensi Karakter <span class="text-rose-500">*</span></label>
                    <select name="character_dimension_id" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <option value="">-- Pilih Dimensi --</option>
                        @foreach($dimensions as $d)
                            <option value="{{ $d->id }}" @selected(old('character_dimension_id') == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="cth: Membantu teman yang kesulitan" 
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi</label>
                    <textarea name="description" rows="3" placeholder="Detail kejadian..." 
                              class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Bukti / Link</label>
                    <input type="text" name="evidence" value="{{ old('evidence') }}" placeholder="URL atau keterangan bukti" 
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Konteks</label>
                        <select name="context" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">— pilih —</option>
                            <option value="in_class">Di Kelas</option>
                            <option value="extracurricular">Ekstrakurikuler</option>
                            <option value="break_time">Jam Istirahat</option>
                            <option value="exam">Saat Ujian</option>
                            <option value="activity">Kegiatan Sekolah</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal <span class="text-rose-500">*</span></label>
                        <input type="date" name="record_date" required value="{{ date('Y-m-d') }}" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="flex items-center gap-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                    <input type="checkbox" name="notify_parent" value="1" id="notify_parent" class="mt-0.5 accent-emerald-600 rounded">
                    <label for="notify_parent" class="text-xs font-semibold text-slate-700 cursor-pointer">
                        Kirim notifikasi ke orang tua via WhatsApp
                    </label>
                </div>

                <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('classes.character-portfolio.student', [$class, $student]) }}"
                       class="flex-1 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit" class="flex-1 inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">
                        Simpan Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
