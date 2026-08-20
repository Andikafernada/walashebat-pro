@php
    $judul = 'Wali Kelas Hebat — Presensi WhatsApp & Administrasi Kelas Smart AI';
    $ringkas = 'Presensi WhatsApp 1-klik, Form Biodata Mandiri 33 Field, Refleksi Karakter P5 Kurikulum Merdeka, EWS Siswa Berisiko, dan Cetak Laporan PDF Resmi 1-Klik. Gratis '.$bulanGratis.' bulan, tanpa kartu kredit.';
    $beranda = 'https://walas.my.id/';
    $gambar = 'https://walas.my.id/images/3d_teacher_story.jpg';
    $rupiah = 'Rp '.number_format($hargaPro, 0, ',', '.');

    $skema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'SoftwareApplication',
                '@id' => $beranda.'#app',
                'name' => 'Wali Kelas Hebat',
                'operatingSystem' => 'All',
                'applicationCategory' => 'EducationalApplication',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => (string) $hargaPro,
                    'priceCurrency' => 'IDR',
                ],
                'description' => $ringkas,
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

    $fitur_kreatif = [
        [
            'id' => 'wa',
            'judul' => 'Presensi WA 1-Klik',
            'sub' => 'Magic Link + PIN Harian',
            'icon' => '📲',
            'badge' => 'Otomatisasi WhatsApp',
            'ringkasan' => 'Wali Kelas atau Seksi Absensi membuka link presensi harian dengan PIN 6-digit. Rekap kehadiran otomatis diproses dan langsung bisa dikirimkan ke grup WhatsApp orang tua.',
            'poin' => [
                'Tanpa perlu instalasi aplikasi di HP siswa atau orang tua',
                'Magic link aman dilengkapi PIN harian yang diperbarui otomatis',
                'Format rekap WA rapi (Hadir, Sakit, Izin, Alfa) tanpa copy-paste manual'
            ],
            'bg_accent' => 'from-emerald-50 to-teal-50 border-emerald-200 text-emerald-800'
        ],
        [
            'id' => 'biodata',
            'judul' => 'Biodata Mandiri 33 Field',
            'sub' => 'Disisi Mandiri oleh Ortu/Siswa',
            'icon' => '📋',
            'badge' => 'Bebas Ketik Manual',
            'ringkasan' => 'Cukup bagikan 1 tautan kelas. Orang tua atau siswa melengkapi 33 bidang data lengkap (Identitas, Alamat, HP Ortu, Pekerjaan, KIP/PKH) dari HP masing-masing.',
            'poin' => [
                'Wali kelas bebas dari tugas pengetikan 30+ siswa satu per satu',
                'Terkompilasi otomatis menjadi tabel profil kelas yang rapi & valid',
                'Dapat diunduh kapan saja ke format Excel (.xlsx)'
            ],
            'bg_accent' => 'from-indigo-50 to-blue-50 border-indigo-200 text-indigo-800'
        ],
        [
            'id' => 'p5',
            'judul' => 'Refleksi Karakter P5',
            'sub' => '6 Dimensi Profil Pelajar Pancasila',
            'icon' => '🌟',
            'badge' => 'Kurikulum Merdeka',
            'ringkasan' => 'Siswa melakukan penilaian refleksi diri harian/mingguan berbasis 6 Dimensi Pancasila dari HP. Guru tinggal meninjau, memberi umpan balik, dan menyetujui.',
            'poin' => [
                'Mencakup Beriman, Berkebinekaan, Gotong Royong, Mandiri, Bernalar Kritis, & Kreatif',
                'Tersedia grafik statistik perkembangan karakter siswa per semester',
                'Siap dicetak sebagai Lampiran Portofolio Karakter P5'
            ],
            'bg_accent' => 'from-amber-50 to-orange-50 border-amber-200 text-amber-800'
        ],
        [
            'id' => 'ews',
            'judul' => 'Early Warning System (EWS)',
            'sub' => 'Deteksi Dini Siswa Berisiko',
            'icon' => '🛡️',
            'badge' => 'Peringatan Otomatis',
            'ringkasan' => 'Sistem otomatis menandai siswa yang Alfa ≥ 3 kali dalam sebulan atau memiliki poin kedisiplinan berisiko, sehingga tindakan pencegahan dapat dilakukan lebih awal.',
            'poin' => [
                'Deteksi dini sebelum masalah ketidakhadiran menjadi parah',
                'Laporan rekap EWS siap digunakan saat koordinasi dengan Guru BK & Kepsek',
                'Riwayat penanganan siswa tercatat dengan rapi & transparan'
            ],
            'bg_accent' => 'from-rose-50 to-pink-50 border-rose-200 text-rose-800'
        ],
        [
            'id' => 'kas',
            'judul' => 'Buku Kas Digital Kelas',
            'sub' => 'Iuran & Pengeluaran Transparan',
            'icon' => '💰',
            'badge' => 'Kas Transparan 100%',
            'ringkasan' => 'Pencatatan iuran kas kelas per siswa secara digital. Dilengkapi laporan pemasukan, pengeluaran, dan sisa saldo kas yang dapat dipublikasikan ke orang tua.',
            'poin' => [
                'Pencatatan setoran siswa dengan cetak tanda terima digital',
                'Laporan keuangan bulanan otomatis tanpa hitung rumus Excel',
                'Transparansi 100% untuk menjaga kepercayaan orang tua siswa'
            ],
            'bg_accent' => 'from-cyan-50 to-sky-50 border-cyan-200 text-cyan-800'
        ],
        [
            'id' => 'pdf',
            'judul' => 'Cetak Laporan PDF 7 Bab',
            'sub' => 'Format Standar Resmi Siap Cetak',
            'icon' => '🖨️',
            'badge' => '1-Klik Siap Tanda Tangan',
            'ringkasan' => 'Cetak Laporan Wali Kelas lengkap dalam 1 klik. Dilengkapi Sampul Resmi, Penomoran Halaman, Lembar Pengesahan, dan Kolom Tanda Tangan Kepala Sekolah.',
            'poin' => [
                'Mencakup Sampul, Biodata, Rekap Absensi, P5, Buku Kas, EWS, & Lembar Pengesahan',
                'Ukuran A4 standar dinas pendidikan yang rapi & presisi',
                'Hemat waktu penyusunan laporan dari berjam-jam menjadi 1 detik'
            ],
            'bg_accent' => 'from-purple-50 to-fuchsia-50 border-purple-200 text-purple-800'
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $judul }}</title>
    <meta name="description" content="{{ $ringkas }}">
    <meta name="keywords" content="aplikasi wali kelas, wali kelas hebat, administrasi wali kelas, absensi whatsapp otomatis, kurikulum merdeka, refleksi p5, ews sekolah">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="{{ $beranda }}">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="manifest" href="/manifest.webmanifest">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:url" content="{{ $beranda }}">
    <meta property="og:title" content="{{ $judul }}">
    <meta property="og:description" content="{{ $ringkas }}">
    <meta property="og:image" content="{{ $gambar }}">
    <meta name="twitter:card" content="summary_large_image">

    <script type="application/ld+json">{!! json_encode($skema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f7ff !important;
            color: #0f172a;
            overflow-x: hidden;
        }
        h1, h2, h3, h4, .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }

        .gradient-blue-text {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 40%, #4f46e5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .card-informative {
            background: #ffffff;
            border: 1.5px solid #bae6fd;
            box-shadow: 0 14px 35px -10px rgba(14, 165, 233, 0.08);
            border-radius: 1.75rem;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .card-informative:hover {
            border-color: #38bdf8;
            box-shadow: 0 24px 50px -12px rgba(37, 99, 235, 0.16);
            transform: translateY(-3px);
        }

        .btn-creative-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            padding: 1rem 2.25rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #4f46e5 100%);
            color: #ffffff;
            font-size: 1rem;
            font-weight: 800;
            font-family: 'Plus Jakarta Sans', sans-serif;
            text-decoration: none;
            box-shadow: 0 12px 28px -6px rgba(37, 99, 235, 0.45);
            transition: all 0.3s ease;
        }

        .btn-creative-primary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 18px 40px -6px rgba(37, 99, 235, 0.6);
        }

        .btn-creative-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem 1.875rem;
            border-radius: 9999px;
            border: 1.5px solid #93c5fd;
            background: #ffffff;
            color: #1e3a8a;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(14, 165, 233, 0.04);
        }

        .btn-creative-secondary:hover {
            background: #eff6ff;
            border-color: #3b82f6;
            transform: translateY(-2px);
        }

        .pulse-emerald {
            width: 8px; height: 8px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 12px #10b981;
            animation: pdot 2s infinite;
        }
        @keyframes pdot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.65); }
        }

        .float-hero-img {
            animation: floatHero 6s ease-in-out infinite;
        }
        @keyframes floatHero {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

    {{-- CLEAN STICKY NAVBAR --}}
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-sky-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            
            {{-- Brand Logo --}}
            <a href="/" class="flex items-center gap-3.5 no-underline group">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-xl shrink-0 group-hover:scale-105 transition-transform"
                     style="background: linear-gradient(135deg,#2563eb,#4f46e5); box-shadow: 0 4px 14px rgba(37,99,235,.35);">🎓</div>
                <div>
                    <span class="font-black text-xl text-slate-900 block leading-none" style="font-family:'Plus Jakarta Sans',sans-serif;">Wali Kelas <span class="gradient-blue-text">Hebat</span></span>
                    <span class="text-[10px] font-bold tracking-widest text-sky-600 uppercase block mt-1">walas.my.id</span>
                </div>
            </a>

            {{-- Navigation Links --}}
            <div class="hidden md:flex items-center gap-8 text-sm font-bold text-slate-600">
                <a href="#fitur-eksplorasi" class="hover:text-blue-600 transition-colors">Panduan Fitur</a>
                <a href="#simulasi" class="hover:text-blue-600 transition-colors">Simulasi Otomasi</a>
                <a href="#perbandingan" class="hover:text-blue-600 transition-colors">Kisah Guru</a>
                <a href="#harga" class="hover:text-blue-600 transition-colors">Harga Paket</a>
                <a href="#faq" class="hover:text-blue-600 transition-colors">FAQ</a>
            </div>

            {{-- Actions --}}
            <div class="hidden md:flex items-center gap-3.5">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-creative-primary" style="padding: 0.625rem 1.375rem; font-size: 0.875rem;">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-creative-secondary" style="padding: 0.625rem 1.25rem; font-size: 0.875rem;">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="btn-creative-primary" style="padding: 0.625rem 1.5rem; font-size: 0.875rem;">
                        Coba Gratis {{ $bulanGratis }} Bulan
                    </a>
                @endauth
            </div>

            {{-- Mobile Toggle Button --}}
            <button @click="open = !open" x-data="{ open: false }" type="button" class="md:hidden p-2.5 rounded-xl text-slate-600 hover:text-slate-900 bg-sky-50 border border-sky-200" aria-label="Menu Toggle">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </header>

    {{-- HERO SECTION KREATIF --}}
    <section class="py-16 sm:py-24 bg-gradient-to-b from-sky-100/60 via-sky-50/40 to-transparent">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-center">
                
                {{-- Left Content --}}
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    {{-- Badge Pill --}}
                    <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full text-xs sm:text-sm font-extrabold bg-sky-100 border border-sky-300 text-sky-800">
                        <span class="pulse-emerald"></span>
                        <span>Solusi Otomatisasi Administrasi Wali Kelas Indonesia</span>
                    </div>

                    {{-- Headline --}}
                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black leading-tight text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                        Presensi WhatsApp Otomatis &amp;<br>
                        <span class="gradient-blue-text">Administrasi Kelas Rapi 1-Klik</span>
                    </h1>

                    {{-- Description --}}
                    <p class="text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                        Tinggalkan rekap manual yang menyita waktu malam Anda. Nikmati <strong>Presensi WhatsApp 1-Klik</strong>, <strong>Form Biodata Mandiri 33 Field</strong>, <strong>Refleksi P5 Merdeka</strong>, dan <strong>Cetak Laporan PDF Resmi</strong>.
                    </p>

                    {{-- CTAs --}}
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center lg:justify-start gap-4 pt-2">
                        <a href="{{ route('register') }}" class="btn-creative-primary">
                            <span>🚀 Mulai Buat Kelas Gratis</span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                        <a href="#fitur-eksplorasi" class="btn-creative-secondary">
                            📖 Jelajahi Panduan Fitur
                        </a>
                    </div>

                    {{-- Guarantees --}}
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs sm:text-sm text-slate-500 font-semibold pt-2">
                        <span class="flex items-center gap-1.5"><span class="text-emerald-600 font-black">✓</span> Gratis {{ $bulanGratis }} Bulan Pertama</span>
                        <span class="flex items-center gap-1.5"><span class="text-emerald-600 font-black">✓</span> Tanpa Kartu Kredit</span>
                        <span class="flex items-center gap-1.5"><span class="text-emerald-600 font-black">✓</span> Data Aman &amp; Tidak Terkunci</span>
                    </div>
                </div>

                {{-- Right 3D Character Illustration Frame --}}
                <div class="lg:col-span-5 flex justify-center">
                    <div class="float-hero-img card-informative p-5 text-center space-y-4 max-w-md w-full">
                        <div class="overflow-hidden rounded-2xl border border-sky-100 shadow-md">
                            <img src="/images/3d_teacher_story.jpg" alt="3D Character Wali Kelas Relief" class="w-full h-auto object-cover">
                        </div>

                        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs text-emerald-800">
                            <div class="flex items-center gap-2.5 text-left">
                                <span class="text-xl">✨</span>
                                <div>
                                    <span class="font-bold block">Wali Kelas Tenang &amp; Bebas Pusing</span>
                                    <span class="text-[11px] text-emerald-600">Presensi WA &amp; Laporan PDF Selesai Otomatis</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-3 py-1 rounded-full shrink-0">ONLINE ✓</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- MODUL EKSPLORASI FITUR INFORMATIF --}}
    <section id="fitur-eksplorasi" class="py-20 sm:py-28 bg-white border-t border-sky-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold uppercase tracking-widest text-blue-600">Panduan Modul Lengkap</span>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    Informasi Fitur Utama <span class="gradient-blue-text">Wali Kelas Hebat</span>
                </h2>
                <p class="text-base text-slate-600">Pelajari bagaimana setiap fitur membantu memangkas beban kerja administrasi kelas Anda secara nyata.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($fitur_kreatif as $fk)
                    <div class="card-informative p-7 space-y-5 flex flex-col justify-between">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl shrink-0" style="background: rgba(37,99,235,.08); border: 1px solid rgba(37,99,235,.15);">
                                    {{ $fk['icon'] }}
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-full border {{ $fk['bg_accent'] }}">{{ $fk['badge'] }}</span>
                            </div>

                            <div>
                                <h3 class="text-lg font-bold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">{{ $fk['judul'] }}</h3>
                                <span class="text-xs font-semibold text-sky-600 block mt-0.5">{{ $fk['sub'] }}</span>
                            </div>

                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">{{ $fk['ringkasan'] }}</p>

                            <ul class="space-y-2 pt-3 border-t border-slate-100 text-xs text-slate-700">
                                @foreach($fk['poin'] as $p)
                                    <li class="flex items-start gap-2">
                                        <span class="text-emerald-600 font-bold shrink-0">✓</span>
                                        <span>{{ $p }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- SIMULASI INTERAKTIF KREATIF (LIVE WHATSAPP STREAM MOCKUP) --}}
    <section id="simulasi" class="py-20 sm:py-28 border-t border-sky-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold uppercase tracking-widest text-blue-600">Simulasi Alur Kerja</span>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    Bagaimana WhatsApp Gateway <span class="gradient-blue-text">Bekerja Otomatis?</span>
                </h2>
                <p class="text-base text-slate-600">Alur sederhana tanpa aplikasi tambahan bagi orang tua maupun siswa.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center max-w-5xl mx-auto">
                {{-- Left Interactive Steps --}}
                <div class="lg:col-span-6 space-y-4">
                    <div class="card-informative p-5 flex items-start gap-4 border-l-4 border-l-blue-600">
                        <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm shrink-0">1</div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">Buka Link Presensi Harian</h4>
                            <p class="text-xs text-slate-600 mt-1">Guru atau Seksi Absensi membuka tautan khusus presensi kelas dengan PIN harian.</p>
                        </div>
                    </div>

                    <div class="card-informative p-5 flex items-start gap-4 border-l-4 border-l-emerald-600">
                        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm shrink-0">2</div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">Tandai Kehadiran Siswa</h4>
                            <p class="text-xs text-slate-600 mt-1">Tandai status (Hadir, Sakit, Izin, Alfa) dalam hitungan detik dari HP.</p>
                        </div>
                    </div>

                    <div class="card-informative p-5 flex items-start gap-4 border-l-4 border-l-purple-600">
                        <div class="w-9 h-9 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm shrink-0">3</div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">Kirim Rekap WA Otomatis</h4>
                            <p class="text-xs text-slate-600 mt-1">Format laporan otomatis tersusun dan terkirim ke grup WhatsApp orang tua.</p>
                        </div>
                    </div>
                </div>

                {{-- Right WA Message Bubble Mockup --}}
                <div class="lg:col-span-6">
                    <div class="card-informative p-6 bg-slate-900 text-slate-100 space-y-4 font-sans">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-bold text-slate-300">Grup WA Orang Tua · Kelas 8B</span>
                            </div>
                            <span class="text-[10px] font-semibold text-emerald-400 bg-emerald-500/20 px-2 py-0.5 rounded">TERVERIFIKASI ✓</span>
                        </div>

                        <div class="p-4 rounded-2xl bg-emerald-950/80 border border-emerald-800/60 text-xs text-emerald-100 space-y-2 leading-relaxed">
                            <p class="font-bold text-emerald-400">📊 LAPORAN PRESENSI HARIAN KELAS 8B</p>
                            <p>Hari/Tanggal: <strong>{{ date('d F Y') }}</strong></p>
                            <div class="py-1 border-t border-b border-emerald-800/40 my-1 space-y-0.5">
                                <p>✅ Hadir: <strong>34 Siswa</strong></p>
                                <p>🤒 Sakit: <strong>1 Siswa</strong> (Citra Dewi)</p>
                                <p>✉️ Izin: <strong>1 Siswa</strong> (Budi Santoso)</p>
                                <p>❌ Alfa: <strong>0 Siswa</strong></p>
                            </div>
                            <p class="text-[11px] text-emerald-300 italic">"Terima kasih atas perhatian bapak/ibu orang tua siswa."</p>
                            <span class="text-[9px] text-emerald-500 block text-right">10:15 WIB · Otomatis oleh Wali Kelas Hebat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- PERBANDINGAN SEBELUM VS SESUDAH --}}
    <section id="perbandingan" class="py-20 sm:py-28 bg-white border-t border-sky-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold uppercase tracking-widest text-blue-600">Kisah Transformasi Guru</span>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    Perjuangan Wali Kelas: <span class="gradient-blue-text">Sebelum vs Sesudah</span>
                </h2>
                <p class="text-base text-slate-600">Bandingkan kerepotan masa lalu saat rekap manual dengan kemudahan aplikasi Wali Kelas Hebat.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                {{-- SEBELUM --}}
                <div class="card-informative p-7 sm:p-8 space-y-6 border-rose-200 bg-rose-50/30">
                    <div class="flex items-center justify-between border-b border-rose-200 pb-4">
                        <span class="px-3.5 py-1 rounded-full text-xs font-extrabold bg-rose-100 text-rose-800 border border-rose-300">SEBELUM 😫</span>
                        <span class="text-xs font-bold text-rose-700">Cara Manual &amp; Lelah</span>
                    </div>

                    <ul class="space-y-4 text-xs sm:text-sm text-rose-950 leading-relaxed">
                        <li class="flex items-start gap-3">
                            <span class="text-rose-600 font-bold text-base shrink-0">❌</span>
                            <span><strong>Rekap WA Manual Tiap Malam</strong>: Menghabiskan 30+ menit copy-paste data hadir/sakit dari grup kelas.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-rose-600 font-bold text-base shrink-0">❌</span>
                            <span><strong>Mengetik Biodata 30+ Siswa</strong>: Capek mengetik alamat, NIS, dan HP ortu satu per satu ke Word/Excel.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-rose-600 font-bold text-base shrink-0">❌</span>
                            <span><strong>Kecolongan Siswa Bolos</strong>: Baru sadar ada siswa Alfa berkali-kali saat akhir semester.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-rose-600 font-bold text-base shrink-0">❌</span>
                            <span><strong>Laporan Berantakan</strong>: Jam-jaman mengedit format tabel laporan wali kelas sebelum disetor ke Kepsek.</span>
                        </li>
                    </ul>
                </div>

                {{-- SESUDAH --}}
                <div class="card-informative p-7 sm:p-8 space-y-6 border-emerald-200 bg-emerald-50/30">
                    <div class="flex items-center justify-between border-b border-emerald-200 pb-4">
                        <span class="px-3.5 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">SESUDAH ✨</span>
                        <span class="text-xs font-bold text-emerald-700">Dengan Wali Kelas Hebat</span>
                    </div>

                    <ul class="space-y-4 text-xs sm:text-sm text-emerald-950 leading-relaxed">
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-600 font-bold text-base shrink-0">✅</span>
                            <span><strong>Presensi WA 1-Klik</strong>: PIN harian ke Seksi Absensi, rekap kehadiran langsung tersusun otomatis.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-600 font-bold text-base shrink-0">✅</span>
                            <span><strong>Form Biodata Mandiri 33 Field</strong>: Ortu/Siswa isi via link HP, data langsung rapi di dasbor.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-600 font-bold text-base shrink-0">✅</span>
                            <span><strong>EWS Alert Otomatis</strong>: Sistem langsung menandai siswa yang Alfa ≥ 3x secara realtime.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-600 font-bold text-base shrink-0">✅</span>
                            <span><strong>Cetak PDF 1-Klik</strong>: Laporan Administrasi 7 Bab lengkap dengan sampul &amp; kolom tanda tangan Kepsek.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- CARA KERJA 4 LANGKAH --}}
    <section id="cara-kerja" class="py-20 sm:py-28 border-t border-sky-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold uppercase tracking-widest text-blue-600">Praktis &amp; Langsung Jalan</span>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    Mulai Dalam <span class="gradient-blue-text">4 Langkah Sederhana</span>
                </h2>
                <p class="text-base text-slate-600">Tidak perlu instalasi aplikasi rumit. Cukup daftar dari browser HP Anda.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto">
                @php $steps = [
                    ['1','📝','Daftar Akun Guru','Buat akun dalam 30 detik. Gratis '.$bulanGratis.' bulan tanpa kartu kredit.'],
                    ['2','🏫','Buat Kelas Baru','Input nama kelas & daftar siswa, atau import langsung dari Excel.'],
                    ['3','📱','Hubungkan WA Gateway','Scan QR sekali, sistem siap kirim absensi harian otomatis.'],
                    ['4','📊','Cetak PDF Resmi','Rekap masuk realtime. Cetak laporan resmi siap tanda tangan.'],
                ]; @endphp
                @foreach($steps as [$num,$icon,$title,$desc])
                    <div class="card-informative p-6 text-center space-y-3">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold text-white mx-auto shadow-md" style="background: linear-gradient(135deg,#2563eb,#4f46e5);">
                            {{ $icon }}
                        </div>
                        <span class="text-xs font-bold text-blue-600 uppercase tracking-widest block">Langkah 0{{ $num }}</span>
                        <h3 class="text-base font-bold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">{{ $title }}</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- PRICING SECTION --}}
    <section id="harga" class="py-20 sm:py-28 bg-white border-t border-sky-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-3xl mx-auto space-y-3">
                <span class="text-xs font-bold uppercase tracking-widest text-blue-600">Harga Sederhana</span>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    Transparan &amp; <span class="gradient-blue-text">Tanpa Biaya Tersembunyi</span>
                </h2>
                <p class="text-base text-slate-600">Gratis {{ $bulanGratis }} bulan pertama. Tanpa penagihan otomatis &amp; tanpa kartu kredit.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                {{-- FREE PLAN --}}
                <div class="card-informative p-8 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <span class="px-3.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 uppercase tracking-wider w-fit block border border-slate-200">Masa Gratis</span>
                        <div>
                            <span class="text-4xl sm:text-5xl font-black text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">Rp 0</span>
                            <span class="text-xs text-slate-500"> / {{ $bulanGratis }} bulan pertama</span>
                        </div>
                        <p class="text-xs text-slate-500">Akses seluruh fitur tanpa batasan kelas.</p>
                        <ul class="space-y-3 text-xs text-slate-700 pt-4 border-t border-slate-100">
                            <li class="flex items-center gap-2.5"><span class="text-emerald-600 font-bold">✓</span> Presensi WhatsApp Otomatis</li>
                            <li class="flex items-center gap-2.5"><span class="text-emerald-600 font-bold">✓</span> Biodata Mandiri 33 Field &amp; EWS</li>
                            <li class="flex items-center gap-2.5"><span class="text-emerald-600 font-bold">✓</span> Refleksi P5 &amp; Buku Kas Kelas</li>
                            <li class="flex items-center gap-2.5"><span class="text-emerald-600 font-bold">✓</span> Cetak PDF &amp; Export Excel</li>
                        </ul>
                    </div>
                    <a href="{{ route('register') }}" class="btn-creative-secondary w-full text-center">Daftar Gratis Sekarang</a>
                </div>

                {{-- PRO PLAN --}}
                <div class="card-informative p-8 flex flex-col justify-between space-y-6 border-blue-300 bg-blue-50/50">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider text-white" style="background: linear-gradient(135deg,#2563eb,#4f46e5);">PRO</span>
                            <span class="text-xs text-blue-700 font-bold">setelah masa gratis</span>
                        </div>
                        <div>
                            <span class="text-4xl sm:text-5xl font-black text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">{{ $rupiah }}</span>
                            <span class="text-xs text-slate-500"> / bulan</span>
                        </div>
                        <p class="text-xs text-slate-600">Transfer DANA ke 083817203455, diverifikasi manual operator.</p>
                        <ul class="space-y-3 text-xs text-slate-700 pt-4 border-t border-blue-100">
                            <li class="flex items-center gap-2.5"><span class="text-blue-600 font-bold">✓</span> Melanjutkan Otomasi WhatsApp</li>
                            <li class="flex items-center gap-2.5"><span class="text-blue-600 font-bold">✓</span> Pengiriman Tautan Terjadwal</li>
                            <li class="flex items-center gap-2.5"><span class="text-blue-600 font-bold">✓</span> Balasan Otomatis untuk Orang Tua</li>
                            <li class="flex items-center gap-2.5"><span class="text-blue-600 font-bold">✓</span> Perpanjang / Berhenti Kapan Saja</li>
                        </ul>
                    </div>
                    <a href="{{ route('register') }}" class="btn-creative-primary w-full text-center">Mulai dari Masa Gratis</a>
                </div>
            </div>

            {{-- Guarantee Card --}}
            <div class="mt-8 max-w-4xl mx-auto p-6 rounded-2xl text-xs sm:text-sm text-emerald-900 leading-relaxed bg-emerald-50 border border-emerald-200">
                <strong class="text-emerald-700 font-bold text-sm block mb-1">🛡️ Jaminan Keamanan Data Sekolah:</strong> Masa gratis habis tidak berarti aplikasi terkunci. Absensi, biodata siswa, buku kas, dan seluruh laporan Anda <strong>TETAP BISA DIBUKA, DIUBAH, &amp; DICETAK SELAMANYA</strong>. Data absensi adalah dokumen wajib sekolah, jadi kami tidak menyanderanya untuk menagih pembayaran.
            </div>
        </div>
    </section>

    {{-- FAQ SECTION --}}
    <section id="faq" class="py-20 sm:py-28 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 space-y-3">
            <span class="text-xs font-bold uppercase tracking-widest text-blue-600">Pertanyaan Umum</span>
            <h2 class="text-2xl sm:text-4xl font-black text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                Sering Ditanyakan (FAQ)
            </h2>
        </div>

        <div class="space-y-4">
            @foreach($faq as $item)
                <details class="card-informative rounded-2xl group transition-all">
                    <summary class="flex items-center justify-between p-5 cursor-pointer list-none font-bold text-sm sm:text-base text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                        <span class="pr-4 leading-snug">{{ $item['q'] }}</span>
                        <svg class="w-5 h-5 text-blue-600 transition-transform group-open:rotate-180 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="px-5 pb-5 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-sky-100 pt-4">
                        {{ $item['a'] }}
                    </div>
                </details>
            @endforeach
        </div>
    </section>

    {{-- CTA BOX --}}
    <section class="py-16 sm:py-24 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="card-informative rounded-3xl p-8 sm:p-14 relative overflow-hidden bg-gradient-to-br from-sky-100/70 via-blue-50/50 to-white border-blue-200 space-y-6">
            <h2 class="text-2xl sm:text-4xl font-black text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                Siap memangkas pekerjaan <span class="gradient-blue-text">administrasi kelas?</span>
            </h2>
            <p class="text-sm sm:text-base text-slate-600 max-w-xl mx-auto leading-relaxed">
                Buat akun gratis hari ini. Nikmati {{ $bulanGratis }} bulan penuh tanpa kartu kredit.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                <a href="{{ route('register') }}" class="btn-creative-primary">
                    🚀 Buat Kelas Sekarang
                </a>
                <a href="{{ route('login') }}" class="btn-creative-secondary">
                    Masuk ke Akun
                </a>
            </div>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="py-10 border-t border-sky-100 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6 text-center sm:text-left">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-lg shrink-0"
                     style="background: linear-gradient(135deg,#2563eb,#4f46e5);">🎓</div>
                <span class="font-black text-base text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">Wali Kelas Hebat</span>
            </div>
            <p class="text-xs text-slate-500">© {{ date('Y') }} Wali Kelas Hebat · walas.my.id</p>
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 sm:gap-x-6 text-xs text-slate-600 font-bold">
                <a href="#fitur-eksplorasi" class="hover:text-blue-600 transition-colors">Panduan Fitur</a>
                <a href="#simulasi" class="hover:text-blue-600 transition-colors">Simulasi</a>
                <a href="#perbandingan" class="hover:text-blue-600 transition-colors">Kisah Guru</a>
                <a href="#harga" class="hover:text-blue-600 transition-colors">Harga</a>
                <a href="#faq" class="hover:text-blue-600 transition-colors">FAQ</a>
                <a href="{{ route('login') }}" class="hover:text-blue-600 transition-colors">Masuk</a>
                <a href="{{ route('register') }}" class="hover:text-blue-600 transition-colors">Daftar</a>
            </div>
        </div>
    </footer>

</body>
</html>
