@extends('layouts.app')

@section('title', 'Broadcast WhatsApp Pengumuman — ' . $classroom->name)

@section('content')

@include('partials.class-nav', ['classroom' => $classroom])

<div class="space-y-6 pb-12" x-data="{
    message: `{{ $templates[0]['pesan'] }}`,
    selectedTemplate: 0,
    templates: @js($templates),
    students: @js($students),
    selectedStudents: @js(collect($students)->pluck('id')),
    previewStudentIndex: 0,

    insertTag(tag) {
        this.message += ' ' + tag + ' ';
    },

    setTemplate(index) {
        this.selectedTemplate = index;
        this.message = this.templates[index].pesan;
    },

    toggleSelectAll() {
        if (this.selectedStudents.length === this.students.length) {
            this.selectedStudents = [];
        } else {
            this.selectedStudents = this.students.map(s => s.id);
        }
    },

    get previewMessage() {
        if (!this.students.length) return this.message;
        let s = this.students[this.previewStudentIndex] || this.students[0];
        return this.message
            .replace(/\{\{nama_siswa\}\}/g, s.name)
            .replace(/\{\{nama_ortu\}\}/g, s.parent_name)
            .replace(/\{\{nis\}\}/g, s.nis)
            .replace(/\{\{persen_kehadiran\}\}/g, s.persen_kehadiran)
            .replace(/\{\{nama_kelas\}\}/g, '{{ $classroom->name }}');
    },

    getWaLink(student) {
        let phone = (student.parent_phone || '').replace(/\D/g, '');
        if (phone.startsWith('0')) phone = '62' + phone.substring(1);
        if (!phone) return '#';
        let msg = this.message
            .replace(/\{\{nama_siswa\}\}/g, student.name)
            .replace(/\{\{nama_ortu\}\}/g, student.parent_name)
            .replace(/\{\{nis\}\}/g, student.nis)
            .replace(/\{\{persen_kehadiran\}\}/g, student.persen_kehadiran)
            .replace(/\{\{nama_kelas\}\}/g, '{{ $classroom->name }}');
        return 'https://api.whatsapp.com/send?phone=' + phone + '&text=' + encodeURIComponent(msg);
    }
}">

    {{-- ══════════ 1. HEADER ══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white rounded-3xl border border-emerald-200 p-5 sm:p-6 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-950 border border-emerald-300">
                    📢 WhatsApp Blast Cerdas
                </span>
                <span class="text-xs text-slate-500 font-bold">Personalisasi Nama Otomatis</span>
            </div>
            <h1 class="mt-1 text-xl sm:text-2xl font-black tracking-tight text-slate-900">
                Pesan Siaran Pengumuman Kelas
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500 font-medium">
                Kirim pengumuman resmi ke seluruh orang tua siswa kelas {{ $classroom->name }} dengan sapaan nama otomatis.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('whatsapp.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                <span>📱</span>
                <span>Status Gateway</span>
            </a>
        </div>
    </div>

    @include('partials.flash')

    <form method="POST" action="{{ route('classes.broadcast.send', $classroom) }}">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- ══════════ 2. KOLOM KIRI: EDITOR PESAN & TAGS (7 COLS) ══════════ --}}
            <div class="lg:col-span-7 space-y-4">

                {{-- Template Cepat --}}
                <div class="bg-white rounded-3xl border border-emerald-200 p-4 sm:p-5 shadow-xs space-y-3">
                    <label class="block text-xs font-extrabold text-slate-900 uppercase tracking-wider">
                        ⚡ Pilih Template Pengumuman Cepat:
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($templates as $idx => $t)
                            <button type="button" 
                                    @click="setTemplate({{ $idx }})"
                                    :class="selectedTemplate === {{ $idx }} ? 'bg-emerald-600 text-white font-black shadow-xs' : 'bg-slate-50 hover:bg-emerald-50 text-slate-700 font-bold border border-slate-200'"
                                    class="p-2.5 rounded-xl text-xs text-left transition-all flex items-center justify-between">
                                <span class="truncate">{{ $t['judul'] }}</span>
                                <span class="text-[10px]" x-show="selectedTemplate === {{ $idx }}">✓</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Textarea & Tags --}}
                <div class="bg-white rounded-3xl border border-emerald-200 p-4 sm:p-5 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <label for="message_template" class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">
                            📝 Format Teks Pengumuman:
                        </label>
                        <span class="text-[11px] text-slate-400 font-bold">Klik tag untuk menyisipkan:</span>
                    </div>

                    {{-- Merge Tags Click-to-Insert --}}
                    <div class="flex flex-wrap gap-1.5 bg-emerald-50/70 p-2.5 rounded-2xl border border-emerald-200">
                        <button type="button" @click="insertTag('\{\{nama_siswa\}\}')" class="px-2.5 py-1 rounded-lg bg-white hover:bg-emerald-100 text-emerald-900 font-extrabold text-[11px] border border-emerald-200 shadow-2xs transition-all active:scale-95">
                            + {{'{{nama_siswa}}'}}
                        </button>
                        <button type="button" @click="insertTag('\{\{nama_ortu\}\}')" class="px-2.5 py-1 rounded-lg bg-white hover:bg-emerald-100 text-emerald-900 font-extrabold text-[11px] border border-emerald-200 shadow-2xs transition-all active:scale-95">
                            + {{'{{nama_ortu}}'}}
                        </button>
                        <button type="button" @click="insertTag('\{\{persen_kehadiran\}\}')" class="px-2.5 py-1 rounded-lg bg-white hover:bg-emerald-100 text-emerald-900 font-extrabold text-[11px] border border-emerald-200 shadow-2xs transition-all active:scale-95">
                            + {{'{{persen_kehadiran}}'}}
                        </button>
                        <button type="button" @click="insertTag('\{\{nis\}\}')" class="px-2.5 py-1 rounded-lg bg-white hover:bg-emerald-100 text-emerald-900 font-extrabold text-[11px] border border-emerald-200 shadow-2xs transition-all active:scale-95">
                            + {{'{{nis}}'}}
                        </button>
                    </div>

                    <textarea id="message_template" name="message_template" rows="8" x-model="message" required
                              class="w-full rounded-2xl border border-slate-200 bg-white p-3.5 text-xs text-slate-900 leading-relaxed placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 shadow-2xs font-mono"></textarea>
                    <p class="text-[11px] text-slate-400">
                        💡 Tag dalam kurung kurawal akan otomatis diganti dengan data siswa masing-masing saat terkirim.
                    </p>
                </div>

                {{-- Tombol Kirim Massal --}}
                <div class="bg-gradient-to-tr from-emerald-700 to-teal-700 rounded-3xl p-5 text-white shadow-md flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <h4 class="text-sm font-black">Kirim Pengumuman Massal</h4>
                        <p class="text-xs text-emerald-100 mt-0.5">
                            Target: <span class="font-extrabold text-white" x-text="selectedStudents.length"></span> Orang Tua Siswa Terpilih
                        </p>
                    </div>
                    <button type="submit" 
                            :disabled="selectedStudents.length === 0"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-white text-emerald-900 font-black text-xs hover:bg-emerald-50 transition-all shadow-md active:scale-95 cursor-pointer disabled:opacity-50">
                        <span>🚀</span>
                        <span>Kirim Otomatis via Bot WA</span>
                    </button>
                </div>

            </div>

            {{-- ══════════ 3. KOLOM KANAN: LIVE CHAT PREVIEW & ROSTER (5 COLS) ══════════ --}}
            <div class="lg:col-span-5 space-y-4">

                {{-- LIVE WHATSAPP BUBBLE PREVIEW --}}
                <div class="bg-[#efeae2] rounded-3xl border border-emerald-200/80 p-4 shadow-xs space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-300/60">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">💬</span>
                            <span class="text-xs font-black text-slate-800">Pratinjau WhatsApp Ortu</span>
                        </div>
                        {{-- Pilih Siswa Simulasi --}}
                        <select x-model="previewStudentIndex" class="rounded-xl border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-bold text-slate-800 focus:outline-none">
                            <template x-for="(st, idx) in students" :key="st.id">
                                <option :value="idx" x-text="'Simulasi: ' + st.name"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Chat Bubble --}}
                    <div class="bg-white rounded-2xl rounded-tl-none p-3.5 shadow-xs border border-slate-200/60 space-y-2">
                        <p class="text-xs text-slate-800 whitespace-pre-line leading-relaxed" x-text="previewMessage"></p>
                        <div class="text-right text-[10px] text-slate-400 font-mono">
                            {{ date('H:i') }} WIB &middot; <span class="text-teal-600 font-bold">✓✓</span>
                        </div>
                    </div>
                </div>

                {{-- DAFTAR REKAN ORANG TUA (CHECKLIST & JAPRI) --}}
                <div class="bg-white rounded-3xl border border-emerald-200 overflow-hidden shadow-xs">
                    <div class="p-3.5 border-b border-emerald-100 bg-emerald-50/60 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="toggleSelectAll()" class="text-xs font-black text-emerald-900 hover:underline">
                                <span x-text="selectedStudents.length === students.length ? 'Batal Pilih Semua' : 'Pilih Semua'"></span>
                            </button>
                        </div>
                        <span class="text-xs font-bold text-slate-500" x-text="selectedStudents.length + '/' + students.length + ' Siswa'"></span>
                    </div>

                    <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                        <template x-for="st in students" :key="st.id">
                            <div class="p-3 flex items-center justify-between gap-2 hover:bg-slate-50 transition-colors">
                                <label class="flex items-center gap-2.5 min-w-0 cursor-pointer flex-1">
                                    <input type="checkbox" name="student_ids[]" :value="st.id" x-model="selectedStudents"
                                           class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <div class="min-w-0">
                                        <p class="text-xs font-extrabold text-slate-900 truncate" x-text="st.name"></p>
                                        <p class="text-[10px] text-slate-400 font-medium">
                                            Ortu: <span class="text-slate-600 font-bold" x-text="st.parent_name"></span> &middot; WA: <span class="text-slate-600" x-text="st.parent_phone || 'Belum ada nomor'"></span>
                                        </p>
                                    </div>
                                </label>

                                {{-- Tombol Japri Langsung --}}
                                <a :href="getWaLink(st)" target="_blank"
                                   :class="st.parent_phone ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200' : 'opacity-30 pointer-events-none bg-slate-100 text-slate-400 border-slate-200'"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-bold border transition-colors shrink-0 shadow-2xs">
                                    <span>📲</span>
                                    <span>Japri</span>
                                </a>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

        </div>

    </form>
</div>
@endsection
