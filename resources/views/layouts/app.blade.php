@php
    $modeOperator = request()->routeIs('admin.*');
    
    // Safety null-coalescing defaults for all pages
    $kelasAktif = $kelasAktif ?? null;
    $kelasPintasan = $kelasPintasan ?? collect();
    $pembatas = $pembatas ?? [];

    $pembatasAktif = 'bg-gradient-to-r from-pink-500 via-purple-600 to-cyan-500 text-white font-black rounded-full px-4 py-2 sm:px-6 sm:py-2.5 shadow-lg shadow-pink-500/30 scale-105 border border-white/50 shrink-0';
    $pembatasDiam  = 'text-slate-800 dark:text-slate-100 hover:text-pink-600 hover:bg-white/90 dark:hover:bg-slate-800/90 font-extrabold rounded-full px-3.5 py-1.5 sm:px-5 sm:py-2 transition-all duration-200 shrink-0';
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full antialiased" x-data="{ darkMode: localStorage.getItem('theme') === 'dark', showCmd: false }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>✨ {{ config('app.name', 'Wali Kelas Hebat') }} — Responsive Edition ✨</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @stack('styles')

    <style>
        body {
            font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #fce7f3 0%, #e0e7ff 25%, #dbeafe 50%, #f3e8ff 75%, #fae8ff 100%) !important;
            background-size: 300% 300% !important;
            animation: gradientBg 12s ease infinite !important;
            color: #0f172a;
            min-height: 100vh;
        }

        .dark body {
            background: linear-gradient(135deg, #090d16 0%, #1e1b4b 35%, #31103f 70%, #030712 100%) !important;
            background-size: 300% 300% !important;
            animation: gradientBg 12s ease infinite !important;
            color: #f8fafc;
        }

        @keyframes gradientBg {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* HYPER VIBRANT NEON GRADIENT TEXT */
        .gradient-rainbow-text {
            background: linear-gradient(135deg, #ff007a 0%, #7928ca 35%, #0070f3 70%, #00dfd8 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: textShimmer 4s linear infinite;
        }
        @keyframes textShimmer {
            to { background-position: 200% center; }
        }

        /* EXTRAVAGANT GLASS CARDS WITH RESPONSIVE RADIUS */
        .card-colorful {
            position: relative;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 2px solid rgba(255, 255, 255, 0.8);
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px -15px rgba(236, 72, 153, 0.25);
            transition: all 0.3s ease;
        }

        @media (min-width: 640px) {
            .card-colorful {
                border-radius: 2rem;
            }
        }

        .dark .card-colorful {
            background: rgba(15, 23, 42, 0.9);
            box-shadow: 0 30px 70px -15px rgba(168, 85, 247, 0.4);
            color: #f8fafc;
        }

        /* BUTTONS WITH TOUCH TARGETS */
        .btn-primary, .btn-primary--sm, .btn-colorful-primary {
            background: linear-gradient(135deg, #ff007a 0%, #7928ca 50%, #0070f3 100%) !important;
            background-size: 200% auto !important;
            color: #ffffff !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-weight: 900 !important;
            border-radius: 9999px !important;
            padding: 0.625rem 1.25rem !important;
            font-size: 0.8125rem !important;
            border: 2px solid rgba(255, 255, 255, 0.6) !important;
            box-shadow: 0 8px 20px -4px rgba(255, 0, 122, 0.5) !important;
            transition: all 0.25s ease !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        @media (min-width: 640px) {
            .btn-primary, .btn-primary--sm, .btn-colorful-primary {
                padding: 0.75rem 1.875rem !important;
                font-size: 0.9375rem !important;
            }
        }

        .btn-secondary, .btn-secondary--sm {
            background: #ffffff !important;
            border: 2px solid #8b5cf6 !important;
            color: #7928ca !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-weight: 900 !important;
            border-radius: 9999px !important;
            padding: 0.5rem 1rem !important;
            font-size: 0.8125rem !important;
            transition: all 0.25s ease !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        @media (min-width: 640px) {
            .btn-secondary, .btn-secondary--sm {
                padding: 0.625rem 1.5rem !important;
                font-size: 0.875rem !important;
            }
        }

        /* STAT CARDS RESPONSIVE GRID */
        .deret-angka {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            margin-bottom: 1.5rem !important;
        }

        @media (min-width: 640px) {
            .deret-angka {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 1.25rem !important;
            }
        }

        .deret-angka > div, .papan-tugas {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 2px solid #f472b6 !important;
            border-radius: 1.25rem !important;
            padding: 1.25rem !important;
            box-shadow: 0 10px 25px -5px rgba(244, 114, 182, 0.25) !important;
        }

        .dark .deret-angka > div, .dark .papan-tugas {
            background: rgba(15, 23, 42, 0.9) !important;
            border-color: rgba(244, 114, 182, 0.4) !important;
        }

        .deret-angka > div:nth-child(1) {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%) !important;
            border-color: #818cf8 !important;
        }
        .deret-angka > div:nth-child(2) {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%) !important;
            border-color: #34d399 !important;
        }
        .deret-angka > div:nth-child(3) {
            background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%) !important;
            border-color: #fb7185 !important;
        }

        .blok {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 2px solid #c084fc !important;
            border-radius: 1.25rem !important;
            box-shadow: 0 15px 35px -10px rgba(192, 132, 252, 0.2) !important;
            overflow: hidden !important;
        }
        .dark .blok {
            background: rgba(15, 23, 42, 0.9) !important;
            border-color: rgba(192, 132, 252, 0.4) !important;
        }

        .stat-value {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-weight: 900 !important;
            background: linear-gradient(135deg, #ff007a 0%, #7928ca 50%, #0070f3 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            font-size: 2rem !important;
        }

        @media (min-width: 640px) {
            .stat-value {
                font-size: 2.5rem !important;
            }
        }

        /* HIDE SCROLLBARS FOR TOUCH SLIDING */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* LIVE PULSE DOT — dipakai badge "aktif" & indikator koneksi realtime */
        .pulse-rainbow {
            position: relative;
            display: inline-flex;
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #ff007a, #7928ca, #0070f3);
            box-shadow: 0 0 6px rgba(255, 0, 122, 0.8);
        }
        .pulse-rainbow::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            background: inherit;
            animation: pulseRing 1.8s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulseRing {
            0% { transform: scale(1); opacity: 0.7; }
            100% { transform: scale(2.6); opacity: 0; }
        }

        /* ENTRANCE — kartu muncul bertahap, bukan sekaligus */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in {
            animation: fadeSlideUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .animate-in-1 { animation-delay: 0ms; }
        .animate-in-2 { animation-delay: 80ms; }
        .animate-in-3 { animation-delay: 160ms; }
        .animate-in-4 { animation-delay: 240ms; }
        .animate-in-5 { animation-delay: 320ms; }
        .animate-in-6 { animation-delay: 400ms; }
        .animate-in-7 { animation-delay: 480ms; }
        .animate-in-8 { animation-delay: 560ms; }

        /* KARTU STATISTIK terangkat halus saat disentuh */
        .papan-tugas {
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .papan-tugas:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 32px -12px rgba(244, 114, 182, 0.35);
        }

        @media (prefers-reduced-motion: reduce) {
            .animate-in, .pulse-rainbow::before, .papan-tugas {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body class="h-full antialiased" @keydown.window.ctrl.k.prevent="showCmd = !showCmd" @keydown.window.cmd.k.prevent="showCmd = !showCmd">

{{-- PITA PENYAMARAN --}}
@if (session('impersonator_id'))
    <div class="fixed top-0 inset-x-0 z-50 flex items-center justify-between bg-gradient-to-r from-pink-600 via-purple-600 to-cyan-500 px-4 py-2 font-mono text-[11px] font-black text-white shadow-lg">
        <span class="truncate pr-2">⚡ Mode Penyamaran Operator Aktif</span>
        <form method="POST" action="{{ route('admin.users.stop-impersonating') }}" class="shrink-0">
            @csrf
            <button type="submit" class="underline hover:text-amber-200">Kembali</button>
        </form>
    </div>
@endif

{{-- RESPONSIVE STICKY NAVBAR --}}
<header class="sticky top-0 z-50 bg-white/90 dark:bg-slate-900/90 backdrop-blur-2xl border-b-2 border-pink-200 dark:border-purple-900 shadow-xl">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between">
        
        {{-- Brand Logo --}}
        <a href="{{ $modeOperator ? route('admin.dashboard') : route('dashboard') }}" class="flex items-center gap-2.5 sm:gap-3.5 no-underline group shrink-0">
            <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-2xl flex items-center justify-center text-lg sm:text-2xl shrink-0 group-hover:rotate-12 transition-transform duration-300"
                 style="background: linear-gradient(135deg,#ff007a,#7928ca,#0070f3); box-shadow: 0 4px 15px rgba(255,0,122,.4);">🎓</div>
            <div>
                <span class="font-black text-lg sm:text-2xl text-slate-900 dark:text-white block leading-none" style="font-family:'Plus Jakarta Sans',sans-serif;">Wali Kelas <span class="gradient-rainbow-text">Hebat</span></span>
                <span class="text-[9px] sm:text-[10px] font-black tracking-widest text-pink-600 dark:text-pink-400 uppercase block mt-0.5">walas.my.id</span>
            </div>
        </a>

        {{-- Search Command Palette Trigger (Desktop) --}}
        <button @click="showCmd = true" type="button" class="hidden md:flex items-center gap-3 bg-gradient-to-r from-pink-50 to-purple-50 dark:from-slate-800 dark:to-purple-950 border-2 border-pink-300 dark:border-purple-700 px-4 py-2 rounded-full text-xs font-black text-purple-900 dark:text-purple-200 hover:scale-105 transition-all shadow-md">
            <span>⚡ Cari Pintasan (Presensi, Siswa, PDF)...</span>
            <kbd class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-2 py-0.5 rounded-full font-mono text-[10px]">Ctrl + K</kbd>
        </button>

        {{-- Right Controls --}}
        <div class="flex items-center gap-1 sm:gap-3 shrink-0">

            {{-- Search Icon Trigger (Mobile) --}}
            <button @click="showCmd = true" type="button" class="md:hidden p-1.5 sm:p-2 rounded-full bg-pink-100 dark:bg-slate-800 text-purple-700 dark:text-purple-300 text-xs font-black" title="Cari Menu">
                🔍
            </button>

            {{-- Dark / Light Theme Toggle Button --}}
            <button @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')" type="button"
                    class="p-1.5 sm:p-2.5 rounded-full bg-pink-100 dark:bg-slate-800 border-2 border-pink-300 dark:border-purple-700 text-slate-800 dark:text-amber-300 hover:scale-110 transition-transform shadow-md"
                    :title="darkMode ? 'Ubah ke Mode Terang' : 'Ubah ke Mode Gelap'">
                <span x-show="!darkMode" class="text-xs sm:text-base">🌙</span>
                <span x-show="darkMode" x-cloak class="text-xs sm:text-base">☀️</span>
            </button>

            {{-- Class Selector Button & Dropdown --}}
            @unless ($modeOperator)
                <div class="relative" x-data="{ buka: false }" @keydown.escape.window="buka = false">
                    <button type="button" @click="buka = !buka"
                            class="flex items-center gap-1 sm:gap-2.5 rounded-full bg-gradient-to-r from-pink-500 via-purple-600 to-indigo-600 text-white border border-white/60 px-2 py-1.5 sm:px-4 sm:py-2 text-[11px] sm:text-xs font-black hover:scale-105 transition-all shadow-md">
                        <span class="truncate max-w-[52px] sm:max-w-[160px]">{{ $kelasAktif?->name ?? 'Pilih Kelas' }}</span>
                        <span class="text-[9px] text-pink-200">▼</span>
                    </button>

                    <div x-show="buka" x-cloak @click.outside="buka = false"
                         class="absolute right-0 z-50 mt-2 w-60 sm:w-72 overflow-hidden rounded-3xl border-2 border-pink-300 dark:border-purple-700 bg-white dark:bg-slate-900 py-2 shadow-2xl">
                        <div class="border-b border-pink-100 dark:border-slate-800 px-4 py-2 text-[10px] sm:text-[11px] font-black text-pink-600 uppercase tracking-wider bg-pink-50 dark:bg-slate-800">
                            🏫 Pilih Kelas Perwalian / Ajar
                        </div>
                        @forelse ($kelasPintasan as $k)
                            <a href="{{ route('classes.show', $k) }}"
                               class="block truncate px-4 py-2 text-xs font-extrabold transition-all hover:bg-pink-50 dark:hover:bg-slate-800 {{ ($kelasAktif && $kelasAktif->is($k)) ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white font-black' : 'text-slate-700 dark:text-slate-300' }}">
                                {{ $k->name }}
                            </a>
                        @empty
                            <p class="px-4 py-2 text-xs text-slate-500">Belum ada kelas.</p>
                        @endforelse
                        <div class="border-t border-pink-100 dark:border-slate-800 mt-1 pt-1">
                            <a href="{{ route('classes.index') }}" class="block px-4 py-2 text-xs font-black text-slate-700 dark:text-slate-300 hover:bg-pink-50">Kelola Semua Kelas</a>
                            <a href="{{ route('classes.create') }}" class="block px-4 py-2 text-xs font-black text-pink-600 hover:bg-pink-50">+ Buat Kelas Baru</a>
                        </div>
                    </div>
                </div>
            @endunless

            {{-- User Account Dropdown --}}
            <div class="relative" x-data="{ buka: false }" @keydown.escape.window="buka = false">
                <button type="button" @click="buka = !buka"
                        class="flex items-center gap-1.5 sm:gap-2 rounded-full border-2 border-pink-300 dark:border-purple-700 bg-white dark:bg-slate-800 p-1 sm:px-3 sm:py-1.5 shadow-md hover:scale-105 transition-all">
                    <span class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full text-white text-[11px] sm:text-xs font-black shrink-0 shadow-md"
                          style="background: linear-gradient(135deg,#ff007a,#7928ca);">
                        {{ Str::upper(Str::substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </span>
                    <span class="hidden max-w-[8rem] truncate text-xs font-black text-slate-900 dark:text-white md:block">{{ auth()->user()->name ?? 'Pengguna' }}</span>
                    <span class="hidden md:inline text-[9px] text-pink-400">▼</span>
                </button>

                <div x-show="buka" x-cloak @click.outside="buka = false"
                     class="absolute right-0 z-50 mt-2 w-60 sm:w-64 overflow-hidden rounded-3xl border-2 border-pink-300 dark:border-purple-700 bg-white dark:bg-slate-900 py-2 shadow-2xl">
                    <div class="border-b border-pink-100 dark:border-slate-800 px-4 py-2.5 bg-gradient-to-r from-pink-50 via-purple-50 to-cyan-50 dark:from-slate-800 dark:to-purple-950">
                        <p class="truncate text-xs font-black text-purple-900 dark:text-purple-300" style="font-family:'Plus Jakarta Sans',sans-serif;">{{ auth()->user()->name ?? '' }}</p>
                        <p class="truncate text-[10px] text-pink-600 dark:text-pink-400 font-bold">{{ auth()->user()->email ?? '' }}</p>
                    </div>

                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-xs font-extrabold text-slate-700 dark:text-slate-300 hover:bg-pink-50 dark:hover:bg-slate-800">⚙️ Profil &amp; Kata Sandi</a>

                    @unless ($modeOperator)
                        <a href="{{ route('holidays.index') }}" class="block px-4 py-2 text-xs font-extrabold text-slate-700 dark:text-slate-300 hover:bg-pink-50 dark:hover:bg-slate-800">📅 Kalender Sekolah</a>
                        <a href="{{ route('whatsapp.index') }}" class="block px-4 py-2 text-xs font-extrabold text-slate-700 dark:text-slate-300 hover:bg-pink-50 dark:hover:bg-slate-800">💬 Integrasi WhatsApp</a>
                        <a href="{{ route('violation-types.index') }}" class="block px-4 py-2 text-xs font-extrabold text-slate-700 dark:text-slate-300 hover:bg-pink-50 dark:hover:bg-slate-800">🚨 Jenis Pelanggaran</a>
                        <a href="{{ route('analytics.index') }}" class="block px-4 py-2 text-xs font-extrabold text-slate-700 dark:text-slate-300 hover:bg-pink-50 dark:hover:bg-slate-800">📊 Analitik Kelas</a>
                        <a href="{{ route('subscription.index') }}" class="flex items-center justify-between gap-2 border-t border-pink-100 dark:border-slate-800 px-4 py-2 text-xs font-black text-pink-600 hover:bg-pink-50 dark:hover:bg-slate-800">
                            Langganan PRO <span class="px-2 py-0.5 rounded-full text-[9px] bg-gradient-to-r from-amber-400 to-pink-500 text-white font-black">PRO</span>
                        </a>
                    @endunless

                    <form method="POST" action="{{ route('logout') }}" class="border-t border-pink-100 dark:border-slate-800">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-2 text-left text-xs font-black text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40">🚪 Keluar</button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    {{-- RESPONSIVE SLIDING PILL NAVIGATION TABS --}}
    @if (!empty($pembatas))
        <div class="bg-gradient-to-r from-pink-200/80 via-purple-200/80 to-cyan-200/80 dark:from-slate-900/90 dark:via-purple-950/60 dark:to-slate-900/90 border-t border-pink-200 dark:border-slate-800 px-3 sm:px-6 py-2.5">
            <nav class="max-w-7xl mx-auto flex items-center gap-2 overflow-x-auto no-scrollbar scroll-smooth" aria-label="Bagian">
                @foreach ($pembatas as [$label, $tautan, $ini])
                    <a href="{{ $tautan }}" @if($ini) aria-current="page" @endif
                       class="shrink-0 transition-all text-[11px] sm:text-xs {{ $ini ? $pembatasAktif : $pembatasDiam }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>
    @endif
</header>

{{-- MAIN CONTENT WRAPPER --}}
<main class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8 space-y-6">
    @include('partials.masa-otomasi')

    <div class="card-colorful p-4 sm:p-8 lg:p-10">
        @yield('content')
    </div>
</main>

{{-- COMMAND PALETTE MODAL (CTRL+K) --}}
<div x-show="showCmd" x-cloak @click.outside="showCmd = false" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-md">
    <div class="w-full max-w-xl bg-white dark:bg-slate-900 border-2 border-pink-400 dark:border-purple-600 rounded-3xl p-5 sm:p-7 space-y-5 shadow-2xl">
        <div class="flex items-center justify-between border-b border-pink-100 dark:border-slate-800 pb-3">
            <span class="font-black text-xs sm:text-sm text-pink-600 dark:text-pink-400">⚡ Pintasan Kilat (Command Palette)</span>
            <button @click="showCmd = false" class="text-xs font-black text-slate-400 hover:text-slate-600">✕ ESC</button>
        </div>

        <div class="space-y-2 text-xs font-extrabold">
            <a href="{{ route('dashboard') }}" class="flex items-center justify-between p-3 rounded-2xl bg-gradient-to-r from-pink-50 to-purple-50 dark:from-slate-800 dark:to-purple-950 text-purple-900 dark:text-purple-200">
                <span>📊 Ke Dasbor Utama</span>
                <span>&rsaquo;</span>
            </a>
            @if ($kelasAktif)
                <a href="{{ route('classes.students.index', $kelasAktif) }}" class="flex items-center justify-between p-3 rounded-2xl bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-slate-800 dark:to-indigo-950 text-indigo-900 dark:text-indigo-200">
                    <span>📋 Kelola Daftar Siswa &amp; Form Biodata</span>
                    <span>&rsaquo;</span>
                </a>
                <a href="{{ route('classes.attendance-sessions.index', $kelasAktif) }}" class="flex items-center justify-between p-3 rounded-2xl bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-slate-800 dark:to-emerald-950 text-emerald-900 dark:text-emerald-200">
                    <span>📲 Buka Presensi WhatsApp 1-Klik</span>
                    <span>&rsaquo;</span>
                </a>
                <a href="{{ route('classes.reports.index', $kelasAktif) }}" class="flex items-center justify-between p-3 rounded-2xl bg-gradient-to-r from-purple-50 to-pink-50 dark:from-slate-800 dark:to-purple-950 text-purple-900 dark:text-purple-200">
                    <span>🖨️ Cetak Laporan PDF 7 Bab</span>
                    <span>&rsaquo;</span>
                </a>
            @endif
            <a href="{{ route('whatsapp.index') }}" class="flex items-center justify-between p-3 rounded-2xl bg-gradient-to-r from-cyan-50 to-sky-50 dark:from-slate-800 dark:to-cyan-950 text-cyan-900 dark:text-cyan-200">
                <span>💬 Status WhatsApp Bot</span>
                <span>&rsaquo;</span>
            </a>
        </div>
    </div>
</div>

{{-- FOOTER --}}
<footer class="py-6 sm:py-8 border-t border-pink-200 dark:border-purple-900 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl mt-8 sm:mt-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-2xl flex items-center justify-center text-sm sm:text-base shrink-0"
                 style="background: linear-gradient(135deg,#ff007a,#7928ca,#0070f3);">🎓</div>
            <span class="font-black text-sm sm:text-base text-slate-900 dark:text-white" style="font-family:'Plus Jakarta Sans',sans-serif;">Wali Kelas Hebat ✨</span>
        </div>
        <p class="text-[11px] sm:text-xs text-slate-500 font-bold">© {{ date('Y') }} Wali Kelas Hebat · walas.my.id</p>
    </div>
</footer>

@stack('scripts')

</body>
</html>
