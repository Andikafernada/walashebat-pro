@extends('layouts.app')
@section('title', 'Tambah Catatan Karakter')
@section('content')
    @php $classroom = $class; @endphp
    @include('partials.class-nav')

    <div class="max-w-2xl mx-auto">
        <div class="glass-card">
            <div class="flex items-center gap-4 mb-6">
                <a href="{{ route('classes.character-portfolio.student', [$class, $student]) }}"
                   class="p-2 rounded-xl hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Tambah Catatan Karakter</h2>
                    <p class="text-sm text-slate-500">{{ $student->name }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('classes.character-portfolio.record.store', $class) }}" class="space-y-5">
                @csrf
                <input type="hidden" name="student_id" value="{{ $student->id }}">

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label form-label--required">Tipe</label>
                        <select name="type" required class="form-select">
                            <option value="positive">🟢 Positif</option>
                            <option value="negative">🔴 Negatif</option>
                            <option value="observation">📝 Observasi</option>
                            <option value="achievement">🏆 Prestasi</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label--required">Skor (-10 s/d +10)</label>
                        <input type="number" name="score" required min="-10" max="10" value="1" class="form-input">
                        <p class="text-xs text-slate-500 mt-1">Positif: +1 s/d +10, Negatif: -1 s/d -10</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label form-label--required">Dimensi Karakter</label>
                    <select name="character_dimension_id" required class="form-select">
                        <option value="">-- Pilih Dimensi --</option>
                        @foreach($dimensions as $d)
                            <option value="{{ $d->id }}" @selected(old('character_dimension_id') == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label form-label--required">Judul</label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="cth: Membantu teman yang kesulitan" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" rows="3" placeholder="Detail kejadian..." class="form-textarea"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Bukti/Link</label>
                    <input type="text" name="evidence" value="{{ old('evidence') }}" placeholder="URL atau keterangan bukti" class="form-input">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Konteks</label>
                        <select name="context" class="form-select">
                            <option value="">— pilih —</option>
                            <option value="in_class">Di Kelas</option>
                            <option value="extracurricular">Ekstrakurikuler</option>
                            <option value="break_time">Jam Istirahat</option>
                            <option value="exam">Saat Ujian</option>
                            <option value="activity">Kegiatan Sekolah</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label form-label--required">Tanggal</label>
                        <input type="date" name="record_date" required value="{{ date('Y-m-d') }}" class="form-input">
                    </div>
                </div>

                <div class="flex items-center gap-2 p-3 bg-slate-50 rounded-xl">
                    <input type="checkbox" name="notify_parent" value="1" id="notify_parent" class="form-checkbox">
                    <label for="notify_parent" class="text-sm text-slate-600">
                        Kirim notifikasi ke orang tua via WhatsApp
                    </label>
                </div>

                <div class="flex items-center gap-3 pt-4">
                    <a href="{{ route('classes.character-portfolio.student', [$class, $student]) }}"
                       class="btn btn-secondary flex-1 justify-center">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary flex-1 justify-center">
                        Simpan Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
