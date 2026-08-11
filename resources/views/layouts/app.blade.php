<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Wali Kelas Hebat') }}</title>

    <!-- PWA Settings -->
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#4f46e5">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">

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
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-heading { font-family: 'Outfit', sans-serif; }
        
        .nav-category {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94a3b8;
            padding: 12px 12px 4px 12px;
        }

        /* Segitiga bawaan Safari tidak ikut mati oleh list-style-type: none. */
        summary.nav-category::-webkit-details-marker { display: none; }

        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 40;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-top: 1px solid #e2e8f0;
        }
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
<body class="h-full antialiased text-slate-800 selection:bg-indigo-500 selection:text-white" x-data="{ sidebarOpen: false }">

<div class="min-h-screen bg-slate-50 lg:flex">
    
    <!-- MOBILE SIDEBAR OVERLAY -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" 
         class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs lg:hidden transition-opacity"></div>

    <!-- SIDEBAR NAV -->
    <aside id="main-sidebar" 
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 transition-transform duration-300 ease-in-out lg:static lg:z-auto flex flex-col justify-between shadow-2xl">
        
        <div class="overflow-y-auto flex-1 p-4 space-y-4">
            <!-- App Brand Logo -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-2 py-2 group border-b border-slate-800 pb-4">
                <div class="h-10 w-10 rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-purple-600 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-indigo-500/30 group-hover:scale-105 transition-transform">
                    📚
                </div>
                <div>
                    <span class="font-heading font-extrabold text-base tracking-tight text-white block leading-none">
                        Wali Kelas <span class="text-indigo-400">Hebat</span>
                    </span>
                    <span class="text-[9px] font-bold tracking-widest text-slate-400 uppercase block mt-1">Manajemen Kelas Modern</span>
                </div>
            </a>

            @php
                /*
                 * Kelas yang sedang dibuka — dipakai seluruh menu di bawah.
                 *
                 * Diperiksa dengan instanceof, bukan isset(). Blade meneruskan
                 * SELURUH variabel yang terdefinisi di view anak ke layout
                 * (get_defined_vars()), termasuk sisa variabel perulangan. Satu
                 * @foreach($daftar as $class) di halaman mana pun sudah cukup
                 * membuat menu ini menunjuk ke objek yang salah — dan bila
                 * isinya bukan Classroom, seluruh halaman berhenti dengan galat
                 * 500 dari route() atau dari ->kelasAjar(). Persis itu yang
                 * terjadi pada dashboard admin begitu tabel ringkasan kelasnya
                 * berisi data.
                 */
                $kelasAktif = collect([$classroom ?? null, $class ?? null])
                    ->first(fn ($k) => $k instanceof \App\Models\Classroom);
            @endphp

            {{--
                Sidebar ini dulu merangkap DUA pekerjaan dalam satu daftar datar:
                navigasi aplikasi dan navigasi satu kelas. Sembilan belas tautan
                di bawah enam judul kategori — salah satunya berisi tepat satu
                item — membuat "Absensi", yang dibuka tiap hari, tampak sederajat
                dengan "Denah Tempat Duduk", yang diisi sekali lalu ditinggal.

                Sekarang tiga kelompok saja, disusun menurut seberapa sering
                halamannya benar-benar dibuka (dihitung dari log akses, bukan
                dikira-kira): yang harian terlihat, yang diatur sekali per
                semester dilipat, yang milik akun berdiri sendiri di bawah.
            --}}

            <!-- KELOMPOK 1: KELAS YANG SEDANG DIBUKA -->
            <div class="space-y-1">
                <div class="nav-category">{{ $kelasAktif ? 'Kelas '.$kelasAktif->name : 'Beranda' }}</div>

                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <span>🎛️</span><span>Dashboard Kelas</span>
                </a>

                @if($kelasAktif)
                    @php $c = $kelasAktif; @endphp
                    <a href="{{ route('classes.students.index', $c) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('classes.students.*') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                        <span>👥</span><span>Data Siswa</span>
                    </a>
                    <a href="{{ route('classes.attendance.index', $c) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('classes.attendance.*') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                        <span>📋</span><span>Absensi Presensi</span>
                    </a>
                    <a href="{{ route('classes.character-portfolio.index', $c) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('classes.character-portfolio.*') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                        <span>🌟</span><span>Portofolio Karakter P5</span>
                    </a>
                    {{-- Buku kas dan laporan administrasi adalah dokumen wali
                         kelas; pada kelas ajar wali kelasnya orang lain. --}}
                    @if (! $c->kelasAjar())
                        <a href="{{ route('classes.cashbook.index', $c) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('classes.cashbook.*') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                            <span>👛</span><span>Buku Kas Kelas</span>
                        </a>
                        <a href="{{ route('classes.reports.full', $c) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('classes.reports.*') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                            <span>🖨️</span><span>Laporan PDF 7 Bab</span>
                        </a>
                    @endif
                @else
                    <a href="{{ route('classes.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold text-slate-300 hover:bg-slate-800">
                        <span>🏫</span><span>Pilih / Kelola Kelas</span>
                    </a>
                @endif
            </div>

            {{--
                KELOMPOK 2: yang diisi sekali per semester lalu ditinggal.

                <details> bawaan peramban, bukan Alpine: tidak ada state yang
                harus dijaga, tidak ada yang bisa rusak saat JavaScript gagal
                dimuat, dan tautannya tetap ada di DOM sehingga pencarian teks
                peramban (Ctrl+F) masih menemukannya walau lipatan tertutup.

                Dibuka sendiri saat halamannya sedang aktif — kalau tidak,
                pengguna yang sedang berada di Denah Tempat Duduk melihat
                lipatan tertutup dan tidak menemukan penanda di mana ia berada.
            --}}
            {{--
                Seluruh isi lipatan ini milik wali kelas saja. Pada kelas ajar
                keempatnya tersembunyi — dan lipatan yang isinya kosong lebih
                buruk daripada tidak ada lipatan: ia mengaku menyimpan sesuatu,
                lalu tidak menyimpan apa-apa. Jadi syaratnya dipasang di luar.
            --}}
            @if($kelasAktif && ! $kelasAktif->kelasAjar())
                @php
                    $c = $kelasAktif;
                    $diPengaturan = request()->routeIs('classes.schedules.*', 'classes.violations.*', 'classes.seating.*', 'classes.organization.*');
                @endphp
                <details class="group" @if($diPengaturan) open @endif>
                    <summary class="nav-category flex items-center justify-between cursor-pointer list-none hover:text-slate-300">
                        <span>Pengaturan Kelas</span>
                        <span class="transition-transform group-open:rotate-180" aria-hidden="true">⌄</span>
                    </summary>
                    <div class="space-y-1 mt-1">
                        {{-- Jadwal disusun per rombongan belajar oleh wali kelas, bukan
                             per mapel. Guru mapel yang menyuntingnya menimpa jadwal
                             seluruh kelas — termasuk jam mengajar guru lain. --}}
                        <a href="{{ route('classes.schedules.index', $c) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('classes.schedules.*') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                            <span>📅</span><span>Jadwal Pelajaran</span>
                        </a>
                        {{-- Buku poin dipegang SATU orang: wali kelasnya. Bila tiap guru
                             mapel ikut mencatat, satu kejadian tercatat berkali-kali dan
                             sanksi siswa dihitung dari angka yang salah. --}}
                        <a href="{{ route('classes.violations.index', $c) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('classes.violations.*') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                            <span>📖</span><span>Pelanggaran &amp; Poin</span>
                        </a>
                        <a href="{{ route('classes.seating.index', $c) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('classes.seating.*') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                            <span>📐</span><span>Denah Tempat Duduk</span>
                        </a>
                        <a href="{{ route('classes.organization.index', $c) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('classes.organization.*') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                            <span>🏢</span><span>Struktur Organisasi</span>
                        </a>
                    </div>
                </details>
            @endif

            <!-- KELOMPOK 3: MILIK AKUN, BUKAN MILIK KELAS -->
            <div class="space-y-1">
                <div class="nav-category">Akun &amp; Sistem</div>
                {{--
                    Kalender sekolah dulu bersarang di dalam blok kelas aktif,
                    padahal rutenya global: wali kelas yang belum memilih kelas
                    tidak punya jalan ke sana sama sekali. Tempatnya di sini.
                --}}
                <a href="{{ route('holidays.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('holidays.*') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>🗓️</span><span>Kalender Sekolah</span>
                </a>
                <a href="{{ route('whatsapp.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('whatsapp.*') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>📲</span><span>Integrasi WhatsApp</span>
                </a>
                <a href="{{ route('subscription.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('subscription.*') ? 'bg-amber-500 text-slate-900 shadow-md font-extrabold' : 'text-amber-400 hover:bg-slate-800' }}">
                    <span>👑</span><span>Langganan PRO</span>
                </a>
                {{--
                    Halaman analitik sudah lengkap dan berfungsi, tetapi selama
                    ini tidak punya SATU PUN tautan dari mana pun — rutenya ada,
                    controllernya ada, viewnya 363 baris, dan tidak ada yang
                    bisa mencapainya kecuali mengetik URL-nya sendiri.
                --}}
                <a href="{{ route('analytics.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('analytics.*') ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-300 hover:bg-slate-800' }}">
                    <span>📊</span><span>Analitik</span>
                </a>
                {{--
                    isAdmin() membaca kolom `role` — penanda yang SAMA dipakai
                    middleware role:admin dan ClassroomPolicy.

                    Sebelumnya di sini dipakai kolom `is_admin`, penanda kedua
                    yang tidak pernah diisi siapa pun. Akibatnya akun ber-role
                    admin masuk lalu tidak melihat satu pun menu admin, dan
                    tidak ada galat apa pun yang menjelaskan kenapa.
                --}}
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-purple-600 text-white shadow-md' : 'text-purple-400 hover:bg-slate-800' }}">
                        <span>🛰️</span><span>Panel Operator</span>
                    </a>
                    <a href="{{ route('admin.teachers.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('admin.teachers.*') ? 'bg-purple-600 text-white shadow-md' : 'text-purple-400 hover:bg-slate-800' }}">
                        <span>📇</span><span>Daftar Guru</span>
                    </a>
                    <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-bold transition-all {{ request()->routeIs('admin.subscriptions.*') ? 'bg-purple-600 text-white shadow-md' : 'text-purple-400 hover:bg-slate-800' }}">
                        <span>💳</span><span>Persetujuan PRO</span>
                    </a>
                @endif
            </div>

        </div>

        <!-- User Profile Bottom Drawer -->
        <div class="p-4 border-t border-slate-800 flex items-center justify-between text-xs">
            {{--
                Kartu ini sudah menampilkan nama dan email, jadi ke sinilah
                orang mencari pengaturan akunnya — tetapi selama ini tidak bisa
                diklik, dan halaman Profil tidak punya tautan dari mana pun.
            --}}
            <a href="{{ route('profile.edit') }}" title="Ubah profil & kata sandi"
               class="flex items-center gap-3 min-w-0 rounded-xl px-1 py-1 -mx-1 transition-colors hover:bg-slate-800 {{ request()->routeIs('profile.*') ? 'bg-slate-800' : '' }}">
                <div class="h-9 w-9 rounded-xl bg-indigo-600 text-white font-bold flex items-center justify-center shrink-0">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <span class="font-bold text-white block truncate">{{ auth()->user()->name }}</span>
                    <span class="text-[10px] text-slate-400 block truncate">{{ auth()->user()->email }}</span>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Keluar Akun" class="text-slate-400 hover:text-rose-400 p-1 font-bold">
                    ➔
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- Header Topbar -->
        <header class="bg-white border-b border-slate-200/80 px-4 py-3 sticky top-0 z-30 flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-3">
                <button type="button" @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 lg:hidden">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-base font-extrabold text-slate-900 truncate">@yield('title', 'Dashboard')</h1>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('subscription.index') }}" class="hidden sm:inline-flex items-center gap-1.5 rounded-xl bg-amber-50 border border-amber-200 px-3 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-100 transition-colors">
                    👑 Upgrade PRO
                </a>
            </div>
        </header>

        <!-- Main Content View -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto pb-24 lg:pb-8">
            @include('partials.masa-otomasi')
            @yield('content')
        </main>
    </div>

