@php
    $kelasAktif = $classroom ?? $class ?? null;
    $isKelasAjar = $kelasAktif && $kelasAktif->kelasAjar();

    $urlKelasHome      = $kelasAktif ? route('classes.show', $kelasAktif) : route('classes.index');
    $urlKelasSiswa     = $kelasAktif ? route('classes.students.index', $kelasAktif) : route('classes.index');
    $urlKelasAbsensi   = $kelasAktif ? route('classes.attendance.index', $kelasAktif) : route('classes.index');
    $urlKelasJurnal    = $kelasAktif ? route('classes.jurnal.index', $kelasAktif) : route('classes.index');
    $urlKelasNilai     = $kelasAktif ? route('classes.nilai.index', $kelasAktif) : route('classes.index');
    $urlKelasJadwal    = $kelasAktif ? route('classes.schedules.index', $kelasAktif) : route('classes.index');
    $urlKelasEws       = $kelasAktif ? route('classes.ews.index', $kelasAktif) : route('classes.index');
    $urlKelasKarakter  = $kelasAktif ? route('classes.character-portfolio.index', $kelasAktif) : route('classes.index');
    $urlKelasViolasi   = $kelasAktif ? route('classes.violations.index', $kelasAktif) : route('classes.index');
    $urlKelasKerajinan = $kelasAktif ? route('classes.kerajinan.index', $kelasAktif) : route('classes.index');
    $urlKelasKas       = $kelasAktif ? route('classes.cashbook.index', $kelasAktif) : route('classes.index');
    $urlKelasDenah     = $kelasAktif ? route('classes.seating.index', $kelasAktif) : route('classes.index');
    $urlKelasStruktur  = $kelasAktif ? route('classes.organization.index', $kelasAktif) : route('classes.index');
    $urlKelasAnalisis  = $kelasAktif ? route('classes.reports.analisis', $kelasAktif) : route('classes.index');
    $urlKelasNarasi    = $kelasAktif ? route('classes.rapor.narasi', $kelasAktif) : route('classes.index');
    $urlKelasLaporan   = $kelasAktif ? route('classes.reports.full', $kelasAktif) : route('classes.index');
    $urlKelasQr        = $kelasAktif ? route('classes.students.qr-cards', $kelasAktif) : route('classes.index');
@endphp

<style>
    .sidebar-link-active {
        background-color: #ecfdf5;
        color: #064e3b;
        font-weight: 800;
        border: 1px solid #a7f3d0;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    .sidebar-link-inactive {
        color: #334155;
        font-weight: 600;
        border: 1px solid transparent;
    }
    .sidebar-link-inactive:hover {
        background-color: #f8fafc;
        color: #0f172a;
        border-color: #e2e8f0;
    }
</style>

