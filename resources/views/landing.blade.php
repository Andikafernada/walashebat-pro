<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <title>Wali Kelas Hebat — Solusi Administrasi Wali Kelas &amp; Absensi WA Otomatis</title>
    <meta name="description" content="Aplikasi Wali Kelas Hebat membantu guru SD, SMP, SMA, SMK mengelola absensi WhatsApp grup otomatis, form biodata mandiri siswa 33 field, refleksi karakter P5, EWS, dan cetak laporan 1-click PDF.">
    <meta name="keywords" content="aplikasi wali kelas, wali kelas hebat, administrasi wali kelas, absensi whatsapp otomatis, kurikulum merdeka, refleksi p5, ews sekolah, rekap absensi guru">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://walas.my.id/">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .hero-glow {
            background: radial-gradient(circle at 50% 20%, rgba(99, 102, 241, 0.25) 0%, rgba(16, 185, 129, 0.1) 35%, transparent 70%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .glass-dark {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 selection:bg-indigo-500 selection:text-white min-h-screen">

    <!-- NAVBAR -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-slate-950/80 backdrop-blur-xl border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3 group">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-500 to-indigo-600 shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-all">
                    <span class="text-xl">🎓</span>
                </div>
                <div>
                    <span class="text-lg font-black tracking-tight text-white block leading-none">WALI KELAS <span class="text-emerald-400">HEBAT</span></span>
                    <span class="text-[10px] font-semibold tracking-wider uppercase text-slate-400">walas.my.id</span>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-300">
                <a href="#fitur" class="hover:text-emerald-400 transition-colors">Fitur Unggulan</a>
                <a href="#wa" class="hover:text-emerald-400 transition-colors">Integrasi WhatsApp</a>
                <a href="#p5" class="hover:text-emerald-400 transition-colors">Refleksi P5</a>
                <a href="#harga" class="hover:text-emerald-400 transition-colors">Harga VIP</a>
                <a href="#faq" class="hover:text-emerald-400 transition-colors">FAQ</a>
            </nav>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="h-11 px-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold text-sm transition-all shadow-lg shadow-emerald-500/25">
                        <span>Masuk Dashboard</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="h-11 px-5 inline-flex items-center text-sm font-bold text-slate-300 hover:text-white transition-colors">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="h-11 px-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500 hover:opacity-95 text-white font-bold text-sm transition-all shadow-lg shadow-indigo-500/25">
                        Coba Gratis Sekarang
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="relative pt-36 pb-24 overflow-hidden hero-glow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full glass-dark text-xs font-bold text-emerald-400 mb-8 border border-emerald-500/30 shadow-inner">
                <span class="flex h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span>Aplikasi Administrasi Wali Kelas &amp; Presensi WA No. 1 di Indonesia</span>
            </div>

            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black tracking-tight text-white leading-[1.15] max-w-5xl mx-auto">
                Kelola Kelas <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400 bg-clip-text text-transparent">Lebih Cepat</span>, Bebas Server Error &amp; Otomatis WhatsApp.
            </h1>

            <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-3xl mx-auto font-normal leading-relaxed">
                Tinggalkan rekap manual yang melelahkan. **Wali Kelas Hebat** menghadirkan presensi Magic Link WhatsApp 1-klik, form biodata mandiri siswa 33 field, EWS deteksi dini, dan refleksi P5 Kurikulum Merdeka.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}" class="w-full sm:w-auto h-14 px-8 inline-flex items-center justify-center gap-3 rounded-2xl bg-gradient-to-r from-emerald-500 via-teal-500 to-indigo-600 hover:scale-105 text-white font-extrabold text-base transition-all shadow-xl shadow-emerald-500/20">
                    <span>🚀 Mulai Buat Kelas Gratis</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                <a href="#fitur" class="w-full sm:w-auto h-14 px-8 inline-flex items-center justify-center gap-2 rounded-2xl glass-dark hover:bg-white/10 text-slate-300 font-bold text-base transition-all">
                    <span>🔍 Lihat Semua Fitur</span>
                </a>
            </div>

            <!-- DASHBOARD PREVIEW MOCKUP -->
            <div class="mt-16 relative max-w-5xl mx-auto rounded-3xl p-3 bg-gradient-to-b from-slate-800/80 to-slate-900/40 border border-slate-700/60 shadow-2xl shadow-emerald-500/10">
                <div class="rounded-2xl overflow-hidden bg-slate-900 border border-slate-800 text-left p-6 sm:p-8 space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full bg-rose-500"></span>
                            <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                            <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-mono text-slate-500 ml-2">https://walas.my.id/dashboard</span>
                        </div>
                        <span class="text-xs font-bold text-emerald-400 bg-emerald-950/80 border border-emerald-800/50 px-3 py-1 rounded-full">🟢 WA Gateway Online</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/50">
                            <span class="text-xs font-semibold text-slate-400">Presensi Hari Ini</span>
                            <p class="text-2xl font-black text-white mt-1">98.5%</p>
                        </div>
                        <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/50">
                            <span class="text-xs font-semibold text-slate-400">Total Siswa Binaan</span>
                            <p class="text-2xl font-black text-emerald-400 mt-1">36 Siswa</p>
                        </div>
                        <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/50">
                            <span class="text-xs font-semibold text-slate-400">Refleksi Karakter P5</span>
                            <p class="text-2xl font-black text-indigo-400 mt-1">12 Jurnal</p>
                        </div>
                        <div class="bg-slate-800/60 p-4 rounded-xl border border-slate-700/50">
                            <span class="text-xs font-semibold text-slate-400">Laporan Siap Cetak</span>
                            <p class="text-2xl font-black text-amber-400 mt-1">PDF / Excel</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- FEATURES GRID -->
    <section id="fitur" class="py-24 bg-slate-900/60 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-xs font-bold tracking-widest text-emerald-400 uppercase">Solusi Administrasi Kelas Modern</span>
                <h2 class="text-3xl sm:text-5xl font-black text-white mt-3">6 Fitur Utama Wali Kelas Hebat</h2>
                <p class="text-slate-400 mt-4 text-base">Dirancang khusus untuk memangkas waktu kerja administrasi guru dari jam-jaman menjadi hitungan detik.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <!-- FITUR 1 -->
                <div class="glass-dark p-8 rounded-3xl border border-slate-800 hover:border-emerald-500/50 transition-all hover:-translate-y-1 space-y-4 group">
                    <div class="h-14 w-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                        💬
                    </div>
                    <h3 class="text-xl font-bold text-white">Presensi WA Magic Link &amp; PIN</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Terbitkan link absensi 1-klik dan PIN harian 6-digit yang dikirimkan otomatis ke WhatsApp Seksi Absensi atau Grup Kelas Anda.
                    </p>
                </div>

                <!-- FITUR 2 -->
                <div class="glass-dark p-8 rounded-3xl border border-slate-800 hover:border-indigo-500/50 transition-all hover:-translate-y-1 space-y-4 group">
                    <div class="h-14 w-14 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                        📝
                    </div>
                    <h3 class="text-xl font-bold text-white">Form Biodata Mandiri (33 Field)</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Siswa mengisi data pribadi, orang tua, pekerjaan, KIP/PKH, hobi, dan cita-cita secara mandiri tanpa login melalui link publik aman.
                    </p>
                </div>

                <!-- FITUR 3 -->
                <div class="glass-dark p-8 rounded-3xl border border-slate-800 hover:border-amber-500/50 transition-all hover:-translate-y-1 space-y-4 group">
                    <div class="h-14 w-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                        🛡️
                    </div>
                    <h3 class="text-xl font-bold text-white">Deteksi Dini EWS &amp; Poin Disiplin</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Sistem Early Warning otomatis mendeteksi siswa yang berpotensi bermasalah (Alfa >= 3x atau Poin Disiplin < 75) untuk penanganan cepat.
                    </p>
                </div>

                <!-- FITUR 4 -->
                <div class="glass-dark p-8 rounded-3xl border border-slate-800 hover:border-cyan-500/50 transition-all hover:-translate-y-1 space-y-4 group">
                    <div class="h-14 w-14 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                        🎨
                    </div>
                    <h3 class="text-xl font-bold text-white">Refleksi Karakter P5 Merdeka</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Jurnal perkembangan 6 Dimensi Profil Pelajar Pancasila lengkap dengan analisis persentase &amp; lencana penghargaan siswa.
                    </p>
                </div>

                <!-- FITUR 5 -->
                <div class="glass-dark p-8 rounded-3xl border border-slate-800 hover:border-purple-500/50 transition-all hover:-translate-y-1 space-y-4 group">
                    <div class="h-14 w-14 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                        💰
                    </div>
                    <h3 class="text-xl font-bold text-white">Buku Kas &amp; Keuangan Kelas</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Catat pemasukan kas mingguan, pengeluaran keperluan kelas, dan cetak rincian saldo keuangan transparan 100%.
                    </p>
                </div>

                <!-- FITUR 6 -->
                <div class="glass-dark p-8 rounded-3xl border border-slate-800 hover:border-emerald-500/50 transition-all hover:-translate-y-1 space-y-4 group">
                    <div class="h-14 w-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                        📊
                    </div>
                    <h3 class="text-xl font-bold text-white">Cetak Laporan PDF 1-Klik</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Rekap bulanan, leger absensi, portofolio siswa, dan laporan wali kelas siap dicetak dan ditandatangani Kepala Sekolah.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="py-12 bg-slate-950 border-t border-slate-900 text-slate-500 text-sm text-center">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p>&copy; {{ date('Y') }} <strong>Wali Kelas Hebat</strong> (walas.my.id). Hak Cipta Dilindungi.</p>
            <div class="flex gap-6 font-medium text-slate-400">
                <a href="{{ route('login') }}" class="hover:text-emerald-400">Masuk Guru</a>
                <a href="{{ route('register') }}" class="hover:text-emerald-400">Daftar Sekarang</a>
            </div>
        </div>
    </footer>

</body>
</html>