</div>

<!-- MOBILE BOTTOM NAVIGATION BAR (HP DISPLAY ONLY) -->
<nav class="mobile-bottom-nav lg:hidden">
    <div class="grid grid-cols-5 gap-1 text-center py-2 text-[10px] font-bold text-slate-500">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('dashboard') ? 'text-indigo-600 font-extrabold' : '' }}">
            <span class="text-base">🎛️</span>
            <span>Beranda</span>
        </a>
        @if($kelasAktif)
            @php $c = $kelasAktif; @endphp
            <a href="{{ route('classes.students.index', $c) }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('classes.students.*') ? 'text-indigo-600 font-extrabold' : '' }}">
                <span class="text-base">👥</span>
                <span>Siswa</span>
            </a>
            <a href="{{ route('classes.attendance.index', $c) }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('classes.attendance.*') ? 'text-indigo-600 font-extrabold' : '' }}">
                <span class="text-base">📋</span>
                <span>Absensi</span>
            </a>
            <a href="{{ route('classes.character-portfolio.index', $c) }}" class="flex flex-col items-center gap-0.5 {{ request()->routeIs('classes.character-portfolio.*') ? 'text-indigo-600 font-extrabold' : '' }}">
                <span class="text-base">🌟</span>
                <span>Karakter P5</span>
            </a>
        @else
            <a href="{{ route('classes.index') }}" class="flex flex-col items-center gap-0.5 text-slate-500">
                <span class="text-base">🏫</span>
                <span>Kelas</span>
            </a>
        @endif
        <button type="button" @click="sidebarOpen = true" class="flex flex-col items-center gap-0.5 text-slate-500">
            <span class="text-base">•••</span>
            <span>Lainnya</span>
        </button>
    </div>
</nav>

</body>
</html>
