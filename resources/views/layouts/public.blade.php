<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f7f5f1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') - WaliKelas Pro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- @vite, bukan mix(): alasannya ditulis panjang di layouts/guest.blade.php. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 text-slate-700 antialiased">

<div class="mx-auto flex min-h-dvh max-w-lg flex-col px-4 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-6">

    {{-- Kop halaman. Kelas dan tanggal ditaruh di sini, bukan di dalam kartu,
         supaya identitas sesi tetap terlihat di setiap langkah. --}}
    <header class="mb-5">
        <p class="eyebrow">{{ config('app.name') }}</p>
        <h1 class="mt-1.5 truncate text-lg font-semibold tracking-tight text-slate-900">
            @hasSection('heading') @yield('heading') @else Absensi Kelas @endif
        </h1>
        <div class="mt-3 h-px bg-slate-300"></div>
    </header>

    {{--
        Penanda langkah. Petugas selalu tahu ada berapa langkah dan di mana dia
        berada, jadi halaman PIN tidak terasa seperti jalan buntu.

        Di sini penomoran memang menyampaikan sesuatu — urutannya nyata dan
        tidak bisa dilompati — jadi angkanya ditulis, bukan cuma batang warna.
    --}}
    @php $step = (int) trim($__env->yieldContent('step', '1')); @endphp
    <ol class="mb-5 flex items-stretch gap-px overflow-hidden rounded border border-slate-200 bg-white" aria-label="Langkah pengisian">
        @foreach ([1 => 'PIN', 2 => 'Isi daftar', 3 => 'Selesai'] as $i => $label)
            @php $keadaan = $i < $step ? 'lewat' : ($i == $step ? 'kini' : 'nanti'); @endphp
            <li @class([
                    'flex flex-1 items-center gap-1.5 px-2.5 py-2 text-[11px] font-medium',
                    'bg-slate-900 text-white' => $keadaan === 'kini',
                    'text-slate-500' => $keadaan === 'lewat',
                    'text-slate-300' => $keadaan === 'nanti',
                ])
                @if($keadaan === 'kini') aria-current="step" @endif>
                <span class="font-mono">{{ $keadaan === 'lewat' ? '✓' : $i }}</span>
                <span class="truncate">{{ $label }}</span>
            </li>
        @endforeach
    </ol>

    <main class="flex-1">
        {{-- Galat validasi ikut di sini. Dulu blok galatnya ditulis dua kali:
             sekali di layout ini dan sekali lagi di partials.flash, sehingga
             setiap penolakan validasi tampil dobel di layar petugas. --}}
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="mt-8 border-t border-slate-200 pt-4 text-center text-[11px] leading-relaxed text-slate-400">
        <p>Tautan ini pribadi. Jangan teruskan ke grup atau siapa pun selain petugas absensi.</p>
        <p class="mt-1 font-mono uppercase tracking-[0.14em]">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
    </footer>
</div>

</body>
</html>
