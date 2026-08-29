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
        ],
        [
            'id' => 'biodata',
            'judul' => 'Biodata Mandiri 33 Field',
            'sub' => 'Diisi Mandiri oleh Ortu/Siswa',
            'icon' => '📋',
            'badge' => 'Bebas Ketik Manual',
            'ringkasan' => 'Cukup bagikan 1 tautan kelas. Orang tua atau siswa melengkapi 33 bidang data lengkap (Identitas, Alamat, HP Ortu, Pekerjaan, KIP/PKH) dari HP masing-masing.',
            'poin' => [
                'Wali kelas bebas dari tugas pengetikan 30+ siswa satu per satu',
                'Terkompilasi otomatis menjadi tabel profil kelas yang rapi & valid',
                'Dapat diunduh kapan saja ke format Excel (.xlsx)'
            ],
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
    <meta name="theme-color" content="#10b981">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:url" content="{{ $beranda }}">
    <meta property="og:title" content="{{ $judul }}">
    <meta property="og:description" content="{{ $ringkas }}">
    <meta property="og:image" content="{{ $gambar }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Wali Kelas Hebat">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $judul }}">
    <meta name="twitter:description" content="{{ $ringkas }}">
    <meta name="twitter:image" content="{{ $gambar }}">

    <script type="application/ld+json">{!! json_encode($skema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f0fdf4 !important;
            color: #0f172a;
            overflow-x: hidden;
        }
        h1, h2, h3, h4, .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }

        .gradient-emerald-text {
            background: linear-gradient(135deg, #064e3b 0%, #059669 50%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .card-informative {
            background: #ffffff;
            border: 1.5px solid #a7f3d0;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.06);
            border-radius: 1.5rem;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .card-informative:hover {
            border-color: #059669;
            box-shadow: 0 16px 36px rgba(16, 185, 129, 0.12);
            transform: translateY(-2px);
        }

        .btn-creative-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            padding: 0.9rem 2rem;
            border-radius: 9999px;
            background: #059669;
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 800;
            font-family: 'Plus Jakarta Sans', sans-serif;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
            transition: all 0.25s ease;
        }

        .btn-creative-primary:hover {
            background: #047857;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.35);
        }

        .btn-creative-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.9rem 1.75rem;
            border-radius: 9999px;
            border: 1.5px solid #a7f3d0;
            background: #ffffff;
            color: #064e3b;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .btn-creative-secondary:hover {
            background: #f0fdf4;
            border-color: #059669;
            transform: translateY(-1px);
        }

        .pulse-emerald {
            width: 8px; height: 8px;
            background: #059669;
            border-radius: 50%;
            box-shadow: 0 0 10px #10b981;
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
            50% { transform: translateY(-6px); }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased">

    {{-- CLEAN STICKY NAVBAR --}}
    <header class="sticky top-0 z-50 bg-[#f0fdf4]/95 backdrop-blur-md border-b border-emerald-200 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between">
            
            {{-- Brand Logo --}}
            <a href="/" class="flex items-center gap-3 no-underline group">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0 bg-emerald-600 text-white shadow-xs">🎓</div>
                <div>
                    <span class="font-extrabold text-lg text-slate-900 block leading-none" style="font-family:'Plus Jakarta Sans',sans-serif;">Wali Kelas <span class="text-emerald-800">Hebat</span></span>
                    <span class="text-[10px] font-bold tracking-widest text-emerald-800 uppercase block mt-0.5">walas.my.id</span>
                </div>
            </a>

            {{-- Navigation Links --}}
            <div class="hidden md:flex items-center gap-7 text-xs sm:text-sm font-bold text-slate-700">
                <a href="#fitur" class="hover:text-emerald-800 transition-colors">Panduan Fitur</a>
                <a href="#simulasi" class="hover:text-emerald-800 transition-colors">Simulasi Otomasi</a>
                <a href="#perbandingan" class="hover:text-emerald-800 transition-colors">Kisah Guru</a>
                <a href="#harga" class="hover:text-emerald-800 transition-colors">Harga Paket</a>
                <a href="#faq" class="hover:text-emerald-800 transition-colors">FAQ</a>
            </div>

            {{-- Actions --}}
            <div class="hidden md:flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-creative-primary" style="padding: 0.55rem 1.25rem; font-size: 0.85rem;">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-creative-secondary" style="padding: 0.55rem 1.15rem; font-size: 0.85rem;">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="btn-creative-primary" style="padding: 0.55rem 1.35rem; font-size: 0.85rem;">
                        Coba Gratis {{ $bulanGratis }} Bulan
                    </a>
                @endauth
            </div>

            {{-- Mobile Toggle Button --}}
            <a href="{{ route('login') }}" class="md:hidden px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-950 bg-emerald-100 border border-emerald-200">
                Masuk
            </a>
        </div>
    </header>

    {{-- HERO SECTION KREATIF --}}
    <section class="py-12 sm:py-20 bg-gradient-to-b from-emerald-100/50 via-[#f0fdf4] to-[#f0fdf4]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                
                {{-- Left Content --}}
                <div class="lg:col-span-7 space-y-5 text-center lg:text-left">
                    {{-- Badge Pill --}}
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-extrabold bg-emerald-100 border border-emerald-300 text-emerald-950">
                        <span class="pulse-emerald"></span>
                        <span>Solusi Otomatisasi Administrasi Wali Kelas Indonesia</span>
                    </div>

                    {{-- Headline --}}
                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold leading-tight text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                        Presensi WhatsApp Otomatis &amp;<br>
                        <span class="gradient-emerald-text">Administrasi Kelas Rapi 1-Klik</span>
                    </h1>

                    {{-- Description --}}
                    <p class="text-sm sm:text-base text-slate-700 leading-relaxed max-w-2xl mx-auto lg:mx-0 font-medium">
                        Tinggalkan rekap manual yang menyita waktu malam Anda. Nikmati <strong>Presensi WhatsApp 1-Klik</strong>, <strong>Form Biodata Mandiri 33 Field</strong>, <strong>Refleksi P5 Merdeka</strong>, dan <strong>Cetak Laporan PDF Resmi</strong>.
                    </p>

                    {{-- CTAs --}}
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center lg:justify-start gap-3 pt-2">
                        <a href="{{ route('register') }}" class="btn-creative-primary">
                            <span>🚀 Mulai Buat Kelas Gratis</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                        <a href="#fitur" class="btn-creative-secondary">
                            📖 Jelajahi Panduan Fitur
                        </a>
                    </div>

                    {{-- Guarantees --}}
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-5 text-xs text-slate-600 font-bold pt-1">
                        <span class="flex items-center gap-1.5"><span class="text-emerald-800 font-extrabold">✓</span> Gratis {{ $bulanGratis }} Bulan Pertama</span>
                        <span class="flex items-center gap-1.5"><span class="text-emerald-800 font-extrabold">✓</span> Tanpa Kartu Kredit</span>
                        <span class="flex items-center gap-1.5"><span class="text-emerald-800 font-extrabold">✓</span> Data Aman &amp; Terbuka</span>
                    </div>
                </div>

                {{-- Right 3D Character Illustration Frame --}}
                <div class="lg:col-span-5 flex justify-center">
                    <div class="float-hero-img card-informative p-4 sm:p-5 text-center space-y-3.5 max-w-md w-full">
                        <div class="overflow-hidden rounded-2xl border border-emerald-200 shadow-2xs">
                            <img src="/images/3d_teacher_story.jpg" alt="Wali Kelas Hebat" class="w-full h-auto object-cover">
                        </div>

                        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs text-emerald-950">
                            <div class="flex items-center gap-2 text-left">
                                <span class="text-lg">✨</span>
                                <div>
                                    <span class="font-extrabold block text-slate-900">Wali Kelas Tenang &amp; Rapi</span>
                                    <span class="text-[11px] text-emerald-800 font-medium">Presensi WA &amp; Laporan PDF Selesai Otomatis</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-950 bg-emerald-100 border border-emerald-300 px-2.5 py-1 rounded-full shrink-0">ONLINE ✓</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- MODUL EKSPLORASI FITUR INFORMATIF --}}
    <section id="fitur" class="py-16 sm:py-24 bg-white border-t border-emerald-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center max-w-3xl mx-auto space-y-2">
                <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-800">Panduan Modul Lengkap</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    Informasi Fitur Utama <span class="gradient-emerald-text">Wali Kelas Hebat</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 font-medium">Pelajari bagaimana setiap fitur membantu memangkas beban kerja administrasi kelas Anda secara nyata.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($fitur_kreatif as $fk)
                    @if(isset($fk['id']))
                    <div id="{{ $fk['id'] }}">
                    @endif
                    <div class="card-informative p-6 space-y-4 flex flex-col justify-between h-full">
                        <div class="space-y-3.5">
                            <div class="flex items-center justify-between">
                                <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-2xl shrink-0 bg-emerald-50 border border-emerald-200">
                                    {{ $fk['icon'] }}
                                </div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider px-3 py-1 rounded-full border bg-emerald-50 border-emerald-200 text-emerald-950">{{ $fk['badge'] }}</span>
                            </div>

                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">{{ $fk['judul'] }}</h3>
                                <span class="text-xs font-semibold text-emerald-800 block mt-0.5">{{ $fk['sub'] }}</span>
                            </div>

                            <p class="text-xs text-slate-600 leading-relaxed font-medium">{{ $fk['ringkasan'] }}</p>

                            <ul class="space-y-2 pt-3 border-t border-emerald-100 text-xs text-slate-800">
                                @foreach($fk['poin'] as $p)
                                    <li class="flex items-start gap-2">
                                        <span class="text-emerald-800 font-bold shrink-0">✓</span>
                                        <span>{{ $p }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @if(isset($fk['id']))
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- SIMULASI INTERAKTIF KREATIF (LIVE WHATSAPP STREAM MOCKUP) --}}
    <section id="simulasi" class="py-16 sm:py-24 bg-[#f0fdf4] border-t border-emerald-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center max-w-3xl mx-auto space-y-2">
                <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-800">Simulasi Alur Kerja</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    Bagaimana WhatsApp Gateway <span class="gradient-emerald-text">Bekerja Otomatis?</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 font-medium">Alur sederhana tanpa instalasi aplikasi tambahan bagi orang tua maupun siswa.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center max-w-5xl mx-auto">
                {{-- Left Interactive Steps --}}
                <div class="lg:col-span-6 space-y-3.5">
                    <div class="card-informative p-4 sm:p-5 flex items-start gap-3.5 border-l-4 border-l-emerald-600">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-950 flex items-center justify-center font-extrabold text-xs shrink-0">1</div>
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">Buka Link Presensi Harian</h4>
                            <p class="text-xs text-slate-600 mt-1">Guru atau Seksi Absensi membuka tautan khusus presensi kelas dengan PIN harian.</p>
                        </div>
                    </div>

                    <div class="card-informative p-4 sm:p-5 flex items-start gap-3.5 border-l-4 border-l-emerald-600">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-950 flex items-center justify-center font-extrabold text-xs shrink-0">2</div>
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">Tandai Kehadiran Siswa</h4>
                            <p class="text-xs text-slate-600 mt-1">Tandai status (Hadir, Sakit, Izin, Alfa) dalam hitungan detik dari HP.</p>
                        </div>
                    </div>

                    <div class="card-informative p-4 sm:p-5 flex items-start gap-3.5 border-l-4 border-l-emerald-600">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-950 flex items-center justify-center font-extrabold text-xs shrink-0">3</div>
                        <div>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">Kirim Rekap WA Otomatis</h4>
                            <p class="text-xs text-slate-600 mt-1">Format laporan otomatis tersusun dan terkirim ke grup WhatsApp orang tua.</p>
                        </div>
                    </div>
                </div>

                {{-- Right WA Message Bubble Mockup --}}
                <div class="lg:col-span-6">
                    <div class="card-informative p-5 bg-white border-emerald-300 text-slate-900 space-y-3 font-sans">
                        <div class="flex items-center justify-between border-b border-emerald-100 pb-2.5">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-emerald-600"></span>
                                <span class="text-xs font-bold text-slate-900">Grup WA Orang Tua · Kelas XII TKJ D</span>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-950 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-200">TERVERIFIKASI ✓</span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-slate-900 space-y-1.5 leading-relaxed">
                            <p class="font-bold text-emerald-950">📊 LAPORAN PRESENSI HARIAN KELAS XII TKJ D</p>
                            <p class="text-slate-700">Hari/Tanggal: <strong>{{ date('d F Y') }}</strong></p>
                            <div class="py-1.5 border-t border-b border-emerald-200 my-1 space-y-0.5 font-medium">
                                <p>✅ Hadir: <strong>34 Siswa</strong></p>
                                <p>🤒 Sakit: <strong>1 Siswa</strong> (Citra Dewi)</p>
                                <p>✉️ Izin: <strong>1 Siswa</strong> (Budi Santoso)</p>
                                <p>❌ Alfa: <strong>0 Siswa</strong></p>
                            </div>
                            <p class="text-[11px] text-slate-600 italic">"Terima kasih atas perhatian bapak/ibu orang tua siswa."</p>
                            <span class="text-[9px] text-emerald-800 font-bold block text-right">10:15 WIB · Otomatis oleh Wali Kelas Hebat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- PERBANDINGAN SEBELUM VS SESUDAH --}}
    <section id="perbandingan" class="py-16 sm:py-24 bg-white border-t border-emerald-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center max-w-3xl mx-auto space-y-2">
                <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-800">Kisah Transformasi Guru</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    Perjuangan Wali Kelas: <span class="gradient-emerald-text">Sebelum vs Sesudah</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 font-medium">Bandingkan kerepotan masa lalu saat rekap manual dengan kemudahan aplikasi Wali Kelas Hebat.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl mx-auto">
                {{-- SEBELUM --}}
                <div class="card-informative p-6 sm:p-7 space-y-5 border-slate-300 bg-white">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-slate-100 text-slate-800 border border-slate-300">SEBELUM 😫</span>
                        <span class="text-xs font-bold text-slate-600">Cara Manual &amp; Melelahkan</span>
                    </div>

                    <ul class="space-y-3.5 text-xs sm:text-sm text-slate-800 leading-relaxed font-medium">
                        <li class="flex items-start gap-2.5">
                            <span class="text-slate-900 font-bold shrink-0">✕</span>
                            <span><strong>Rekap WA Manual Tiap Malam</strong>: Menghabiskan 30+ menit copy-paste data hadir/sakit dari grup kelas.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="text-slate-900 font-bold shrink-0">✕</span>
                            <span><strong>Mengetik Biodata 30+ Siswa</strong>: Capek mengetik alamat, NIS, dan HP ortu satu per satu ke Word/Excel.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="text-slate-900 font-bold shrink-0">✕</span>
                            <span><strong>Kecolongan Siswa Bolos</strong>: Baru sadar ada siswa Alfa berkali-kali saat akhir semester.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="text-slate-900 font-bold shrink-0">✕</span>
                            <span><strong>Laporan Berantakan</strong>: Jam-jaman mengedit format tabel laporan wali kelas sebelum disetor ke Kepsek.</span>
                        </li>
                    </ul>
                </div>

                {{-- SESUDAH --}}
                <div class="card-informative p-6 sm:p-7 space-y-5 border-emerald-300 bg-emerald-50/40">
                    <div class="flex items-center justify-between border-b border-emerald-200 pb-3">
                        <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-950 border border-emerald-300">SESUDAH ✨</span>
                        <span class="text-xs font-bold text-emerald-800">Dengan Wali Kelas Hebat</span>
                    </div>

                    <ul class="space-y-3.5 text-xs sm:text-sm text-slate-900 leading-relaxed font-medium">
                        <li class="flex items-start gap-2.5">
                            <span class="text-emerald-800 font-extrabold shrink-0">✓</span>
                            <span><strong>Presensi WA 1-Klik</strong>: PIN harian ke Seksi Absensi, rekap kehadiran langsung tersusun otomatis.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="text-emerald-800 font-extrabold shrink-0">✓</span>
                            <span><strong>Form Biodata Mandiri 33 Field</strong>: Ortu/Siswa isi via link HP, data langsung rapi di dasbor.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="text-emerald-800 font-extrabold shrink-0">✓</span>
                            <span><strong>EWS Alert Otomatis</strong>: Sistem langsung menandai siswa yang Alfa ≥ 3x secara realtime.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="text-emerald-800 font-extrabold shrink-0">✓</span>
                            <span><strong>Cetak PDF 1-Klik</strong>: Laporan Administrasi 7 Bab lengkap dengan sampul &amp; kolom tanda tangan Kepsek.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- PRICING SECTION --}}
    <section id="harga" class="py-16 sm:py-24 bg-[#f0fdf4] border-t border-emerald-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center max-w-3xl mx-auto space-y-2">
                <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-800">Harga Sederhana</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    Transparan &amp; <span class="gradient-emerald-text">Tanpa Biaya Tersembunyi</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 font-medium">Gratis {{ $bulanGratis }} bulan pertama. Tanpa penagihan otomatis &amp; tanpa kartu kredit.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                {{-- FREE PLAN --}}
                <div class="card-informative p-7 flex flex-col justify-between space-y-5">
                    <div class="space-y-3.5">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-950 uppercase tracking-wider w-fit block border border-emerald-200">Masa Gratis</span>
                        <div>
                            <span class="text-4xl sm:text-5xl font-extrabold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">Rp 0</span>
                            <span class="text-xs text-slate-500 font-medium"> / {{ $bulanGratis }} bulan pertama</span>
                        </div>
                        <p class="text-xs text-slate-600 font-medium">Akses seluruh fitur tanpa batasan kelas.</p>
                        <ul class="space-y-2.5 text-xs text-slate-800 pt-3 border-t border-emerald-100 font-medium">
                            <li class="flex items-center gap-2"><span class="text-emerald-800 font-bold">✓</span> Presensi WhatsApp Otomatis</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-800 font-bold">✓</span> Biodata Mandiri 33 Field &amp; EWS</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-800 font-bold">✓</span> Refleksi P5 &amp; Buku Kas Kelas</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-800 font-bold">✓</span> Cetak PDF &amp; Export Excel</li>
                        </ul>
                    </div>
                    <a href="{{ route('register') }}" class="btn-creative-secondary w-full text-center">Daftar Gratis Sekarang</a>
                </div>

                {{-- PRO PLAN --}}
                <div class="card-informative p-7 flex flex-col justify-between space-y-5 border-emerald-300 bg-white">
                    <div class="space-y-3.5">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider text-white bg-emerald-600">PRO</span>
                            <span class="text-xs text-emerald-800 font-bold">setelah masa gratis</span>
                        </div>
                        <div>
                            <span class="text-4xl sm:text-5xl font-extrabold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">{{ $rupiah }}</span>
                            <span class="text-xs text-slate-500 font-medium"> / bulan</span>
                        </div>
                        <p class="text-xs text-slate-600 font-medium">Transfer DANA ke 083817203455, diverifikasi manual operator.</p>
                        <ul class="space-y-2.5 text-xs text-slate-800 pt-3 border-t border-emerald-100 font-medium">
                            <li class="flex items-center gap-2"><span class="text-emerald-800 font-bold">✓</span> Melanjutkan Otomasi WhatsApp</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-800 font-bold">✓</span> Pengiriman Tautan Terjadwal</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-800 font-bold">✓</span> Balasan Otomatis untuk Orang Tua</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-800 font-bold">✓</span> Perpanjang / Berhenti Kapan Saja</li>
                        </ul>
                    </div>
                    <a href="{{ route('register') }}" class="btn-creative-primary w-full text-center">Mulai dari Masa Gratis</a>
                </div>
            </div>

            {{-- Guarantee Card --}}
            <div class="mt-6 max-w-4xl mx-auto p-5 rounded-2xl text-xs sm:text-sm text-slate-900 leading-relaxed bg-white border border-emerald-200">
                <strong class="text-emerald-800 font-extrabold text-xs sm:text-sm block mb-1">🛡️ Jaminan Keamanan Data Sekolah:</strong> Masa gratis habis tidak berarti aplikasi terkunci. Absensi, biodata siswa, buku kas, dan seluruh laporan Anda <strong>TETAP BISA DIBUKA, DIUBAH, &amp; DICETAK SELAMANYA</strong>. Data absensi adalah dokumen wajib sekolah, jadi kami tidak menyanderanya untuk menagih pembayaran.
            </div>
        </div>
    </section>

    {{-- FAQ SECTION --}}
    <section id="faq" class="py-16 sm:py-24 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10 space-y-2">
            <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-800">Pertanyaan Umum</span>
            <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                Sering Ditanyakan (FAQ)
            </h2>
        </div>

        <div class="space-y-3.5">
            @foreach($faq as $item)
                <details class="card-informative rounded-2xl group transition-all">
                    <summary class="flex items-center justify-between p-4 sm:p-5 cursor-pointer list-none font-bold text-xs sm:text-sm text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                        <span class="pr-3 leading-snug">{{ $item['q'] }}</span>
                        <svg class="w-4 h-4 text-emerald-700 transition-transform group-open:rotate-180 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="px-4 sm:px-5 pb-4 sm:pb-5 text-xs text-slate-700 leading-relaxed border-t border-emerald-100 pt-3 font-medium">
                        {{ $item['a'] }}
                    </div>
                </details>
            @endforeach
        </div>
    </section>

    {{-- CTA BOX --}}
    <section class="py-12 sm:py-20 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="card-informative rounded-3xl p-7 sm:p-12 relative overflow-hidden bg-gradient-to-br from-emerald-100/70 via-emerald-50/50 to-white border-emerald-300 space-y-5">
            <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">
                Siap memangkas pekerjaan <span class="gradient-emerald-text">administrasi kelas?</span>
            </h2>
            <p class="text-xs sm:text-sm text-slate-700 max-w-xl mx-auto leading-relaxed font-medium">
                Buat akun gratis hari ini. Nikmati {{ $bulanGratis }} bulan penuh tanpa kartu kredit.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
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
    <footer class="py-8 border-t border-emerald-200 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
            <div class="flex items-center gap-2.5 justify-center sm:justify-start">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-base shrink-0 bg-emerald-600 text-white">🎓</div>
                <span class="font-extrabold text-sm text-slate-900" style="font-family:'Plus Jakarta Sans',sans-serif;">Wali Kelas Hebat</span>
            </div>
            <p class="text-xs text-slate-500 font-medium">© {{ date('Y') }} Wali Kelas Hebat · walas.my.id</p>
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-xs text-slate-700 font-bold">
                <a href="#fitur" class="hover:text-emerald-800 transition-colors">Panduan Fitur</a>
                <a href="#simulasi" class="hover:text-emerald-800 transition-colors">Simulasi</a>
                <a href="#perbandingan" class="hover:text-emerald-800 transition-colors">Kisah Guru</a>
                <a href="#harga" class="hover:text-emerald-800 transition-colors">Harga</a>
                <a href="#faq" class="hover:text-emerald-800 transition-colors">FAQ</a>
                <a href="{{ route('login') }}" class="hover:text-emerald-800 transition-colors">Masuk</a>
                <a href="{{ route('register') }}" class="hover:text-emerald-800 transition-colors">Daftar</a>
            </div>
        </div>
    </footer>

</body>
</html>
