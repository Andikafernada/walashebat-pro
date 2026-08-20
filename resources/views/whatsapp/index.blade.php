@extends('layouts.app')

@section('title', 'Integrasi WhatsApp & Balasan Otomatis')

@section('content')
<script src="/vendor/qrcode.min.js?v=1.4.4"></script>

<div class="space-y-6 pb-12" x-data="waSession({ connected: {{ auth()->user()->whatsappConnected() ? 'true' : 'false' }}, statusUrl: '{{ route('whatsapp.status') }}' })">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">
                Integrasi WhatsApp &amp; Balasan Otomatis
            </h1>
            <p class="mt-1 text-sm text-slate-500">Tautkan nomor WhatsApp Anda, atur kata kunci patokan izin/sakit, dan tentukan grup mana yang dibalas otomatis.</p>
        </div>
    </div>

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- PAIRING / CONNECTION STATUS CARD -->
    <div class="card space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 pb-4">
            <div class="flex items-center gap-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Status Koneksi WhatsApp</h3>
                    <p class="text-xs text-slate-500">Nomor Guru: <strong class="text-slate-800">{{ auth()->user()->whatsapp_number ?: 'Belum diisi' }}</strong></p>
                </div>
            </div>

            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                  :class="status === 'connected' ? 'bg-emerald-100 text-emerald-800' : (status === 'pairing' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')"
                  x-text="label()">
            </span>
        </div>

        @if(!auth()->user()->whatsappConnected())
            <div class="bg-amber-50 p-4 rounded border border-amber-200 text-xs text-amber-900 space-y-3">
                <div>
                    <p class="font-semibold text-sm text-amber-950">Nomor WhatsApp Belum Tersambung</p>
                    <p class="text-slate-600 mt-0.5">Pilih cara menautkan yang sesuai dengan perangkat yang sedang Anda pakai.</p>
                </div>

                {{--
                    Dua jalan, karena QR menuntut sesuatu yang tidak selalu ada:
                    layar kedua. Guru yang membuka halaman ini DARI ponselnya
                    tidak mungkin memindai layar ponsel itu dengan ponsel yang
                    sama — sebelumnya ia berhenti di jalan buntu. Pilihan
                    diserahkan kepada guru, bukan ditebak dari user agent:
                    salah tebak mengunci dia pada satu-satunya cara yang justru
                    tidak bisa ia pakai.
                --}}
                <div class="grid gap-2.5 sm:grid-cols-2">
                    <form method="POST" action="{{ route('whatsapp.pair') }}" class="contents">
                        @csrf
                        <input type="hidden" name="metode" value="kode">
                        <button type="submit"
                                class="btn-success h-auto whitespace-normal py-3 text-left">
                            <span class="block text-sm">Saya memakai HP ini</span>
                            <span class="block font-medium text-emerald-50/90 mt-0.5 leading-snug">
                                Dapatkan kode 8 karakter untuk diketik di WhatsApp. Tanpa memindai apa pun.
                            </span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('whatsapp.pair') }}" class="contents">
                        @csrf
                        <input type="hidden" name="metode" value="qr">
                        <button type="submit"
                                class="h-auto py-3 px-4 rounded border-2 border-emerald-600 bg-white hover:bg-emerald-50 text-emerald-800 font-semibold text-xs transition-colors text-left">
                            <span class="block text-sm">Saya memakai laptop/komputer</span>
                            <span class="block font-medium text-slate-500 mt-0.5 leading-snug">
                                Terbitkan Kode QR untuk dipindai dengan WhatsApp di HP Anda.
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="flex items-center justify-between text-xs text-slate-600 bg-slate-50 p-3 rounded">
                <span class="font-semibold text-emerald-700">✓ WhatsApp Aktif &amp; Siap Mengirim Pesan / Balas Otomatis</span>
                <form method="POST" action="{{ route('whatsapp.disconnect') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Putus koneksi WhatsApp ini?')" class="text-xs font-semibold text-rose-600 hover:underline">
                        Putus Koneksi
                    </button>
                </form>
            </div>
        @endif
    </div>

    {{-- KARTU KODE PENAUTAN — jalur "saya memakai HP ini" --}}
    <div class="rounded-lg border-2 border-emerald-500 bg-white p-6 text-center space-y-4"
         x-show="status !== 'connected' && kodePairing" x-cloak>
        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200">
            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
            <span>Kode Penautan Berhasil Diterbitkan</span>
        </div>

        <h3 class="text-xl font-semibold text-slate-900">Ketik Kode Ini di WhatsApp Anda</h3>

        {{-- Dipecah per karakter: kode ini diketik ulang oleh guru sambil
             berpindah aplikasi, dan deretan 8 huruf-angka tanpa jeda paling
             mudah salah baca. --}}
        <div class="flex justify-center gap-1.5 sm:gap-2 py-1 flex-wrap">
            <template x-for="(huruf, i) in String(kodePairing).split('')" :key="i">
                <span class="inline-flex h-12 w-9 sm:h-14 sm:w-11 items-center justify-center rounded border-2 border-slate-900 bg-slate-50 text-xl sm:text-2xl font-semibold tracking-widest text-slate-900"
                      x-text="huruf"></span>
            </template>
        </div>

        {{--
            Kode ini diterbitkan UNTUK satu nomor tertentu. Kalau nomor yang
            tersimpan bukan nomor WhatsApp di HP ini — salah ketik satu digit
            saat mendaftar sudah cukup — kodenya tidak akan pernah diterima,
            dan yang guru lihat hanya kode yang diam sampai kedaluwarsa.
            Validasi tidak bisa menangkap ini: nomornya sah, cuma bukan
            miliknya. Yang bisa menangkapnya hanya mata guru, jadi nomornya
            ditaruh di sini, di detik ia menunggu.
        --}}
        <div class="mx-auto max-w-md rounded border border-amber-200 bg-amber-50 p-3 text-left text-xs text-amber-900">
            Kode ini hanya berlaku untuk WhatsApp bernomor
            <strong class="font-mono">{{ auth()->user()->whatsapp_number }}</strong>.
            Bukan nomor di HP ini?
            <a href="{{ route('profile.edit') }}" class="font-semibold underline">Betulkan dulu nomornya</a>,
            lalu terbitkan kode baru.
        </div>

        <div class="mx-auto max-w-md rounded bg-slate-50 border border-slate-200 p-3.5 text-left text-xs text-slate-700 space-y-1.5">
            <p class="font-semibold text-slate-900">Langkahnya:</p>
            <p>1. Buka <strong>WhatsApp</strong> di HP ini.</p>
            <p>2. Android: ketuk <strong>Titik Tiga (⋮)</strong> &rarr; <strong>Perangkat Tertaut</strong>.
               iPhone: <strong>Setelan</strong> &rarr; <strong>Perangkat Tertaut</strong>.</p>
            <p>3. Ketuk <strong>Tautkan Perangkat</strong>. Kamera pemindai QR akan terbuka &mdash; itu wajar, jangan ditutup.</p>
            {{--
                Langkah 4 dulu berbunyi "pilih Tautkan dengan nomor telepon"
                seolah ia item menu di sebelah Tautkan Perangkat. Bukan: ia
                tombol di DALAM layar kamera QR. Guru yang mencarinya di menu
                hanya menemukan pemindai QR, menyimpulkan fiturnya tidak ada,
                lalu bertanya. Yang mahal bukan kalimatnya, tapi guru yang
                menyerah sebelum sempat bertanya.
            --}}
            <p>4. <strong>Tetap di layar kamera itu.</strong> Di bagian bawahnya ada tulisan biru
               <strong>&ldquo;Tautkan dengan nomor telepon&rdquo;</strong> &mdash; ketuk itu.
               (Di sebagian HP letaknya di pojok kanan atas.)</p>
            <p>5. Masukkan kode 8 huruf di atas.</p>
        </div>

        <p class="mx-auto max-w-md text-left text-[11px] text-slate-500">
            Tulisan itu tidak ada sama sekali? WhatsApp Anda terlalu lama &mdash; perbarui lewat
            Play Store atau App Store. WhatsApp tiruan (GB/FM WhatsApp) memang tidak punya fitur ini;
            pakai jalur <strong>pindai QR</strong> di bawah sebagai gantinya.
        </p>

        <p class="text-xs font-semibold text-indigo-600">
            ⏳ Menunggu kode dimasukkan… Halaman akan otomatis terhubung.
        </p>

        <p class="text-[11px] text-slate-400">
            Kode berlaku sebentar. Bila kedaluwarsa, tekan &ldquo;Saya memakai HP ini&rdquo; sekali lagi untuk kode baru.
        </p>
    </div>

    <!-- DYNAMIC REAL-TIME QR CODE DISPLAY CARD -->
    <div class="rounded-lg border-2 border-emerald-500 bg-white p-6 text-center space-y-4"
         x-show="status !== 'connected' && qr">
        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200">
            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
            <span>Kode QR WhatsApp Berhasil Diterbitkan</span>
        </div>

        <h3 class="text-xl font-semibold text-slate-900">Pindai Kode QR Ini Menggunakan WhatsApp HP</h3>

        <p class="text-xs text-slate-600 max-w-md mx-auto leading-relaxed">
            Buka aplikasi WhatsApp di HP Anda &rarr; Ketuk <strong>Setelan (atau Titik Tiga)</strong> &rarr; <strong>Perangkat Tertaut</strong> &rarr; <strong>Tautkan Perangkat</strong>.
        </p>

        <!-- QR CODE CANVAS CONTAINER -->
        <div class="py-2 flex justify-center">
            <div class="p-3 bg-white border-2 border-slate-900 rounded-lg inline-block">
                <canvas id="wa-qr-canvas-dynamic"></canvas>
            </div>
        </div>

        <p class="text-xs font-semibold text-indigo-600">
            ⏳ Menunggu pemindaian dari HP... Halaman akan otomatis terhubung begitu dipindai.
        </p>
    </div>

    <!-- AUTOREPLY GROUPS SELECTION & EDITING (IF WA CONNECTED) -->
    @if(auth()->user()->whatsappConnected())
        <div class="rounded-lg border-2 border-indigo-200 bg-white p-6 space-y-5"
             x-data="autoreply({
                grupUrl: '{{ route('whatsapp.groups') }}',
                terpilih: {{ json_encode($autoreply['groups'] ?? []) }},
                label: {{ json_encode($grupLabels ?? []) }},
             })"
             x-init="awal()">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900 flex items-center gap-2">
                        <span>Pilih &amp; Kelola Grup WhatsApp yang Dibalas Otomatis</span>
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">Centang grup WhatsApp orang tua yang ingin diaktifkan. Anda bisa menambah, mengurangi, atau mengedit kapan saja.</p>
                </div>

                <div class="shrink-0 flex items-center gap-2">
                    <span class="text-xs font-semibold px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <span x-text="terpilih.length"></span> Grup Terkoneksi
                    </span>
                    {{-- Memindai grup itu mahal: WhatsApp membatasi laju
                         groupFetchAllParticipating dengan ketat. Selama guru
                         hanya membuka halaman untuk melihat pilihannya, tidak
                         ada yang perlu dipindai — jadi tombol inilah satu-satunya
                         jalan masuk ke pemindaian. --}}
                    <template x-if="mode === 'ringkas'">
                        <button type="button" @click="bukaPemilih()"
                                class="text-xs text-indigo-600 hover:underline font-semibold">
                Ubah pilihan grup
                        </button>
                    </template>
                    <template x-if="mode === 'pilih'">
                        <button type="button" @click="muat(true)" :disabled="memuat"
                                class="text-xs text-indigo-600 hover:underline font-semibold disabled:opacity-50">
                Refresh Grup
                        </button>
                    </template>
                </div>
            </div>

            @php
                /*
                 * Pengaturan yang ditampilkan hanya bisa dipercaya bila gateway
                 * benar-benar menjawab. Bila tidak, autoreplyStatus() membalas
                 * "mati, tanpa grup" — kebetulan sama persis dengan tampilan
                 * seorang guru yang memang mematikannya. Menyajikannya sebagai
                 * fakta membuat guru menyangka pengaturannya hilang dan menyetel
                 * ulang sesuatu yang sebenarnya utuh.
                 */
                $gatewayTerjangkau = blank($autoreply['error'] ?? null);
                $tungguDetik = (int) ($gatewayStatus['circuit']['time_until_retry'] ?? 0);
                $kirimTerganggu = ! ($channelStatus['healthy'] ?? true);
            @endphp

            @unless ($gatewayTerjangkau)
                <div class="rounded border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-950 space-y-1">
                    <p class="font-semibold">Pengaturan di bawah belum bisa dibaca</p>
                    <p>Sistem sedang tidak bisa menghubungi layanan WhatsApp, jadi keadaan balasan otomatis Anda <strong>tidak diketahui</strong> — bukan berarti mati, dan pilihan grup Anda <strong>tidak hilang</strong>.</p>
                    <p class="text-amber-800">Ini gangguan di sisi kami, bukan pengaturan Anda.
                        @if ($tungguDetik > 0)
                            Sistem mencoba lagi otomatis dalam <strong>{{ $tungguDetik }} detik</strong>.
                        @else
                            Sistem mencoba lagi otomatis. Muat ulang halaman ini beberapa saat lagi.
                        @endif
                    </p>
                    <p class="text-amber-800">Selama gangguan ini, <strong>jangan menekan Simpan</strong> — biarkan pengaturan lama tetap berjalan.</p>
                </div>
            @endunless

            @if ($gatewayTerjangkau && $kirimTerganggu)
                {{-- Sirkuit berbeda: yang ini soal MENGIRIM pesan, bukan membaca
                     pengaturan. Magic link absensi lewat jalur yang sama. --}}
                <div class="rounded border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-950">
                    <p class="font-semibold">Pengiriman WhatsApp sedang terganggu</p>
                    <p>Pengaturan Anda terbaca normal, tetapi pesan keluar (balasan otomatis dan magic link absensi) sedang gagal terkirim. Sistem mencoba lagi otomatis.</p>
                </div>
            @endif

            <form method="POST" action="{{ route('whatsapp.autoreply') }}" class="space-y-4">
                @csrf

                {{--
                    Pasangan hidden + checkbox adalah pola baku Laravel: tidak
                    dicentang mengirim 0, dicentang mengirim 1.

                    Desain ulang 1 Agustus menghapus checkbox-nya dan menyisakan
                    hidden value="1", sehingga formulir SELALU mengirim "nyala".
                    Melepas semua centang grup pun tidak menolong — controller
                    menolaknya dengan peringatan dan tidak menyimpan apa pun.
                    Akibatnya wali kelas bisa menyalakan bot yang membalas orang
                    tua di grup, tetapi tidak punya satu pun cara mematikannya
                    selain memutus seluruh sambungan WhatsApp — yang sekalian
                    mematikan magic link absensi.
                --}}
                <input type="hidden" name="enabled" value="0">

                <label class="flex items-start gap-2.5 rounded border border-slate-200 bg-white p-3 cursor-pointer hover:bg-slate-50 transition-colors">
                    <input type="checkbox" name="enabled" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                           @checked($autoreply['enabled'] ?? false)>
                    <span class="text-xs">
                        <span class="font-semibold text-slate-800">Nyalakan balasan otomatis</span>
                        @if ($gatewayTerjangkau)
                            <span class="ml-1.5 align-middle inline-block rounded-sm px-2 py-0.5 text-[10px] font-semibold {{ ($autoreply['enabled'] ?? false) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ ($autoreply['enabled'] ?? false) ? 'Sedang aktif' : 'Sedang mati' }}
                            </span>
                        @else
                            {{-- Jangan mengaku tahu. "Mati" di sini akan keliru. --}}
                            <span class="ml-1.5 align-middle inline-block rounded-sm px-2 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-800">
                                Tidak diketahui
                            </span>
                        @endif
                        <span class="block text-slate-500 mt-0.5">Lepas centang ini untuk menghentikan balasan tanpa memutus sambungan WhatsApp.</span>
                    </span>
                </label>

                {{--
                    RINGKAS — keadaan biasa: grup sudah dipilih dan disimpan.

                    Menampilkan pilihan yang tersimpan dari nama yang sudah
                    diingat server, tanpa satu pun permintaan ke WhatsApp.
                    Hidden input di bawah WAJIB ada: tanpa itu formulir ini
                    terkirim tanpa groups[] sama sekali, dan autoreplySave
                    membacanya sebagai "tidak ada grup" — menyimpan kata kunci
                    saja akan menghapus seluruh pilihan grup guru.
                --}}
                <template x-if="mode === 'ringkas'">
                    <div class="space-y-2">
                        <div class="rounded border border-slate-200 bg-white divide-y divide-slate-200">
                            <template x-for="id in terpilih" :key="id">
                                <div class="flex items-center justify-between gap-3 p-3">
                                    <input type="hidden" name="groups[]" :value="id">

                                    <div class="min-w-0">
                                        <span class="block truncate text-xs font-semibold text-slate-900" x-text="namaGrup(id)"></span>
                                        <span class="block text-[10px] text-slate-400" x-text="ketGrup(id)"></span>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <button type="button" @click.prevent="periksa(id)" :disabled="memeriksa === id"
                                                class="text-[10px] font-semibold px-2 py-0.5 rounded-full border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 disabled:opacity-50">
                                            <span x-show="memeriksa !== id">Cek status</span>
                                            <span x-show="memeriksa === id">Memeriksa…</span>
                                        </button>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                            ✓ Terkoneksi (Aktif)
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <p class="text-[10px] text-slate-400">
                            Pilihan tersimpan, ditampilkan tanpa memindai WhatsApp.
                            Tekan <b>Ubah pilihan grup</b> di atas bila ingin menambah atau mengganti grup.
                        </p>
                    </div>
                </template>

                {{--
                    PEMILIH — hanya dirender setelah guru menekan "Ubah pilihan".

                    x-if, BUKAN x-show. Checkbox name="groups[]" yang sekadar
                    disembunyikan tetap ikut terkirim, sehingga blok ini dan
                    blok ringkas di atas akan mengirim groups[] ganda.
                --}}
                <template x-if="mode === 'pilih'">
                <div class="space-y-4">

                <div class="flex gap-2">
                    <input type="text" x-model="cari" placeholder=" Cari nama grup WhatsApp..."
                           class="flex-1 h-10 rounded border border-slate-200 bg-slate-50 px-3.5 text-xs text-slate-800 focus:bg-white focus:border-indigo-500 focus:outline-none">
                    {{-- Daftar grup di-cache 5 menit untuk menghindari batas laju
                         WhatsApp; tombol ini untuk guru yang baru menambah grup. --}}
                    <button type="button" @click="muat(true)" :disabled="memuat" title="Ambil ulang daftar grup dari WhatsApp"
                            class="h-10 shrink-0 rounded border border-slate-200 bg-white hover:bg-slate-50 px-3 text-xs font-semibold text-slate-700 transition-colors disabled:opacity-50">
                        <span x-show="!memuat">↻ Muat ulang</span>
                        <span x-show="memuat">Memuat…</span>
                    </button>
                </div>

                <p x-show="dariCache && !memuat && !galat && !catatan" class="text-[10px] text-slate-400 -mt-1">
                    Daftar dari simpanan sementara. Tekan “Muat ulang” bila ada grup baru.
                </p>

                {{-- Muat ulang gagal tapi daftar lama masih ada: tampilkan daftarnya,
                     jangan dikosongkan, dan beri tahu bahwa datanya belum tersegarkan. --}}
                <p x-show="catatan && !memuat && !galat" x-text="catatan"
                   class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5 -mt-1"></p>

                <div x-show="memuat" class="flex items-center justify-center gap-2 py-8 text-xs text-slate-500 bg-slate-50 rounded">
                    <span class="h-5 w-5 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></span>
                    <span>Memuat daftar grup WhatsApp dari ponsel...</span>
                </div>

                <div x-show="!memuat && tersaring.length > 0" class="max-h-72 overflow-y-auto divide-y divide-slate-200 rounded border border-slate-200 bg-white">
                    <template x-for="g in tersaring" :key="g.id">
                        <label class="flex cursor-pointer items-center justify-between gap-3 p-3 hover:bg-indigo-50/50 transition-colors"
                               :class="terpilih.includes(g.id) ? 'bg-emerald-50/30' : ''">
                            <div class="flex items-center gap-3 min-w-0">
                                <input type="checkbox" name="groups[]" :value="g.id" x-model="terpilih"
                                       class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <div class="min-w-0">
                                    <span class="block truncate text-xs font-semibold text-slate-900" x-text="g.subject"></span>
                                    <span class="block text-[10px] text-slate-400" x-text="g.peserta + ' Anggota'"></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                {{-- Diagnosa, bukan uji kirim: tidak ada pesan yang masuk ke
                                     grup orang tua, jadi aman ditekan berkali-kali. --}}
                                <button type="button" x-show="terpilih.includes(g.id)"
                                        @click.prevent="periksa(g.id)" :disabled="memeriksa === g.id"
                                        class="text-[10px] font-semibold px-2 py-0.5 rounded-full border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 disabled:opacity-50">
                                    <span x-show="memeriksa !== g.id">Cek status</span>
                                    <span x-show="memeriksa === g.id">Memeriksa…</span>
                                </button>

                                <span x-show="terpilih.includes(g.id)"
                                      class="inline-flex items-center gap-1 text-[10px] font-semibold bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                    ✓ Terkoneksi (Aktif)
                                </span>
                                <span x-show="!terpilih.includes(g.id)"
                                      class="inline-flex items-center text-[10px] font-semibold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">
                                    Tidak Aktif
                                </span>
                            </div>
                        </label>
                    </template>
                </div>

                {{-- Gagal memuat BUKAN sama dengan tidak punya grup. Dulu keduanya
                     tampil sebagai pesan "tidak ditemukan grup" yang menyesatkan. --}}
                <div x-show="!memuat && galat" class="py-6 px-4 bg-rose-50 border border-rose-200 rounded text-xs text-rose-800 space-y-2">
                    <p class="font-semibold">Daftar grup gagal dimuat.</p>
                    <p x-text="galat"></p>
                    <button type="button" @click="muat(true)"
                            class="btn-danger btn-danger--sm">
                        Coba muat ulang
                    </button>
                </div>

                <div x-show="!memuat && !galat && tersaring.length === 0" class="py-8 text-center bg-slate-50 rounded text-xs text-slate-500">
                    Tidak ditemukan grup WhatsApp. Pastikan WhatsApp ponsel Anda sudah bergabung di grup.
                </div>

                </div>
                </template>

                {{-- Hasil "Cek status": daftar syarat yang bisa ditindaklanjuti guru,
                     bukan sekadar aktif/tidak. --}}
                <div x-show="hasilCek" x-cloak class="rounded border p-3 text-xs space-y-2"
                     :class="hasilCek && hasilCek.siap ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-semibold" :class="hasilCek && hasilCek.siap ? 'text-emerald-800' : 'text-amber-800'">
                            <span x-text="hasilCek && hasilCek.siap ? '✓ Siap membalas' : '⚠ Belum siap membalas'"></span>
                            — <span x-text="hasilCek ? hasilCek.grup : ''"></span>
                        </p>
                        <button type="button" @click.prevent="hasilCek = null" class="text-slate-400 hover:text-slate-600 font-semibold">✕</button>
                    </div>

                    <p x-show="hasilCek && hasilCek.pesan" x-text="hasilCek ? hasilCek.pesan : ''" class="text-amber-800"></p>

                    <template x-if="hasilCek && hasilCek.syarat">
                        <div class="space-y-1">
                            <template x-for="[kunci, label] in [
                                ['sesi_tersambung', 'Nomor WhatsApp tersambung'],
                                ['fitur_menyala', 'Balasan otomatis dinyalakan'],
                                ['grup_terdaftar', 'Grup ini terdaftar'],
                                ['dalam_jam_kerja', 'Sekarang di dalam jam aktif'],
                            ]" :key="kunci">
                                <p class="flex items-center gap-1.5 text-slate-700">
                                    <span x-text="hasilCek.syarat[kunci] ? '✓' : '✕'"
                                          :class="hasilCek.syarat[kunci] ? 'text-emerald-600' : 'text-rose-600'"
                                          class="font-semibold w-3"></span>
                                    <span x-text="label"></span>
                                </p>
                            </template>
                            <p class="flex items-center gap-1.5 text-slate-700">
                                <span class="font-semibold w-3"
                                      x-text="hasilCek.syarat.kuota_tersisa > 0 ? '✓' : '✕'"
                                      :class="hasilCek.syarat.kuota_tersisa > 0 ? 'text-emerald-600' : 'text-rose-600'"></span>
                                <span>Kuota harian tersisa
                                    <strong x-text="hasilCek.syarat.kuota_tersisa"></strong>
                                    dari <span x-text="hasilCek.kuota"></span>
                                </span>
                            </p>
                            <p class="text-[10px] text-slate-500 pt-1">
                                Jam aktif <strong x-text="hasilCek.jam"></strong>.
                                Pemeriksaan ini tidak mengirim pesan ke grup.
                            </p>
                        </div>
                    </template>
                </div>

                <div class="flex items-center justify-between border-t border-slate-200 pt-3">
                    <span class="text-xs text-slate-500">
                        Total terpilih: <strong class="text-indigo-600" x-text="terpilih.length"></strong> grup.
                    </span>
                    <button type="submit" class="btn-primary w-full">
                Simpan Perubahan Grup WA
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{--
        PENGINGAT IURAN BULANAN (SPP) — per kelas, bukan per guru.

        Ditaruh di halaman ini, bukan di Buku Kas, karena targetnya grup
        WhatsApp orang tua: seekor dengan seluruh pengaturan WA lain. Kelas
        ajar tidak muncul di sini (dikecualikan di controller) — iuran adalah
        urusan wali kelasnya.

        Isinya teks bebas dan TIDAK menyebut siapa yang belum membayar.
        Menyebut nama anak yang menunggak di grup yang dibaca seluruh orang
        tua adalah keputusan yang jauh lebih besar daripada teknisnya.
    --}}
    @if ($kelasWali->isNotEmpty())
        @foreach ($kelasWali as $kelas)
            <div class="card space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-200 pb-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded bg-emerald-100 text-emerald-700 shrink-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Pengingat Iuran Bulanan — {{ $kelas->name }}</h3>
                        <p class="text-xs text-slate-500">Terkirim otomatis ke grup WhatsApp orang tua kelas ini</p>
                    </div>
                </div>

                @if (! $kelas->parent_group_wa)
                    <p class="rounded border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        Grup WhatsApp orang tua kelas <strong>{{ $kelas->name }}</strong> belum diatur di halaman Kelas, jadi pengingat belum bisa dikirim.
                    </p>
                @else
                    <form method="POST" action="{{ route('classes.cashbook.pengingat', $kelas) }}" class="space-y-3">
                        @csrf

                        <label class="flex items-center gap-2.5 rounded border border-slate-200 bg-slate-50 p-3">
                            <input type="hidden" name="spp_pengingat_aktif" value="0">
                            <input type="checkbox" name="spp_pengingat_aktif" value="1"
                                   @checked($kelas->spp_pengingat_aktif)
                                   class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-semibold text-slate-800">Kirim pengingat tiap bulan</span>
                        </label>

                        <div>
                            <label for="spp_pengingat_tanggal_{{ $kelas->id }}" class="block eyebrow mb-1">Tanggal kirim</label>
                            <input id="spp_pengingat_tanggal_{{ $kelas->id }}" type="number" name="spp_pengingat_tanggal" min="1" max="31"
                                   value="{{ old('spp_pengingat_tanggal', $kelas->spp_pengingat_tanggal) }}"
                                   class="h-9 w-24 rounded border border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-800 focus:border-emerald-500 focus:outline-none">
                            <span class="text-[11px] text-slate-500">tiap bulan, pukul 07.00</span>
                            <p class="mt-1 text-[11px] text-slate-400">Bila bulannya lebih pendek, dikirim pada hari terakhir bulan itu.</p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label for="spp_pengingat_teks_{{ $kelas->id }}" class="block eyebrow mb-1">Isi pesan</label>
                                <span class="text-[11px] font-semibold text-emerald-700">Tag: {nama_kelas}, {bulan}, {tahun}, {wali_kelas}</span>
                            </div>
                            {{--
                                Pesan bawaan ditampilkan APA ADANYA di kotaknya,
                                bukan disembunyikan di balik placeholder "kosongkan
                                untuk bawaan" — guru berhak melihat persis apa yang
                                akan dikirim sebelum menyalakannya.
                            --}}
                            <textarea id="spp_pengingat_teks_{{ $kelas->id }}" name="spp_pengingat_teks" rows="7" maxlength="1000"
                                      class="form-textarea text-xs font-mono">{{ old('spp_pengingat_teks', $kelas->spp_pengingat_teks ?: \App\Console\Commands\KirimPengingatSpp::TEKS_BAWAAN) }}</textarea>
                        </div>

                        @if ($kelas->spp_pengingat_terkirim_pada)
                            <p class="text-[11px] text-slate-500">
                                Terakhir terkirim {{ $kelas->spp_pengingat_terkirim_pada->translatedFormat('d F Y') }}.
                            </p>
                        @endif

                        <button type="submit" class="h-9 w-full rounded bg-emerald-600 text-xs font-semibold text-white transition-colors hover:bg-emerald-700">
                            Simpan pengaturan pengingat
                        </button>
                    </form>
                @endif
            </div>
        @endforeach
    @endif

    <!-- CUSTOM KEYWORDS & TEMPLATE FORM -->
    <div class="card space-y-6">
        <div class="border-b border-slate-200 pb-3">
            <h3 class="text-base font-semibold text-slate-900">Klasifikasi Kata Kunci &amp; Templat Balasan Guru</h3>
            <p class="mt-1 text-sm text-slate-500">Tentukan sendiri kata kunci yang dijadikan patokan balasan otomatis. Pesan di luar kata kunci ini <strong>TIDAK AKAN DIBALAS</strong>.</p>
        </div>

        <form method="POST" action="{{ route('whatsapp.template.save') }}" class="space-y-6 text-xs">
            @csrf

            <!-- KEYWORDS CONFIGURATION -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-indigo-50/50 p-4 rounded border border-indigo-100">
                {{--
                    Kolom ini TAMBAHAN, bukan daftar utama.

                    Dulu kolomnya diisi otomatis dengan daftar contoh, dan
                    apa pun yang tersimpan MENGGANTIKAN daftar bawaan di
                    gateway. Akibatnya guru yang cuma membuka halaman ini lalu
                    menekan Simpan diam-diam mematikan puluhan kata bawaan —
                    "muntah", "takziyah", "ke luar kota", dan seluruh kata
                    Sunda berhenti dikenali tanpa ada yang tahu sebabnya.

                    Sekarang bawaan selalu berlaku dan isian di sini menambah,
                    jadi kolomnya sengaja DIBIARKAN KOSONG.
                --}}
                <div class="md:col-span-2 flex items-start gap-2 text-[11px] text-indigo-900">
                    <span>ℹ </span>
                    <p>Kata umum seperti <span class="font-mono">izin, sakit, demam, acara, takziyah</span> — termasuk kata Sunda seperti <span class="font-mono">gering, muriang, teu tiasa</span> — <strong>sudah dikenali otomatis</strong>. Kolom di bawah hanya untuk menambah kata khas daerah atau kebiasaan grup Anda. Boleh dikosongkan.</p>
                </div>

                <div class="space-y-1.5">
                    <label class="block font-semibold text-indigo-950 uppercase tracking-wider text-[11px]">1. Tambahan Kata Kunci IZIN <span class="text-indigo-600 font-normal lowercase">(pisahkan koma, boleh kosong)</span></label>
                    <input type="text" name="wa_permission_keywords"
                           value="{{ auth()->user()->wa_permission_keywords }}"
                           placeholder="mis. wangsul, ngalayad..."
                           class="w-full rounded border border-indigo-200 bg-white p-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none font-mono">
                    <p class="text-[10px] text-slate-500">Ditambahkan ke kata bawaan, bukan menggantikannya.</p>
                </div>

                <div class="space-y-1.5">
                    <label class="block font-semibold text-indigo-950 uppercase tracking-wider text-[11px]">2. Tambahan Kata Kunci SAKIT <span class="text-indigo-600 font-normal lowercase">(pisahkan koma, boleh kosong)</span></label>
                    <input type="text" name="wa_sick_keywords"
                           value="{{ auth()->user()->wa_sick_keywords }}"
                           placeholder="mis. meriang, nyeri huntu..."
                           class="w-full rounded border border-indigo-200 bg-white p-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none font-mono">
                    <p class="text-[10px] text-slate-500">Ditambahkan ke kata bawaan, bukan menggantikannya.</p>
                </div>
            </div>

            {{--
                Dua kolom ini dulu satu kolom yang DITULIS ke basis data lalu
                tidak pernah dibaca siapa pun — gateway bahkan tidak mengenal
                kata "template". Guru menyunting balasannya, menekan Simpan,
                melihat "berhasil disimpan", dan tidak ada yang berubah.

                Teks bawaannya juga menjanjikan "telah tercatat dalam sistem
                absensi", padahal balasan otomatis memang tidak menyentuh
                absensi sama sekali. Janji itu ikut dihapus.
            --}}
            <div class="space-y-2 border-t border-slate-200 pt-4">
                <div class="flex items-start justify-between gap-3">
                    <label class="block font-semibold text-slate-800 uppercase tracking-wider">Variasi Balasan Anda Sendiri</label>
                    <span class="text-[11px] font-semibold text-indigo-600 shrink-0">Tag: {nama}</span>
                </div>
                <div class="flex items-start gap-2 text-[11px] text-slate-600 bg-slate-50 rounded p-2.5">
                    <span> </span>
                    <p>Balasan sudah berganti-ganti otomatis dari 8 kalimat bawaan, jadi kolom ini <strong>boleh dikosongkan</strong>. Isi hanya bila ingin menambah kalimat dengan gaya bahasa Anda sendiri — <strong>satu kalimat per baris</strong>. Tulis <span class="font-mono">{nama}</span> untuk menyebut nama anaknya. Kalimat Anda <strong>ditambahkan</strong>, tidak menggantikan yang bawaan.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider">Untuk kabar IZIN</label>
                        <textarea name="wa_permission_template" rows="4"
                                  placeholder="Nuhun infona Bu, mugia urusan {nama} lancar.&#10;Baik Pak, izin {nama} sudah saya terima."
                                  class="form-textarea text-xs">{{ auth()->user()->wa_permission_template }}</textarea>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider">Untuk kabar SAKIT</label>
                        <textarea name="wa_sick_template" rows="4"
                                  placeholder="Mugia {nama} enggal damang nya Bu.&#10;Semoga {nama} lekas sehat, salam dari saya."
                                  class="form-textarea text-xs">{{ auth()->user()->wa_sick_template }}</textarea>
                    </div>
                </div>
            </div>

            <!-- TEMPLATE 2: MAGIC LINK ABSENSI BROADCAST -->
            <div class="space-y-2 border-t border-slate-200 pt-4">
                <div class="flex items-center justify-between">
                    <label class="block font-semibold text-slate-800 uppercase tracking-wider">Format Broadcast Magic Link Presensi Kelas</label>
                    <span class="text-[11px] font-semibold text-indigo-600">Tag: {nama_kelas}, {magic_link}, {pin}, {jam_berlaku}</span>
                </div>
                <textarea name="wa_magic_link_template" rows="5"
                          placeholder="Ketikkan templat pesan magic link..."
                          class="form-textarea text-xs">{{ auth()->user()->wa_magic_link_template ?: "*Wali Kelas Hebat - Absensi Kelas {nama_kelas}*\n\nPetugas absensi, silakan isi kehadiran hari ini melalui tautan berikut:\n{magic_link}\n\nPIN Harian: *{pin}*\nBerlaku s/d: {jam_berlaku} WIB\n\nJangan bagikan PIN ini ke siapa pun." }}</textarea>
            </div>

            <div class="flex items-center justify-end border-t border-slate-200 pt-4">
                <button type="submit" class="btn-primary">Simpan Kata Kunci &amp; Templat WhatsApp</button>
            </div>
        </form>
    </div>

