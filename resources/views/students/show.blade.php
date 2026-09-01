@extends('layouts.app')
@section('title', 'Karakter Siswa - ' . $siswa->name)
@section('content')

@php
    $kodeStatus = [
        'hadir' => ['H', 'Hadir', 'bg-emerald-100 text-emerald-950 border border-emerald-300'],
        'terlambat' => ['T', 'Terlambat', 'bg-amber-100 text-amber-950 border border-amber-300'],
        'sakit' => ['S', 'Sakit', 'bg-sky-100 text-sky-950 border border-sky-300'],
        'izin' => ['I', 'Izin', 'bg-purple-100 text-purple-950 border border-purple-300'],
        'alfa' => ['A', 'Alfa', 'bg-rose-100 text-rose-950 border border-rose-300'],
    ];

    $q = request()->query();
    $ajar = $classroom->kelasAjar();

    $persen = $kehadiran['persen'];
    $warnaHadir = $persen === null ? 'text-slate-500' : ($persen >= 85 ? 'text-emerald-700' : ($persen >= 75 ? 'text-amber-700' : 'text-rose-700'));

    // Rating OVR Siswa (Footbar Style)
    $scoreHadir = $persen ?? 100;
    $scoreDisiplin = min(100, max(0, $poin['sekarang'] ?? 100));
    $scoreNilai = $nilai['rata_rapor'] ? (float) $nilai['rata_rapor'] : 85;
    $scoreP5 = min(99, max(50, 80 + ($karakter['total']['positif'] ?? 0) * 2 - ($karakter['total']['negatif'] ?? 0) * 4));
    
    $ovrRating = round(($scoreHadir * 0.35) + ($scoreDisiplin * 0.25) + ($scoreNilai * 0.25) + ($scoreP5 * 0.15));
    if ($ovrRating > 99) $ovrRating = 99;
    if ($ovrRating < 50) $ovrRating = 50;

    $isGold = $ovrRating >= 90;
    $isEmerald = $ovrRating >= 75 && $ovrRating < 90;
    
    $posisiBadge = $peran && $peran->isNotEmpty() ? $peran->first()->roleLabel() : 'SISWA';
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">

