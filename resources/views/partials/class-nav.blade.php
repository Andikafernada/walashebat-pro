@php
    $classroom = $classroom ?? $class ?? null;

    $tabs = [
        ['route' => 'classes.show',                    'aktif' => 'classes.show',                    'label' => 'Ringkasan',   'icon' => '📊'],
        ['route' => 'classes.students.index',          'aktif' => 'classes.students.*',              'label' => 'Siswa',       'icon' => '👥', 'badge' => $classroom->students_count ?? null],
        ['route' => 'classes.ews.index',               'aktif' => 'classes.ews.*',                   'label' => 'EWS Risiko',  'icon' => '🛡️'],
        ['route' => 'classes.attendance.index',        'aktif' => 'classes.attendance.*',            'label' => 'Absensi',     'icon' => '📋'],
        ['route' => 'classes.schedules.index',         'aktif' => 'classes.schedules.*',             'label' => 'Jadwal',      'icon' => '🗓️'],
        ['route' => 'classes.nilai.index',             'aktif' => 'classes.nilai.*',                 'label' => 'Nilai',       'icon' => '📝'],
        ['route' => 'classes.jurnal.index',            'aktif' => 'classes.jurnal.*',                'label' => 'Jurnal',      'icon' => '📖'],
        ['route' => 'classes.character-portfolio.index','aktif' => 'classes.character-portfolio.*',  'label' => 'Karakter P5', 'icon' => '🌱'],
        ['route' => 'classes.violations.index',        'aktif' => 'classes.violations.*',            'label' => 'Pelanggaran', 'icon' => '⚠️'],
        ['route' => 'classes.kerajinan.index',         'aktif' => 'classes.kerajinan.*',             'label' => 'Kerajinan',   'icon' => '🎖️'],
        ['route' => 'classes.cashbook.index',          'aktif' => 'classes.cashbook.*',              'label' => 'Buku Kas',    'icon' => '💰'],
        ['route' => 'classes.seating.index',           'aktif' => 'classes.seating.*',               'label' => 'Denah',       'icon' => '🪑'],
        ['route' => 'classes.organization.index',      'aktif' => 'classes.organization.*',          'label' => 'Struktur',    'icon' => '🏛️'],
        ['route' => 'classes.reports.analisis',        'aktif' => 'classes.reports.analisis',        'label' => 'Analisis',    'icon' => '📈'],
        ['route' => 'classes.rapor.narasi',          'aktif' => 'classes.rapor.narasi*',          'label' => 'AI Narasi Rapor', 'icon' => '🤖'],
        ['route' => 'classes.reports.full',            'aktif' => 'classes.reports.full',            'label' => 'Laporan PDF', 'icon' => '📄'],
    ];

    // Modul Guru Mapel: Siswa, Absensi, Nilai, Jurnal Mengajar, Analisis (+ Ringkasan)
    if ($classroom && $classroom->kelasAjar()) {
        $hanyaMapel = [
            'classes.show',
            'classes.students.index',
            'classes.attendance.index',
            'classes.nilai.index',
            'classes.jurnal.index',
            'classes.reports.analisis',
        ];
        $tabs = array_values(array_filter(
            $tabs,
            fn ($t) => in_array($t['route'], $hanyaMapel, true),
        ));
    }
@endphp

@if ($classroom)
@php $ajar = $classroom->kelasAjar(); @endphp

{{-- 1. KEPALA INFO KELAS (DESKTOP & MOBILE) --}}
<div class="mb-4 bg-white rounded-2xl border border-emerald-200 p-3 sm:p-4 shadow-xs">
    <div class="flex flex-wrap items-center justify-between gap-2.5">
        <div class="flex items-center gap-2 min-w-0">
            <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-[11px] font-black shrink-0 {{ $ajar ? 'bg-indigo-50 text-indigo-900 border border-indigo-200' : 'bg-emerald-50 text-emerald-950 border border-emerald-200' }}">
                {{ $ajar ? '📚 Guru Mapel' : '👑 Wali Kelas' }}
            </span>
            <span class="font-black text-slate-900 text-sm sm:text-base truncate">{{ $classroom->name }}</span>
            <span class="text-xs text-slate-400 font-medium hidden sm:inline">&middot; TA {{ $classroom->academic_year ?? '2026/2027' }}</span>
        </div>
        
        {{-- TOMBOL GANTI MENU DI HP (Pemicu Drawer Bawah) --}}
        <div class="lg:hidden">
            <button type="button" 
                    @click="openMenuDrawer = true"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-950 text-xs font-bold border border-emerald-200 shadow-2xs transition-all active:scale-95">
                <span>⚡ Modul Kelas</span>
                <span class="text-[10px]">▾</span>
            </button>
        </div>

        <div class="hidden lg:block text-xs text-slate-600 font-semibold">
            @if ($ajar)
                Mapel diampu: <span class="font-bold text-emerald-800">{{ implode(', ', $classroom->mapelDiampu()) ?: 'Semua Mapel' }}</span>
            @else
                Administrasi Kelas Terpadu
            @endif
        </div>
    </div>
</div>

{{-- 2. NAVIGASI TAB (DESKTOP SAJA) --}}
<div class="hidden lg:block mb-6 border-b border-emerald-200 bg-white rounded-2xl px-3 shadow-xs">
    <nav class="flex items-center gap-1 overflow-x-auto py-1" aria-label="Subnavigasi Kelas">
        @foreach ($tabs as $tab)
            @php
                $isAktif = request()->routeIs($tab['aktif']);
                $url = route($tab['route'], $classroom);
            @endphp
            <a href="{{ $url }}"
               class="inline-flex items-center gap-2 px-3.5 py-2.5 text-xs font-bold transition-all border-b-2 whitespace-nowrap {{ $isAktif ? 'border-emerald-600 text-emerald-950 bg-emerald-50/70 rounded-t-xl' : 'border-transparent text-slate-700 hover:text-slate-950 hover:bg-slate-50 rounded-xl' }}">
                <span>{{ $tab['icon'] }}</span>
                <span>{{ $tab['label'] }}</span>
                @if (!empty($tab['badge']))
                    <span class="ml-0.5 px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-emerald-200 text-emerald-950">{{ $tab['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>
</div>
@endif