</div>

<script>
    function waSession(cfg) {
        return {
            status: cfg.connected ? 'connected' : 'disconnected',
            qr: @json(session('wa_qr')),
            kodePairing: @json(session('wa_pairing_code')),
            timer: null,
            lastQrRendered: null,
            init() {
                if (this.qr) this.renderQr(this.qr);

                /*
                 * Polling hanya berguna selama menunggu guru memindai QR.
                 * Kalau sesi SUDAH tersambung saat halaman dibuka, tidak ada
                 * yang perlu ditunggu — dan menjalankan poll() di sini justru
                 * langsung memicu window.location.reload() di bawah, sehingga
                 * halaman memuat ulang tanpa henti.
                 */
                if (this.status === 'connected') return;

                this.poll();
                this.timer = setInterval(() => this.poll(), 2500);
            },
            label() {
                if (this.status === 'connected') return 'Tersambung ✓';
                // Menyebut "pemindaian QR" pada jalur kode akan menyuruh guru
                // melakukan hal yang justru tidak bisa ia lakukan.
                if (this.status === 'pairing') {
                    return this.kodePairing ? 'Menunggu Kode Dimasukkan' : 'Menunggu Pemindaian QR';
                }
                return 'Belum Tersambung';
            },
            renderQr(qrStr) {
                if (!qrStr || this.lastQrRendered === qrStr) return;
                this.lastQrRendered = qrStr;
                this.$nextTick(() => {
                    const canvas = document.getElementById('wa-qr-canvas-dynamic');
                    if (canvas && typeof qrcode === 'function') {
                        // Clear previous QR
                        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                        // Generate QR code using qrcode-generator library
                        const typeNumber = 0; // Auto-detect
                        const errorCorrectionLevel = 'M';
                        const qr = qrcode(typeNumber, errorCorrectionLevel);
                        qr.addData(qrStr);
                        qr.make();

                        // Calculate cell size to fit canvas
                        const cellSize = Math.floor(260 / qr.getModuleCount());
                        const qrSize = cellSize * qr.getModuleCount();

                        canvas.width = qrSize;
                        canvas.height = qrSize;

                        const ctx = canvas.getContext('2d');
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, qrSize, qrSize);
                        ctx.fillStyle = '#0f172a';

                        for (let row = 0; row < qr.getModuleCount(); row++) {
                            for (let col = 0; col < qr.getModuleCount(); col++) {
                                if (qr.isDark(row, col)) {
                                    ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize);
                                }
                            }
                        }
                    }
                });
            },
            async poll() {
                try {
                    const res = await fetch(cfg.statusUrl, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (data.status) {
                        this.status = data.status;
                        if (data.qr) {
                            this.qr = data.qr;
                            this.renderQr(data.qr);
                        }
                        /*
                         * Kode pairing terbit beberapa detik setelah /pair
                         * menjawab (Baileys harus berjabat tangan dulu). Tanpa
                         * dipungut di sini, guru yang menekan tombol tepat
                         * sebelum kodenya siap hanya melihat halaman diam.
                         */
                        if (data.pairing_code) {
                            this.kodePairing = data.pairing_code;
                        }
                        // Hanya tercapai bila halaman dibuka dalam keadaan belum
                        // tersambung, jadi ini benar-benar peralihan: QR baru
                        // saja dipindai. Muat ulang sekali untuk menampilkan
                        // tampilan tersambung, lalu berhenti.
                        if (data.status === 'connected') {
                            clearInterval(this.timer);
                            this.timer = null;
                            window.location.reload();
                        }
                    }
                } catch (e) {}
            }
        }
    }

    function autoreply(cfg) {
        return {
            grup: [],
            memuat: false,
            galat: '',
            catatan: '',
            dariCache: false,
            memeriksa: '',
            hasilCek: null,
            cari: '',
            terpilih: cfg.terpilih || [],

            // Nama grup yang sudah diingat server, dipakai agar pilihan
            // tersimpan bisa ditampilkan tanpa memindai. Berkunci JID.
            label: cfg.label || {},

            /*
             * 'ringkas' = tampilkan pilihan tersimpan, jangan memindai.
             * 'pilih'   = daftar grup lengkap untuk dipilih, perlu memindai.
             *
             * Guru yang sudah menyimpan pilihannya mulai dari 'ringkas'; yang
             * belum punya pilihan tidak akan bisa memilih apa pun tanpa daftar,
             * jadi baginya pemindaian memang tak terhindarkan.
             */
            mode: (cfg.terpilih || []).length > 0 ? 'ringkas' : 'pilih',
            sudahMuat: false,

            awal() {
                if (this.mode === 'pilih') this.muat();
            },

            /*
             * Daftar hasil pindai didahulukan karena paling baru; peta label
             * dari server menjadi cadangannya saat belum ada pemindaian sama
             * sekali. Kalau keduanya tidak tahu, JID mentah tetap ditampilkan —
             * lebih jujur daripada kosong.
             */
            infoGrup(id) {
                return this.grup.find((g) => g.id === id) || this.label[id] || null;
            },

            namaGrup(id) {
                const g = this.infoGrup(id);
                return g ? g.subject : id;
            },

            ketGrup(id) {
                const g = this.infoGrup(id);
                return g
                    ? g.peserta + ' Anggota'
                    : 'Nama grup belum tersimpan — tekan “Ubah pilihan grup” untuk memuatnya';
            },

            /*
             * Satu-satunya jalan dari 'ringkas' ke pemindaian, dan hanya sekali
             * per kunjungan: setelah daftarnya ada, menutup lalu membuka lagi
             * tidak menghubungi WhatsApp untuk kedua kalinya. Tombol
             * "Refresh Grup" tetap tersedia bila guru memang menambah grup baru.
             */
            async bukaPemilih() {
                this.mode = 'pilih';
                if (! this.sudahMuat) await this.muat();
            },

            get tersaring() {
                const kata = this.cari.trim().toLowerCase();
                if (!kata) return this.grup;

                return this.grup.filter((g) =>
                    g.subject.toLowerCase().includes(kata) || this.terpilih.includes(g.id)
                );
            },
            /*
             * Memeriksa apakah balasan otomatis akan benar-benar jalan untuk
             * satu grup. Tidak mengirim pesan apa pun — berbeda dari uji kirim.
             */
            async periksa(idGrup) {
                this.memeriksa = idGrup;
                this.hasilCek = null;
                try {
                    const res = await fetch('{{ route('whatsapp.autoreply.check') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ group_id: idGrup }),
                    });
                    const data = await res.json();

                    const nama = this.namaGrup(idGrup);

                    if (!data.ok) {
                        this.hasilCek = { grup: nama, siap: false, pesan: data.pesan || 'Pemeriksaan gagal.', syarat: null };
                        return;
                    }

                    this.hasilCek = {
                        grup: nama,
                        siap: data.siap,
                        syarat: data.syarat,
                        jam: data.jam,
                        kuota: data.kuota_harian,
                        terpakai: data.terpakai_hari_ini,
                        pesan: null,
                    };
                } catch (e) {
                    this.hasilCek = { grup: idGrup, siap: false, pesan: 'Tidak bisa menghubungi server.', syarat: null };
                } finally {
                    this.memeriksa = '';
                }
            },

            async muat(paksaSegar = false) {
                this.memuat = true;
                this.galat = '';
                try {
                    const url = cfg.grupUrl + (paksaSegar ? '?refresh=1' : '');
                    const res = await fetch(url, { headers: { Accept: 'application/json' } });
                    const data = await res.json();

                    // data.ok membedakan "pengambilan gagal" dari "memang tidak
                    // punya grup" — keduanya sama-sama berupa daftar kosong.
                    if (!data.ok) {
                        this.galat = data.warning || 'Gateway tidak merespons dengan benar.';
                        this.grup = [];
                        return;
                    }

                    // ok=true tapi ada warning berarti muat ulang gagal dan yang
                    // tampil adalah data terakhir — daftarnya tetap dipakai.
                    this.catatan = data.warning || '';
                    this.dariCache = data.cached === true;
                    this.grup = (data.groups || []).sort((a, b) => a.peserta - b.peserta);
                    this.sudahMuat = true;
                } catch (e) {
                    console.error('Gagal memuat grup:', e);
                    this.galat = 'Tidak bisa menghubungi server. Periksa koneksi lalu coba lagi.';
                    this.grup = [];
                } finally {
                    this.memuat = false;
                }
            }
        }
    }
</script>
@endsection
