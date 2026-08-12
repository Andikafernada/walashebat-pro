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

    /*
     * Ikonnya dibuang, semuanya.
     *
     * Tiga belas ikon garis 14px di dalam kotak berwarna: tidak satu pun
     * dikenali tanpa membaca labelnya juga, jadi masing-masing hanya menambah
     * satu benda untuk dilewati mata sebelum sampai ke tulisan yang sebenarnya
     * dibaca. Yang hilang cuma 13 baris data jalur SVG; yang didapat, daftar
     * yang bisa dipindai dalam sekali lihat.
     */
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

<div class="mb-5 rounded-lg border border-slate-200 bg-white">
    {{--
        Penanda jenis kelas.

        Banyak wali kelas juga mengajar sebagai guru mapel, sehingga dalam satu
        sesi kerja mereka berpindah-pindah antara kelas perwalian dan kelas ajar.
        Sebelumnya satu-satunya petunjuk adalah tab mana yang absen — perbedaan
        yang baru terasa setelah salah masuk.
    --}}
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-slate-50 px-3 py-2">
        <span class="badge {{ $ajar ? 'badge--emerald' : 'badge--indigo' }}">{{ $ajar ? 'Guru Mapel' : 'Wali Kelas' }}</span>
        <span class="text-sm font-semibold tracking-tight text-slate-900">{{ $classroom->name }}</span>
        <span class="text-xs text-slate-500">
            @if ($ajar)
                {{ implode(' · ', $classroom->mapelDiampu()) ?: 'mapel belum diisi' }}
            @else
                seluruh administrasi kelas
            @endif
        </span>
    </div>

    {{-- Daftar isi buku: rapat, satu baris, digeser bila layarnya sempit. --}}
    <nav class="pembatas flex overflow-x-auto" aria-label="Navigasi kelas">
        @foreach ($tabs as $tab)
            @php $ini = request()->routeIs($tab['aktif']); @endphp
            <a href="{{ route($tab['route'], [$classroom]) }}"
               @if($ini) aria-current="page" @endif
               class="flex shrink-0 items-center gap-1.5 border-r border-slate-200 px-3 py-2 text-xs font-medium transition-colors last:border-r-0 {{ $ini ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                {{ $tab['label'] }}
                @if (($tab['badge'] ?? 0) > 0)
                    <span class="font-mono text-[10px] {{ $ini ? 'text-slate-300' : 'text-slate-400' }}">{{ $tab['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>
</div>
@endif
