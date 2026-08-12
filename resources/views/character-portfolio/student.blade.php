@extends('layouts.app')

@section('title', 'Portofolio Karakter - ' . $student->name)

@section('content')
<div class="space-y-6 pb-12" x-data="{ showRecordModal: false, showFeedbackModal: false, activeReflectionId: null }">

    <!-- Header Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('classes.index') }}" class="hover:text-indigo-600 transition-colors">Kelas</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('classes.character-portfolio.index', $class) }}" class="hover:text-indigo-600 transition-colors">Portofolio Karakter</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">{{ $student->name }}</span>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                Portofolio Karakter: {{ $student->name }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">NIS: {{ $student->nis ?? '-' }} &middot; Kelas {{ $class->name }}</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="showRecordModal = true"
                    class="btn-primary btn-primary--sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                + Catat Observasi Guru
            </button>
            <a href="{{ route('public.reflection.show', $class->tokenPublik()) }}" target="_blank"
               class="inline-flex items-center gap-1.5 rounded border border-purple-200 bg-purple-50 px-3.5 py-2 text-xs font-semibold text-purple-700 hover:bg-purple-100 transition-colors">
 Link Refleksi Siswa
            </a>
        </div>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $class])

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- Student Top Hero Card -->
    <div class="rounded-lg border border-slate-800 bg-slate-900 p-5 text-white">
        <div class="absolute right-0 top-0 -mr-10 -mt-10 h-48 w-48 rounded-full bg-purple-500/10"></div>
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded border border-white/20 font-mono text-xl font-medium text-white">
                    {{ substr($student->name, 0, 1) }}
                </div>
                <div>
                    <span class="text-[10px] font-semibold tracking-wider text-purple-300 uppercase block">Profil Pelajar Pancasila</span>
                    <h2 class="text-2xl font-semibold text-white">{{ $student->name }}</h2>
                    <p class="text-xs text-slate-300">Catatan Karakter Terkumpul: <span class="font-semibold text-white">{{ $records->total() }} Record</span></p>
                </div>
            </div>

            <!-- Badges Summary -->
            <div class="flex items-center gap-2">
                @forelse($badges as $sb)
                    <div class="flex items-center gap-1.5 rounded bg-white/10 px-3 py-1.5 border border-white/10 text-xs">
                        <span>{{ $sb->badge->icon ?? '🏅' }}</span>
                        <span class="font-semibold text-white text-[11px]">{{ $sb->badge->name }}</span>
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
            <div class="rounded-lg border border-slate-200 bg-white p-3 text-center space-y-1" style="border-top: 3px solid {{ $dim->color }};">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block truncate">{{ $dim->name }}</span>
                <span class="text-xl font-semibold {{ $scoreData['score'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $scoreData['score'] > 0 ? '+'.$scoreData['score'] : $scoreData['score'] }}
                </span>
                <span class="text-[10px] text-slate-400 block">{{ $scoreData['records_count'] }} Catatan</span>
            </div>
        @endforeach
    </div>

    <!-- MAIN CONTENT: 2 TABS (Catatan Guru vs Refleksi Siswa) -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        <!-- LEFT COLUMN: CATATAN OBSERVASI GURU -->
        <div class="card space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Catatan Observasi Wali Kelas</h3>
                    <p class="text-xs text-slate-500">Rekam catatan positif, evaluasi, &amp; prestasi</p>
                </div>
                <button type="button" @click="showRecordModal = true" class="h-8 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3 text-xs font-semibold transition-colors">
                    + Tambah
                </button>
            </div>

            @if($records->isNotEmpty())
                <div class="space-y-3">
                    @foreach($records as $rec)
                        <div class="rounded border border-slate-200 bg-slate-50/70 p-3 text-xs space-y-1.5 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-semibold {{ $rec->type === 'positive' ? 'bg-emerald-100 text-emerald-800' : ($rec->type === 'achievement' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                    {{ ucfirst($rec->type) }}
                                </span>
                                <span class="font-semibold {{ $rec->score >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    Skor: {{ $rec->score > 0 ? '+'.$rec->score : $rec->score }}
                                </span>
                            </div>

                            <h4 class="font-semibold text-slate-900 text-sm">{{ $rec->title }}</h4>
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
                    <p class="font-semibold text-slate-600">Belum ada catatan observasi guru</p>
                    <p>Klik "+ Catat Observasi Guru" di atas untuk menambahkan catatan karakter siswa ini.</p>
                </div>
            @endif
        </div>

        <!-- RIGHT COLUMN: JURNAL REFLEKSI MANDIRI SISWA -->
        <div class="card space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Jurnal Refleksi Mandiri (Siswa)</h3>
                    <p class="text-xs text-slate-500">Diisi sendiri oleh {{ $student->name }} dari HP</p>
                </div>
                <span class="rounded-sm bg-purple-100 px-2.5 py-0.5 text-[10px] font-semibold text-purple-800">
                    Mandiri Siswa
                </span>
            </div>

            @if($reflections->isNotEmpty())
                <div class="space-y-3">
                    @foreach($reflections as $ref)
                        <div class="rounded border border-purple-100 bg-purple-50/40 p-4 text-xs space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-purple-900">{{ $ref->dimension->name ?? 'Refleksi Karakter' }}</span>
                                <div class="flex items-center text-amber-400 font-semibold">
                                    @for($i=1; $i<=$ref->self_rating; $i++) ★ @endfor
                                    <span class="text-slate-600 text-[10px] ml-1">({{ $ref->self_rating }}/5)</span>
                                </div>
                            </div>

                            <div class="space-y-1 text-slate-700 bg-white p-2.5 rounded-lg border border-purple-100">
                                <p><strong class="text-emerald-700">✓ Hal Baik:</strong> {{ $ref->what_went_well }}</p>
                                <p><strong class="text-rose-700">Perlu Ditingkatkan:</strong> {{ $ref->what_to_improve }}</p>
                                <p><strong class="text-indigo-700">Rencana Aksi:</strong> {{ $ref->action_plan }}</p>
                            </div>

                            {{-- Cermin dari luar. Jarak antara kotak ini dan tiga isian
                                 di atasnya sering lebih memberi tahu daripada isinya
                                 sendiri — anak yang menilai dirinya sempurna tetapi
                                 menulis kesan teman yang pahit sedang jujur; yang
                                 sebaliknya sedang menutupi sesuatu. --}}
                            @if ($ref->kesan_teman)
                                <div class="bg-sky-50 p-2.5 rounded-lg border border-sky-200 text-sky-900">
                                    <span class="font-semibold block text-[10px] uppercase text-sky-700">Kata Temannya</span>
                                    <p class="italic mt-0.5">"{{ $ref->kesan_teman }}"</p>
                                </div>
                            @endif

                            {{-- Ditujukan kepada orang tua, jadi dipisahkan dari tiga
                                 isian di atas yang ditujukan ke diri sendiri & wali kelas. --}}
                            @if ($ref->pesan_ortu)
                                <div class="bg-amber-50 p-2.5 rounded-lg border border-amber-200 text-amber-900">
                                    <span class="font-semibold block text-[10px] uppercase text-amber-700">Pesan untuk Orang Tua</span>
                                    <p class="italic mt-0.5">"{{ $ref->pesan_ortu }}"</p>
                                </div>
                            @endif

                            <!-- Teacher Feedback Section -->
                            @if($ref->teacher_feedback)
                                <div class="bg-indigo-50 p-2 rounded-lg text-indigo-900 border border-indigo-100">
                                    <span class="font-semibold block text-[10px] uppercase text-indigo-600">Umpan Balik Wali Kelas:</span>
                                    <p class="italic">"{{ $ref->teacher_feedback }}"</p>
                                </div>
                            @else
                                <form method="POST" action="{{ route('classes.character-portfolio.reflection.feedback', [$class, $ref]) }}" class="flex gap-2 pt-1">
                                    @csrf
                                    <input type="text" name="teacher_feedback" value="{{ old('teacher_feedback') }}" placeholder="Beri umpan balik / motivasi singkat..." required class="h-8 flex-1 rounded-lg border border-slate-200 px-2.5 text-xs text-slate-800 focus:outline-none">
                                    <button type="submit" class="h-8 rounded-lg bg-purple-600 hover:bg-purple-700 text-white px-3 font-semibold text-xs shrink-0">
                                        ✓ Respon
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-xs text-slate-400 space-y-2">
                    <p class="font-semibold text-slate-600">Belum ada refleksi mandiri dari {{ $student->name }}</p>
                    <p>Siswa dapat mengisi dari HP via tautan refleksi mandiri.</p>
                </div>
            @endif
        </div>

    </div>

    <!-- MODAL TAMBAH CATATAN OBSERVASI GURU -->
    <div x-show="showRecordModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
        <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-2xl space-y-4" @click.away="showRecordModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <h3 class="text-base font-semibold text-slate-900">Tambah Catatan Observasi Guru</h3>
                <button type="button" @click="showRecordModal = false" class="text-slate-400 hover:text-slate-600 font-semibold text-xl">×</button>
            </div>

            <form method="POST" action="{{ route('classes.character-portfolio.record.store', [$class, $student]) }}" class="space-y-3 text-xs">
                @csrf
                <input type="hidden" name="student_id" value="{{ $student->id }}">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Tipe Catatan *</label>
                        <select name="type" required class="form-input form-input--sm">
                            <option value="positive">Catatan Positif</option>
                            <option value="negative">Catatan Evaluasi (Negatif)</option>
                            <option value="observation">Observasi Umum</option>
                            <option value="achievement">Prestasi Siswa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Skor Poin (-10 s/d +10) *</label>
                        <input type="number" name="score" required min="-10" max="10" value="1" class="form-input form-input--sm">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Dimensi Profil Pelajar Pancasila *</label>
                    <select name="character_dimension_id" required class="form-input form-input--sm">
                        <option value="">-- Pilih Dimensi --</option>
                        @foreach($dimensions as $d)
                            <option value="{{ $d->id }}" @selected(old('character_dimension_id') == $d->id)>{{ $d->name }} ({{ $d->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Judul Catatan *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="cth: Membantu merapikan bangku kelas" class="form-input form-input--sm">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Detail</label>
                    <textarea name="description" rows="2" placeholder="Jelaskan detail kejadian..." class="form-input form-input--sm"></textarea>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Tanggal Observasi *</label>
                    <input type="date" name="record_date" required value="{{ date('Y-m-d') }}" class="form-input form-input--sm">
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <button type="button" @click="showRecordModal = false" class="h-10 w-1/3 rounded border border-slate-200 bg-slate-100 font-semibold text-slate-700">
                        Batal
                    </button>
                    <button type="submit" class="h-10 w-2/3 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition-colors">
                        ✓ Simpan Catatan Observasi
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
