@extends('layouts.app')

@section('title', 'Early Warning System (EWS) — ' . $classroom->name)

@section('content')

@include('partials.class-nav', ['classroom' => $classroom])

<div class="space-y-6 pb-12" x-data="ewsManager()">

    {{-- ══════════ 1. HEADER & INTRO ══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-950 border border-emerald-300">
                    🤖 AI-Driven Copilot
                </span>
                <span class="text-xs text-slate-500 font-semibold">{{ $periode['label'] }}</span>
            </div>
            <h1 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                Early Warning System (EWS) Siswa
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-600 font-medium">
                Deteksi dini anomali kehadiran, tren penurunan akademik, dan risiko kedisiplinan siswa di kelas {{ $classroom->name }}.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="window.location.reload()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all">
                <span>🔄</span>
                <span>Refresh Analisis AI</span>
            </button>
        </div>
    </div>

    @include('partials.flash')

    {{-- FILTER PERIODE BULAN EWS --}}
    <div class="bg-white rounded-2xl border border-emerald-200 p-3 sm:p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('classes.ews.index', $classroom) }}" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="mode" value="bulan">
            <label for="bulan" class="text-xs font-bold text-slate-700">Pilih Periode Bulan:</label>
            <input type="month" id="bulan" name="bulan" 
                   value="{{ request('bulan', $periode['bulan']->format('Y-m')) }}"
                   class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
            @if(request('level'))
                <input type="hidden" name="level" value="{{ request('level') }}">
            @endif
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition-colors shadow-2xs">
                Tampilkan
            </button>
        </form>
        <span class="text-xs font-semibold text-slate-500">
            Analisis Komprehensif Periode: <strong class="text-emerald-800">{{ $periode['label'] }}</strong>
        </span>
    </div>


    {{-- ══════════ 2. KPI METRICS & RISK LEVEL FILTER ══════════ --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 sm:gap-4">
        {{-- Total --}}
        <a href="{{ route('classes.ews.index', $classroom) }}"
           class="p-4 rounded-3xl border transition-all text-left group {{ !$activeLevel ? 'bg-emerald-600 text-white shadow-md border-emerald-600' : 'bg-white border-emerald-200 hover:bg-emerald-50 text-slate-900' }}">
            <span class="text-[10px] font-bold uppercase tracking-wider block {{ !$activeLevel ? 'text-emerald-100' : 'text-slate-500' }}">Semua Siswa</span>
            <p class="text-2xl font-extrabold mt-1">{{ $kpi['total'] }}</p>
            <p class="text-[11px] font-semibold mt-0.5 {{ !$activeLevel ? 'text-emerald-100' : 'text-slate-500' }}">Terekam di kelas</p>
        </a>

        {{-- Kritis --}}
        <a href="{{ route('classes.ews.index', ['class' => $classroom, 'level' => 'critical']) }}"
           class="p-4 rounded-3xl border transition-all text-left group {{ $activeLevel === 'critical' ? 'bg-rose-700 text-white shadow-md border-rose-700' : 'bg-white border-emerald-200 hover:bg-emerald-50 text-slate-900' }}">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider block {{ $activeLevel === 'critical' ? 'text-rose-100' : 'text-rose-700' }}">🔴 Kritis</span>
            </div>
            <p class="text-2xl font-extrabold mt-1 {{ $activeLevel === 'critical' ? 'text-white' : 'text-rose-700' }}">{{ $kpi['critical'] }}</p>
            <p class="text-[11px] font-semibold mt-0.5 {{ $activeLevel === 'critical' ? 'text-rose-100' : 'text-slate-500' }}">Butuh tindakan segera</p>
        </a>

        {{-- Peringatan Dini --}}
        <a href="{{ route('classes.ews.index', ['class' => $classroom, 'level' => 'warning']) }}"
           class="p-4 rounded-3xl border transition-all text-left group {{ $activeLevel === 'warning' ? 'bg-amber-600 text-white shadow-md border-amber-600' : 'bg-white border-emerald-200 hover:bg-emerald-50 text-slate-900' }}">
            <span class="text-[10px] font-bold uppercase tracking-wider block {{ $activeLevel === 'warning' ? 'text-amber-100' : 'text-amber-700' }}">🟠 Peringatan</span>
            <p class="text-2xl font-extrabold mt-1 {{ $activeLevel === 'warning' ? 'text-white' : 'text-amber-700' }}">{{ $kpi['warning'] }}</p>
            <p class="text-[11px] font-semibold mt-0.5 {{ $activeLevel === 'warning' ? 'text-amber-100' : 'text-slate-500' }}">Tren performa menurun</p>
        </a>

        {{-- Perhatian --}}
        <a href="{{ route('classes.ews.index', ['class' => $classroom, 'level' => 'attention']) }}"
           class="p-4 rounded-3xl border transition-all text-left group {{ $activeLevel === 'attention' ? 'bg-emerald-700 text-white shadow-md border-emerald-700' : 'bg-white border-emerald-200 hover:bg-emerald-50 text-slate-900' }}">
            <span class="text-[10px] font-bold uppercase tracking-wider block {{ $activeLevel === 'attention' ? 'text-emerald-100' : 'text-emerald-800' }}">🟡 Perhatian</span>
            <p class="text-2xl font-extrabold mt-1 {{ $activeLevel === 'attention' ? 'text-white' : 'text-slate-900' }}">{{ $kpi['attention'] }}</p>
            <p class="text-[11px] font-semibold mt-0.5 {{ $activeLevel === 'attention' ? 'text-emerald-100' : 'text-slate-500' }}">Anomali ringan</p>
        </a>

        {{-- Aman --}}
        <a href="{{ route('classes.ews.index', ['class' => $classroom, 'level' => 'safe']) }}"
           class="p-4 rounded-3xl border transition-all text-left group {{ $activeLevel === 'safe' ? 'bg-emerald-600 text-white shadow-md border-emerald-600' : 'bg-white border-emerald-200 hover:bg-emerald-50 text-slate-900' }}">
            <span class="text-[10px] font-bold uppercase tracking-wider block {{ $activeLevel === 'safe' ? 'text-emerald-100' : 'text-emerald-700' }}">🟢 Aman &amp; Baik</span>
            <p class="text-2xl font-extrabold mt-1 {{ $activeLevel === 'safe' ? 'text-white' : 'text-slate-900' }}">{{ $kpi['safe'] }}</p>
            <p class="text-[11px] font-semibold mt-0.5 {{ $activeLevel === 'safe' ? 'text-emerald-100' : 'text-slate-500' }}">Kondisi stabil optimal</p>
        </a>
    </div>

    {{-- ══════════ 3. DAFTAR SISWA & ANALISIS RISIKO ══════════ --}}
    @if ($students->isEmpty())
        <div class="rounded-3xl border border-emerald-200 bg-white p-12 text-center space-y-3 shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl mx-auto border border-emerald-200">✨</div>
            <p class="text-base font-bold text-slate-900">Tidak Ada Siswa pada Kategori Ini</p>
            <p class="text-xs text-slate-500 max-w-sm mx-auto font-medium">
                Semua siswa di kelas ini berada pada level risiko yang berbeda atau kondisi kelas sangat kondusif.
            </p>
        </div>
    @else
        {{-- DESKTOP VIEW: TABEL LEBAR --}}
        <div class="hidden md:block overflow-x-auto rounded-3xl border border-emerald-200 bg-white shadow-xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-emerald-50/70 border-b border-emerald-100 text-emerald-950 font-bold">
                        <th class="px-4 py-3.5 font-extrabold">Nama Siswa</th>
                        <th class="px-4 py-3.5 font-extrabold">Level &amp; Skor Risiko</th>
                        <th class="px-4 py-3.5 font-extrabold">Faktor Pemicu Anomali</th>
                        <th class="px-4 py-3.5 font-extrabold text-center">Kehadiran</th>
                        <th class="px-4 py-3.5 font-extrabold text-center">Nilai Rata2</th>
                        <th class="px-4 py-3.5 font-extrabold text-center">Poin Disiplin</th>
                        <th class="px-4 py-3.5 text-right font-extrabold">Tindakan AI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-100/60">
                    @foreach ($students as $item)
                        @php
                            $s = $item['student'];
                            $m = $item['metrics'];
                        @endphp
                        <tr class="hover:bg-emerald-50/40 transition-colors align-middle">
                            {{-- Siswa --}}
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-950 font-bold text-xs flex items-center justify-center shrink-0 border border-emerald-200">
                                        {{ Str::upper(Str::substr($s->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('classes.students.show', [$classroom, $s]) }}" class="font-bold text-slate-900 hover:text-emerald-700 block transition-colors">
                                            {{ $s->name }}
                                        </a>
                                        <span class="text-[10px] text-slate-500 font-medium">NIS: {{ $s->nis ?: '—' }} &middot; {{ $s->parent_phone ? '📱 Ortu Ada' : '⚠️ No HP Kosong' }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- Level & Skor Risiko --}}
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold border {{ $item['badge_class'] }}">
                                            {{ $item['icon'] }} {{ $item['level_label'] }} ({{ $item['risk_score'] }}%)
                                        </span>
                                    </div>
                                    <div class="w-28 h-1.5 rounded-full bg-emerald-100 overflow-hidden">
                                        <div class="h-full {{ $item['risk_score'] >= 70 ? 'bg-rose-600' : ($item['risk_score'] >= 45 ? 'bg-amber-500' : ($item['risk_score'] >= 20 ? 'bg-emerald-600' : 'bg-emerald-400')) }}"
                                             style="width: {{ $item['risk_score'] }}%"></div>
                                    </div>
                                </div>
                            </td>

                            {{-- Faktor Pemicu --}}
                            <td class="px-4 py-3.5 max-w-xs">
                                @if (count($item['triggers']) > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($item['triggers'] as $trig)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-50 text-slate-800 border border-emerald-200">
                                                {{ $trig }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-400 text-xs font-medium">Tidak ada anomali</span>
                                @endif
                            </td>

                            {{-- Kehadiran --}}
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="font-extrabold {{ $m['attendance_percent'] < 75 ? 'text-rose-700' : ($m['attendance_percent'] < 85 ? 'text-amber-700' : 'text-slate-900') }}">
                                    {{ $m['attendance_percent'] }}%
                                </span>
                                <span class="block text-[10px] text-slate-500 font-medium">A:{{ $m['alfa'] }} &middot; T:{{ $m['terlambat'] }} &middot; S/I:{{ $m['sakit'] + $m['izin'] }}</span>
                            </td>

                            {{-- Nilai Rata-rata --}}
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="font-extrabold {{ ($m['avg_score'] ?? 0) < 75 ? 'text-amber-700' : 'text-slate-900' }}">
                                    {{ $m['avg_score'] ?? '—' }}
                                </span>
                                @if ($m['scores_below_kkm'] > 0)
                                    <span class="block text-[10px] text-rose-600 font-semibold">{{ $m['scores_below_kkm'] }} &lt; KKM</span>
                                @endif
                            </td>

                            {{-- Poin Disiplin --}}
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="font-extrabold {{ $m['sisa_poin'] < 75 ? 'text-rose-700' : 'text-slate-900' }}">
                                    {{ $m['sisa_poin'] }}/100
                                </span>
                                @if ($m['violations_count'] > 0)
                                    <span class="block text-[10px] text-slate-500 font-medium">{{ $m['violations_count'] }} pelanggaran</span>
                                @endif
                            </td>

                            {{-- Aksi AI --}}
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <button type="button" @click="bukaModal(@js($item))"
                                        class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1 ml-auto">
                                    <span>🧠</span>
                                    <span>Analisis AI &amp; Solusi</span>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- MOBILE VIEW: KARTU SISWA EWS --}}
        <div class="md:hidden space-y-3">
            @foreach ($students as $item)
                @php
                    $s = $item['student'];
                    $m = $item['metrics'];
                @endphp
                <div class="bg-white rounded-2xl border border-emerald-200 p-4 shadow-xs space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-950 font-bold text-xs flex items-center justify-center shrink-0 border border-emerald-200">
                                {{ Str::upper(Str::substr($s->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-slate-900 truncate">{{ $s->name }}</h3>
                                <p class="text-[11px] text-slate-500 font-medium">NIS: {{ $s->nis ?: '—' }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold border shrink-0 {{ $item['badge_class'] }}">
                            {{ $item['icon'] }} {{ $item['level_label'] }} ({{ $item['risk_score'] }}%)
                        </span>
                    </div>

                    {{-- 3 Quick Metrics --}}
                    <div class="grid grid-cols-3 gap-2 bg-emerald-50/50 p-2.5 rounded-xl border border-emerald-100 text-center">
                        <div>
                            <span class="text-[9px] font-bold text-slate-500 uppercase block">Hadir</span>
                            <span class="text-xs font-extrabold text-slate-900">{{ $m['attendance_percent'] }}%</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-slate-500 uppercase block">Nilai</span>
                            <span class="text-xs font-extrabold text-slate-900">{{ $m['avg_score'] ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-bold text-slate-500 uppercase block">Poin</span>
                            <span class="text-xs font-extrabold text-slate-900">{{ $m['sisa_poin'] }}</span>
                        </div>
                    </div>

                    {{-- Triggers --}}
                    @if (count($item['triggers']) > 0)
                        <div class="flex flex-wrap gap-1">
                            @foreach ($item['triggers'] as $trig)
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-50 text-slate-800 border border-emerald-200">
                                    {{ $trig }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <button type="button" @click="bukaModal(@js($item))"
                            class="w-full py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-all flex items-center justify-center gap-1.5">
                        <span>🧠</span>
                        <span>Buka Diagnosa AI &amp; Solusi</span>
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ══════════ 4. INTERACTIVE AI COPILOT MODAL ══════════ --}}
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div class="bg-white rounded-3xl border border-emerald-200 max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl relative max-h-[90vh] overflow-y-auto"
             @click.away="modalOpen = false">

            {{-- Modal Header --}}
            <div class="flex items-start justify-between gap-4 border-b border-emerald-100 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border"
                              :class="currentData?.badge_class"
                              x-text="currentData?.icon + ' Tingkat Risiko: ' + currentData?.level_label + ' (' + currentData?.risk_score + '%)'"></span>
                    </div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 mt-1" x-text="currentData?.student?.name"></h2>
                    <p class="text-xs text-slate-500 font-medium">NIS: <span x-text="currentData?.student?.nis || '—'"></span> &middot; Kontak Ortu: <strong class="text-slate-800" x-text="currentData?.student?.parent_phone || 'Belum ada nomor'"></strong></p>
                </div>
                <button type="button" @click="modalOpen = false"
                        class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-sm transition-colors">
                    ✕
                </button>
            </div>

            {{-- 1. Diagnosa Cerdas AI --}}
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 space-y-2">
                <div class="flex items-center gap-2 text-xs font-extrabold text-emerald-950 uppercase tracking-wider">
                    <span>🧠</span>
                    <span>Diagnosa Cerdas AI (Akar Masalah)</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-800 font-medium leading-relaxed" x-text="currentData?.ai?.diagnosis"></p>
            </div>

            {{-- 2. Panduan Langkah Guru --}}
            <div class="space-y-2">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                    <span>📋</span>
                    <span>Rekomendasi Tindakan Guru / Wali Kelas</span>
                </h3>
                <ul class="space-y-2 text-xs text-slate-700 font-medium">
                    <template x-for="(rec, idx) in currentData?.ai?.recommendations" :key="idx">
                        <li class="flex items-start gap-2 p-2.5 rounded-xl bg-white border border-emerald-200">
                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-950 font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5" x-text="idx + 1"></span>
                            <span class="leading-relaxed" x-text="rec"></span>
                        </li>
                    </template>
                </ul>
            </div>

            {{-- 3. Draf Pesan WhatsApp Siap Kirim --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                        <span>💬</span>
                        <span>Draf Pesan WhatsApp ke Orang Tua</span>
                    </h3>
                    <button type="button" @click="salinWa()"
                            class="text-[11px] font-bold text-emerald-800 hover:underline">
                        <span x-text="copied ? '✓ Tersalin!' : '📋 Salin Pesan'"></span>
                    </button>
                </div>
                <textarea rows="4" readonly
                          class="w-full p-3 text-xs rounded-xl border border-emerald-200 bg-emerald-50/30 text-slate-800 font-mono leading-relaxed focus:outline-none"
                          x-text="currentData?.ai?.whatsapp_draft"></textarea>
                
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <template x-if="currentData?.student?.parent_phone">
                        <a :href="'https://wa.me/' + currentData?.student?.parent_phone.replace(/\D/g, '') + '?text=' + encodeURIComponent(currentData?.ai?.whatsapp_draft)"
                           target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-all">
                            <span>📲</span>
                            <span>Kirim Langsung ke WhatsApp Ortu</span>
                        </a>
                    </template>
                    <template x-if="!currentData?.student?.parent_phone">
                        <span class="text-xs text-slate-500 font-medium">⚠️ Nomor WhatsApp orang tua belum tercatat di biodata siswa.</span>
                    </template>
                </div>
            </div>

            {{-- 4. Rujukan BK --}}
            <template x-if="currentData?.risk_score >= 45">
                <div class="p-3.5 rounded-2xl bg-white border border-emerald-200 text-xs space-y-1">
                    <span class="font-bold text-slate-900 uppercase text-[10px] tracking-wider block">📑 Draf Rujukan Kasus Guru BK</span>
                    <p class="text-slate-600 font-medium" x-text="currentData?.ai?.bk_draft"></p>
                </div>
            </template>

            {{-- Modal Footer --}}
            <div class="border-t border-emerald-100 pt-4 flex justify-end">
                <button type="button" @click="modalOpen = false"
                        class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs transition-colors">
                    Tutup
                </button>
            </div>

        </div>
    </div>

</div>

<script>
function ewsManager() {
    return {
        modalOpen: false,
        currentData: null,
        copied: false,
        bukaModal(item) {
            this.currentData = item;
            this.copied = false;
            this.modalOpen = true;
        },
        salinWa() {
            if (this.currentData?.ai?.whatsapp_draft) {
                navigator.clipboard.writeText(this.currentData.ai.whatsapp_draft);
                this.copied = true;
                setTimeout(() => this.copied = false, 2500);
            }
        }
    }
}
</script>
@endsection
