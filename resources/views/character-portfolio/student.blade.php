@extends('layouts.app')

@section('title', 'Portofolio Karakter - ' . $student->name)

@section('content')
<div class="space-y-6 pb-12" x-data="{ showRecordModal: false, showFeedbackModal: false, activeReflectionId: null }">

    <!-- Header Bar -->
    <div class="page-header">
        <div>
            <nav class="text-xs font-semibold uppercase tracking-wider text-slate-400 flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.character-portfolio.index', $class) }}" class="hover:text-slate-600">Portofolio Karakter</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">{{ $student->name }}</span>
            </nav>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900">
                Portofolio Karakter: {{ $student->name }}
            </h1>
            <p class="mt-1 text-xs text-slate-500">NIS: {{ $student->nis ?? '-' }} &middot; Kelas {{ $class->name }}</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="showRecordModal = true"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-emerald-700 transition-colors">+ Catat Observasi Guru
            </button>
            <a href="{{ route('public.reflection.show', $class->tokenPublik()) }}" target="_blank"
               class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                Link Refleksi Siswa
            </a>
        </div>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $class])

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- Student Top Hero Card -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900 p-5 text-white shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 border border-white/20 font-bold text-xl text-white">
                    {{ substr($student->name, 0, 1) }}
                </div>
                <div>
                    <span class="text-[10px] font-bold tracking-wider text-emerald-400 uppercase block">Profil Pelajar Pancasila</span>
                    <h2 class="text-xl font-extrabold text-white mt-0.5">{{ $student->name }}</h2>
                    <p class="text-xs text-slate-300 mt-0.5">Catatan Karakter Terkumpul: <span class="font-bold text-white">{{ $records->total() }} Record</span></p>
                </div>
            </div>

            <!-- Badges Summary -->
            <div class="flex items-center gap-2">
                @forelse($badges as $sb)
                    <div class="flex items-center gap-1.5 rounded-xl bg-white/10 px-3 py-1.5 border border-white/10 text-xs">
                        <span>{{ $sb->badge->icon ?? '🏅' }}</span>
                        <span class="font-bold text-white text-[11px]">{{ $sb->badge->name }}</span>
                    </div>
                @empty
                    <span class="text-xs text-slate-400">Belum ada badge karakter terverifikasi</span>
                @endforelse
            </div>
        </div>
    </div>

    <!-- 6 Dimensi P5 Progress Cards -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach($dimensions as $dim)
            @php $scoreData = $dimensionScores[$dim->id] ?? ['score' => 0, 'records_count' => 0]; @endphp
            <div class="rounded-2xl border border-emerald-200 bg-white p-3 text-center space-y-1 shadow-xs" style="border-top: 3.5px solid {{ $dim->color }};">
                <span class="text-xs font-bold text-slate-600 uppercase tracking-wider block truncate">{{ $dim->name }}</span>
                <span class="text-xl font-extrabold {{ $scoreData['score'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ $scoreData['score'] > 0 ? '+'.$scoreData['score'] : $scoreData['score'] }}
                </span>
                <span class="text-[10px] text-slate-400 block font-semibold">{{ $scoreData['records_count'] }} Catatan</span>
            </div>
        @endforeach
    </div>

    <!-- MAIN CONTENT: 2 TABS (Catatan Guru vs Refleksi Siswa) -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        <!-- LEFT COLUMN: CATATAN OBSERVASI GURU -->
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-emerald-100 pb-3">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Catatan Observasi Wali Kelas</h3>
                    <p class="text-xs text-slate-500">Rekam catatan positif, evaluasi, &amp; prestasi</p>
                </div>
                <button type="button" @click="showRecordModal = true" class="inline-flex items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">+ Tambah</button>
            </div>

            @if($records->isNotEmpty())
                <div class="space-y-3">
                    @foreach($records as $rec)
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3 text-xs space-y-1.5 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $rec->type === 'positive' ? 'bg-emerald-100 text-emerald-800' : ($rec->type === 'achievement' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                    {{ ucfirst($rec->type) }}
                                </span>
                                <span class="font-mono font-bold {{ $rec->score >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    Skor: {{ $rec->score > 0 ? '+'.$rec->score : $rec->score }}
                                </span>
                            </div>

                            <h4 class="font-bold text-slate-900 text-xs">{{ $rec->title }}</h4>
                            <p class="text-slate-600">{{ $rec->description ?: 'Tidak ada deskripsi detail.' }}</p>

                            <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1 border-t border-slate-200/50">
                                <span>Dimensi: {{ $rec->dimension->name ?? '-' }}</span>
                                <span>{{ $rec->record_date->format('d M Y') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($records->hasPages())
                    <div class="pt-2">{{ $records->links() }}</div>
                @endif
            @else
                <div class="py-8 text-center text-xs text-slate-400 space-y-2">
                    <p class="font-bold text-slate-700">Belum ada catatan observasi guru</p>
                    <p>Klik "+ Catat Observasi Guru" di atas untuk menambahkan catatan karakter siswa ini.</p>
                </div>
            @endif
        </div>

        <!-- RIGHT COLUMN: JURNAL REFLEKSI MANDIRI SISWA -->
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-emerald-100 pb-3">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Jurnal Refleksi Mandiri (Siswa)</h3>
                    <p class="text-xs text-slate-500">Diisi sendiri oleh {{ $student->name }} dari HP</p>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                    Mandiri Siswa
                </span>
            </div>

            @if($reflections->isNotEmpty())
                <div class="space-y-3">
                    @foreach($reflections as $ref)
                        <div class="rounded-xl border border-purple-100 bg-purple-50/30 p-3.5 text-xs space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-purple-950">{{ $ref->dimension->name ?? 'Refleksi Karakter' }}</span>
                                <div class="flex items-center text-amber-500 font-bold">
                                    @for($i=1; $i<=$ref->self_rating; $i++) ★ @endfor
                                    <span class="text-slate-600 text-[10px] ml-1">({{ $ref->self_rating }}/5)</span>
                                </div>
                            </div>

                            <div class="space-y-1 text-slate-700 bg-white p-3 rounded-xl border border-purple-100">
                                <p><strong class="text-emerald-700">Hal Baik:</strong> {{ $ref->what_went_well }}</p>
                                <p><strong class="text-rose-700">Perlu Ditingkatkan:</strong> {{ $ref->what_to_improve }}</p>
                                <p><strong class="text-emerald-800">Rencana Aksi:</strong> {{ $ref->action_plan }}</p>
                            </div>

                            @if ($ref->kesan_teman)
                                <div class="bg-sky-50 p-2.5 rounded-xl border border-sky-200 text-sky-900">
                                    <span class="font-bold block text-[10px] uppercase text-sky-800">Kata Temannya</span>
                                    <p class="italic mt-0.5">"{{ $ref->kesan_teman }}"</p>
                                </div>
                            @endif

                            @if ($ref->pesan_ortu)
                                <div class="bg-amber-50 p-2.5 rounded-xl border border-amber-200 text-amber-900">
                                    <span class="font-bold block text-[10px] uppercase text-amber-800">Pesan untuk Orang Tua</span>
                                    <p class="italic mt-0.5">"{{ $ref->pesan_ortu }}"</p>
                                </div>
                            @endif

                            <!-- Teacher Feedback Section -->
                            @if($ref->teacher_feedback)
                                <div class="bg-emerald-50 p-2.5 rounded-xl text-emerald-900 border border-emerald-200">
                                    <span class="font-bold block text-[10px] uppercase text-emerald-700">Umpan Balik Wali Kelas:</span>
                                    <p class="italic">"{{ $ref->teacher_feedback }}"</p>
                                </div>
                            @else
                                <form method="POST" action="{{ route('classes.character-portfolio.reflection.feedback', [$class, $ref]) }}" class="flex gap-2 pt-1">
                                    @csrf
                                    <input type="text" name="teacher_feedback" value="{{ old('teacher_feedback') }}" placeholder="Beri umpan balik / motivasi singkat..." required 
                                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 flex-1">
                                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors shrink-0">Respon
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-xs text-slate-400 space-y-2">
                    <p class="font-bold text-slate-700">Belum ada refleksi mandiri dari {{ $student->name }}</p>
                    <p>Siswa dapat mengisi dari HP via tautan refleksi mandiri.</p>
                </div>
            @endif
        </div>

    </div>

    <!-- MODAL TAMBAH CATATAN OBSERVASI GURU -->
    <div x-show="showRecordModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden" @click.away="showRecordModal = false">
            <div class="flex items-center justify-between gap-3 border-b border-emerald-100 bg-emerald-50/40 px-5 py-4">
                <h2 class="text-sm font-extrabold text-slate-900">Tambah Catatan Observasi Guru</h2>
                <button type="button" @click="showRecordModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg" aria-label="Tutup">&times;</button>
            </div>

            <form method="POST" action="{{ route('classes.character-portfolio.record.store', [$class, $student]) }}" class="space-y-3.5 p-5 text-xs">
                @csrf
                <input type="hidden" name="student_id" value="{{ $student->id }}">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe Catatan <span class="text-rose-500">*</span></label>
                        <select name="type" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="positive">Catatan Positif</option>
                            <option value="negative">Catatan Evaluasi (Negatif)</option>
                            <option value="observation">Observasi Umum</option>
                            <option value="achievement">Prestasi Siswa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Skor Poin (-10 s/d +10) <span class="text-rose-500">*</span></label>
                        <input type="number" name="score" required min="-10" max="10" value="1" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Dimensi Profil Pelajar Pancasila <span class="text-rose-500">*</span></label>
                    <select name="character_dimension_id" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <option value="">-- Pilih Dimensi --</option>
                        @foreach($dimensions as $d)
                            <option value="{{ $d->id }}" @selected(old('character_dimension_id') == $d->id)>{{ $d->name }} ({{ $d->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Catatan <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="cth: Membantu merapikan bangku kelas" 
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Detail</label>
                    <textarea name="description" rows="2" placeholder="Jelaskan detail kejadian..." 
                              class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Observasi <span class="text-rose-500">*</span></label>
                    <input type="date" name="record_date" required value="{{ date('Y-m-d') }}" 
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                <div class="flex flex-col gap-2 pt-3 sm:flex-row sm:items-center border-t border-slate-100">
                    <button type="button" @click="showRecordModal = false" class="sm:w-1/3 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Batal</button>
                    <button type="submit" class="sm:w-2/3 inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">Simpan Catatan Observasi</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
