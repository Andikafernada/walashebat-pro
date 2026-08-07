<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') - WaliKelas Pro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ mix('build/assets/app.css') }}">
    <script src="{{ mix('build/assets/app.js') }}" defer></script>
</head>
<body class="min-h-full bg-slate-100 font-sans text-slate-800 antialiased">

<div class="mx-auto flex min-h-dvh max-w-lg flex-col px-4 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-6">

    {{-- Kop halaman. Kelas dan tanggal ditaruh di sini, bukan di dalam kartu,
         supaya identitas sesi tetap terlihat di setiap langkah. --}}
    <header class="mb-5 flex items-center gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm shadow-indigo-600/25">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="truncate text-sm font-bold leading-tight text-slate-900">
                @hasSection('heading') @yield('heading') @else Absensi Kelas @endif
            </p>
            <p class="truncate text-xs text-slate-500">{{ config('app.name') }}</p>
        </div>
    </header>

    {{-- Penanda langkah. Petugas selalu tahu ada berapa langkah dan di mana dia
         berada, jadi halaman PIN tidak terasa seperti jalan buntu. --}}
    @php $step = trim($__env->yieldContent('step', '1')); @endphp
    <ol class="mb-5 flex items-center gap-2" aria-label="Langkah pengisian">
        @foreach ([1 => 'PIN', 2 => 'Isi daftar', 3 => 'Selesai'] as $i => $label)
            @php
                $state = $i < $step ? 'done' : ($i == $step ? 'current' : 'todo');
            @endphp
            <li class="flex flex-1 flex-col gap-1.5" @if($state === 'current') aria-current="step" @endif>
                <span @class([
                    'h-1 rounded-full transition-colors',
                    'bg-indigo-600' => $state !== 'todo',
                    'bg-slate-300' => $state === 'todo',
                ])></span>
                <span @class([
                    'text-[11px] font-semibold tracking-wide',
                    'text-indigo-700' => $state === 'current',
                    'text-slate-500' => $state === 'done',
                    'text-slate-400' => $state === 'todo',
                ])>{{ $label }}</span>
            </li>
        @endforeach
    </ol>

    {{-- Tanpa blok ini, penolakan validasi di formulir publik tidak terlihat
         sama sekali: halaman hanya termuat ulang seolah tidak terjadi apa-apa. --}}
    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3.5" role="alert">
            <p class="text-xs font-bold text-rose-800">Isian belum bisa disimpan:</p>
            <ul class="mt-1.5 space-y-1 text-xs text-rose-700">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <main class="flex-1">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="mt-8 text-center text-[11px] leading-relaxed text-slate-400">
        <p>Tautan ini pribadi. Jangan teruskan ke grup atau siapa pun selain petugas absensi.</p>
        <p class="mt-1">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
    </footer>
</div>

</body>
</html>
