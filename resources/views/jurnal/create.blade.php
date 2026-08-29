@extends('layouts.app')

@section('title', 'Buat Jurnal Mengajar AI - ' . $classroom->name)

@section('content')
<div class="space-y-6 pb-12" x-data="jurnalAi()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 font-semibold" aria-label="Breadcrumb">
                <a href="{{ route('classes.index') }}" class="hover:text-emerald-700">Daftar Kelas</a>
                <span>/</span>
                <a href="{{ route('classes.jurnal.index', $classroom) }}" class="hover:text-emerald-700">Jurnal Mengajar</a>
                <span>/</span>
                <span class="text-slate-900">Buat Jurnal</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Buat Jurnal Mengajar Baru</h1>
            <p class="text-xs sm:text-sm text-slate-600">Ketik materi/topik pembelajaran lalu klik tombol AI untuk merumuskan TP, Aktivitas, dan Refleksi otomatis.</p>
        </div>

        <a href="{{ route('classes.jurnal.index', $classroom) }}"
           class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors">
            <span>&larr;</span>
            <span>Kembali ke Rekap</span>
        </a>
    </div>

    @include('partials.flash')

    {{-- Form --}}
    <form method="POST" action="{{ route('classes.jurnal.store', $classroom) }}" class="space-y-6">
        @csrf

        {{-- Parameter Inti & Tombol AI --}}
        <div class="bg-white rounded-3xl border border-emerald-200 shadow-xs p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-emerald-100 pb-3">
                <h2 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                    <span>📌</span>
                    <span>Informasi Pembelajaran Hari Ini</span>
                </h2>
                <span class="text-[11px] font-bold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                    Kurikulum Merdeka
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                {{-- Tanggal --}}
                <div>
                    <label for="session_date" class="block text-xs font-bold text-slate-700 mb-1">Tanggal Pertemuan</label>
                    <input type="date" id="session_date" name="session_date" value="{{ date('Y-m-d') }}" required
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                {{-- Pertemuan Ke --}}
                <div>
                    <label for="meeting_number" class="block text-xs font-bold text-slate-700 mb-1">Pertemuan Ke-</label>
                    <input type="number" id="meeting_number" name="meeting_number" x-model="meetingNumber" min="1" required
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                {{-- Mata Pelajaran --}}
                <div>
                    <label for="subject" class="block text-xs font-bold text-slate-700 mb-1">Mata Pelajaran</label>
                    @if(count($mapelList) > 1)
                        <select id="subject" name="subject" x-model="subject" required
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            @foreach($mapelList as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" id="subject" name="subject" x-model="subject" placeholder="cth: Informatika" required
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @endif
                </div>

                {{-- Materi / Topik --}}
                <div>
                    <label for="topic" class="block text-xs font-bold text-slate-700 mb-1">Bab / Materi Ajar</label>
                    <input type="text" id="topic" name="topic" x-model="topic" placeholder="cth: Algoritma Pemrograman" required
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
            </div>

            {{-- AI Magic Generate Button --}}
            <div class="pt-2 flex items-center justify-between gap-3">
                <p class="text-[11px] text-slate-500">
                    💡 <em>Isi mata pelajaran dan materi, lalu klik tombol AI di kanan untuk merumuskan perangkat jurnal lengkap:</em>
                </p>
                <button type="button" @click="generateWithAi()" :disabled="loading || !topic"
                        class="shrink-0 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2.5 text-xs font-black text-white shadow-md shadow-emerald-600/20 hover:from-emerald-700 hover:to-teal-700 disabled:opacity-50 transition-all cursor-pointer">
                    <span x-show="!loading">🤖 Generate Rincian via OpenCode AI</span>
                    <span x-show="loading" class="animate-spin">🔄</span>
                    <span x-show="loading">Merumuskan TP &amp; Aktivitas...</span>
                </button>
            </div>
        </div>

        {{-- Hasil AI / Form Rincian --}}
        <div class="bg-white rounded-3xl border border-emerald-200 shadow-xs p-6 space-y-5">
            <h2 class="text-sm font-extrabold text-slate-900 border-b border-emerald-100 pb-3 flex items-center gap-2">
                <span>📝</span>
                <span>Rincian Perangkat &amp; Evaluasi Pembelajaran</span>
            </h2>

            {{-- Tujuan Pembelajaran --}}
            <div>
                <label for="learning_objective" class="block text-xs font-bold text-slate-700 mb-1">
                    Tujuan Pembelajaran (TP) / Capaian Pembelajaran (CP)
                </label>
                <textarea id="learning_objective" name="learning_objective" rows="4" x-model="learningObjective"
                          placeholder="Rumusan Tujuan Pembelajaran sesuai Taksonomi Bloom..."
                          class="w-full rounded-2xl border border-slate-200 bg-white p-3 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 leading-relaxed"></textarea>
            </div>

            {{-- Langkah / Aktivitas Pembelajaran --}}
            <div>
                <label for="activity" class="block text-xs font-bold text-slate-700 mb-1">
                    Aktivitas Pembelajaran (Pendahuluan, Kegiatan Inti, Penutup)
                </label>
                <textarea id="activity" name="activity" rows="8" x-model="activity"
                          placeholder="Langkah-langkah skenario pembelajaran di kelas..."
                          class="w-full rounded-2xl border border-slate-200 bg-white p-3 text-xs text-slate-900 font-mono text-[11px] focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 leading-relaxed"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Dimensi P5 --}}
                <div>
                    <label for="p5_dimension" class="block text-xs font-bold text-slate-700 mb-1">
                        Dimensi Profil Pelajar Pancasila (P5) Terkait
                    </label>
                    <input type="text" id="p5_dimension" name="p5_dimension" x-model="p5Dimension"
                           placeholder="cth: Bernalar Kritis & Gotong Royong"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>

                {{-- Catatan Kehadiran Singkat --}}
                <div>
                    <label for="attendance_summary" class="block text-xs font-bold text-slate-700 mb-1">
                        Catatan Kehadiran / Keterangan Khusus
                    </label>
                    <input type="text" id="attendance_summary" name="attendance_summary"
                           placeholder="cth: 35 Hadir, 1 Izin (Lengkap)"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
            </div>

            {{-- Refleksi Guru --}}
            <div>
                <label for="reflection" class="block text-xs font-bold text-slate-700 mb-1">
                    Refleksi Guru &amp; Catatan Tindak Lanjut
                </label>
                <textarea id="reflection" name="reflection" rows="3" x-model="reflection"
                          placeholder="Evaluasi suasana belajar, pencapaian KKTP siswa, dan rencana pendampingan pertemuan selanjutnya..."
                          class="w-full rounded-2xl border border-slate-200 bg-white p-3 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 leading-relaxed"></textarea>
            </div>
        </div>

        {{-- Submit Action --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('classes.jurnal.index', $classroom) }}"
               class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition-all hover:scale-105">
                💾 Simpan Jurnal Mengajar
            </button>
        </div>
    </form>
</div>

<script>
    function jurnalAi() {
        return {
            subject: '{{ $mapelList[0] ?? "Informatika" }}',
            topic: '',
            meetingNumber: {{ $nextMeeting }},
            learningObjective: '',
            activity: '',
            p5Dimension: '',
            reflection: '',
            loading: false,

            async generateWithAi() {
                if (!this.topic) return;
                this.loading = true;

                try {
                    const response = await fetch('{{ route("classes.jurnal.generate-ai", $classroom) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            subject: this.subject,
                            topic: this.topic,
                            meeting_number: this.meetingNumber,
                        }),
                    });

                    const res = await response.json();
                    if (res.success && res.data) {
                        this.learningObjective = res.data.learning_objective;
                        this.activity = res.data.activity;
                        this.p5Dimension = res.data.p5_dimension;
                        this.reflection = res.data.reflection;
                    }
                } catch (err) {
                    alert('Gagal menghasilkan jurnal dengan AI: ' + err);
                } finally {
                    this.loading = false;
                }
            }
        };
    }
</script>
@endsection
