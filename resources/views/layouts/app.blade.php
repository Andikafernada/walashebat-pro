<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Wali Kelas Hebat') }}</title>

    <!-- PWA Settings -->
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#1a1712">

    {{--
        Dua rupa huruf, lima berat — sebelumnya dua rupa dengan delapan berat.

        Instrument Sans untuk seluruh antarmuka: sedikit rapat, tenang, dan
        tidak berlagak. IBM Plex Mono khusus untuk yang berkolom — kode absensi,
        kepala tabel, label bagian, nomor urut. Angka administrasi harus lurus
        ke bawah, dan itu pekerjaan rupa monospasi, bukan hiasan.
    --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Gaya tambahan per halaman. Lima halaman sudah memakai @push('styles')
         sejak lama, tetapi tidak ada satu pun layout yang merendernya — jadi
         seluruh isinya tidak pernah sampai ke browser. --}}
    @stack('styles')
    {{-- Alpine sudah ikut di dalam bundel Vite (resources/js/app.js) dan
         dijalankan di sana. Memuatnya sekali lagi dari CDN membuat dua
         salinan Alpine berjalan berbarengan — "Detected multiple instances of
         Alpine running" — dan penangan yang sama terpasang dua kali. --}}

    <style>
        /* Deret pembatas boleh digeser di ponsel, tetapi batang gulirnya
           sendiri hanya jadi kotoran di bawah tab. */
        .pembatas { scrollbar-width: none; }
        .pembatas::-webkit-scrollbar { display: none; }
    </style>
    {{--
        Chart.js TIDAK dimuat di sini.

        Dulu setiap halaman menariknya — 200 KB untuk halaman yang tidak punya
        satu pun grafik, pada ponsel guru dengan kuota terbatas. Lebih buruk
        lagi, alamatnya tanpa nomor versi, sehingga isinya bisa berganti kapan
        saja tanpa sepengetahuan kita, dan halaman analitik terlanjur memuat
        versinya sendiri sehingga berkasnya masuk dua kali.

        Sekarang hanya dua halaman yang memakainya (dashboard dan analitik)
        yang memuatnya sendiri, dengan versi terpaku dan integrity hash.
    --}}
</head>
<body class="h-full bg-slate-50 text-slate-700 antialiased">

{{-- Pita penyamaran: operator yang sedang masuk sebagai guru harus selalu
     sadar ia bukan dirinya, dan selalu punya jalan kembali dalam satu klik. --}}
@if (session('impersonator_id'))
    <div class="sticky top-0 z-[60] flex h-7 items-center justify-center gap-3 border-b border-amber-700 bg-amber-600 px-4 text-center text-xs font-medium text-white">
        <span class="kode kode--izin border-white/30 bg-white/10 text-white" aria-hidden="true">OP</span>
        <span>Masuk sebagai <strong class="font-semibold">{{ auth()->user()->name }}</strong></span>
        <form method="POST" action="{{ route('teachers.stop-impersonate') }}">
            @csrf
            <button type="submit" class="rounded-sm border border-white/40 px-1.5 py-0.5 text-[11px] font-medium hover:bg-white/15">Kembali ke admin</button>
        </form>
    </div>
@endif

@php
    /*
     * Kelas yang sedang dibuka — dipakai seluruh chrome di bawah.
     *
     * Diperiksa dengan instanceof, bukan isset(). Blade meneruskan SELURUH
     * variabel yang terdefinisi di view anak ke layout (get_defined_vars()),
     * termasuk sisa variabel perulangan. Satu @foreach($daftar as $class) di
     * halaman mana pun sudah cukup membuat menu ini menunjuk ke objek yang
     * salah — dan bila isinya bukan Classroom, seluruh halaman berhenti dengan
     * galat 500 dari route() atau dari ->kelasAjar(). Persis itu yang terjadi
     * pada dashboard admin begitu tabel ringkasan kelasnya berisi data.
     */
    $kelasAktif = collect([$classroom ?? null, $class ?? null])
        ->first(fn ($k) => $k instanceof \App\Models\Classroom);

    /*
     * Admin adalah operator SaaS, bukan pemegang kelas: ia tidak punya kelas,
     * kalender, integrasi WA, atau langganan pribadi. Chrome-nya sama persis,
     * isinya saja yang lain.
     */
    $modeOperator = auth()->user()->isAdmin();

    /*
     * PEMBATAS.
     *
     * Aplikasi ini bukan sembilan belas tujuan sederajat — ia satu kelas
     * dengan beberapa bagian, persis buku administrasi kelas yang dipegang
     * guru. Maka bentuknya pembatas buku, bukan sidebar dasbor SaaS: yang
     * jarang dibuka duduk di ujung kanan dan tinggal digeser, tanpa perlu
     * lipatan, laci, atau kategori.
     *
     * Kelas ditulis utuh di dua konstanta di bawah, bukan dirakit dari
     * potongan: Tailwind memindai berkas ini sebagai teks dan tidak
     * menjalankan PHP-nya.
     *
     * Bentuknya pembatas sungguhan, bukan garis bawah tebal. Pembatas yang
     * aktif berlatar warna kertas dan kehilangan garis bawahnya, lalu digeser
     * satu piksel menutupi garis sampul — sehingga ia menyatu dengan halaman
     * di bawahnya persis seperti pembatas yang sedang dibuka pada map. Itu
     * juga yang membuat "di mana saya" terbaca tanpa perlu membaca.
     */
    $pembatasAktif = '-mb-px rounded-t border border-slate-200 border-b-slate-50 bg-slate-50 text-slate-900';
    $pembatasDiam = 'border border-transparent text-slate-500 hover:text-slate-900';

    if ($modeOperator) {
        $pembatas = [
            ['Panel', route('admin.dashboard'), request()->routeIs('admin.dashboard')],
            ['Daftar Guru', route('admin.teachers.index'), request()->routeIs('admin.teachers.*')],
            ['Persetujuan PRO', route('admin.subscriptions.index'), request()->routeIs('admin.subscriptions.*')],
            ['Pengumuman', route('admin.announcements.form'), request()->routeIs('admin.announcements.*')],
        ];
    } else {
        $pembatas = [['Hari ini', route('dashboard'), request()->routeIs('dashboard')]];

        if ($kelasAktif) {
            $c = $kelasAktif;
            $pembatas[] = ['Ringkasan', route('classes.show', $c), request()->routeIs('classes.show')];
            $pembatas[] = ['Siswa', route('classes.students.index', $c), request()->routeIs('classes.students.*')];
            $pembatas[] = ['Absensi', route('classes.attendance.index', $c), request()->routeIs('classes.attendance.*')];
            $pembatas[] = ['Karakter P5', route('classes.character-portfolio.index', $c), request()->routeIs('classes.character-portfolio.*')];

            /*
             * Sisanya dokumen wali kelas. Pada kelas ajar wali kelasnya orang
             * lain: buku kas dan laporan bukan miliknya, jadwal disusun per
             * rombongan belajar (guru mapel yang menyuntingnya menimpa jam
             * mengajar guru lain), dan buku poin dipegang SATU orang — bila
             * tiap guru mapel ikut mencatat, satu kejadian tercatat berkali-kali
             * dan sanksi siswa dihitung dari angka yang salah.
             */
            if (! $c->kelasAjar()) {
                $pembatas[] = ['Poin', route('classes.violations.index', $c), request()->routeIs('classes.violations.*')];
                $pembatas[] = ['Kas', route('classes.cashbook.index', $c), request()->routeIs('classes.cashbook.*')];
                $pembatas[] = ['Jadwal', route('classes.schedules.index', $c), request()->routeIs('classes.schedules.*')];
                $pembatas[] = ['Denah', route('classes.seating.index', $c), request()->routeIs('classes.seating.*')];
                $pembatas[] = ['Organisasi', route('classes.organization.index', $c), request()->routeIs('classes.organization.*')];
                $pembatas[] = ['Laporan', route('classes.reports.full', $c), request()->routeIs('classes.reports.*')];
            }
        }

        /*
         * Halaman milik akun dibuka dari menu avatar, jadi tidak punya
         * pembatas tetap. Tanpa ini guru yang sedang berada di Kalender Sekolah
         * melihat deret pembatas yang seluruhnya tidak aktif dan kehilangan
         * penanda di mana ia berada — kegagalan yang sama persis dengan lipatan
         * sidebar yang dulu menutup halaman aktifnya.
         */
        foreach ([
            'holidays.*' => ['Kalender Sekolah', 'holidays.index'],
            'whatsapp.*' => ['Integrasi WhatsApp', 'whatsapp.index'],
            'analytics.*' => ['Analitik', 'analytics.index'],
            'subscription.*' => ['Langganan PRO', 'subscription.index'],
            'violation-types.*' => ['Jenis Pelanggaran', 'violation-types.index'],
            'profile.*' => ['Profil Saya', 'profile.edit'],
            'classes.index' => ['Daftar Kelas', 'classes.index'],
            'classes.create' => ['Kelas Baru', 'classes.create'],
        ] as $pola => [$label, $rute]) {
            if (request()->routeIs($pola)) {
                $pembatas[] = [$label, route($rute), true];
            }
        }
    }
@endphp

{{--
    BARIS 1 — identitas & akun.

    Setipis mungkin dan tidak pernah menuntut apa pun. Semua yang dibuka
    sebulan sekali (kalender, gateway WA, langganan, analitik, profil) hidup di
    balik avatar: dulu kelimanya memakan tinggi sidebar setiap hari untuk
    pekerjaan yang dilakukan sekali per semester.
--}}
{{-- Baris ini sengaja TIDAK menempel: yang berguna tetap terlihat saat guru
     menggulir daftar siswa adalah pembatasnya, bukan merek aplikasi. --}}
<header class="bg-slate-900">
    <div class="mx-auto flex h-10 max-w-buku items-center justify-between gap-4 px-4 sm:px-6">
        {{-- Merek diperlakukan sebagai label arsip: mono, huruf besar,
             direnggangkan. Ia menamai bukunya, tidak menjual apa pun. --}}
        <a href="{{ $modeOperator ? route('admin.dashboard') : route('dashboard') }}"
           class="font-mono text-[11px] font-medium uppercase tracking-[0.18em] text-slate-300 transition-colors hover:text-white">
            Wali Kelas <span class="text-white">Hebat</span>
        </a>

        <div class="relative shrink-0" x-data="{ buka: false }" @keydown.escape.window="buka = false">
            <button type="button" @click="buka = !buka" :aria-expanded="buka ? 'true' : 'false'" aria-haspopup="true"
                    class="flex items-center gap-2 rounded-sm py-1 pl-1 pr-1.5 transition-colors hover:bg-white/10">
                <span class="flex h-6 w-6 items-center justify-center rounded-sm border border-white/20 font-mono text-[11px] font-medium text-white">
                    {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
                </span>
                <span class="hidden max-w-[10rem] truncate text-xs font-medium text-slate-200 sm:block">{{ auth()->user()->name }}</span>
                <span class="text-[9px] text-slate-400" aria-hidden="true">&#9662;</span>
            </button>

            <div x-show="buka" x-cloak @click.outside="buka = false"
                 class="absolute right-0 z-50 mt-1 w-60 overflow-hidden rounded border border-slate-300 bg-white py-1 shadow-xl">
                <div class="border-b border-slate-200 px-3 py-2">
                    <p class="truncate text-xs font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="truncate font-mono text-[10px] text-slate-500">{{ auth()->user()->email }}</p>
                </div>

                <a href="{{ route('profile.edit') }}" class="block px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Profil &amp; kata sandi</a>

                @unless ($modeOperator)
                    <a href="{{ route('holidays.index') }}" class="block px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Kalender Sekolah</a>
                    <a href="{{ route('whatsapp.index') }}" class="block px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Integrasi WhatsApp</a>
                    <a href="{{ route('violation-types.index') }}" class="block px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Jenis Pelanggaran</a>
                    {{-- Halaman analitik sudah lengkap dan berfungsi, tetapi dulu tidak
                         punya SATU PUN tautan dari mana pun: rutenya ada, controllernya
                         ada, viewnya 363 baris, dan tak seorang pun bisa mencapainya
                         kecuali mengetik URL-nya sendiri. --}}
                    <a href="{{ route('analytics.index') }}" class="block px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Analitik</a>
                    <a href="{{ route('subscription.index') }}" class="flex items-center justify-between gap-2 border-t border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        Langganan <span class="kode kode--izin">PRO</span>
                    </a>
                @endunless

                <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-200">
                    @csrf
                    <button type="submit" class="block w-full px-3 py-1.5 text-left text-xs font-medium text-rose-600 hover:bg-rose-50">Keluar</button>
                </form>
            </div>
        </div>
    </div>
</header>

{{--
    BARIS 2 — sampul kelas & pembatasnya.

    Nama kelas adalah judul halaman yang sesungguhnya; ia sekaligus tombol
    ganti kelas, sehingga pindah kelas tidak lagi menuntut mampir ke Daftar
    Kelas dulu.
--}}
{{-- Menempel di bawah pita penyamaran bila pitanya ada; tanpa offset ini
     pitanya menutupi deret pembatas begitu halaman digulir. --}}
<div class="sticky {{ session('impersonator_id') ? 'top-7' : 'top-0' }} z-30 border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-buku px-4 sm:px-6">
        <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 pt-3">
            @if ($modeOperator)
                <span class="text-lg font-semibold tracking-tight text-slate-900">Panel Operator</span>
            @else
                <div class="relative" x-data="{ buka: false }" @keydown.escape.window="buka = false">
                    <button type="button" @click="buka = !buka" :aria-expanded="buka ? 'true' : 'false'" aria-haspopup="true"
                            class="-mx-1.5 flex items-center gap-1.5 rounded-sm px-1.5 py-0.5 transition-colors hover:bg-slate-100">
                        {{-- Bukan "Semua Kelas": itu label saringan di halaman Daftar
                             Kelas, dan dua benda berbeda dengan nama sama di satu
                             layar adalah cara tercepat membingungkan orang. --}}
                        <span class="text-lg font-semibold tracking-tight text-slate-900">{{ $kelasAktif?->name ?? 'Pilih kelas' }}</span>
                        <span class="text-[10px] text-slate-400" aria-hidden="true">&#9662;</span>
                    </button>

                    <div x-show="buka" x-cloak @click.outside="buka = false"
                         class="absolute left-0 z-50 mt-1 max-h-80 w-64 overflow-y-auto rounded border border-slate-300 bg-white py-1 shadow-xl">
                        @forelse ($kelasPintasan as $k)
                            <a href="{{ route('classes.show', $k) }}"
                               class="block truncate px-3 py-1.5 text-xs font-medium hover:bg-slate-50 {{ $kelasAktif?->is($k) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700' }}">
                                {{ $k->name }}
                            </a>
                        @empty
                            <p class="px-3 py-1.5 text-xs text-slate-500">Belum ada kelas.</p>
                        @endforelse
                        <a href="{{ route('classes.index') }}" class="block border-t border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Kelola semua kelas</a>
                        <a href="{{ route('classes.create') }}" class="block px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Buat kelas baru</a>
                    </div>
                </div>

                @if ($kelasAktif)
                    {{-- Wali kelas dan guru mapel memegang kelas yang tampak sama
                         tetapi menuntut pekerjaan yang sangat berbeda; penandanya
                         harus terbaca sebelum guru mulai mengisi apa pun. --}}
                    <span class="badge {{ $kelasAktif->kelasAjar() ? 'badge--emerald' : 'badge--indigo' }}">
                        {{ $kelasAktif->kelasAjar() ? 'Guru Mapel' : 'Perwalian' }}
                    </span>
                @endif
            @endif
        </div>

        {{-- Deret pembatas. Yang aktif menyatu dengan halaman di bawahnya. --}}
        <nav class="pembatas -mb-px mt-2.5 flex gap-0.5 overflow-x-auto" aria-label="Bagian">
            @foreach ($pembatas as [$label, $tautan, $ini])
                <a href="{{ $tautan }}" @if($ini) aria-current="page" @endif
                   class="shrink-0 px-3 py-1.5 text-xs font-medium transition-colors {{ $ini ? $pembatasAktif : $pembatasDiam }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>
</div>

<main class="mx-auto w-full max-w-buku px-4 py-6 sm:px-6">
    @include('partials.masa-otomasi')
    @yield('content')
</main>

{{-- Tiga view memakai @push('scripts') — notifikasi, pengaturan notifikasi, dan
     analitik — tetapi tidak ada layout yang merender stack-nya, jadi seluruh
     JS-nya tidak pernah sampai ke peramban. Cacat yang sama persis dengan
     @stack('styles') di head. --}}
@stack('scripts')

</body>
</html>
