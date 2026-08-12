<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Siswa') - WaliKelas Pro</title>
    <meta name="theme-color" content="#1a1712">

    {{-- @vite, bukan mix(). Layout ini satu-satunya yang masih memanggil
         mix('build/assets/app.css') — persis cacat yang sudah ditulis panjang
         lebar di layouts/guest.blade.php. Ia bertahan hanya karena skrip build
         menuliskan ulang mix-manifest.json tiap kali; hilangkan satu baris di
         package.json dan seluruh portal siswa kehilangan gayanya. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="icon" href="/favicon-32.png" sizes="32x32">
</head>
<body class="h-full text-slate-700 antialiased">

@php
    $siswa = Auth::guard('student')->user();

    /*
     * Sebelumnya sidebar tetap selebar 256px tanpa satu pun titik henti
     * responsif — pada ponsel ia memakan dua pertiga layar dan isi halaman
     * terjepit di sisa sepertiganya. Portal ini nyaris hanya dibuka dari
     * ponsel siswa. Jadi navigasinya kini deret pembatas yang sama dengan
     * aplikasi wali kelas: satu bentuk, bekerja di kedua ukuran layar.
     */
    $bagian = [
        ['Beranda', route('student.dashboard'), request()->routeIs('student.dashboard')],
        ['Portofolio', route('student.portfolio'), request()->routeIs('student.portfolio*')],
        ['Biodata', route('student.biodata'), request()->routeIs('student.biodata*')],
    ];

    $pembatasAktif = '-mb-px rounded-t border border-slate-200 border-b-slate-50 bg-slate-50 text-slate-900';
    $pembatasDiam = 'border border-transparent text-slate-500 hover:text-slate-900';
@endphp

<header class="bg-slate-900">
    <div class="mx-auto flex h-10 max-w-3xl items-center justify-between gap-4 px-4">
        <span class="font-mono text-[11px] font-medium uppercase tracking-[0.18em] text-slate-300">
            Portal <span class="text-white">Siswa</span>
        </span>

        <form method="POST" action="{{ route('student.logout') }}">
            @csrf
            <button type="submit" class="rounded-sm px-1.5 py-1 text-xs font-medium text-slate-300 transition-colors hover:bg-white/10 hover:text-white">
                Keluar
            </button>
        </form>
    </div>
</header>

<div class="sticky top-0 z-30 border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-3xl px-4">
        <div class="flex items-center gap-2 pt-3">
            <span class="avatar avatar--sm">
                <span class="avatar-initials">{{ Str::substr($siswa->name, 0, 1) }}</span>
            </span>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold tracking-tight text-slate-900">{{ $siswa->name }}</p>
            </div>
            <span class="kode ml-auto shrink-0">{{ $siswa->nis }}</span>
        </div>

        <nav class="-mb-px mt-2.5 flex gap-0.5" aria-label="Bagian">
            @foreach ($bagian as [$label, $tautan, $ini])
                <a href="{{ $tautan }}" @if($ini) aria-current="page" @endif
                   class="shrink-0 px-3 py-1.5 text-xs font-medium transition-colors {{ $ini ? $pembatasAktif : $pembatasDiam }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>
</div>

<main class="mx-auto w-full max-w-3xl px-4 py-5">
    {{-- Pesan kilat ikut mengalir bersama isi halaman, tidak lagi mengambang
         di pojok kanan bawah: pada layar ponsel pojok itu tertutup papan
         ketik, dan galat validasi jadi tidak pernah terbaca sama sekali. --}}
    @include('partials.flash')

    @yield('content')
</main>

</body>
</html>
