<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - WaliKelas Pro</title>
    <meta name="theme-color" content="#f7f5f1">

    {{-- @vite, bukan mix(). Aplikasi ini dibangun dengan Vite; public/mix-manifest.json
         hanyalah berkas tempelan yang isinya disalin manual dari manifest Vite.
         Selama nama berkasnya kebetulan masih sama, halaman ini tampak baik —
         tetapi build aset berikutnya menghasilkan hash baru yang tidak ikut
         tersalin, dan halaman masuk kehilangan seluruh gayanya tanpa ada yang
         menyadari sampai ada pengguna yang mengeluh. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="icon" href="/favicon-32.png" sizes="32x32">

    {{-- Halaman masuk, daftar, dan verifikasi masing-masing menitipkan gayanya
         lewat @push('styles'). Tanpa baris ini titipan itu dibuang diam-diam. --}}
    @stack('styles')
</head>
<body class="flex min-h-full items-start justify-center bg-slate-50 p-4 pt-10 sm:pt-16">

<div class="w-full max-w-sm">

    {{--
        Sampul buku, bukan hero produk.

        Sebelumnya: logo bergradien dengan cahaya di baliknya, tiga lapis
        gradien radial, pola titik, dan tiga animasi masuk — seluruhnya untuk
        satu formulir berisi dua kolom isian. Yang tersisa sekarang cuma label
        arsip: nama buku, siapa pemiliknya, dan garis.
    --}}
    <div class="mb-6">
        <a href="/" class="eyebrow inline-block hover:text-slate-600">WaliKelas Pro</a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Buku administrasi kelas</h1>
        <div class="mt-3 h-px bg-slate-300"></div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6">
        @yield('content')
    </div>

    <p class="mt-5 text-center font-mono text-[10px] uppercase tracking-[0.14em] text-slate-400">
        &copy; {{ date('Y') }} WaliKelas Pro
    </p>
</div>

</body>
</html>
