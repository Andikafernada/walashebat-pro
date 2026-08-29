@php
    $modeOperator = request()->routeIs('admin.*');
    
    // Ambil daftar kelas guru yang aktif
    $kelasPintasan = (isset($kelasPintasan) && $kelasPintasan->isNotEmpty())
        ? $kelasPintasan
        : (auth()->check() ? auth()->user()->classes()->where('is_active', true)->orderBy('name')->get() : collect());
    
    // Tentukan kelas aktif secara cerdas
    $kelasAktif = $kelasAktif 
        ?? (isset($classroom) && $classroom instanceof \App\Models\Classroom ? $classroom : null)
        ?? (request()->route('class') instanceof \App\Models\Classroom ? request()->route('class') : null)
        ?? (session('active_class_id') ? $kelasPintasan->firstWhere('id', session('active_class_id')) : null)
        ?? $kelasPintasan->first();

    $pembatas = $pembatas ?? [];

    // URL kelas aktif
    $urlKelasAbsensi   = $kelasAktif ? route('classes.attendance.index', $kelasAktif)        : route('classes.index');
    $urlKelasSiswa     = $kelasAktif ? route('classes.students.index', $kelasAktif)           : route('classes.index');
    $urlKelasNilai     = $kelasAktif ? route('classes.nilai.index', $kelasAktif)              : route('classes.index');
    $urlKelasJadwal    = $kelasAktif ? route('classes.schedules.index', $kelasAktif)          : route('classes.index');
    $urlKelasKarakter  = $kelasAktif ? route('classes.character-portfolio.index', $kelasAktif): route('classes.index');
    $urlKelasKas       = $kelasAktif ? route('classes.cashbook.index', $kelasAktif)           : route('classes.index');
    $urlKelasLaporan   = ($kelasAktif && ! $kelasAktif->kelasAjar()) ? route('classes.reports.full', $kelasAktif) : ($kelasAktif ? route('classes.reports.attendance', $kelasAktif) : route('classes.index'));
    $urlKelasAnalisis  = $kelasAktif ? route('classes.reports.analisis', $kelasAktif)         : route('classes.index');
    $urlKelasImport    = $kelasAktif ? route('classes.students.import.form', $kelasAktif)     : route('classes.index');
    $urlKelasQr        = $kelasAktif ? route('classes.students.qr-cards', $kelasAktif)        : route('classes.index');
    $urlKelasViolasi   = $kelasAktif ? route('classes.violations.index', $kelasAktif)         : route('classes.index');
    $urlKelasJurnal    = $kelasAktif ? route('classes.jurnal.index', $kelasAktif)             : route('classes.index');
    $urlKelasKerajinan = $kelasAktif ? route('classes.kerajinan.index', $kelasAktif)          : route('classes.index');
    $urlKelasDenah     = $kelasAktif ? route('classes.seating.index', $kelasAktif)            : route('classes.index');
    $urlKelasStruktur  = $kelasAktif ? route('classes.organization.index', $kelasAktif)       : route('classes.index');
    $urlKelasPerSiswa  = $kelasAktif ? route('classes.cashbook.per-siswa', $kelasAktif)       : route('classes.index');
    $urlKelasEws       = $kelasAktif ? route('classes.ews.index', $kelasAktif)                : route('classes.index');

    // Active route states
    $isBerandaActive = request()->routeIs('dashboard', 'admin.dashboard');
    $isAbsensiActive = request()->routeIs('classes.attendance.*');
    $isClassesActive = request()->routeIs('classes.index', 'classes.create');
    $isProfileActive = request()->routeIs('profile.edit');
    $isAktivitasActive = request()->routeIs(
        'classes.schedules.*',
        'classes.nilai.*',
        'classes.cashbook.*',
        'classes.jurnal.*',
        'classes.reports.*',
        'holidays.*',
        'analytics.*'
    );
    $isSiswaActive = request()->routeIs(
        'classes.students.*',
        'classes.ews.*',
        'classes.character-portfolio.*',
        'classes.violations.*',
        'classes.kerajinan.*',
        'classes.organization.*',
        'classes.seating.*'
    );