<nav class="flex-1 overflow-y-auto px-3.5 py-4 space-y-5 text-xs scrollbar-thin">

    {{-- KELAS AKTIF BADGE / QUICK SELECTOR --}}
    @if ($kelasAktif)
        <div class="bg-white rounded-2xl border {{ $isKelasAjar ? 'border-indigo-300' : 'border-emerald-300' }} p-3 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black uppercase tracking-wider {{ $isKelasAjar ? 'text-indigo-900' : 'text-emerald-800' }}">
                    {{ $isKelasAjar ? '📚 Kelas Ajar' : '👑 Kelas Perwalian' }}
                </span>
                <a href="{{ route('classes.index') }}" class="text-[10px] font-bold text-slate-500 hover:text-emerald-700 underline">Ganti</a>
            </div>
            <p class="text-sm font-black text-slate-900 truncate mt-0.5">{{ $kelasAktif->name }}</p>
            <p class="text-[10px] text-slate-500 font-mono">
                @if ($isKelasAjar)
                    {{ implode(', ', $kelasAktif->mapelDiampu()) ?: 'Guru Mapel' }}
                @else
                    {{ $kelasAktif->students_count ?? $kelasAktif->students()->count() }} Siswa Terdaftar
                @endif
            </p>
        </div>
    @endif

    {{-- ══════════ PILAR 1: AKSI HARIAN (DAILY ACTIONS) ══════════ --}}
    <div class="space-y-1">
        <div class="px-2 text-[10px] font-black text-emerald-900 uppercase tracking-widest flex items-center gap-1.5 mb-1.5">
            <span>⚡</span>
            <span>Aksi Harian</span>
        </div>

        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">🏠</span>
            <span>Beranda Guru</span>
        </a>

        <a href="{{ $urlKelasAbsensi }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.attendance.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">📋</span>
            <span>Presensi Harian</span>
        </a>

        <a href="{{ $urlKelasJurnal }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.jurnal.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">📖</span>
            <span>Jurnal Mengajar</span>
        </a>

        @if (! $isKelasAjar)
        <a href="{{ route('whatsapp.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('whatsapp.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">💬</span>
            <span>Integrasi WhatsApp</span>
        </a>
        @endif
    </div>

    {{-- ══════════ PILAR 2: SISWA & DATA ══════════ --}}
    <div class="space-y-1">
        <div class="px-2 text-[10px] font-black text-emerald-900 uppercase tracking-widest flex items-center gap-1.5 mb-1.5">
            <span>👥</span>
            <span>Siswa &amp; Data</span>
        </div>

        {{-- DAFTAR SISWA SELALU TAMPIL UNTUK WALI KELAS & GURU MAPEL --}}
        <a href="{{ $urlKelasSiswa }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.students.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">🎴</span>
            <span>Daftar Siswa &amp; Profil</span>
        </a>

        @if (! $isKelasAjar)
        <a href="{{ $urlKelasEws }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.ews.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">🛡️</span>
            <span>Early Warning (EWS)</span>
        </a>

        <a href="{{ $urlKelasKarakter }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.character-portfolio.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">🌱</span>
            <span>Portofolio Karakter P5</span>
        </a>

        <a href="{{ $urlKelasKerajinan }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.kerajinan.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">🎖️</span>
            <span>Poin Kerajinan</span>
        </a>

        <a href="{{ $urlKelasViolasi }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.violations.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">⚠️</span>
            <span>Buku Pelanggaran</span>
        </a>
        @endif
    </div>

    {{-- ══════════ PILAR 3: AKADEMIK & KEUANGAN ══════════ --}}
    <div class="space-y-1">
        <div class="px-2 text-[10px] font-black text-emerald-900 uppercase tracking-widest flex items-center gap-1.5 mb-1.5">
            <span>📊</span>
            <span>Akademik {{ ! $isKelasAjar ? '& Keuangan' : '' }}</span>
        </div>

        <a href="{{ $urlKelasNilai }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.nilai.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">📝</span>
            <span>Nilai {{ $isKelasAjar ? 'Mapel' : 'Rapor & Harian' }}</span>
        </a>

        <a href="{{ $urlKelasAnalisis }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.reports.analisis') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">📈</span>
            <span>Analisis Presensi</span>
        </a>

        <a href="{{ route('analytics.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('analytics.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">📉</span>
            <span>Grafik Performa</span>
        </a>

        @if (! $isKelasAjar)
        <a href="{{ $urlKelasNarasi }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.rapor.narasi*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">🤖</span>
            <span>AI Narasi Rapor</span>
        </a>

        <a href="{{ $urlKelasKas }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.cashbook.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">💰</span>
            <span>Buku Kas &amp; Iuran</span>
        </a>

        <a href="{{ $urlKelasLaporan }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.reports.full') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">📄</span>
            <span>Laporan PDF Lengkap</span>
        </a>
        @endif
    </div>

    {{-- ══════════ PILAR 4: ORGANISASI & KELAS ══════════ --}}
    <div class="space-y-1">
        <div class="px-2 text-[10px] font-black text-emerald-900 uppercase tracking-widest flex items-center gap-1.5 mb-1.5">
            <span>🏛️</span>
            <span>Kelas</span>
        </div>

        <a href="{{ route('classes.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.index') || request()->routeIs('classes.create') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">🏫</span>
            <span>Semua Kelas Anda</span>
        </a>

        @if (! $isKelasAjar)
        <a href="{{ $urlKelasJadwal }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.schedules.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">🗓️</span>
            <span>Jadwal Pelajaran</span>
        </a>

        <a href="{{ $urlKelasDenah }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.seating.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">🪑</span>
            <span>Denah Tempat Duduk</span>
        </a>

        <a href="{{ $urlKelasStruktur }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.organization.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">🏛️</span>
            <span>Struktur Organisasi</span>
        </a>

        <a href="{{ $urlKelasQr }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('classes.students.qr-cards') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">📇</span>
            <span>Cetak Kartu QR Siswa</span>
        </a>
        @endif
    </div>

    {{-- ══════════ PENGATURAN APLIKASI (SETTINGS) ══════════ --}}
    <div class="space-y-1 pb-4 pt-2 border-t border-emerald-100">
        <div class="px-2 text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-1.5 mb-1">
            <span>⚙️</span>
            <span>Pengaturan</span>
        </div>

        <a href="{{ route('holidays.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('holidays.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">🏖️</span>
            <span>Kalender Libur</span>
        </a>

        @if (! $isKelasAjar)
        <a href="{{ route('violation-types.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('violation-types.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">⚖️</span>
            <span>Master Pelanggaran</span>
        </a>
        @endif

        <a href="{{ route('subscription.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('subscription.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">👑</span>
            <span>Langganan PRO</span>
        </a>

        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition-all {{ request()->routeIs('profile.*') ? 'sidebar-link-active' : 'sidebar-link-inactive' }}">
            <span class="text-base">👤</span>
            <span>Profil Guru</span>
        </a>
    </div>

</nav>

{{-- User Profile Footer in Sidebar --}}
<div class="p-3 border-t border-emerald-100 bg-white shrink-0">
    @auth
        <div class="flex items-center justify-between p-2 rounded-xl bg-emerald-50/60 border border-emerald-200">
            <div class="flex items-center gap-2 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                    {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-emerald-800 font-medium">{{ $isKelasAjar ? 'Guru Mapel' : 'Wali Kelas' }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 transition-colors" title="Keluar">
                    🚪
                </button>
            </form>
        </div>
    @endauth
</div>
