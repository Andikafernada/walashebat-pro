<!DOCTYPE html>
<html lang="id" class="h-full bg-[#f0fdf4]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Siswa') - WaliKelas Pro</title>
    <meta name="theme-color" content="#059669">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" href="/favicon-32.png" sizes="32x32">
    <link rel="apple-touch-icon" href="/favicon-192.png">

    <style>
        :root {
            --safe-area-inset-top: env(safe-area-inset-top, 0px);
            --safe-area-inset-bottom: env(safe-area-inset-bottom, 0px);
        }
        .safe-area-inset-top { padding-top: var(--safe-area-inset-top); }
        .nav-touch-btn {
            transition: transform 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }
        .nav-touch-btn:active {
            transform: scale(0.90);
        }
    </style>
</head>
<body class="h-full text-slate-900 bg-[#f0fdf4] antialiased flex flex-col pb-28">

@php
    $siswa = Auth::guard('student')->user();

    $isBerandaActive = request()->routeIs('student.dashboard');
    $isPortfolioActive = request()->routeIs('student.portfolio*');
    $isBiodataActive = request()->routeIs('student.biodata*');
    $isPasswordActive = request()->routeIs('student.password*');
@endphp

{{-- HEADER — Modern Compact Header for Student Portal --}}
<header class="bg-white/95 backdrop-blur-md border-b border-emerald-200 sticky top-0 z-40 px-4 py-2.5 shadow-2xs safe-area-inset-top">
    <div class="max-w-md mx-auto flex items-center justify-between">
        {{-- Logo / Brand --}}
        <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2.5 text-decoration-none">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-700 to-teal-500 flex items-center justify-center text-white font-black text-sm shadow-xs">
                🎓
            </div>
            <div>
                <span class="font-black text-slate-900 text-xs leading-none block tracking-tight">Portal <span class="text-emerald-700 font-extrabold">Siswa</span></span>
                <span class="text-[9px] font-bold text-emerald-800 uppercase block mt-0.5">{{ $siswa ? ($siswa->classroom->name ?? 'WaliKelas') : 'Siswa' }}</span>
            </div>
        </a>

        {{-- User Info & Logout --}}
        <div class="flex items-center gap-2">
            @if($siswa)
                <div class="w-7 h-7 rounded-full bg-emerald-600 text-white font-black text-xs flex items-center justify-center shadow-2xs">
                    {{ Str::upper(Str::substr($siswa->name, 0, 1)) }}
                </div>
                <form method="POST" action="{{ route('student.logout') }}" onsubmit="return confirm('Keluar dari Portal Siswa?');" class="inline">
                    @csrf
                    <button type="submit"
                            class="w-7 h-7 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 flex items-center justify-center text-xs font-bold transition-colors"
                            title="Keluar / Logout">
                        🚪
                    </button>
                </form>
            @endif
        </div>
    </div>
</header>

{{-- MAIN CONTENT --}}
<main class="max-w-md w-full mx-auto px-3 sm:px-4 pt-4 flex-1">
    @include('partials.flash')
    @yield('content')
</main>

{{-- NEXT-GEN FLOATING GLASS ISLAND FOOTBAR FOR STUDENTS --}}
<nav class="fixed bottom-3 left-3 right-3 max-w-md mx-auto z-40 bg-white/92 backdrop-blur-2xl border border-emerald-200/90 rounded-[28px] shadow-[0_12px_36px_-6px_rgba(6,78,59,0.22)] py-1.5 px-3 mb-[env(safe-area-inset-bottom,0px)]" role="navigation" aria-label="Menu Siswa">
    <div class="flex justify-between items-center">

        {{-- 1. BERANDA --}}
        <a href="{{ route('student.dashboard') }}"
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

        {{-- 2. PORTOFOLIO P5 --}}
        <a href="{{ route('student.portfolio') }}"
           class="nav-touch-btn flex-1 flex flex-col items-center justify-center py-1 rounded-2xl no-underline group"
           title="Portofolio P5">
            <div class="p-1 rounded-xl transition-all {{ $isPortfolioActive ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }}">
                <svg class="w-5 h-5 {{ $isPortfolioActive ? 'fill-emerald-600' : 'fill-none stroke-current stroke-2' }}" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                </svg>
            </div>
            <span class="text-[10px] font-black leading-none mt-0.5 {{ $isPortfolioActive ? 'text-emerald-950 font-black' : 'text-slate-600 font-semibold' }}">
                Portofolio
            </span>
            @if($isPortfolioActive)
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shadow-2xs mt-1"></span>
            @else
                <span class="w-1.5 h-1.5 mt-1"></span>
            @endif
        </a>

        {{-- 3. BIODATA SISWA --}}
        <a href="{{ route('student.biodata') }}"
           class="nav-touch-btn flex-1 flex flex-col items-center justify-center py-1 rounded-2xl no-underline group"
           title="Biodata Siswa">
            <div class="p-1 rounded-xl transition-all {{ $isBiodataActive ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }}">
                <svg class="w-5 h-5 {{ $isBiodataActive ? 'fill-emerald-600' : 'fill-none stroke-current stroke-2' }}" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                </svg>
            </div>
            <span class="text-[10px] font-black leading-none mt-0.5 {{ $isBiodataActive ? 'text-emerald-950 font-black' : 'text-slate-600 font-semibold' }}">
                Biodata
            </span>
            @if($isBiodataActive)
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shadow-2xs mt-1"></span>
            @else
                <span class="w-1.5 h-1.5 mt-1"></span>
            @endif
        </a>

        {{-- 4. PROFIL & SANDI --}}
        <a href="{{ route('student.password.change') }}"
           class="nav-touch-btn flex-1 flex flex-col items-center justify-center py-1 rounded-2xl no-underline group"
           title="Ganti Sandi">
            <div class="p-1 rounded-xl transition-all {{ $isPasswordActive ? 'text-emerald-700 font-extrabold' : 'text-slate-500' }}">
                <svg class="w-5 h-5 {{ $isPasswordActive ? 'fill-emerald-600' : 'fill-none stroke-current stroke-2' }}" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
            </div>
            <span class="text-[10px] font-black leading-none mt-0.5 {{ $isPasswordActive ? 'text-emerald-950 font-black' : 'text-slate-600 font-semibold' }}">
                Sandi
            </span>
            @if($isPasswordActive)
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shadow-2xs mt-1"></span>
            @else
                <span class="w-1.5 h-1.5 mt-1"></span>
            @endif
        </a>

    </div>
</nav>

</body>
</html>