@endphp
<!DOCTYPE html>
<html lang="id" class="antialiased h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>@yield('title', config('app.name', 'WaliKelas Pro')) - Mode Web Desktop</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        html { scroll-behavior: smooth; }

        .sidebar-link-active {
            background-color: #ecfdf5;
            color: #064e3b;
            font-weight: 800;
            border-left: 3px solid #059669;
        }
        .sidebar-link-inactive {
            color: #334155;
            font-weight: 600;
            border-left: 3px solid transparent;
        }
        .sidebar-link-inactive:hover {
            background-color: #f0fdf4;
            color: #0f172a;
        }

        .nav-touch-btn {
            transition: transform 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }
        .nav-touch-btn:active {
            transform: scale(0.90);
        }
        .menu-tab-btn.is-active {
            background-color: #ffffff;
            color: #064e3b;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.15);
            font-weight: 900;
        }
        .menu-tab-btn:not(.is-active) {
            color: #065f46;
            font-weight: 700;
        }
    </style>
</head>
<body class="antialiased text-slate-900 bg-[#f0fdf4] min-h-full pb-36 lg:pb-0" x-data="{ sidebarOpen: false, openMenuDrawer: false, drawerTab: '{{ $isSiswaActive ? 'siswa' : 'aktivitas' }}' }">

{{-- BACKDROP OVERLAY FOR MOBILE SCREEN IN WEB MODE --}}
<div x-show="sidebarOpen"
     @click="sidebarOpen = false"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs lg:hidden"
     style="display: none;"
     x-cloak></div>

{{-- ══════════════════════════════════════════════════════════════════════════════════
     MAIN CONTAINER: FLEX-ROW ON DESKTOP (No Overlap Ever!)
     ══════════════════════════════════════════════════════════════════════════════════ --}}
<div class="min-h-screen flex flex-col lg:flex-row w-full">

    {{-- 1. DESKTOP PERMANENT SIDEBAR (Visible only on lg: screens >= 1024px) --}}
    <aside class="hidden lg:flex lg:flex-col w-64 shrink-0 bg-white border-r border-emerald-200 min-h-screen sticky top-0 h-screen z-30">
        @include('layouts.partials.web_sidebar_nav')
    </aside>

    {{-- 2. MOBILE SLIDE-IN SIDEBAR (Controlled by x-show="sidebarOpen", display:none by default) --}}
    <aside x-show="sidebarOpen"
           x-transition:enter="transition ease-out duration-300 transform"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200 transform"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           class="fixed inset-y-0 left-0 w-64 bg-white border-r border-emerald-200 z-50 shadow-2xl flex flex-col h-screen lg:hidden"
           style="display: none;"
           x-cloak>
        <div class="p-3 bg-emerald-50 border-b border-emerald-100 flex items-center justify-between shrink-0">
            <span class="text-xs font-extrabold text-emerald-950">Menu Web Desktop</span>
            <button @click="sidebarOpen = false" type="button" class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-950 font-bold text-xs">
                ✕ Tutup
            </button>
        </div>
        @include('layouts.partials.web_sidebar_nav')
    </aside>

    {{-- 3. MAIN CONTENT CANVAS (Takes remaining flex space) --}}
    <div class="flex-1 min-w-0 flex flex-col min-h-screen bg-[#f0fdf4]">
        
        {{-- DESKTOP TOPBAR HEADER --}}
        <header class="h-16 bg-white border-b border-emerald-200 sticky top-0 z-20 px-4 sm:px-6 flex items-center justify-between shadow-2xs">
            
            {{-- Left: Hamburger Menu (Mobile HP) & Page Info --}}
            <div class="flex items-center gap-3 min-w-0">
                <button @click="sidebarOpen = true" 
                        type="button"
                        class="lg:hidden px-3 py-1.5 rounded-xl bg-emerald-100 text-emerald-950 hover:bg-emerald-200 transition-colors font-extrabold text-xs flex items-center gap-1.5 border border-emerald-300 shadow-2xs shrink-0">
                    <span>☰</span>
                    <span>Menu</span>
                </button>

                <span class="text-lg hidden sm:inline shrink-0">🖥️</span>
                <h1 class="text-xs sm:text-sm font-bold text-slate-900 truncate">Mode Web Desktop</h1>
            </div>

            {{-- Right: View Switcher (Switch to PWA) & User Actions --}}
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <a href="{{ request()->fullUrlWithQuery(['layout' => 'pwa']) }}" 
                   class="px-3 py-1.5 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-950 text-xs font-extrabold border border-emerald-300 transition-all flex items-center gap-1.5 shadow-2xs"
                   title="Klik untuk beralih ke tampilan PWA Mobile">
                    <span>📱</span>
                    <span class="hidden sm:inline">Ubah ke Tampilan</span>
                    <span>PWA Mobile</span>
                </a>

                @if($kelasPintasan->isNotEmpty())
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="px-2.5 sm:px-3 py-1.5 rounded-xl bg-white border border-emerald-200 text-xs font-bold text-slate-700 hover:bg-emerald-50 flex items-center gap-1.5 transition-all">
                            <span>🏫</span>
                            <span class="truncate max-w-[100px] sm:max-w-[140px]">{{ $kelasAktif ? $kelasAktif->name : 'Pilih Kelas' }}</span>
                            <span class="text-[10px]">▼</span>
                        </button>
                        <div x-show="open" 
                             @click.away="open = false" 
                             class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-emerald-200 py-2 z-50 divide-y divide-emerald-100"
                             style="display: none;"
                             x-cloak>
                            <div class="px-3 py-1 text-[10px] font-bold text-emerald-800 uppercase">Pilih Kelas Aktif</div>
                            <div class="max-h-60 overflow-y-auto py-1">
                                @foreach($kelasPintasan as $kp)
                                    <a href="{{ route('classes.show', $kp) }}" 
                                       class="block px-4 py-2 text-xs font-bold text-slate-800 hover:bg-emerald-50 {{ $kelasAktif && $kelasAktif->id === $kp->id ? 'bg-emerald-100 text-emerald-950 font-extrabold' : '' }}">
                                        {{ $kp->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </header>

        {{-- MAIN CONTENT CANVAS --}}
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
            @include('partials.flash')
            @yield('content')
        </main>

        {{-- DESKTOP FOOTER --}}
        <footer class="py-4 px-6 bg-white border-t border-emerald-100 text-center text-xs text-slate-500 hidden lg:block">
            <p>WaliKelas Pro &bull; Mode Web Desktop Terisolasi &bull; &copy; {{ date('Y') }}</p>
        </footer>

    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════════════════
     FLOATING GLASS PILL NAVBAR FOR MOBILE SCREENS (lg:hidden) - BULLETPROOF INLINE
     ══════════════════════════════════════════════════════════════════════════════════ --}}
