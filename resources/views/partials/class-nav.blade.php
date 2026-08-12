@php
    $classroom = $classroom ?? $class ?? null;

    /*
     * Modul yang HANYA milik wali kelas.
     *
     * Pada kelas ajar, wali kelasnya orang lain: buku kas, struktur organisasi,
     * denah, dan laporan administrasi bukan urusan guru mapel. Menampilkannya
     * mengundang guru mengisi data yang akan menjadi salinan kedua dan
     * menyimpang dari milik wali kelas aslinya.
     *
     * Pelanggaran dan jadwal masuk daftar ini dengan alasan yang sama.
     * Buku poin pelanggaran adalah catatan pembinaan yang dipegang satu orang —
     * wali kelasnya; bila setiap guru mapel ikut mencatat, poin siswa dihitung
     * berkali-kali dari kejadian yang sama dan sanksinya jadi salah. Jadwal
     * pelajaran pun disusun per rombongan belajar oleh wali kelas/kurikulum,
     * bukan per mapel, sehingga guru mapel yang mengubahnya menimpa jadwal
     * seluruh kelas tanpa menyadarinya.
     *
     * Yang disembunyikan hanya MENU-nya, bukan route-nya: data pelanggaran dan
     * jadwal yang terlanjur ada harus tetap bisa dijangkau.
     */
    $hanyaPerwalian = [
        'classes.organization.index',
        'classes.cashbook.index',
        'classes.seating.index',
        'classes.reports.full',
        'classes.violations.index',
        'classes.schedules.index',
    ];

    $tabs = [
        ['route' => 'classes.show', 'aktif' => 'classes.show', 'label' => 'Ringkasan', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
        ['route' => 'classes.students.index', 'aktif' => 'classes.students.*', 'label' => 'Siswa', 'badge' => $classroom->students_count ?? null, 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z'],
        ['route' => 'classes.attendance.index', 'aktif' => 'classes.attendance.*', 'label' => 'Absensi', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
        ['route' => 'classes.schedules.index', 'aktif' => 'classes.schedules.*', 'label' => 'Jadwal', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['route' => 'classes.organization.index', 'aktif' => 'classes.organization.*', 'label' => 'Struktur', 'icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2'],
        ['route' => 'classes.violations.index', 'aktif' => 'classes.violations.*', 'label' => 'Pelanggaran', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
        ['route' => 'classes.kerajinan.index', 'aktif' => 'classes.kerajinan.*', 'label' => 'Kerajinan', 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
        ['route' => 'classes.cashbook.index', 'aktif' => 'classes.cashbook.*', 'label' => 'Buku Kas', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
        ['route' => 'classes.seating.index', 'aktif' => 'classes.seating.*', 'label' => 'Denah', 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z'],
        ['route' => 'classes.jurnal.index', 'aktif' => 'classes.jurnal.*', 'label' => 'Jurnal', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
        ['route' => 'classes.nilai.index', 'aktif' => 'classes.nilai.*', 'label' => 'Nilai', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
        ['route' => 'classes.reports.analisis', 'aktif' => 'classes.reports.analisis', 'label' => 'Analisis', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ['route' => 'classes.reports.full', 'aktif' => 'classes.reports.full', 'label' => 'Laporan', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ];

    if ($classroom && $classroom->kelasAjar()) {
        $tabs = array_values(array_filter(
            $tabs,
            fn ($t) => ! in_array($t['route'], $hanyaPerwalian, true),
        ));
    }
@endphp

{{-- Navigation tabs dengan modern glass styling --}}
@if ($classroom)
<div class="mb-6">
    {{--
        Penanda jenis kelas.

        Banyak wali kelas juga mengajar sebagai guru mapel, sehingga dalam satu
        sesi kerja mereka berpindah-pindah antara kelas perwalian dan kelas ajar.
        Sebelumnya satu-satunya petunjuk adalah tab mana yang absen — perbedaan
        yang baru terasa setelah salah masuk. Warna dan tulisannya sengaja
        dibuat sama persis dengan kartu di daftar kelas.
    --}}
    @php $ajar = $classroom->kelasAjar(); @endphp
    <div class="mb-3 flex flex-wrap items-center gap-2.5 rounded-xl border {{ $ajar ? 'border-teal-200 bg-teal-50/60' : 'border-indigo-200 bg-indigo-50/60' }} px-3.5 py-2.5">
        <span class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[10px] font-black uppercase tracking-wider text-white {{ $ajar ? 'bg-teal-600' : 'bg-indigo-600' }}">
            {{ $ajar ? '📚 Guru Mapel' : '🏫 Wali Kelas' }}
        </span>
        <span class="text-sm font-bold text-slate-900">{{ $classroom->name }}</span>
        <span class="text-xs {{ $ajar ? 'text-teal-800' : 'text-indigo-800' }}">
            @if ($ajar)
                {{ implode(' · ', $classroom->mapelDiampu()) ?: 'mapel belum diisi' }}
            @else
                seluruh administrasi kelas
            @endif
        </span>
    </div>

    <div class="flex flex-wrap gap-2.5" role="tablist" aria-label="Navigasi kelas">
        @foreach ($tabs as $tab)
            @php $ini = request()->routeIs($tab['aktif']); @endphp
            <a href="{{ route($tab['route'], [$classroom]) }}"
               class="group inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-semibold transition-all duration-300
                      {{ $ini
                          ? 'border-indigo-300 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow-md shadow-indigo-500/20'
                          : 'border-slate-200/80 bg-white/90 text-slate-700 hover:border-indigo-300 hover:bg-slate-50 hover:text-indigo-600 shadow-xs'
                      }}"
               role="tab"
               aria-selected="{{ $ini ? 'true' : 'false' }}"
               @if($ini) aria-current="page" @endif>
                <span class="flex h-6 w-6 items-center justify-center rounded-lg transition-colors
                           {{ $ini
                               ? 'bg-white/20 text-white'
                               : 'bg-slate-100 text-slate-500 group-hover:bg-indigo-100 group-hover:text-indigo-600'
                           }}">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}"/>
                    </svg>
                </span>
                {{ $tab['label'] }}
                @if (($tab['badge'] ?? 0) > 0)
                    <span class="ml-0.5 rounded-full {{ $ini ? 'bg-white/20 text-white' : 'bg-indigo-100 text-indigo-700' }} px-2 py-0.5 text-[10px] font-bold tabular-nums">
                        {{ $tab['badge'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</div>
@endif
