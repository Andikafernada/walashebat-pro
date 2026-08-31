<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'WaliKelas Pro'))</title>

    {{-- PWA Primary Meta Tags --}}
    <meta name="theme-color" content="#064e3b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="WaliKelas">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    {{-- Tailwind & Alpine.js via Laravel Mix / Compiled Bundle --}}
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Safe Area Padding for Modern Mobile Devices (iOS & Android Notch) */
        :root {
            --sat: env(safe-area-inset-top, 0px);
            --sab: env(safe-area-inset-bottom, 0px);
            --sal: env(safe-area-inset-left, 0px);
            --sar: env(safe-area-inset-right, 0px);
        }

        body {
            padding-top: var(--sat);
            padding-bottom: calc(var(--sab) + 76px);
            -webkit-tap-highlight-color: transparent;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        /* Smooth Drawer & Touch Feedback */
        .drawer-scroll-area {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .drawer-scroll-area::-webkit-scrollbar {
            display: none;
        }

        .nav-touch-btn:active {
            transform: scale(0.92);
        }

        @keyframes drawerSlideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }

        .animate-drawer-up {
            animation: drawerSlideUp 0.26s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .menu-tab-btn {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            color: #064e3b;
            font-weight: 700;
        }
        .menu-tab-btn.is-active {
            background-color: #047857;
            color: #ffffff;
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(4, 120, 87, 0.35);
        }
    </style>
    @stack('styles')
</head>
<body class="h-full flex flex-col text-slate-800 antialiased selection:bg-emerald-500 selection:text-white pb-24">

@php
    $currentRoute = Route::currentRouteName() ?? '';
    $user = auth()->user();

    // Resolusi Kelas Aktif
    $kelasAktif = null;
    if (isset($classroom) && $classroom instanceof \App\Models\Classroom) {
        $kelasAktif = $classroom;
    } elseif (isset($class) && $class instanceof \App\Models\Classroom) {
        $kelasAktif = $class;
    } elseif ($user) {
        $kelasAktif = $user->classes()->latest()->first();
    }

    // Active State Navigation
    $isBerandaActive = in_array($currentRoute, ['dashboard', 'home']);
    $isClassesActive = str_starts_with($currentRoute, 'classes.');
    $isProfileActive = in_array($currentRoute, ['profile.edit', 'profile.show']);
    $isWhatsappActive = str_starts_with($currentRoute, 'whatsapp.');

    // Helper URLs
    $urlKelasAbsensi  = $kelasAktif ? route('classes.attendance.index', $kelasAktif) : route('classes.index');
    $urlKelasNilai    = $kelasAktif ? route('classes.nilai.index', $kelasAktif) : route('classes.index');
    $urlKelasJurnal   = $kelasAktif ? route('classes.jurnal.index', $kelasAktif) : route('classes.index');
    $urlKelasJadwal   = $kelasAktif ? route('classes.schedules.index', $kelasAktif) : route('classes.index');
    $urlKelasAnalisis = $kelasAktif ? route('classes.reports.analisis', $kelasAktif) : route('classes.index');
    $urlKelasLaporan  = $kelasAktif ? route('classes.reports.full', $kelasAktif) : route('classes.index');

    $urlKelasSiswa     = $kelasAktif ? route('classes.students.index', $kelasAktif) : route('classes.index');
    $urlKelasQr        = $kelasAktif ? route('classes.students.qr-cards', $kelasAktif) : route('classes.index');
    $urlKelasEws       = $kelasAktif ? route('classes.ews.index', $kelasAktif) : route('classes.index');
    $urlKelasImport    = $kelasAktif ? route('classes.students.import', $kelasAktif) : route('classes.index');
    $urlKelasKarakter  = $kelasAktif ? route('classes.character-portfolio.index', $kelasAktif) : route('classes.index');
    $urlKelasViolasi   = $kelasAktif ? route('classes.violations.index', $kelasAktif) : route('classes.index');
    $urlKelasKerajinan = $kelasAktif ? route('classes.kerajinan.index', $kelasAktif) : route('classes.index');
    $urlKelasKas       = $kelasAktif ? route('classes.cashbook.index', $kelasAktif) : route('classes.index');
    $urlKelasDenah     = $kelasAktif ? route('classes.seating.index', $kelasAktif) : route('classes.index');
    $urlKelasOrganisasi= $kelasAktif ? route('classes.organization.index', $kelasAktif) : route('classes.index');
@endphp

{{-- ══════════════════════════════════════════════════════════════════════════════════
     1. NATIVE-LIKE MOBILE APP HEADER (CLEAN & SLICK)
     ══════════════════════════════════════════════════════════════════════════════════ --}}
<header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-emerald-200 px-4 py-2.5 flex items-center justify-between shadow-2xs">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 no-underline">
        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white font-black text-sm shadow-xs">
            W
        </div>
        <div class="min-w-0">
            <h1 class="text-xs font-black text-slate-900 leading-none tracking-tight">WaliKelas <span class="text-emerald-800">Pro</span></h1>
            <span class="text-[9px] font-bold text-emerald-800 uppercase block mt-0.5">{{ $kelasAktif ? $kelasAktif->name : 'Daftar Kelas' }}</span>
        </div>
    </a>

    <div class="flex items-center gap-1.5">
        {{-- QUICK WHATSAPP BADGE --}}
        @auth
            <a href="{{ route('whatsapp.index') }}" 
               class="px-2.5 py-1 rounded-xl {{ $isWhatsappActive ? 'bg-emerald-600 text-white font-black' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-950 font-bold border border-emerald-200' }} text-[11px] transition-all flex items-center gap-1 shadow-2xs"
               title="Integrasi WhatsApp">
                <span>📱</span>
                <span>WhatsApp</span>
            </a>
        @endauth

        {{-- VIEW SWITCHER: Switch to Web Desktop Mode --}}
        <a href="{{ request()->fullUrlWithQuery(['layout' => 'web']) }}" 
           class="px-2 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold border border-slate-200 transition-all flex items-center gap-1"
           title="Ganti ke Tampilan Desktop Web">
            <span>🖥️</span>
        </a>

        @auth
            <a href="{{ route('profile.edit') }}" 
               class="w-7 h-7 rounded-full bg-emerald-600 text-white font-black text-xs flex items-center justify-center shadow-2xs" 
               title="Profil Guru">
                {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
            </a>
        @endauth
    </div>
</header>

{{-- ══════════════════════════════════════════════════════════════════════════════════
     2. MAIN CONTENT WRAPPER
     ══════════════════════════════════════════════════════════════════════════════════ --}}
<main class="flex-1 w-full max-w-lg mx-auto px-4 pt-3.5 pb-28">
    {{-- Global Flash Messages --}}
    @if(session('success'))
        <div class="mb-3.5 p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center gap-2 shadow-2xs">
            <span class="text-base">✅</span>
            <div class="flex-1 leading-snug">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-3.5 p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs font-bold flex items-center gap-2 shadow-2xs">
            <span class="text-base">❌</span>
            <div class="flex-1 leading-snug">{{ session('error') }}</div>
        </div>
    @endif

    @yield('content')
</main>

{{-- ══════════════════════════════════════════════════════════════════════════════════
     3. NEXT-GEN FLOATING GLASS ISLAND FOOTBAR & SUPERAPP ACTION HUB
     ══════════════════════════════════════════════════════════════════════════════════ --}}
<div x-data="{ openMenuDrawer: false, drawerTab: '{{ $isWhatsappActive ? 'integrasi' : 'aktivitas' }}' }">
    
    {{-- FLOATING GLASS PILL NAVBAR --}}
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
               title="Presensi Harian">
                <div class="p-1 rounded-xl transition-all {{ str_contains($currentRoute, 'attendance') ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }}">
                    <svg class="w-5 h-5 {{ str_contains($currentRoute, 'attendance') ? 'fill-emerald-600' : 'fill-none stroke-current stroke-2' }}" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-[10px] font-black leading-none mt-0.5 {{ str_contains($currentRoute, 'attendance') ? 'text-emerald-950 font-black' : 'text-slate-600 font-semibold' }}">
                    Absensi
                </span>
                @if(str_contains($currentRoute, 'attendance'))
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shadow-2xs mt-1"></span>
                @else
                    <span class="w-1.5 h-1.5 mt-1"></span>
                @endif
            </a>

            {{-- 3. TOMBOL TENGAH: SUPER MENU DRAWER (ALL APPS) --}}
            <div class="flex-1 flex flex-col items-center justify-center relative -top-3">
                <button @click="openMenuDrawer = true" 
                        class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 via-emerald-700 to-teal-600 text-white flex items-center justify-center shadow-[0_8px_20px_-4px_rgba(5,150,105,0.6)] active:scale-90 transition-transform cursor-pointer"
                        title="Buka Seluruh Fitur">
                    <svg class="w-6 h-6 stroke-white stroke-2 fill-none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </button>
                <span class="text-[10px] font-black leading-none mt-1 text-emerald-950">
                    Menu Fitur
                </span>
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400/80 shadow-2xs mt-1"></span>
            </div>

            {{-- 4. INTEGRASI WHATSAPP --}}
            <a href="{{ route('whatsapp.index') }}"
               class="nav-touch-btn flex-1 flex flex-col items-center justify-center py-1 rounded-2xl no-underline group"
               title="Integrasi WhatsApp & Bot">
                <div class="p-1 rounded-xl transition-all {{ $isWhatsappActive ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }}">
                    <span class="text-lg leading-none">📱</span>
                </div>
                <span class="text-[10px] font-black leading-none mt-0.5 {{ $isWhatsappActive ? 'text-emerald-950 font-black' : 'text-slate-600 font-semibold' }}">
                    WhatsApp
                </span>
                @if($isWhatsappActive)
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
         style="display:none;">

        <div @click.away="openMenuDrawer = false" 
             class="bg-[#f0fdf4] rounded-t-[36px] border-t-2 border-emerald-300 p-5 max-h-[88vh] flex flex-col animate-drawer-up shadow-2xl">
            
            <div class="w-14 h-1.5 rounded-full bg-emerald-300 mx-auto mb-3 shrink-0"></div>

            <div class="flex items-center justify-between pb-3 border-b border-emerald-200 shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-sm flex items-center justify-center shadow-xs">
                        🚀
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 leading-none">Pusat Fitur &amp; Integrasi</h2>
                        <p class="text-[11px] font-bold text-emerald-800 mt-0.5">Kelas {{ $kelasAktif ? $kelasAktif->name : 'Pilih Kelas' }}</p>
                    </div>
                </div>
                <button @click="openMenuDrawer = false" 
                        class="w-7 h-7 rounded-full bg-emerald-100 hover:bg-emerald-200 text-emerald-950 font-bold text-xs flex items-center justify-center transition-colors">
                    ✕
                </button>
            </div>

            {{-- 3 TABS: AKTIVITAS, SISWA, INTEGRASI --}}
            <div class="grid grid-cols-3 gap-1.5 p-1 bg-emerald-200/60 rounded-2xl my-3 shrink-0">
                <button @click="drawerTab = 'aktivitas'" 
                        :class="drawerTab === 'aktivitas' ? 'is-active' : ''"
                        class="menu-tab-btn py-2 text-[11px] rounded-xl text-center flex items-center justify-center gap-1">
                    <span>📖</span>
                    <span>Kelas &amp; Nilai</span>
                </button>
                <button @click="drawerTab = 'siswa'" 
                        :class="drawerTab === 'siswa' ? 'is-active' : ''"
                        class="menu-tab-btn py-2 text-[11px] rounded-xl text-center flex items-center justify-center gap-1">
                    <span>👥</span>
                    <span>Siswa</span>
                </button>
                <button @click="drawerTab = 'integrasi'" 
                        :class="drawerTab === 'integrasi' ? 'is-active' : ''"
                        class="menu-tab-btn py-2 text-[11px] rounded-xl text-center flex items-center justify-center gap-1">
                    <span>⚡</span>
                    <span>Integrasi &amp; WA</span>
                </button>
            </div>

            <div class="overflow-y-auto flex-1 drawer-scroll-area space-y-4 py-2">
                {{-- TAB 1: AKTIVITAS & NILAI --}}
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
                    <a href="{{ route('classes.index') }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">🏫</span>
                        <span class="text-xs font-black text-slate-900">Semua Kelas</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Kelola Kelas</span>
                    </a>
                </div>

                {{-- TAB 2: SISWA & ADMINISTRASI --}}
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
                    <a href="{{ $urlKelasOrganisasi }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">🏛️</span>
                        <span class="text-xs font-black text-slate-900">Struktur</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Organisasi Kelas</span>
                    </a>
                </div>

                {{-- TAB 3: INTEGRASI & WHATSAPP --}}
                <div x-show="drawerTab === 'integrasi'" class="grid grid-cols-3 gap-2.5">
                    <a href="{{ route('whatsapp.index') }}" class="flex flex-col items-center p-3 rounded-2xl bg-gradient-to-tr from-emerald-50 to-teal-50 border-2 border-emerald-300 text-center shadow-xs hover:border-emerald-500 transition-all active:scale-95">
                        <span class="text-2xl mb-1">📱</span>
                        <span class="text-xs font-black text-emerald-950">WhatsApp</span>
                        <span class="text-[9px] text-emerald-700 font-bold mt-0.5">Bot &amp; Gateway</span>
                    </a>
                    <a href="{{ route('subscription.index') }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">💎</span>
                        <span class="text-xs font-black text-slate-900">Paket PRO</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Langganan Aktif</span>
                    </a>
                    <a href="{{ route('holidays.index') }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">🏖️</span>
                        <span class="text-xs font-black text-slate-900">Hari Libur</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Kalender Sekolah</span>
                    </a>
                    <a href="{{ route('notifications.index') }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">🔔</span>
                        <span class="text-xs font-black text-slate-900">Notifikasi</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Pemberitahuan</span>
                    </a>
                    <a href="{{ route('analytics.index') }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">📊</span>
                        <span class="text-xs font-black text-slate-900">Analitik</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Statistik Global</span>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="flex flex-col items-center p-3 rounded-2xl bg-white border border-emerald-200 text-center shadow-xs hover:border-emerald-400 transition-all active:scale-95">
                        <span class="text-2xl mb-1">⚙️</span>
                        <span class="text-xs font-black text-slate-900">Pengaturan</span>
                        <span class="text-[9px] text-slate-500 font-semibold mt-0.5">Profil &amp; Akun</span>
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
