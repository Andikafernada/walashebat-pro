@extends('layouts.app')
@section('title', 'AI Narasi Rapor — ' . $classroom->name)
@section('content')

@include('partials.class-nav', ['classroom' => $classroom])

<div class="space-y-6 pb-12" x-data="{
    searchQuery: '',
    copiedIndex: null,
    copyText(text, index) {
        navigator.clipboard.writeText(text).then(() => {
            this.copiedIndex = index;
            setTimeout(() => { this.copiedIndex = null; }, 2000);
        });
    }
}">

    {{-- HEADER & ACTIONS --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white rounded-3xl border border-emerald-200 p-5 sm:p-6 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-950 font-black text-xs">
                    🤖 OpenCode AI Generator
                </span>
                <span class="text-xs font-bold text-slate-500">Kurikulum Merdeka</span>
            </div>
            <h1 class="mt-1 text-xl sm:text-2xl font-black tracking-tight text-slate-950">
                Generator Narasi Rapor Otomatis
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-700 font-medium">
                {{ $classroom->name }} &middot; Narasi deskripsi capaian pembelajaran &amp; catatan wali kelas siap salin ke e-Rapor.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Filter Semester -->
            <div class="inline-flex rounded-xl border border-emerald-200 bg-emerald-50/50 p-1">
                <a href="{{ request()->fullUrlWithQuery(['semester' => 1]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $semester == 1 ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-700 hover:text-black' }}">
                    Semester 1
                </a>
                <a href="{{ request()->fullUrlWithQuery(['semester' => 2]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $semester == 2 ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-700 hover:text-black' }}">
                    Semester 2
                </a>
            </div>

            <a href="{{ route('classes.rapor.narasi.pdf', [$classroom, 'semester' => $semester]) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-950 text-xs font-bold shadow-xs transition-all">
                <span>📄</span>
                <span>Cetak PDF</span>
            </a>
        </div>
    </div>

    {{-- SEARCH BAR --}}
    <div class="relative max-w-md">
        <input type="text" x-model="searchQuery" placeholder="Cari nama siswa atau NIS..."
               class="block w-full rounded-2xl border border-emerald-200 bg-white px-4 py-2.5 text-xs text-slate-950 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 shadow-2xs">
        <span class="absolute right-3.5 top-2.5 text-slate-400 text-sm">🔍</span>
    </div>

    {{-- LIST KARTU NARASI SISWA --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach ($narratives as $idx => $item)
            <div class="bg-white rounded-3xl border border-emerald-200 p-5 sm:p-6 shadow-xs flex flex-col justify-between space-y-4 hover:border-emerald-400 transition-all"
                 x-show="searchQuery === '' || '{{ strtolower($item['name']) }}'.includes(searchQuery.toLowerCase()) || '{{ $item['nis'] }}'.includes(searchQuery)">
                
                <div>
                    <!-- Header Siswa -->
                    <div class="flex items-center justify-between border-b border-emerald-100 pb-3 mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-950 font-black text-sm flex items-center justify-center border border-emerald-200">
                                {{ $idx + 1 }}
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-950">{{ $item['name'] }}</h3>
                                <p class="text-[11px] text-slate-600 font-mono">
                                    NIS: <strong class="text-black">{{ $item['nis'] ?: '—' }}</strong> &middot; 
                                    Hadir: <strong class="text-emerald-800">{{ $item['attendance_rate'] }}%</strong>
                                </p>
                            </div>
                        </div>

                        @if ($item['role'])
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-950 border border-amber-300">
                                👑 {{ $item['role'] }}
                            </span>
                        @endif
                    </div>

                    <!-- 3 Bagian Narasi Resmi -->
                    <div class="space-y-3 text-xs">
                        <!-- 1. Capaian Akademik -->
                        <div class="p-3 rounded-2xl bg-emerald-50/50 border border-emerald-100 space-y-1">
                            <span class="text-[10px] font-black text-emerald-950 block uppercase tracking-wider">
                                📚 Capaian Kompetensi Pembelajaran:
                            </span>
                            <p class="text-slate-900 leading-relaxed font-medium">
                                {{ $item['academic_narrative'] }}
                            </p>
                        </div>

                        <!-- 2. Karakter P5 -->
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
                            <span class="text-[10px] font-black text-slate-900 block uppercase tracking-wider">
                                🌱 Dimensi Profil Pelajar Pancasila:
                            </span>
                            <p class="text-slate-900 leading-relaxed font-medium">
                                {{ $item['character_narrative'] }}
                            </p>
                        </div>

                        <!-- 3. Catatan Wali Kelas -->
                        <div class="p-3 rounded-2xl bg-amber-50/60 border border-amber-200 space-y-1">
                            <span class="text-[10px] font-black text-amber-950 block uppercase tracking-wider">
                                ✍️ Catatan Resmi Wali Kelas:
                            </span>
                            <p class="text-slate-900 leading-relaxed font-medium">
                                {{ $item['homeroom_notes'] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer Tombol Salin Narasi -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[10px] text-slate-400 font-medium">Format e-Rapor Kemendikbud</span>
                    <button type="button"
                            @click="copyText(`{{ addslashes($item['full_text']) }}`, {{ $idx }})"
                            :class="copiedIndex === {{ $idx }} ? 'bg-emerald-700 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white'"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-2xs">
                        <span x-text="copiedIndex === {{ $idx }} ? '✅ Tersalin!' : '📋 Salin Narasi'"></span>
                    </button>
                </div>

            </div>
        @endforeach
    </div>

</div>
@endsection
