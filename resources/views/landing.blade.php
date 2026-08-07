@php
    // Dijaga di bawah ~65 karakter: lebih dari itu dipotong Google di hasil
    // pencarian, dan yang terpotong justru ekor yang membawa kata kuncinya.
    $judul = 'Wali Kelas Hebat — Absensi WhatsApp & Administrasi Kelas';
    $ringkas = 'Presensi WhatsApp 1 klik, form biodata mandiri siswa, refleksi karakter P5 Kurikulum Merdeka, deteksi dini EWS, dan laporan PDF siap tanda tangan. Gratis '.$bulanGratis.' bulan, tanpa kartu kredit.';
    $beranda = 'https://walas.my.id/';
    $gambar = 'https://walas.my.id/og-image.png?v=2';
    $rupiah = 'Rp '.number_format($hargaPro, 0, ',', '.');

    /*
     * JSON-LD disusun sebagai array PHP lalu di-encode, bukan ditulis tangan
     * sebagai teks JSON di dalam Blade. Dua sebabnya: Blade memperlakukan
     * "@context" sebagai awal direktif dan merusaknya, dan JSON yang ditulis
     * tangan mudah menjadi tidak valid tanpa ketahuan — Google diam saja saat
     * menolaknya, jadi kerusakannya tak pernah terlihat dari sisi kita.
     *
     * FAQ-nya memakai $faq yang sama dengan yang dirender di bawah, sehingga
     * apa yang dibaca Google selalu sama persis dengan yang dibaca pengunjung.
     */
    $skema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'SoftwareApplication',
                '@id' => $beranda.'#app',
                'name' => 'Wali Kelas Hebat',
                'url' => $beranda,
                'applicationCategory' => 'EducationalApplication',
                'operatingSystem' => 'Web',
                'inLanguage' => 'id-ID',
                'description' => $ringkas,
                'screenshot' => $gambar,
                'featureList' => [
                    'Presensi WhatsApp Magic Link dan PIN harian',
                    'Form biodata mandiri siswa tanpa login',
                    'Refleksi karakter P5 Kurikulum Merdeka',
                    'Deteksi dini EWS dan poin kedisiplinan',
                    'Buku kas kelas',
                    'Laporan PDF dan Excel siap cetak',
                ],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => (string) $hargaPro,
                    'priceCurrency' => 'IDR',
                    'category' => 'subscription',
                    'description' => 'Gratis '.$bulanGratis.' bulan, selanjutnya '.$rupiah.' per bulan untuk otomasi WhatsApp.',
                ],
            ],
            [
                '@type' => 'FAQPage',
                '@id' => $beranda.'#faq',
                'mainEntity' => array_map(fn ($t) => [
                    '@type' => 'Question',
                    'name' => $t['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $t['a']],
                ], $faq),
            ],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $judul }}</title>
    <meta name="description" content="{{ $ringkas }}">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="theme-color" content="#020617">
    <link rel="canonical" href="{{ $beranda }}">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="manifest" href="/manifest.webmanifest">

    {{--
        Tautan aplikasi ini beredar dari mulut ke mulut di grup WhatsApp guru.
        Tanpa tag di bawah, tautannya muncul di sana sebagai teks telanjang
        tanpa judul maupun gambar — dan tautan tanpa pratinjau nyaris tidak
        pernah diklik. Ini kanal penyebaran utamanya, bukan pelengkap.
    --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="Wali Kelas Hebat">
    <meta property="og:url" content="{{ $beranda }}">
    <meta property="og:title" content="{{ $judul }}">
    <meta property="og:description" content="{{ $ringkas }}">
    <meta property="og:image" content="{{ $gambar }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Wali Kelas Hebat — presensi WhatsApp, biodata mandiri, dan refleksi P5">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $judul }}">
    <meta name="twitter:description" content="{{ $ringkas }}">
    <meta name="twitter:image" content="{{ $gambar }}">

    <script type="application/ld+json">{!! json_encode($skema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .hero-glow {
            background: radial-gradient(circle at 50% 20%, rgba(99, 102, 241, 0.25) 0%, rgba(16, 185, 129, 0.1) 35%, transparent 70%);
        }
        .glass-dark {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        /* Panah FAQ berputar saat terbuka; segitiga bawaan peramban dimatikan. */
        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
        details[open] .faq-panah { transform: rotate(180deg); }
        /* Sasaran #anchor tidak boleh tersembunyi di balik navbar melayang. */
        section[id] { scroll-margin-top: 6rem; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 selection:bg-indigo-500 selection:text-white min-h-screen">

    <a href="#fitur" class="sr-only focus:not-sr-only focus:absolute focus:z-[60] focus:top-4 focus:left-4 focus:px-4 focus:py-2 focus:rounded-xl focus:bg-emerald-500 focus:text-white focus:font-bold">
        Lewati ke konten
    </a>

    <!-- NAVBAR -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-slate-950/80 backdrop-blur-xl border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3 group">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-500 to-indigo-600 shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-all">
                    <span class="text-xl" aria-hidden="true">🎓</span>
                </div>
                <div>
                    <span class="text-lg font-black tracking-tight text-white block leading-none">WALI KELAS <span class="text-emerald-400">HEBAT</span></span>
                    <span class="text-[10px] font-semibold tracking-wider uppercase text-slate-400">walas.my.id</span>
                </div>
            </a>

            {{--
                Setiap butir di sini WAJIB punya <section id="..."> pasangannya.
                Menu lama menjanjikan empat halaman yang tidak pernah dibuat,
                jadi empat dari lima tautannya mati: diklik, halaman diam saja.
                Pengunjung tidak menyimpulkan "menunya salah", ia menyimpulkan
                "situsnya rusak" — lalu pergi. LandingTest menjaga agar setiap
                tautan di sini benar-benar punya tujuan.
            --}}
            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-300" aria-label="Navigasi utama">
                <a href="#fitur" class="hover:text-emerald-400 transition-colors">Fitur</a>
                <a href="#wa" class="hover:text-emerald-400 transition-colors">Integrasi WhatsApp</a>
                <a href="#p5" class="hover:text-emerald-400 transition-colors">Refleksi P5</a>
                <a href="#harga" class="hover:text-emerald-400 transition-colors">Harga</a>
                <a href="#faq" class="hover:text-emerald-400 transition-colors">FAQ</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="h-11 px-5 hidden sm:inline-flex items-center text-sm font-bold text-slate-300 hover:text-white transition-colors">
                    Masuk
                </a>
                <a href="{{ route('register') }}" class="h-11 px-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 hover:opacity-95 text-white font-bold text-sm transition-all shadow-lg shadow-indigo-500/25">
                    Coba Gratis
                </a>
            </div>
        </div>
    </header>

    <!-- HERO -->
    <section class="relative pt-36 pb-24 overflow-hidden hero-glow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">

            {{--
                Lencana ini dulu berbunyi "Aplikasi ... No. 1 di Indonesia".
                Klaim itu tidak bisa dibuktikan, dan pembaca yang skeptis justru
                jadi kurang percaya pada sisa halaman. Diganti janji yang
                memang ditepati kodenya: masa gratis penuh tanpa kartu kredit.
            --}}
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full glass-dark text-xs font-bold text-emerald-400 mb-8 border border-emerald-500/30 shadow-inner">
                <span class="flex h-2 w-2 rounded-full bg-emerald-400 animate-ping" aria-hidden="true"></span>
                <span>Gratis {{ $bulanGratis }} bulan penuh — tanpa kartu kredit</span>
            </div>

            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black tracking-tight text-white leading-[1.15] max-w-5xl mx-auto">
                Administrasi wali kelas selesai <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 bg-clip-text text-transparent">sebelum bel istirahat</span>.
            </h1>

            {{--
                Paragraf ini pernah memuat **Wali Kelas Hebat** dengan bintang
                Markdown. Blade bukan Markdown: bintangnya tercetak apa adanya
                di layar, dan itu terpampang di kalimat pembuka halaman muka.
            --}}
            <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-3xl mx-auto font-normal leading-relaxed">
                Tinggalkan rekap manual yang melelahkan. <strong class="font-bold text-slate-200">Wali Kelas Hebat</strong>
                menghadirkan presensi 1 klik lewat WhatsApp, form biodata mandiri siswa, deteksi dini EWS,
                dan refleksi P5 Kurikulum Merdeka — semuanya dari satu dasbor.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}" class="w-full sm:w-auto h-14 px-8 inline-flex items-center justify-center gap-3 rounded-2xl bg-gradient-to-r from-emerald-500 via-teal-500 to-indigo-600 hover:scale-105 text-white font-extrabold text-base transition-all shadow-xl shadow-emerald-500/20">
                    <span>Mulai Buat Kelas Gratis</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                <a href="#wa" class="w-full sm:w-auto h-14 px-8 inline-flex items-center justify-center gap-2 rounded-2xl glass-dark hover:bg-white/10 text-slate-300 font-bold text-base transition-all">
                    <span>Lihat cara kerjanya</span>
                </a>
            </div>

            <p class="mt-6 text-sm text-slate-500">
                Untuk wali kelas SD, SMP, SMA, dan SMK. Siswa tidak perlu memasang aplikasi apa pun.
            </p>

            <!-- PRATINJAU DASBOR -->
            <div class="mt-16 relative max-w-5xl mx-auto rounded-3xl p-3 bg-gradient-to-b from-slate-800/80 to-slate-900/40 border border-slate-700/60 shadow-2xl shadow-emerald-500/10">
                <div class="rounded-2xl overflow-hidden bg-slate-900 border border-slate-800 text-left p-6 sm:p-8 space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4 gap-3">
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full bg-rose-500"></span>
                            <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                            <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-mono text-slate-500 ml-2 hidden sm:inline">walas.my.id/dashboard</span>
                        </div>
                        <span class="text-xs font-bold text-emerald-400 bg-emerald-950/80 border border-emerald-800/50 px-3 py-1 rounded-full whitespace-nowrap">WA Gateway Online</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/50">
                            <span class="text-xs font-semibold text-slate-400">Presensi Hari Ini</span>
                            <p class="text-2xl font-black text-white mt-1">98,5%</p>
                        </div>
                        <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/50">
                            <span class="text-xs font-semibold text-slate-400">Siswa Binaan</span>
                            <p class="text-2xl font-black text-emerald-400 mt-1">36</p>
                        </div>
                        <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/50">
                            <span class="text-xs font-semibold text-slate-400">Refleksi Karakter</span>
                            <p class="text-2xl font-black text-indigo-400 mt-1">12 Jurnal</p>
                        </div>
                        <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/50">
                            <span class="text-xs font-semibold text-slate-400">Laporan</span>
                            <p class="text-2xl font-black text-amber-400 mt-1">PDF / Excel</p>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-600">Ilustrasi tampilan dasbor. Angka di atas contoh, bukan data pengguna sungguhan.</p>
                </div>
            </div>

        </div>
    </section>

    <!-- FITUR -->
    <section id="fitur" class="py-24 bg-slate-900/60 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold tracking-widest text-emerald-400 uppercase">Solusi administrasi kelas</span>
                <h2 class="text-3xl sm:text-5xl font-black text-white mt-3">Enam pekerjaan yang tidak lagi manual</h2>
                <p class="text-slate-400 mt-4 text-base">Dirancang untuk memangkas waktu administrasi wali kelas dari berjam-jam menjadi hitungan menit.</p>
            </div>

            {{--
                Warnanya ditulis sebagai kelas UTUH, bukan dirakit dari potongan
                seperti "bg-{$warna}-500/10". Tailwind memindai berkas ini
                sebagai teks biasa dan tidak menjalankan PHP-nya, jadi kelas
                hasil rakitan tidak pernah ia temukan — kelasnya ikut terbuang
                saat build dan kartunya tampil tanpa warna sama sekali, padahal
                kodenya terlihat benar.
            --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ([
                    ['💬', 'hover:border-emerald-500/50', 'bg-emerald-500/10 border-emerald-500/20', 'Presensi WhatsApp Magic Link & PIN', 'Terbitkan tautan absensi sekali pakai dan PIN harian 6 digit yang dikirim otomatis ke Seksi Absensi atau grup kelas Anda.'],
                    ['📝', 'hover:border-indigo-500/50', 'bg-indigo-500/10 border-indigo-500/20', 'Form Biodata Mandiri Siswa', 'Siswa mengisi data pribadi, orang tua, pekerjaan, KIP/PKH, hobi, dan cita-cita sendiri lewat tautan publik beralamat acak — tanpa login.'],
                    ['🛡️', 'hover:border-amber-500/50', 'bg-amber-500/10 border-amber-500/20', 'Deteksi Dini EWS & Poin Disiplin', 'Sistem peringatan dini menandai siswa yang mulai berisiko — alfa berulang atau poin disiplin menipis — selagi masih bisa ditangani.'],
                    ['🎨', 'hover:border-cyan-500/50', 'bg-cyan-500/10 border-cyan-500/20', 'Refleksi Karakter P5 Merdeka', 'Jurnal perkembangan enam dimensi Profil Pelajar Pancasila, lengkap dengan analisis persentase dan lencana penghargaan siswa.'],
                    ['💰', 'hover:border-purple-500/50', 'bg-purple-500/10 border-purple-500/20', 'Buku Kas & Keuangan Kelas', 'Catat pemasukan kas mingguan berikut nama penyetornya, catat pengeluaran, dan cetak rincian saldo yang bisa dipertanggungjawabkan.'],
                    ['📊', 'hover:border-emerald-500/50', 'bg-emerald-500/10 border-emerald-500/20', 'Cetak Laporan PDF 1 Klik', 'Rekap bulanan, leger absensi, portofolio siswa, dan laporan wali kelas siap dicetak dan ditandatangani kepala sekolah.'],
                ] as [$ikon, $garis, $kotak, $nama, $isi])
                    <div class="glass-dark p-8 rounded-3xl border border-slate-800 {{ $garis }} transition-all hover:-translate-y-1 space-y-4 group">
                        <div class="h-14 w-14 rounded-2xl border {{ $kotak }} flex items-center justify-center text-3xl group-hover:scale-110 transition-transform" aria-hidden="true">
                            {{ $ikon }}
                        </div>
                        <h3 class="text-xl font-bold text-white">{{ $nama }}</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">{{ $isi }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- INTEGRASI WHATSAPP -->
    <section id="wa" class="py-24 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <span class="text-xs font-bold tracking-widest text-emerald-400 uppercase">Integrasi WhatsApp</span>
                    <h2 class="text-3xl sm:text-5xl font-black text-white mt-3 leading-tight">Absensi masuk tanpa Anda mengetik apa pun</h2>
                    <p class="text-slate-400 mt-5 leading-relaxed">
                        Rekap kehadiran biasanya berhenti di satu orang: wali kelas yang harus menyalin ulang laporan dari grup.
                        Di sini pekerjaan itu berpindah ke tautan sekali pakai yang dikirim sendiri oleh sistem setiap pagi.
                    </p>

                    <ol class="mt-8 space-y-6">
                        @foreach ([
                            ['Sistem menerbitkan tautan harian', 'Setiap pagi, satu tautan absensi sekali pakai dan PIN 6 digit dibuat otomatis untuk tiap kelas.'],
                            ['Dikirim ke grup atau Seksi Absensi', 'Pesan berangkat sendiri lewat gateway WhatsApp Anda pada jam yang Anda tentukan.'],
                            ['Petugas mencentang di satu layar', 'Cukup dibuka di peramban HP. Tidak perlu memasang aplikasi dan tidak perlu membuat akun.'],
                            ['Rekap langsung masuk dasbor', 'Begitu dikirim, tautannya tertutup dan hasilnya tampil di dasbor — siap dicetak kapan pun.'],
                        ] as $i => [$langkah, $rinci])
                            <li class="flex gap-5">
                                <span class="flex-none h-10 w-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center font-black text-emerald-400">{{ $i + 1 }}</span>
                                <div>
                                    <h3 class="font-bold text-white">{{ $langkah }}</h3>
                                    <p class="text-slate-400 text-sm mt-1 leading-relaxed">{{ $rinci }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <!-- Ilustrasi percakapan WhatsApp -->
                <div class="glass-dark rounded-3xl border border-slate-800 p-6 sm:p-8 space-y-4">
                    <div class="flex items-center gap-3 border-b border-slate-800 pb-4">
                        <div class="h-10 w-10 rounded-full bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center" aria-hidden="true">👥</div>
                        <div>
                            <p class="font-bold text-white text-sm">Grup Kelas 5A</p>
                            <p class="text-[11px] text-slate-500">36 anggota</p>
                        </div>
                    </div>

                    <div class="bg-emerald-950/50 border border-emerald-800/40 rounded-2xl rounded-tl-sm p-4 text-sm text-slate-200 leading-relaxed">
                        <p class="font-bold text-emerald-400 mb-1.5">Wali Kelas Hebat</p>
                        <p>Assalamualaikum. Berikut tautan presensi Kelas 5A untuk hari ini.</p>
                        <p class="mt-2 font-mono text-xs text-emerald-300 break-all">walas.my.id/a/8f3c1d…</p>
                        <p class="mt-2">PIN hari ini: <strong class="font-black tracking-widest">482619</strong></p>
                        <p class="mt-2 text-slate-400 text-xs">Tautan tertutup otomatis setelah presensi dikirim.</p>
                    </div>

                    <div class="bg-slate-800/60 border border-slate-700/50 rounded-2xl rounded-tr-sm p-4 text-sm text-slate-300 ml-8">
                        <p class="font-bold text-indigo-300 mb-1.5">Seksi Absensi</p>
                        <p>Sudah dikirim, Bu. Hadir 34, izin 1, sakit 1. 🙏</p>
                    </div>

                    <p class="text-[11px] text-slate-600 pt-2">Ilustrasi. Nomor dan PIN di atas bukan data sungguhan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- REFLEKSI P5 -->
    <section id="p5" class="py-24 bg-slate-900/60 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold tracking-widest text-cyan-400 uppercase">Kurikulum Merdeka</span>
                <h2 class="text-3xl sm:text-5xl font-black text-white mt-3">Refleksi karakter P5 yang benar-benar terisi</h2>
                <p class="text-slate-400 mt-4 leading-relaxed">
                    Penilaian karakter sering berhenti sebagai kolom kosong yang diisi buru-buru menjelang rapor.
                    Di sini siswa menulis refleksinya sendiri sepanjang semester, dan wali kelas tinggal membaca perkembangannya.
                </p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-12">
                @foreach ([
                    ['🕌', 'Beriman & Bertakwa'],
                    ['🌏', 'Berkebinekaan Global'],
                    ['🤝', 'Bergotong Royong'],
                    ['🧭', 'Mandiri'],
                    ['💡', 'Bernalar Kritis'],
                    ['🎨', 'Kreatif'],
                ] as [$ikon, $dimensi])
                    <div class="glass-dark rounded-2xl border border-slate-800 p-5 text-center hover:border-cyan-500/40 transition-colors">
                        <div class="text-3xl mb-2" aria-hidden="true">{{ $ikon }}</div>
                        <h3 class="text-xs font-bold text-slate-200 leading-snug">{{ $dimensi }}</h3>
                    </div>
                @endforeach
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                @foreach ([
                    ['Siswa mengisi sendiri', 'Lewat portal siswa, anak menuliskan apa yang sudah baik, apa yang perlu diperbaiki, dan rencana tindak lanjutnya.'],
                    ['Wali kelas memberi umpan balik', 'Setiap refleksi bisa dibalas. Catatan itu tersimpan sebagai jejak pembinaan sepanjang semester.'],
                    ['Portofolio siap dicetak', 'Perkembangan per dimensi terangkum dalam grafik dan portofolio PDF — bahan siap pakai saat pembagian rapor.'],
                ] as [$judulKartu, $isiKartu])
                    <div class="glass-dark p-8 rounded-3xl border border-slate-800 space-y-3">
                        <h3 class="text-lg font-bold text-white">{{ $judulKartu }}</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">{{ $isiKartu }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- HARGA -->
    <section id="harga" class="py-24 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold tracking-widest text-amber-400 uppercase">Harga</span>
                <h2 class="text-3xl sm:text-5xl font-black text-white mt-3">Gratis {{ $bulanGratis }} bulan, lalu {{ $rupiah }} sebulan</h2>
                <p class="text-slate-400 mt-4 leading-relaxed">
                    Tidak ada kartu kredit, tidak ada penagihan otomatis, dan tidak ada kejutan di akhir masa gratis.
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">

                <div class="glass-dark p-8 rounded-3xl border border-slate-800 flex flex-col">
                    <h3 class="text-sm font-bold tracking-widest uppercase text-slate-400">Masa Gratis</h3>
                    <p class="mt-4"><span class="text-5xl font-black text-white">Rp 0</span></p>
                    <p class="text-sm text-slate-400 mt-2">{{ $bulanGratis }} bulan penuh sejak akun dibuat.</p>
                    <ul class="mt-6 space-y-3 text-sm text-slate-300 flex-1">
                        @foreach ([
                            'Seluruh fitur terbuka tanpa batas kelas',
                            'Presensi WhatsApp otomatis aktif',
                            'Biodata mandiri, EWS, buku kas, refleksi P5',
                            'Cetak laporan PDF dan Excel',
                            'Tanpa kartu kredit',
                        ] as $butir)
                            <li class="flex gap-3">
                                <span class="text-emerald-400 font-black" aria-hidden="true">✓</span>
                                <span>{{ $butir }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="mt-8 h-12 inline-flex items-center justify-center rounded-xl border border-slate-700 hover:border-emerald-500/60 hover:text-white text-slate-300 font-bold text-sm transition-colors">
                        Daftar Gratis
                    </a>
                </div>

                <div class="relative p-8 rounded-3xl border border-emerald-500/40 bg-gradient-to-b from-emerald-950/40 to-slate-900/60 flex flex-col shadow-xl shadow-emerald-500/10">
                    <span class="absolute -top-3 left-8 px-3 py-1 rounded-full bg-emerald-500 text-slate-950 text-[11px] font-black tracking-wide uppercase">Setelah masa gratis</span>
                    <h3 class="text-sm font-bold tracking-widest uppercase text-emerald-400">PRO</h3>
                    <p class="mt-4">
                        <span class="text-5xl font-black text-white">{{ $rupiah }}</span>
                        <span class="text-slate-400 font-semibold"> / bulan</span>
                    </p>
                    <p class="text-sm text-slate-400 mt-2">Transfer DANA, diverifikasi manual oleh operator.</p>
                    <ul class="mt-6 space-y-3 text-sm text-slate-300 flex-1">
                        @foreach ([
                            'Membuka kembali otomasi WhatsApp',
                            'Pengiriman tautan presensi terjadwal',
                            'Balasan otomatis untuk orang tua',
                            'Perpanjang kapan saja, berhenti kapan saja',
                        ] as $butir)
                            <li class="flex gap-3">
                                <span class="text-emerald-400 font-black" aria-hidden="true">✓</span>
                                <span>{{ $butir }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="mt-8 h-12 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold text-sm transition-all shadow-lg shadow-emerald-500/25">
                        Mulai dari Masa Gratis
                    </a>
                </div>
            </div>

            {{--
                Ini pembeda yang paling menenangkan calon pengguna, jadi ia
                berdiri sendiri alih-alih terselip sebagai butir kecil: data
                absensi adalah dokumen wajib sekolah, dan menyanderanya untuk
                menagih pembayaran akan merugikan pihak yang paling tidak
                bersalah — wali kelas yang belum sempat memperpanjang.
            --}}
            <div class="mt-12 max-w-3xl mx-auto glass-dark rounded-3xl border border-slate-800 p-8 text-center">
                <h3 class="text-lg font-bold text-white">Masa gratis habis tidak berarti aplikasi terkunci</h3>
                <p class="text-slate-400 text-sm mt-3 leading-relaxed">
                    Absensi, biodata siswa, buku kas, dan seluruh laporan tetap bisa dibuka, diubah, dan dicetak selamanya.
                    Yang berhenti hanya pengiriman WhatsApp otomatis. Data sekolah tidak kami jadikan alat tagih.
                </p>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="py-24 bg-slate-900/60 border-t border-slate-800">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="text-xs font-bold tracking-widest text-indigo-400 uppercase">Tanya Jawab</span>
                <h2 class="text-3xl sm:text-5xl font-black text-white mt-3">Pertanyaan yang sering masuk</h2>
            </div>

            <div class="space-y-4">
                @foreach ($faq as $tanya)
                    <details class="group glass-dark rounded-2xl border border-slate-800 overflow-hidden">
                        <summary class="flex items-center justify-between gap-4 cursor-pointer p-6 font-bold text-white hover:text-emerald-400 transition-colors">
                            <h3 class="text-base">{{ $tanya['q'] }}</h3>
                            <svg class="faq-panah flex-none w-5 h-5 text-slate-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <p class="px-6 pb-6 -mt-1 text-slate-400 text-sm leading-relaxed">{{ $tanya['a'] }}</p>
                    </details>
                @endforeach
            </div>

            <div class="mt-14 text-center">
                <h2 class="text-2xl sm:text-3xl font-black text-white">Siap memangkas pekerjaan administrasi Anda?</h2>
                <p class="text-slate-400 mt-3">Buat kelas pertama Anda hari ini. Gratis {{ $bulanGratis }} bulan penuh.</p>
                <a href="{{ route('register') }}" class="mt-7 h-14 px-8 inline-flex items-center justify-center gap-3 rounded-2xl bg-gradient-to-r from-emerald-500 via-teal-500 to-indigo-600 hover:scale-105 text-white font-extrabold text-base transition-all shadow-xl shadow-emerald-500/20">
                    <span>Mulai Buat Kelas Gratis</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="py-12 bg-slate-950 border-t border-slate-900 text-slate-500 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
            <p>&copy; {{ date('Y') }} <strong class="text-slate-300">Wali Kelas Hebat</strong> (walas.my.id). Hak cipta dilindungi.</p>
            <nav class="flex flex-wrap justify-center gap-6 font-medium text-slate-400" aria-label="Navigasi footer">
                <a href="#fitur" class="hover:text-emerald-400">Fitur</a>
                <a href="#harga" class="hover:text-emerald-400">Harga</a>
                <a href="#faq" class="hover:text-emerald-400">FAQ</a>
                <a href="{{ route('login') }}" class="hover:text-emerald-400">Masuk</a>
                <a href="{{ route('register') }}" class="hover:text-emerald-400">Daftar</a>
            </nav>
        </div>
    </footer>

</body>
</html>
