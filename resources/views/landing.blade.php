@php
    // Dijaga di bawah ~65 karakter: lebih dari itu dipotong Google di hasil
    // pencarian, dan yang terpotong justru ekor yang membawa kata kuncinya.
    $judul = 'Wali Kelas Hebat — Absensi WhatsApp & Administrasi Kelas';
    // "EWS" sengaja tidak dipakai di kalimat jualan. Itu istilah orang dalam
    // sistem informasi sekolah; wali kelas yang mencari solusi mengetik
    // "siswa sering bolos", bukan akronimnya. Akronim tetap disebut sekali di
    // kartu fitur, untuk pembaca yang memang mencarinya.
    $ringkas = 'Presensi WhatsApp 1 klik, form biodata mandiri siswa, refleksi karakter P5 Kurikulum Merdeka, peringatan dini siswa berisiko, dan laporan PDF siap tanda tangan. Gratis '.$bulanGratis.' bulan, tanpa kartu kredit.';
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
                    'Peringatan dini siswa berisiko (EWS) dan poin kedisiplinan',
                    'Buku kas kelas dengan rekap setoran per siswa',
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

    /*
     * Baris contoh untuk daftar hadir di hero.
     *
     * Nama-nama ini karangan, dan harus tetap begitu: satu-satunya data nyata
     * di server ini adalah data produksi, dan menaruh nama siswa sungguhan di
     * halaman publik adalah kebocoran, bukan pemasaran.
     */
    $contohHadir = [
        ['01', 'Ahmad Fauzi', 'H'],
        ['02', 'Bunga Lestari', 'H'],
        ['03', 'Citra Dewi', 'I'],
        ['04', 'Dimas Prakoso', 'H'],
        ['05', 'Eka Ramadhani', 'H'],
        ['06', 'Fitri Handayani', 'A'],
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
    <meta name="theme-color" content="#F6F5F1">
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

    {{--
        Tiga keluarga huruf untuk tiga peran, bukan satu keluarga yang
        diperbesar dan ditebalkan: Archivo membawa judul dengan tulang tebal
        khas kop formulir, Newsreader membuat isinya terbaca seperti dokumen
        cetak, dan IBM Plex Mono memegang segala yang berperilaku seperti data
        — label kolom, nomor, PIN, jam. Bobotnya sengaja sedikit; setiap bobot
        tambahan adalah berkas yang harus diunduh HP kelas menengah.
    --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;800&family=IBM+Plex+Mono:wght@400;600&family=Newsreader:opsz,wght@6..72,400;6..72,500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /*
         * Nilainya disamakan dengan tailwind.config.js.
         *
         * Halaman ini memang berdiri sendiri — ia tidak memakai palet Tailwind
         * aplikasi dan tidak seharusnya, karena rupa huruf jualannya berbeda.
         * Tetapi warnanya harus sama persis: pengunjung yang menekan "Coba
         * gratis" berpindah dari halaman ini ke halaman daftar dalam satu klik,
         * dan tinta yang bergeser sedikit di antara keduanya terbaca sebagai
         * dua situs yang berbeda.
         */
        :root {
            --kertas:      #f7f5f1;  /* = slate-50   */
            --kertas-tua:  #efebe4;  /* = slate-100  */
            --garis:       #e3ded4;  /* = slate-200  */
            --tinta:       #23486b;  /* = indigo-600 */
            --tinta-muda:  #356494;  /* = indigo-500 */
            --abu:         #7c7466;  /* = slate-500  */
            --pena-merah:  #9a3527;  /* = rose-600, HANYA untuk tanda: alfa, belum, koreksi */
            --hijau:       #2e6446;  /* = emerald-600, HANYA untuk centang hadir */
            --putih:       #FFFFFF;
        }

        body { background: var(--kertas); color: #443e33; } /* = slate-700 */

        .judul { font-family: 'Archivo', system-ui, sans-serif; font-weight: 800; letter-spacing: -0.02em; }
        .isi   { font-family: 'Newsreader', Georgia, serif; }
        .data  { font-family: 'IBM Plex Mono', ui-monospace, monospace; }

        /* Label kecil bergaya kop formulir: "NAMA SISWA", "KELAS", "TANGGAL". */
        .label-formulir {
            font-family: 'IBM Plex Mono', ui-monospace, monospace;
            font-size: 11px; font-weight: 600; letter-spacing: 0.14em;
            text-transform: uppercase; color: var(--abu);
        }

        /*
            Garis-garis buku tulis di belakang hero. Dipasang HANYA di hero:
            sekali dipakai ia jadi ciri, dipakai di setiap seksi ia jadi bising
            dan teks di atasnya sulit dibaca.
        */
        .kertas-bergaris {
            background-image: repeating-linear-gradient(
                to bottom,
                transparent 0, transparent 31px,
                var(--garis) 31px, var(--garis) 32px
            );
        }

        /* Lembar: kartu di sini berperilaku seperti kertas, bukan seperti kaca. */
        .lembar {
            background: var(--putih);
            border: 1px solid var(--garis);
            border-radius: 4px;
        }

        /* Garis bawah judul, seperti coretan penegas dengan pena merah. */
        .garis-tegas {
            background-image: linear-gradient(var(--pena-merah), var(--pena-merah));
            background-size: 100% 3px;
            background-position: 0 92%;
            background-repeat: no-repeat;
        }

        /*
            Tanda tangan halaman: daftar hadir yang mencentang dirinya sendiri.
            Satu per satu, secepat orang benar-benar mencentang di layar HP.
        */
        @keyframes tercentang {
            from { opacity: 0; transform: translateY(3px); }
            to   { opacity: 1; transform: none; }
        }
        .tanda { opacity: 0; animation: tercentang .3s ease-out forwards; }

        /* Gerak bukan hiasan wajib. Yang mematikannya tetap melihat hasil akhir. */
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .tanda { opacity: 1; animation: none; }
        }

        details > summary { list-style: none; cursor: pointer; }
        details > summary::-webkit-details-marker { display: none; }
        details[open] .faq-panah { transform: rotate(180deg); }

        /* Fokus keyboard harus terlihat di atas kertas terang. */
        a:focus-visible, summary:focus-visible, button:focus-visible {
            outline: 2px solid var(--tinta);
            outline-offset: 3px;
        }

        /* Sasaran #anchor tidak boleh tersembunyi di balik navbar melayang. */
        section[id] { scroll-margin-top: 6rem; }
        /* Di HP navbar dua tingkat (logo + deretan pil), jadi lebih tinggi. */
        @media (max-width: 767px) { section[id] { scroll-margin-top: 9rem; } }
    </style>
</head>
<body class="min-h-screen antialiased">

    <a href="#fitur" class="sr-only focus:not-sr-only focus:absolute focus:z-[60] focus:top-4 focus:left-4 focus:px-4 focus:py-2 focus:bg-[color:var(--tinta)] focus:text-white focus:font-semibold">
        Lewati ke konten
    </a>

    <!-- NAVBAR -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-[color:var(--kertas)] border-b border-[color:var(--garis)]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <a href="/" class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center border border-[color:var(--tinta)] text-[color:var(--tinta)] judul text-sm" aria-hidden="true">WK</span>
                <span class="leading-none">
                    <span class="judul text-base text-[color:var(--tinta)] block">WALI KELAS HEBAT</span>
                    <span class="label-formulir">walas.my.id</span>
                </span>
            </a>

            {{--
                Setiap butir di sini WAJIB punya <section id="..."> pasangannya.
                Menu lama menjanjikan empat halaman yang tidak pernah dibuat,
                jadi empat dari lima tautannya mati: diklik, halaman diam saja.
                Pengunjung tidak menyimpulkan "menunya salah", ia menyimpulkan
                "situsnya rusak" — lalu pergi. LandingTest menjaga agar setiap
                tautan di sini benar-benar punya tujuan.
            --}}
            <nav class="hidden md:flex items-center gap-7 label-formulir" aria-label="Navigasi utama">
                <a href="#fitur" class="hover:text-[color:var(--tinta)]">Fitur</a>
                <a href="#wa" class="hover:text-[color:var(--tinta)]">WhatsApp</a>
                <a href="#p5" class="hover:text-[color:var(--tinta)]">Refleksi P5</a>
                <a href="#harga" class="hover:text-[color:var(--tinta)]">Harga</a>
                <a href="#faq" class="hover:text-[color:var(--tinta)]">FAQ</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="hidden sm:inline-flex label-formulir hover:text-[color:var(--tinta)]">Masuk</a>
                <a href="{{ route('register') }}" class="h-10 px-4 inline-flex items-center bg-[color:var(--tinta)] text-white judul text-sm">
                    Coba Gratis
                </a>
            </div>
        </div>

        {{--
            Di bawah md, nav di atas tersembunyi — dan sampai sekarang itu berarti
            pengunjung HP tidak punya jalan ke Harga atau FAQ selain menggulir
            seluruh halaman. Padahal hampir semua wali kelas membukanya dari HP.

            Deretan pil yang bisa digeser, bukan menu hamburger: hamburger butuh
            state, panel, jebakan fokus, dan tombol tutup — untuk lima tautan
            jangkar di satu halaman. Yang ini tidak memakai JavaScript sama
            sekali, jadi tidak ada yang bisa rusak diam-diam.
        --}}
        <nav class="md:hidden border-t border-[color:var(--garis)] overflow-x-auto" aria-label="Navigasi bagian halaman">
            <div class="flex gap-4 px-4 py-2.5 w-max label-formulir">
                <a href="#fitur" class="whitespace-nowrap">Fitur</a>
                <a href="#wa" class="whitespace-nowrap">WhatsApp</a>
                <a href="#p5" class="whitespace-nowrap">Refleksi P5</a>
                <a href="#harga" class="whitespace-nowrap">Harga</a>
                <a href="#faq" class="whitespace-nowrap">FAQ</a>
                <a href="{{ route('login') }}" class="whitespace-nowrap text-[color:var(--tinta)]">Masuk</a>
            </div>
        </nav>
    </header>

    <!-- HERO -->
    {{-- pt lebih besar di HP: navbar di sana dua tingkat, bukan satu. --}}
    <section class="relative pt-36 md:pt-28 pb-16 kertas-bergaris">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-12 lg:gap-16 items-start">

            <div class="pt-4">
                {{--
                    Lencana ini dulu berbunyi "Aplikasi ... No. 1 di Indonesia".
                    Klaim itu tidak bisa dibuktikan, dan pembaca yang skeptis justru
                    jadi kurang percaya pada sisa halaman. Diganti janji yang
                    memang ditepati kodenya: masa gratis penuh tanpa kartu kredit.
                --}}
                <p class="label-formulir text-[color:var(--tinta)]">
                    Gratis {{ $bulanGratis }} bulan penuh — tanpa kartu kredit
                </p>

                <h1 class="judul text-4xl sm:text-5xl lg:text-[3.4rem] leading-[1.08] text-[color:var(--tinta)] mt-5">
                    Administrasi wali kelas selesai <span class="garis-tegas">sebelum bel istirahat</span>.
                </h1>

                {{--
                    Paragraf ini pernah memuat **Wali Kelas Hebat** dengan bintang
                    Markdown. Blade bukan Markdown: bintangnya tercetak apa adanya
                    di layar, dan itu terpampang di kalimat pembuka halaman muka.
                --}}
                <p class="isi text-lg sm:text-xl text-[color:var(--abu)] leading-relaxed mt-6 max-w-xl">
                    Tinggalkan rekap manual yang melelahkan. <strong class="text-[#22252B] font-medium">Wali Kelas Hebat</strong>
                    menghadirkan presensi 1 klik lewat WhatsApp, form biodata mandiri siswa, peringatan dini
                    untuk siswa yang mulai sering absen, dan refleksi P5 Kurikulum Merdeka — semuanya dari satu dasbor.
                </p>

                <div class="mt-8 flex flex-col sm:flex-row sm:items-center gap-4">
                    <a href="{{ route('register') }}" class="h-12 px-6 inline-flex items-center justify-center gap-2 bg-[color:var(--tinta)] text-white judul text-sm hover:bg-[#16225A] transition-colors">
                        Mulai buat kelas
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                    <a href="#wa" class="h-12 px-6 inline-flex items-center justify-center border border-[color:var(--garis)] bg-[color:var(--putih)] judul text-sm text-[color:var(--tinta)] hover:border-[color:var(--tinta)] transition-colors">
                        Lihat cara kerjanya
                    </a>
                </div>

                <p class="isi text-sm text-[color:var(--abu)] mt-6">
                    Untuk wali kelas SD, SMP, SMA, dan SMK. Siswa tidak perlu memasang aplikasi apa pun.
                </p>
            </div>

            {{--
                Tanda tangan halaman ini: bukan gambar dasbor, melainkan benda
                kerjanya sendiri. Wali kelas mengenali daftar hadir sebelum ia
                sempat membaca satu kalimat pun — dan yang dijanjikan halaman ini
                memang persis itu: daftar yang terisi tanpa ia mengetik.
            --}}
            <div class="lembar overflow-hidden shadow-[0_1px_0_rgba(0,0,0,0.04)]">
                <div class="flex items-baseline justify-between gap-3 px-4 sm:px-5 py-3 border-b border-[color:var(--garis)] bg-[color:var(--kertas-tua)]">
                    <span class="label-formulir text-[color:var(--tinta)]">Daftar Hadir</span>
                    <span class="data text-[11px] text-[color:var(--abu)]">Kelas 5A &middot; 11 Agu</span>
                </div>

                <table class="table">
                    <caption class="sr-only">Contoh daftar hadir kelas yang terisi lewat tautan presensi</caption>
                    <thead>
                        <tr class="text-[color:var(--abu)] border-b border-[color:var(--garis)]">
                            <th scope="col" class="text-left font-semibold px-4 sm:px-5 py-2 w-10">No</th>
                            <th scope="col" class="text-left font-semibold py-2">Nama</th>
                            <th scope="col" class="font-semibold py-2 w-8">H</th>
                            <th scope="col" class="font-semibold py-2 w-8">I</th>
                            <th scope="col" class="font-semibold py-2 w-8">S</th>
                            <th scope="col" class="font-semibold py-2 w-8 pr-4 sm:pr-5">A</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contohHadir as $i => [$no, $nama, $status])
                            <tr class="{{ $i % 2 ? 'bg-[color:var(--kertas)]' : '' }}">
                                <td class="px-4 sm:px-5 py-2.5 text-[color:var(--abu)]">{{ $no }}</td>
                                <td class="py-2.5 whitespace-nowrap">{{ $nama }}</td>
                                @foreach (['H', 'I', 'S', 'A'] as $kolom)
                                    <td class="py-2.5 text-center {{ $kolom === 'A' ? 'pr-4 sm:pr-5' : '' }}">
                                        @if ($status === $kolom)
                                            <span class="tanda inline-block font-semibold {{ $kolom === 'A' ? 'text-[color:var(--pena-merah)]' : ($kolom === 'H' ? 'text-[color:var(--hijau)]' : 'text-[color:var(--tinta)]') }}"
                                                  style="animation-delay: {{ 250 + $i * 180 }}ms">
                                                {{ $kolom === 'H' ? '✓' : $kolom }}
                                            </span>
                                        @else
                                            <span class="text-[color:var(--garis)]" aria-hidden="true">·</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="flex items-center justify-between gap-3 px-4 sm:px-5 py-3 border-t border-[color:var(--garis)] bg-[color:var(--kertas-tua)]">
                    <span class="data text-[11px] text-[color:var(--hijau)]">terkirim 06:47 &larr; WhatsApp</span>
                    <span class="data text-[11px] text-[color:var(--abu)]">34 / 36 hadir</span>
                </div>
            </div>
        </div>

        <p class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-6 data text-[11px] text-[color:var(--abu)]">
            Ilustrasi. Nama-nama di atas karangan, bukan data siswa sungguhan.
        </p>
    </section>

    <!-- FITUR -->
    <section id="fitur" class="py-20 border-t border-[color:var(--garis)]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <span class="label-formulir">Solusi administrasi kelas</span>
                <h2 class="judul text-3xl sm:text-4xl text-[color:var(--tinta)] mt-3">Enam pekerjaan yang tidak lagi manual</h2>
                <p class="isi text-lg text-[color:var(--abu)] mt-4">Dirancang untuk memangkas waktu administrasi wali kelas dari berjam-jam menjadi hitungan menit.</p>
            </div>

            {{--
                Tanpa ikon. Kartu ini dulu dibuka emoji (   ), dan di tata
                rupa yang meniru formulir cetak, emoji adalah satu-satunya benda
                yang tidak mungkin ada di atas kertas. Yang menggantikannya bukan
                ikon lain, melainkan tidak ada apa-apa: judul yang tegas lebih
                cepat dibaca daripada gambar kecil yang harus ditafsirkan.
            --}}
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-px bg-[color:var(--garis)] border border-[color:var(--garis)] mt-12">
                @foreach ([
                    ['Presensi WhatsApp Magic Link & PIN', 'Terbitkan tautan absensi sekali pakai dan PIN harian 6 digit yang dikirim otomatis ke Seksi Absensi atau grup kelas Anda.'],
                    ['Form Biodata Mandiri Siswa', 'Siswa mengisi data pribadi, orang tua, pekerjaan, KIP/PKH, hobi, dan cita-cita sendiri lewat tautan publik beralamat acak — tanpa login.'],
                    ['Peringatan Dini Siswa Berisiko (EWS)', 'Sistem menandai siswa yang mulai berisiko — alfa berulang atau poin disiplin menipis — selagi masih bisa ditangani, bukan setelah kepala sekolah bertanya.'],
                    ['Refleksi Karakter P5 Merdeka', 'Jurnal perkembangan enam dimensi Profil Pelajar Pancasila, lengkap dengan analisis persentase dan lencana penghargaan siswa.'],
                    ['Buku Kas & Rekap Setoran Siswa', 'Lihat siapa yang sudah setor dan siapa yang belum dalam satu daftar, centang yang membayar hari ini sekaligus, lalu cetak saldo yang bisa dipertanggungjawabkan.'],
                    ['Cetak Laporan PDF 1 Klik', 'Rekap bulanan, leger absensi, portofolio siswa, dan laporan wali kelas siap dicetak dan ditandatangani kepala sekolah.'],
                ] as $i => [$nama, $isi])
                    <div class="bg-[color:var(--putih)] p-7 space-y-3">
                        <span class="data text-[11px] text-[color:var(--abu)]">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="judul text-lg text-[color:var(--tinta)] leading-snug">{{ $nama }}</h3>
                        <p class="isi text-[color:var(--abu)] leading-relaxed">{{ $isi }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- INTEGRASI WHATSAPP -->
    <section id="wa" class="py-20 border-t border-[color:var(--garis)]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-14 items-start">
                <div>
                    <span class="label-formulir">Integrasi WhatsApp</span>
                    <h2 class="judul text-3xl sm:text-4xl text-[color:var(--tinta)] mt-3 leading-tight">Absensi masuk tanpa Anda mengetik apa pun</h2>
                    <p class="isi text-lg text-[color:var(--abu)] mt-5 leading-relaxed">
                        Rekap kehadiran biasanya berhenti di satu orang: wali kelas yang harus menyalin ulang laporan dari grup.
                        Di sini pekerjaan itu berpindah ke tautan sekali pakai yang dikirim sendiri oleh sistem setiap pagi.
                    </p>

                    {{--
                        Bernomor karena urutannya memang informasi: langkah 3
                        mustahil sebelum langkah 1. Di seksi lain penomoran
                        serupa hanya akan jadi hiasan, jadi tidak dipakai.
                    --}}
                    <ol class="mt-9 space-y-7">
                        @foreach ([
                            ['Sistem menerbitkan tautan harian', 'Setiap pagi, satu tautan absensi sekali pakai dan PIN 6 digit dibuat otomatis untuk tiap kelas.'],
                            ['Dikirim ke grup atau Seksi Absensi', 'Pesan berangkat sendiri lewat gateway WhatsApp Anda pada jam yang Anda tentukan.'],
                            ['Petugas mencentang di satu layar', 'Cukup dibuka di peramban HP. Tidak perlu memasang aplikasi dan tidak perlu membuat akun.'],
                            ['Rekap langsung masuk dasbor', 'Begitu dikirim, tautannya tertutup dan hasilnya tampil di dasbor — siap dicetak kapan pun.'],
                        ] as $i => [$langkah, $rinci])
                            <li class="flex gap-5">
                                <span class="flex-none data text-sm font-semibold text-[color:var(--tinta)] pt-0.5">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <div class="border-l border-[color:var(--garis)] pl-5">
                                    <h3 class="judul text-base text-[color:var(--tinta)]">{{ $langkah }}</h3>
                                    <p class="isi text-[color:var(--abu)] mt-1.5 leading-relaxed">{{ $rinci }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <!-- Ilustrasi percakapan WhatsApp -->
                <div class="lembar p-5 sm:p-6 space-y-4">
                    <div class="flex items-center justify-between gap-3 border-b border-[color:var(--garis)] pb-4">
                        <span class="judul text-sm text-[color:var(--tinta)]">Grup Kelas 5A</span>
                        <span class="data text-[11px] text-[color:var(--abu)]">36 anggota</span>
                    </div>

                    <div class="border-l-2 border-[color:var(--hijau)] bg-[color:var(--kertas)] p-4 isi leading-relaxed">
                        <p class="label-formulir text-[color:var(--hijau)] mb-2">Wali Kelas Hebat</p>
                        <p>Assalamualaikum. Berikut tautan presensi Kelas 5A untuk hari ini.</p>
                        <p class="mt-2 data text-xs text-[color:var(--tinta)] break-all">walas.my.id/a/8f3c1d…</p>
                        <p class="mt-2">PIN hari ini: <strong class="data font-semibold tracking-widest">482619</strong></p>
                        <p class="mt-2 text-sm text-[color:var(--abu)]">Tautan tertutup otomatis setelah presensi dikirim.</p>
                    </div>

                    <div class="border-l-2 border-[color:var(--garis)] bg-[color:var(--kertas)] p-4 ml-6 isi">
                        <p class="label-formulir mb-2">Seksi Absensi</p>
                        <p>Sudah dikirim, Bu. Hadir 34, izin 1, sakit 1.</p>
                    </div>

                    <p class="data text-[11px] text-[color:var(--abu)] pt-1">Ilustrasi. Nomor dan PIN di atas bukan data sungguhan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- REFLEKSI P5 -->
    <section id="p5" class="py-20 border-t border-[color:var(--garis)]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <span class="label-formulir">Kurikulum Merdeka</span>
                <h2 class="judul text-3xl sm:text-4xl text-[color:var(--tinta)] mt-3">Refleksi karakter P5 yang benar-benar terisi</h2>
                <p class="isi text-lg text-[color:var(--abu)] mt-4 leading-relaxed">
                    Penilaian karakter sering berhenti sebagai kolom kosong yang diisi buru-buru menjelang rapor.
                    Di sini siswa menulis refleksinya sendiri sepanjang semester, dan wali kelas tinggal membaca perkembangannya.
                </p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-px bg-[color:var(--garis)] border border-[color:var(--garis)] mt-12">
                @foreach ([
                    'Beriman & Bertakwa',
                    'Berkebinekaan Global',
                    'Bergotong Royong',
                    'Mandiri',
                    'Bernalar Kritis',
                    'Kreatif',
                ] as $dimensi)
                    <div class="bg-[color:var(--putih)] px-4 py-6 text-center">
                        <h3 class="judul text-sm text-[color:var(--tinta)] leading-snug">{{ $dimensi }}</h3>
                    </div>
                @endforeach
            </div>

            <div class="grid md:grid-cols-3 gap-8 mt-12">
                @foreach ([
                    ['Siswa mengisi sendiri', 'Lewat portal siswa, anak menuliskan apa yang sudah baik, apa yang perlu diperbaiki, dan rencana tindak lanjutnya.'],
                    ['Wali kelas memberi umpan balik', 'Setiap refleksi bisa dibalas. Catatan itu tersimpan sebagai jejak pembinaan sepanjang semester.'],
                    ['Portofolio siap dicetak', 'Perkembangan per dimensi terangkum dalam grafik dan portofolio PDF — bahan siap pakai saat pembagian rapor.'],
                ] as [$judulKartu, $isiKartu])
                    <div class="border-t-2 border-[color:var(--tinta)] pt-5">
                        <h3 class="judul text-base text-[color:var(--tinta)]">{{ $judulKartu }}</h3>
                        <p class="isi text-[color:var(--abu)] mt-2 leading-relaxed">{{ $isiKartu }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- HARGA -->
    <section id="harga" class="py-20 border-t border-[color:var(--garis)]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <span class="label-formulir">Harga</span>
                <h2 class="judul text-3xl sm:text-4xl text-[color:var(--tinta)] mt-3">Gratis {{ $bulanGratis }} bulan, lalu {{ $rupiah }} sebulan</h2>
                <p class="isi text-lg text-[color:var(--abu)] mt-4 leading-relaxed">
                    Tidak ada kartu kredit, tidak ada penagihan otomatis, dan tidak ada kejutan di akhir masa gratis.
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 mt-12 max-w-4xl">

                <div class="lembar p-7 flex flex-col">
                    <h3 class="label-formulir">Masa Gratis</h3>
                    <p class="judul text-5xl text-[color:var(--tinta)] mt-4">Rp 0</p>
                    <p class="isi text-[color:var(--abu)] mt-2">{{ $bulanGratis }} bulan penuh sejak akun dibuat.</p>
                    <ul class="mt-6 space-y-2.5 isi flex-1">
                        @foreach ([
                            'Seluruh fitur terbuka tanpa batas kelas',
                            'Presensi WhatsApp otomatis aktif',
                            'Biodata mandiri, peringatan dini, buku kas, refleksi P5',
                            'Cetak laporan PDF dan Excel',
                            'Tanpa kartu kredit',
                        ] as $butir)
                            <li class="flex gap-3">
                                <span class="data text-[color:var(--hijau)]" aria-hidden="true">✓</span>
                                <span>{{ $butir }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="mt-8 h-12 inline-flex items-center justify-center border border-[color:var(--tinta)] judul text-sm text-[color:var(--tinta)] hover:bg-[color:var(--kertas)] transition-colors">
                        Daftar gratis
                    </a>
                </div>

                <div class="lembar p-7 flex flex-col border-[color:var(--tinta)] border-2">
                    <div class="flex items-baseline justify-between gap-3">
                        <h3 class="label-formulir text-[color:var(--tinta)]">PRO</h3>
                        <span class="data text-[11px] text-[color:var(--abu)]">setelah masa gratis</span>
                    </div>
                    <p class="mt-4">
                        <span class="judul text-5xl text-[color:var(--tinta)]">{{ $rupiah }}</span>
                        <span class="isi text-[color:var(--abu)]"> / bulan</span>
                    </p>
                    <p class="isi text-[color:var(--abu)] mt-2">Transfer DANA, diverifikasi manual oleh operator.</p>
                    <ul class="mt-6 space-y-2.5 isi flex-1">
                        @foreach ([
                            'Membuka kembali otomasi WhatsApp',
                            'Pengiriman tautan presensi terjadwal',
                            'Balasan otomatis untuk orang tua',
                            'Perpanjang kapan saja, berhenti kapan saja',
                        ] as $butir)
                            <li class="flex gap-3">
                                <span class="data text-[color:var(--hijau)]" aria-hidden="true">✓</span>
                                <span>{{ $butir }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="mt-8 h-12 inline-flex items-center justify-center bg-[color:var(--tinta)] text-white judul text-sm hover:bg-[#16225A] transition-colors">
                        Mulai dari masa gratis
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
            <div class="mt-10 max-w-4xl border-l-2 border-[color:var(--pena-merah)] pl-6 py-1">
                <h3 class="judul text-lg text-[color:var(--tinta)]">Masa gratis habis tidak berarti aplikasi terkunci</h3>
                <p class="isi text-[color:var(--abu)] mt-2 leading-relaxed max-w-2xl">
                    Absensi, biodata siswa, buku kas, dan seluruh laporan tetap bisa dibuka, diubah, dan dicetak selamanya.
                    Yang berhenti hanya pengiriman WhatsApp otomatis. Data sekolah tidak kami jadikan alat tagih.
                </p>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="py-20 border-t border-[color:var(--garis)]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <span class="label-formulir">Tanya Jawab</span>
            <h2 class="judul text-3xl sm:text-4xl text-[color:var(--tinta)] mt-3 mb-10">Pertanyaan yang sering masuk</h2>

            {{--
                "T" dan "J" alih-alih ikon: itu penanda tanya-jawab yang sudah
                dipakai di lembar-lembar sekolah jauh sebelum ada situs web,
                jadi ia menjelaskan dirinya sendiri tanpa perlu dipelajari.
            --}}
            <div class="border-t border-[color:var(--garis)]">
                @foreach ($faq as $tanya)
                    <details class="group border-b border-[color:var(--garis)]">
                        <summary class="flex items-start justify-between gap-4 py-5">
                            <div class="flex gap-4">
                                <span class="data text-sm font-semibold text-[color:var(--pena-merah)] pt-0.5" aria-hidden="true">T</span>
                                <h3 class="judul text-base text-[color:var(--tinta)]">{{ $tanya['q'] }}</h3>
                            </div>
                            <svg class="faq-panah flex-none w-4 h-4 mt-1 text-[color:var(--abu)] transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <div class="flex gap-4 pb-6 -mt-1 isi text-[color:var(--abu)] leading-relaxed">
                            <span class="data text-sm font-semibold text-[color:var(--hijau)]" aria-hidden="true">J</span>
                            <p>{{ $tanya['a'] }}</p>
                        </div>
                    </details>
                @endforeach
            </div>

            <div class="mt-14 border-t-2 border-[color:var(--tinta)] pt-8">
                <h2 class="judul text-2xl sm:text-3xl text-[color:var(--tinta)]">Siap memangkas pekerjaan administrasi Anda?</h2>
                <p class="isi text-lg text-[color:var(--abu)] mt-3">Buat kelas pertama Anda hari ini. Gratis {{ $bulanGratis }} bulan penuh.</p>
                <a href="{{ route('register') }}" class="mt-7 h-12 px-6 inline-flex items-center justify-center gap-2 bg-[color:var(--tinta)] text-white judul text-sm hover:bg-[#16225A] transition-colors">
                    Mulai buat kelas
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="py-10 border-t border-[color:var(--garis)]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
            <p class="data text-[11px] text-[color:var(--abu)]">&copy; {{ date('Y') }} Wali Kelas Hebat &middot; walas.my.id</p>
            <nav class="flex flex-wrap justify-center gap-6 label-formulir" aria-label="Navigasi footer">
                <a href="#fitur" class="hover:text-[color:var(--tinta)]">Fitur</a>
                <a href="#harga" class="hover:text-[color:var(--tinta)]">Harga</a>
                <a href="#faq" class="hover:text-[color:var(--tinta)]">FAQ</a>
                <a href="{{ route('login') }}" class="hover:text-[color:var(--tinta)]">Masuk</a>
                <a href="{{ route('register') }}" class="hover:text-[color:var(--tinta)]">Daftar</a>
            </nav>
        </div>
    </footer>

</body>
</html>