<style>
    .fut-card-shimmer {
        background: linear-gradient(135deg, 
            rgba(255,255,255,0.35) 0%, 
            rgba(255,255,255,0.05) 45%, 
            rgba(255,255,255,0.25) 55%, 
            rgba(255,255,255,0.02) 100%);
        pointer-events: none;
    }
    .fut-foil-gold {
        background: radial-gradient(circle at 50% 20%, #fef08a 0%, #facc15 35%, #ca8a04 70%, #713f12 100%);
    }
    .fut-foil-emerald {
        background: radial-gradient(circle at 50% 20%, #a7f3d0 0%, #34d399 35%, #059669 70%, #064e3b 100%);
    }
    .fut-foil-silver {
        background: radial-gradient(circle at 50% 20%, #f1f5f9 0%, #cbd5e1 35%, #64748b 70%, #1e293b 100%);
    }
    .fut-inner-shield {
        clip-path: polygon(0 0, 100% 0, 100% 85%, 50% 100%, 0 85%);
    }
</style>

<div class="space-y-6 pb-12" x-data="{
    tab: 'presensi',
    tabs: [
        { id: 'presensi', label: 'Presensi', icon: '📋' },
        @unless($ajar) { id: 'disiplin', label: 'Disiplin', icon: '⚠️' }, @endunless
        { id: 'nilai', label: 'Nilai & Akademik', icon: '📝' },
        { id: 'p5', label: 'Karakter P5', icon: '🌱' },
        @unless($ajar) { id: 'biodata', label: 'Biodata', icon: '👤' }, @endunless
    ]
}">

    {{-- ══════════ 1. TOP NAVIGASI SISWA PREV / NEXT ══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white rounded-2xl border border-emerald-200 px-4 py-3 shadow-xs">
        <nav class="text-xs font-bold text-slate-900 flex items-center gap-1.5" aria-label="Breadcrumb">
            <a href="{{ route('classes.index') }}" class="text-slate-600 hover:text-emerald-700 transition-colors">Kelas</a>
            <span aria-hidden="true" class="text-slate-300">/</span>
            <a href="{{ route('classes.students.index', $classroom) }}" class="text-slate-600 hover:text-emerald-700 transition-colors">{{ $classroom->name }}</a>
            <span aria-hidden="true" class="text-slate-300">/</span>
            <span class="text-slate-950 font-black truncate max-w-[150px] sm:max-w-xs">{{ $siswa->name }}</span>
        </nav>

        <div class="flex items-center gap-2 self-end sm:self-auto">
            @if (isset($prevStudent) && $prevStudent)
                <a href="{{ route('classes.students.show', [$classroom, $prevStudent] + $q) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-emerald-50 text-slate-800 text-xs font-extrabold shadow-2xs transition-all hover:border-emerald-300"
                   title="Siswa Sebelumnya: {{ $prevStudent->name }}">
                    <span>&larr;</span>
                    <span class="hidden md:inline truncate max-w-[120px]">{{ $prevStudent->name }}</span>
                    <span class="md:hidden">Sebelumnya</span>
                </a>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-400 text-xs font-semibold cursor-not-allowed">
                    &larr; Sebelumnya
                </span>
            @endif

            <span class="px-3 py-1 rounded-xl bg-emerald-100/80 border border-emerald-200 text-emerald-950 font-black text-xs">
                {{ $posisiNomor ?? 1 }} <span class="text-slate-400 font-normal">/</span> {{ $totalSiswaKelas ?? 1 }}
            </span>

            @if (isset($nextStudent) && $nextStudent)
                <a href="{{ route('classes.students.show', [$classroom, $nextStudent] + $q) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-emerald-50 text-slate-800 text-xs font-extrabold shadow-2xs transition-all hover:border-emerald-300"
                   title="Siswa Selanjutnya: {{ $nextStudent->name }}">
                    <span class="hidden md:inline truncate max-w-[120px]">{{ $nextStudent->name }}</span>
                    <span class="md:hidden">Selanjutnya</span>
                    <span>&rarr;</span>
                </a>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-400 text-xs font-semibold cursor-not-allowed">
                    Selanjutnya &rarr;
                </span>
            @endif
        </div>
    </div>

    
    {{-- ══════════ FILTER PERIODE BULAN & SEMESTER + CETAK PORTOFOLIO PDF ══════════ --}}
    <div class="bg-white rounded-2xl border border-emerald-200 p-3.5 sm:p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('classes.students.show', [$classroom, $siswa]) }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="mode" value="bulan">
            <label for="bulan" class="text-xs font-bold text-slate-700">Filter Bulan:</label>
            <input type="month" id="bulan" name="bulan" 
                   value="{{ request('bulan', $periode['bulan']->format('Y-m')) }}"
                   class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition-colors shadow-2xs">
                Tampilkan Bulan
            </button>
        </form>

        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                <a href="{{ route('classes.students.show', [$classroom, $siswa, 'mode' => 'semester', 'semester' => 1]) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all {{ request('mode') === 'semester' && request('semester', 1) == 1 ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Semester 1
                </a>
                <a href="{{ route('classes.students.show', [$classroom, $siswa, 'mode' => 'semester', 'semester' => 2]) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all {{ request('mode') === 'semester' && request('semester') == 2 ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Semester 2
                </a>
            </div>

            <a href="{{ route('classes.students.pdf', array_merge([$classroom, $siswa], request()->query())) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md shadow-emerald-600/20 transition-all hover:scale-105 cursor-pointer">
                <span>🖨️</span>
                <span>Cetak Portofolio Lengkap PDF</span>
            </a>
        </div>
    </div>

    {{-- ══════════ 2. MASTER HERO SECTION (HOLOGRAPHIC FOOTBAR CARD + ANALYTICS) ══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        {{-- ══════ SISI KIRI: HOLOGRAPHIC FOOTBAR / FUT PLAYER TRADING CARD ══════ --}}
        <div class="lg:col-span-5 flex flex-col items-center">
            
            {{-- THE FUT CARD --}}
            <div class="w-full max-w-sm rounded-[34px] p-2 {{ $isGold ? 'fut-foil-gold' : ($isEmerald ? 'fut-foil-emerald' : 'fut-foil-silver') }} shadow-[0_20px_50px_-10px_rgba(6,78,59,0.35)] relative overflow-hidden group transition-all duration-300 hover:scale-[1.02]">
                
                {{-- Shimmer Foil Reflection Overlay --}}
                <div class="absolute inset-0 fut-card-shimmer z-20"></div>

                {{-- Inner Card Body --}}
                <div class="relative z-10 w-full rounded-[28px] bg-gradient-to-b from-[#064e3b] via-[#022c22] to-[#01140e] p-5 text-white flex flex-col justify-between min-h-[480px] border border-white/20 shadow-inner">
                    
                    {{-- TOP ROW: OVR RATING, BADGE, NATION & CLUB --}}
                    <div class="flex items-start justify-between relative z-10">
                        {{-- Left Rating Column --}}
                        <div class="flex flex-col items-start leading-none space-y-1">
                            <span class="text-4xl sm:text-5xl font-black tracking-tight {{ $isGold ? 'text-amber-300' : 'text-emerald-300' }}" style="font-family: 'Bebas Neue', sans-serif;">
                                {{ $ovrRating }}
                            </span>
                            <span class="text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md bg-white/15 text-white border border-white/20">
                                {{ Str::limit($posisiBadge, 10) }}
                            </span>
                            <div class="flex items-center gap-1 mt-1 text-sm">
                                <span>🇮🇩</span>
                                <span class="text-[9px] font-bold text-slate-300 uppercase">IND</span>
                            </div>
                        </div>

                        {{-- Right School / Class Crest --}}
                        <div class="flex flex-col items-end">
                            <span class="px-2.5 py-1 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-[10px] font-black text-emerald-200 uppercase tracking-wider shadow-xs">
                                🏫 {{ $classroom->name }}
                            </span>
                            <span class="text-[8px] font-bold text-slate-400 mt-1 uppercase tracking-widest">
                                FUT EDITION
                            </span>
                        </div>
                    </div>

                    {{-- CENTER: LARGE STUDENT PORTRAIT --}}
                    <div class="relative z-10 flex flex-col items-center justify-center my-auto py-2">
                        <div class="relative w-36 h-36 sm:w-40 sm:h-40 rounded-full p-1 bg-gradient-to-tr from-amber-400 via-emerald-300 to-teal-400 shadow-[0_0_30px_rgba(52,211,153,0.4)] overflow-hidden">
                            @if ($siswa->photoUrl())
                                <img src="{{ $siswa->photoUrl() }}" alt="{{ $siswa->name }}" class="w-full h-full object-cover rounded-full">
                            @else
                                <div class="w-full h-full rounded-full bg-emerald-950 flex items-center justify-center text-4xl sm:text-5xl font-black text-emerald-300">
                                    {{ str($siswa->name)->substr(0, 1)->upper() }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- PLAYER NAME & NIS --}}
                    <div class="relative z-10 text-center space-y-0.5 border-b border-white/15 pb-3">
                        <h2 class="text-2xl sm:text-3xl font-black tracking-wider uppercase text-white drop-shadow-md" style="font-family: 'Bebas Neue', sans-serif;">
                            {{ $siswa->name }}
                        </h2>
                        <p class="text-[11px] font-bold text-emerald-300 tracking-wider font-mono">
                            NIS: {{ $siswa->nis ?: '—' }} &middot; {{ $siswa->gender == 'L' ? 'LAKI-LAKI' : 'PEREMPUAN' }}
                        </p>
                    </div>

                    {{-- FIFA 6-CORE ATTRIBUTE STAT MATRIX --}}
                    <div class="relative z-10 grid grid-cols-6 gap-1 pt-3 text-center">
                        {{-- HAD --}}
                        <div class="flex flex-col items-center">
                            <span class="text-[9px] font-extrabold text-slate-400">HAD</span>
                            <span class="text-xs sm:text-sm font-black text-white">{{ $persen ?? 100 }}%</span>
                        </div>
                        {{-- DSP --}}
                        <div class="flex flex-col items-center">
                            <span class="text-[9px] font-extrabold text-slate-400">DSP</span>
                            <span class="text-xs sm:text-sm font-black {{ ($poin['sekarang'] ?? 100) >= 90 ? 'text-emerald-400' : 'text-amber-400' }}">{{ $poin['sekarang'] ?? 100 }}</span>
                        </div>
                        {{-- KRJ --}}
                        <div class="flex flex-col items-center">
                            <span class="text-[9px] font-extrabold text-slate-400">KRJ</span>
                            <span class="text-xs sm:text-sm font-black text-sky-400">+{{ $siswa->diligence_points ?? 0 }}</span>
                        </div>
                        {{-- AKD --}}
                        <div class="flex flex-col items-center">
                            <span class="text-[9px] font-extrabold text-slate-400">AKD</span>
                            <span class="text-xs sm:text-sm font-black text-amber-300">{{ $nilai['rata_rapor'] ?: '85' }}</span>
                        </div>
                        {{-- P5 --}}
                        <div class="flex flex-col items-center">
                            <span class="text-[9px] font-extrabold text-slate-400">P5</span>
                            <span class="text-xs sm:text-sm font-black text-teal-300">{{ $scoreP5 }}</span>
                        </div>
                        {{-- EWS --}}
                        <div class="flex flex-col items-center">
                            <span class="text-[9px] font-extrabold text-slate-400">EWS</span>
                            <span class="text-xs sm:text-sm font-black text-emerald-400">A+</span>
                        </div>
                    </div>

                </div>

            </div>

            {{-- QUICK ACTION BUTTONS UNDER CARD --}}
            <div class="w-full max-w-sm grid grid-cols-3 gap-2 mt-4">
                @if ($siswa->parent_phone)
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $siswa->parent_phone) }}?text={{ urlencode('Halo Bapak/Ibu wali dari '.$siswa->name.', kami dari pihak sekolah menginformasikan terkait perkembangan ananda...') }}" 
                       target="_blank" rel="noopener"
                       class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white text-xs font-bold shadow-md shadow-emerald-600/25 transition-all">
                        <span>💬</span>
                        <span>Chat WA</span>
                    </a>
                @else
                    <button disabled class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-2xl bg-slate-200 text-slate-400 text-xs font-bold cursor-not-allowed">
                        <span>💬</span>
                        <span>No WA —</span>
                    </button>
                @endif

                <a href="{{ route('classes.students.pdf', [$classroom, $siswa] + $q) }}"
                   class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-2xl bg-white hover:bg-slate-50 active:scale-95 border border-slate-200 text-slate-900 text-xs font-bold shadow-2xs transition-all">
                    <span>📄</span>
                    <span>Cetak PDF</span>
                </a>

                <a href="{{ route('classes.students.edit', [$classroom, $siswa]) }}"
                   class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-2xl bg-white hover:bg-slate-50 active:scale-95 border border-slate-200 text-slate-900 text-xs font-bold shadow-2xs transition-all">
                    <span>✏️</span>
                    <span>Edit Data</span>
                </a>
            </div>

        </div>

        {{-- ══════ SISI KANAN: MODERN SEGMENTED ANALYTICS DASHBOARD ══════ --}}
        <div class="lg:col-span-7 bg-white rounded-3xl border border-emerald-200 shadow-xs p-5 sm:p-7 space-y-6">
            
            {{-- SEGMENTED TAB CONTROL BAR --}}
            <div class="flex items-center gap-1.5 overflow-x-auto p-1 bg-emerald-50/80 rounded-2xl border border-emerald-100 scrollbar-none">
                <template x-for="t in tabs" :key="t.id">
                    <button type="button" @click="tab = t.id"
                            :class="tab === t.id ? 'bg-white text-emerald-950 font-black shadow-xs border border-emerald-200' : 'text-slate-600 hover:text-slate-900 font-bold'"
                            class="flex-1 py-2 px-3 rounded-xl text-xs whitespace-nowrap transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span x-text="t.icon"></span>
                        <span x-text="t.label"></span>
                    </button>
                </template>
            </div>

            {{-- ══════ TAB 1: PRESENSI & TREN KEHADIRAN ══════ --}}
            <div x-show="tab === 'presensi'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-5">
                
                {{-- Tren 6 Bulan --}}
                <div class="p-4 rounded-2xl bg-gradient-to-b from-[#f0fdf4] to-white border border-emerald-100 shadow-2xs space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Grafik Kehadiran 6 Bulan Terakhir</h4>
                            <p class="text-[11px] text-slate-500">Persentase kehadiran masuk kelas per bulan</p>
                        </div>
                        <span class="text-xs font-black px-2.5 py-1 rounded-xl bg-white border border-emerald-200 {{ $warnaHadir }}">
                            {{ $persen === null ? '—' : $persen.'%' }} Rata-rata
                        </span>
                    </div>

                    <div class="flex items-end gap-2.5 pt-2" style="height: 100px;">
                        @foreach ($tren as $t)
                            <div class="flex flex-1 flex-col items-center justify-end gap-1 h-full">
                                @if ($t['persen'] === null)
                                    <div class="w-full bg-slate-200/80 rounded-lg" style="height: 6px;"></div>
                                @else
                                    <div class="w-full rounded-lg transition-all shadow-xs {{ $t['persen'] >= 85 ? 'bg-emerald-500' : ($t['persen'] >= 75 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                         style="height: {{ max(10, round($t['persen'] * 0.75)) }}px;"></div>
                                    <span class="text-[10px] font-black text-slate-900">{{ $t['persen'] }}%</span>
                                @endif
                                <span class="text-[10px] font-bold text-slate-600">{{ $t['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Status H/S/I/A Counters --}}
                <div class="grid grid-cols-4 gap-2">
                    <div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-center">
                        <span class="text-[10px] font-black text-emerald-900 uppercase">Hadir</span>
                        <span class="text-base font-black text-slate-950 block mt-0.5">{{ $kehadiran['jumlah']['hadir'] ?? 0 }}</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-sky-50 border border-sky-200 text-center">
                        <span class="text-[10px] font-black text-sky-900 uppercase">Sakit</span>
                        <span class="text-base font-black text-slate-950 block mt-0.5">{{ $kehadiran['jumlah']['sakit'] ?? 0 }}</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-purple-50 border border-purple-200 text-center">
                        <span class="text-[10px] font-black text-purple-900 uppercase">Izin</span>
                        <span class="text-base font-black text-slate-950 block mt-0.5">{{ $kehadiran['jumlah']['izin'] ?? 0 }}</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-center">
                        <span class="text-[10px] font-black text-rose-900 uppercase">Alfa</span>
                        <span class="text-base font-black text-slate-950 block mt-0.5">{{ $kehadiran['jumlah']['alfa'] ?? 0 }}</span>
                    </div>
                </div>

                {{-- Log Riwayat Terakhir --}}
                <div class="space-y-2">
                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Log Riwayat Presensi Terbaru</h4>
                    @if ($absensi->isEmpty())
                        <p class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-500 text-center font-medium">Belum ada catatan presensi pada periode ini.</p>
                    @else
                        <div class="max-h-52 overflow-y-auto space-y-1.5 pr-1 scrollbar-thin">
                            @foreach ($absensi->take(8) as $a)
                                @php [$huruf, $label, $gaya] = $kodeStatus[$a->status] ?? ['—', '—', 'bg-slate-100 text-slate-900 border border-slate-200']; @endphp
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 shadow-2xs flex items-center justify-between text-xs hover:bg-emerald-50/50 transition-colors">
                                    <div class="flex items-center gap-2.5 font-mono">
                                        <span class="inline-flex w-6 h-6 items-center justify-center rounded-lg font-black text-[11px] {{ $gaya }}">{{ $huruf }}</span>
                                        <span class="text-slate-900 font-bold">{{ $a->session?->session_date?->format('d M Y') }}</span>
                                    </div>
                                    <span class="text-slate-600 font-medium truncate max-w-[180px] text-[11px]">{{ $a->note ?: 'Tercatat Hadir' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

            {{-- ══════ TAB 2: DISIPLIN & PELANGGARAN ══════ --}}
            @unless($ajar)
            <div x-show="tab === 'disiplin'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">
                
                {{-- Health Bar Poin --}}
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700">Status Poin Kedisiplinan:</span>
                        <span class="font-black text-sm {{ ($poin['sekarang'] ?? 100) >= 90 ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $poin['sekarang'] ?? 100 }} / 100 Poin
                        </span>
                    </div>
                    <div class="w-full h-3 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all {{ ($poin['sekarang'] ?? 100) >= 80 ? 'bg-emerald-500' : (($poin['sekarang'] ?? 100) >= 60 ? 'bg-amber-500' : 'bg-rose-500') }}"
                             style="width: {{ $poin['persen'] ?? 100 }}%;"></div>
                    </div>
                </div>

                {{-- Riwayat Pelanggaran --}}
                @if ($pelanggaran->isEmpty())
                    <div class="p-8 rounded-2xl bg-emerald-50 border border-emerald-200 text-center space-y-1">
                        <p class="text-2xl">🎖️</p>
                        <p class="text-xs font-black text-emerald-950">Siswa Berdisiplin Sangat Baik!</p>
                        <p class="text-[11px] text-emerald-800">Tidak ada catatan pelanggaran tata tertib pada semester ini.</p>
                    </div>
                @else
                    <div class="space-y-2">
                        <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Catatan Kejadian Pelanggaran:</h4>
                        <div class="max-h-56 overflow-y-auto space-y-2 pr-1 scrollbar-thin">
                            @foreach ($pelanggaran as $p)
                                <div class="p-3 rounded-xl bg-white border border-rose-200 shadow-2xs flex items-center justify-between text-xs">
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $p->violationType?->name ?: 'Pelanggaran Disiplin' }}</p>
                                        <p class="text-[10px] text-slate-500">{{ $p->violation_date?->format('d M Y') }} &middot; {{ $p->notes ?: 'Telah ditindaklanjuti' }}</p>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-lg bg-rose-100 text-rose-800 font-black text-xs">
                                        -{{ $p->points }} Poin
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
            @endunless

            {{-- ══════ TAB 3: NILAI & AKADEMIK ══════ --}}
            <div x-show="tab === 'nilai'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">
                
                @if (! $nilai['ada'])
                    <div class="p-8 rounded-2xl bg-slate-50 border border-slate-200 text-center space-y-1">
                        <p class="text-2xl">📝</p>
                        <p class="text-xs font-black text-slate-900">Belum Ada Catatan Nilai</p>
                        <p class="text-[11px] text-slate-500">Nilai formatif atau sumatif belum diinput untuk siswa ini.</p>
                    </div>
                @else
                    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-emerald-900">Rata-Rata Capaian Rapor:</span>
                            <p class="text-[11px] text-emerald-800">Berdasarkan asesmen semester aktif</p>
                        </div>
                        <span class="text-2xl font-black text-slate-950 font-mono">{{ $nilai['rata_rapor'] ?: '—' }}</span>
                    </div>

                    @if ($nilai['rapor'])
                        <div class="space-y-2">
                            <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider">Rekap Capaian Per Mata Pelajaran:</h4>
                            <div class="max-h-56 overflow-y-auto space-y-2 pr-1 scrollbar-thin">
                                @foreach ($nilai['rapor'] as $b)
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 shadow-2xs flex items-center justify-between text-xs">
                                        <span class="font-bold text-slate-900">{{ $b['mapel'] }}</span>
                                        <div class="flex items-center gap-3 font-mono font-bold">
                                            <span class="text-slate-600 text-[11px]">PTS: <strong class="text-slate-950">{{ $b['pts'] ?? '—' }}</strong></span>
                                            <span class="text-slate-600 text-[11px]">PAS: <strong class="text-slate-950">{{ $b['pas'] ?? '—' }}</strong></span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif

            </div>

            {{-- ══════ TAB 4: KARAKTER P5 ══════ --}}
            <div x-show="tab === 'p5'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">
                
                @if (! $karakter['dimensi'])
                    <div class="p-8 rounded-2xl bg-slate-50 border border-slate-200 text-center space-y-1">
                        <p class="text-2xl">🌱</p>
                        <p class="text-xs font-black text-slate-900">Belum Ada Observasi Karakter P5</p>
                        <p class="text-[11px] text-slate-500">Catat perkembangan karakter Profil Pelajar Pancasila di modul Karakter.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-64 overflow-y-auto pr-1 scrollbar-thin">
                        @foreach ($karakter['dimensi'] as $d)
                            <div class="p-3 rounded-2xl bg-teal-50 border border-teal-200 shadow-2xs flex items-center justify-between text-xs">
                                <span class="font-black text-teal-950 truncate">{{ $d['dimensi'] }}</span>
                                <span class="font-mono font-black text-xs text-teal-800 bg-white px-2 py-0.5 rounded-lg border border-teal-200">+{{ $d['positif'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

            </div>

            {{-- ══════ TAB 5: BIODATA SISWA ══════ --}}
            @unless($ajar)
            <div x-show="tab === 'biodata'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-2 text-xs">
                
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex justify-between">
                    <span class="text-slate-500 font-semibold">Tempat, Tgl Lahir:</span>
                    <span class="font-bold text-slate-950">{{ $siswa->tempat_lahir ?: '—' }}, {{ $siswa->tanggal_lahir ? \Illuminate\Support\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : '—' }}</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex justify-between">
                    <span class="text-slate-500 font-semibold">Nama Orang Tua:</span>
                    <span class="font-bold text-slate-950">{{ $siswa->nama_ayah ?: $siswa->nama_ibu ?: '—' }}</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex justify-between">
                    <span class="text-slate-500 font-semibold">No HP Orang Tua:</span>
                    <span class="font-black text-emerald-800 font-mono">{{ $siswa->parent_phone ?: '—' }}</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex justify-between">
                    <span class="text-slate-500 font-semibold">Alamat Domisili:</span>
                    <span class="font-bold text-slate-950 truncate max-w-[220px]">{{ $siswa->address ?: '—' }}</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex justify-between">
                    <span class="text-slate-500 font-semibold">Status Bantuan:</span>
                    <span class="font-bold text-slate-950">{{ $siswa->penerima_kip ? 'Penerima KIP' : ($siswa->penerima_pkh ? 'Penerima PKH' : 'Reguler') }}</span>
                </div>

            </div>
            @endunless

        </div>

    </div>

</div>
@endsection
