@php
    $classroom = $classroom ?? $class ?? null;

    $hanyaPerwalian = [
        'classes.organization.index',
        'classes.cashbook.index',
        'classes.seating.index',
        'classes.reports.full',
        'classes.violations.index',
        'classes.schedules.index',
    ];

    $tabs = [
        ['route' => 'classes.show', 'aktif' => 'classes.show', 'label' => 'Ringkasan'],
        ['route' => 'classes.students.index', 'aktif' => 'classes.students.*', 'label' => 'Siswa', 'badge' => $classroom->students_count ?? null],
        ['route' => 'classes.attendance.index', 'aktif' => 'classes.attendance.*', 'label' => 'Absensi'],
        ['route' => 'classes.schedules.index', 'aktif' => 'classes.schedules.*', 'label' => 'Jadwal'],
        ['route' => 'classes.organization.index', 'aktif' => 'classes.organization.*', 'label' => 'Struktur'],
        ['route' => 'classes.violations.index', 'aktif' => 'classes.violations.*', 'label' => 'Pelanggaran'],
        ['route' => 'classes.kerajinan.index', 'aktif' => 'classes.kerajinan.*', 'label' => 'Kerajinan'],
        ['route' => 'classes.cashbook.index', 'aktif' => 'classes.cashbook.*', 'label' => 'Buku Kas'],
        ['route' => 'classes.seating.index', 'aktif' => 'classes.seating.*', 'label' => 'Denah'],
        ['route' => 'classes.jurnal.index', 'aktif' => 'classes.jurnal.*', 'label' => 'Jurnal'],
        ['route' => 'classes.nilai.index', 'aktif' => 'classes.nilai.*', 'label' => 'Nilai'],
        ['route' => 'classes.reports.analisis', 'aktif' => 'classes.reports.analisis', 'label' => 'Analisis'],
        ['route' => 'classes.reports.full', 'aktif' => 'classes.reports.full', 'label' => 'Laporan'],
    ];

    if ($classroom && $classroom->kelasAjar()) {
        $tabs = array_values(array_filter(
            $tabs,
            fn ($t) => ! in_array($t['route'], $hanyaPerwalian, true),
        ));
    }
@endphp

@if ($classroom)
@php $ajar = $classroom->kelasAjar(); @endphp

<div class="mb-8 rounded-3xl border-3 border-pink-300 dark:border-purple-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur-2xl p-3 shadow-2xl space-y-3">
    {{-- Header Penanda Kelas Extravagant --}}
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-2 border-b-2 border-pink-100 dark:border-slate-800 bg-gradient-to-r from-pink-50 via-purple-50 to-cyan-50 dark:from-slate-800 dark:to-purple-950 rounded-2xl">
        <div class="flex items-center gap-3">
            <span class="badge {{ $ajar ? 'badge--emerald' : 'badge--indigo' }} shadow-md">✨ {{ $ajar ? 'Guru Mapel' : 'Wali Kelas Super' }}</span>
            <span class="text-base font-black text-slate-900 dark:text-white" style="font-family:'Plus Jakarta Sans',sans-serif;">{{ $classroom->name }}</span>
            <span class="text-xs font-black text-pink-600 dark:text-pink-400">
                @if ($ajar)
                    {{ implode(' · ', $classroom->mapelDiampu()) ?: 'mapel belum diisi' }}
                @else
                    seluruh administrasi kelas
                @endif
            </span>
        </div>
    </div>

    {{-- Navigasi Tab Rainbow Extravagant --}}
    <nav class="flex items-center gap-2 overflow-x-auto p-1.5" aria-label="Navigasi kelas">
        @foreach ($tabs as $tab)
            @php $ini = request()->routeIs($tab['aktif']); @endphp
            <a href="{{ route($tab['route'], [$classroom]) }}"
               @if($ini) aria-current="page" @endif
               class="shrink-0 flex items-center gap-2 px-5 py-2.5 text-xs font-black rounded-full transition-all duration-300 {{ $ini ? 'bg-gradient-to-r from-pink-500 via-purple-600 to-cyan-500 text-white shadow-xl shadow-pink-500/40 scale-110 border-2 border-white/60' : 'text-slate-800 dark:text-slate-200 hover:bg-pink-50 dark:hover:bg-slate-800 hover:text-pink-600 hover:scale-105 border-2 border-transparent' }}">
                {{ $tab['label'] }}
                @if (($tab['badge'] ?? 0) > 0)
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-black {{ $ini ? 'bg-white/30 text-white shadow-sm' : 'bg-pink-100 text-pink-700' }}">{{ $tab['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>
</div>
@endif