<div class="lg:hidden">
    <nav style="position: fixed; bottom: 12px; left: 50%; transform: translateX(-50%); width: calc(100% - 24px); max-width: 480px; z-index: 99999;"
         class="bg-white/95 backdrop-blur-2xl border border-emerald-200/90 rounded-[28px] shadow-[0_12px_36px_-6px_rgba(6,78,59,0.28)] py-1.5 px-3">
        <div class="flex justify-between items-center relative">

            {{-- 1. BERANDA (HOME) --}}
            <a href="{{ route('dashboard') }}"
               class="nav-touch-btn flex-1 flex flex-col items-center justify-center py-1 rounded-2xl no-underline group"
               title="Beranda">
                <div class="p-1 rounded-xl transition-all {{ $isBerandaActive ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }}">
                    <svg class="w-5 h-5 {{ $isBerandaActive ? 'fill-emerald-600' : 'fill-none stroke-current stroke-2' }}" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                </div>
                <span class="text-[10px] font-black leading-none mt-0.5 {{ $isBerandaActive ? 'text-emerald-950 font-black' : 'text-slate-600 font-semibold' }}">
                    Beranda
                </span>
                @if($isBerandaActive)
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shadow-2xs mt-1"></span>
                @else
                    <span class="w-1.5 h-1.5 mt-1"></span>
                @endif
            </a>

            {{-- 2. ABSENSI (ATTENDANCE) --}}
            <a href="{{ $urlKelasAbsensi }}"
               class="nav-touch-btn flex-1 flex flex-col items-center justify-center py-1 rounded-2xl no-underline group"
               title="Absensi">
                <div class="p-1 rounded-xl transition-all {{ $isAbsensiActive ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }}">
                    <svg class="w-5 h-5 {{ $isAbsensiActive ? 'fill-emerald-600' : 'fill-none stroke-current stroke-2' }}" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                    </svg>
                </div>
                <span class="text-[10px] font-black leading-none mt-0.5 {{ $isAbsensiActive ? 'text-emerald-950 font-black' : 'text-slate-600 font-semibold' }}">
                    Absensi
                </span>
                @if($isAbsensiActive)
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shadow-2xs mt-1"></span>
                @else
                    <span class="w-1.5 h-1.5 mt-1"></span>
                @endif
            </a>

            {{-- 3. CENTER SUPERAPP FLOATING ACTION HUB (FAB) --}}
            <div class="flex-1 flex flex-col items-center justify-center -mt-7">
                <button @click="openMenuDrawer = true"
                        type="button"
                        style="width: 52px; height: 52px;"
                        class="nav-touch-btn rounded-full bg-gradient-to-tr from-emerald-700 via-emerald-600 to-teal-400 text-white flex items-center justify-center ring-4 ring-[#f0fdf4] shadow-[0_8px_20px_rgba(5,150,105,0.45)] cursor-pointer group"
                        title="Buka Menu Fitur Lengkap">
                    <svg class="w-6 h-6 transform group-hover:rotate-45 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </button>
                <span class="text-[10px] font-black leading-none mt-1 text-emerald-950">
                    Menu Fitur
                </span>
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400/80 shadow-2xs mt-1"></span>
            </div>

            {{-- 4. DAFTAR KELAS (CLASSES) --}}
            <a href="{{ route('classes.index') }}"
               class="nav-touch-btn flex-1 flex flex-col items-center justify-center py-1 rounded-2xl no-underline group"
               title="Daftar Kelas">
                <div class="p-1 rounded-xl transition-all {{ $isClassesActive ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }}">
                    <svg class="w-5 h-5 {{ $isClassesActive ? 'fill-emerald-600' : 'fill-none stroke-current stroke-2' }}" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                    </svg>
                </div>
                <span class="text-[10px] font-black leading-none mt-0.5 {{ $isClassesActive ? 'text-emerald-950 font-black' : 'text-slate-600 font-semibold' }}">
                    Kelas
                </span>
                @if($isClassesActive)
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shadow-2xs mt-1"></span>
                @else
                    <span class="w-1.5 h-1.5 mt-1"></span>
                @endif
            </a>

            {{-- 5. PROFIL GURU (PROFILE) --}}
            <a href="{{ route('profile.edit') }}"
               class="nav-touch-btn flex-1 flex flex-col items-center justify-center py-1 rounded-2xl no-underline group"
               title="Profil Akun">
                <div class="p-1 rounded-xl transition-all {{ $isProfileActive ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }}">
                    <svg class="w-5 h-5 {{ $isProfileActive ? 'fill-emerald-600' : 'fill-none stroke-current stroke-2' }}" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </div>
                <span class="text-[10px] font-black leading-none mt-0.5 {{ $isProfileActive ? 'text-emerald-950 font-black' : 'text-slate-600 font-semibold' }}">
                    Profil
                </span>
                @if($isProfileActive)
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shadow-2xs mt-1"></span>
                @else
                    <span class="w-1.5 h-1.5 mt-1"></span>
                @endif
            </a>

        </div>
    </nav>

    {{-- TOUCH MODAL DRAWER (SUPERAPP BOTTOM SHEET) --}}
    <div x-show="openMenuDrawer" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="position: fixed; inset: 0; z-index: 100000; background: rgba(2, 6, 23, 0.65); backdrop-filter: blur(4px); display: flex; flex-direction: column; justify-content: flex-end;"
         style="display:none;"
         x-cloak>

        <div @click.away="openMenuDrawer = false" 
             class="bg-[#f0fdf4] rounded-t-[36px] border-t-2 border-emerald-300 p-5 max-h-[88vh] flex flex-col animate-drawer-up shadow-2xl">
            
            <div class="w-14 h-1.5 rounded-full bg-emerald-300 mx-auto mb-3 shrink-0"></div>

            <div class="flex items-center justify-between pb-3 border-b border-emerald-200 shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-sm flex items-center justify-center shadow-xs">
                        🚀
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 leading-none">Pusat Fitur &amp; Administrasi</h2>
                        <p class="text-[11px] font-bold text-emerald-800 mt-0.5">Kelas {{ $kelasAktif ? $kelasAktif->name : 'Pilih Kelas' }}</p>
                    </div>
                </div>
                <button @click="openMenuDrawer = false" 
                        class="w-7 h-7 rounded-full bg-emerald-100 hover:bg-emerald-200 text-emerald-950 font-bold text-xs flex items-center justify-center transition-colors">
                    ✕
                </button>
            </div>

            <div class="grid grid-cols-2 gap-2 p-1 bg-emerald-200/60 rounded-2xl my-3 shrink-0">
                <button @click="drawerTab = 'aktivitas'" 
                        :class="drawerTab === 'aktivitas' ? 'is-active' : ''"
                        class="menu-tab-btn py-2 text-xs rounded-xl text-center flex items-center justify-center gap-1.5">
                    <span>📖</span>
                    <span>Aktivitas &amp; Nilai</span>
                </button>
                <button @click="drawerTab = 'siswa'" 
                        :class="drawerTab === 'siswa' ? 'is-active' : ''"
                        class="menu-tab-btn py-2 text-xs rounded-xl text-center flex items-center justify-center gap-1.5">
                    <span>👥</span>
                    <span>Siswa &amp; Cetak</span>
                </button>
            </div>

            <div class="overflow-y-auto flex-1 drawer-scroll-area space-y-4 py-2">
                <div x-show="drawerTab === 'aktivitas'" class="grid grid-cols-3 gap-2.5">
                    <a href="{{ $urlKelasAbsensi }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">📋</span>
                        <span class="text-xs font-black text-slate-900">Absensi</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Presensi Siswa</span>
                    </a>
                    <a href="{{ $urlKelasNilai }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">📝</span>
                        <span class="text-xs font-black text-slate-900">Nilai</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Asesmen &amp; Excel</span>
                    </a>
                    <a href="{{ $urlKelasJurnal }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">🤖</span>
                        <span class="text-xs font-black text-slate-900">Jurnal AI</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Modul TP/CP</span>
                    </a>
                    <a href="{{ $urlKelasJadwal }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">🗓️</span>
                        <span class="text-xs font-black text-slate-900">Jadwal</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Agenda Mapel</span>
                    </a>
                    <a href="{{ $urlKelasAnalisis }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">📈</span>
                        <span class="text-xs font-black text-slate-900">Analisis</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Grafik Presensi</span>
                    </a>
                    <a href="{{ $urlKelasLaporan }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">📄</span>
                        <span class="text-xs font-black text-slate-900">Laporan PDF</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Rekap Resmi</span>
                    </a>
                </div>

                <div x-show="drawerTab === 'siswa'" class="grid grid-cols-3 gap-2.5">
                    <a href="{{ $urlKelasSiswa }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">👥</span>
                        <span class="text-xs font-black text-slate-900">Daftar Siswa</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Biodata Lengkap</span>
                    </a>
                    <a href="{{ $urlKelasQr }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">📇</span>
                        <span class="text-xs font-black text-slate-900">Kartu QR A4</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Siap Cetak PDF</span>
                    </a>
                    <a href="{{ $urlKelasEws }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">🛡️</span>
                        <span class="text-xs font-black text-slate-900">EWS Risiko</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Deteksi Dini</span>
                    </a>
                    <a href="{{ $urlKelasImport }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">📥</span>
                        <span class="text-xs font-black text-slate-900">Impor Excel</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Sinkron Roster</span>
                    </a>
                    <a href="{{ $urlKelasKarakter }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">🌱</span>
                        <span class="text-xs font-black text-slate-900">Karakter P5</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Portofolio Siswa</span>
                    </a>
                    <a href="{{ $urlKelasViolasi }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">⚠️</span>
                        <span class="text-xs font-black text-slate-900">Pelanggaran</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Catatan Disiplin</span>
                    </a>
                    <a href="{{ $urlKelasKerajinan }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">🎖️</span>
                        <span class="text-xs font-black text-slate-900">Kerajinan</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Kebaikan &amp; Poin</span>
                    </a>
                    <a href="{{ $urlKelasKas }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">💰</span>
                        <span class="text-xs font-black text-slate-900">Buku Kas</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Keuangan Kelas</span>
                    </a>
                    <a href="{{ $urlKelasDenah }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">🪑</span>
                        <span class="text-xs font-black text-slate-900">Denah Meja</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Tata Letak Duduk</span>
                    </a>
                </div>
            </div>

            @auth
            <div class="pt-3 mt-2 border-t border-emerald-200 flex items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-xs flex items-center justify-center shrink-0 shadow-xs">
                        {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-black text-slate-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-500 font-medium truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Keluar dari aplikasi WaliKelas Pro?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 text-xs font-black transition-colors shadow-2xs">
                        <span>🚪</span>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
            @endauth

        </div>
    </div>
</div>

</body>
</html>
