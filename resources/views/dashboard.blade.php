@extends('layouts.app')

@section('title', 'Beranda Wali Kelas Hebat')

@section('content')
<div class="space-y-8 pb-16">

    <!-- WELCOME HERO GLASS BANNER -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 sm:p-8 text-white shadow-xl border border-slate-800">
        <div class="absolute right-0 top-0 -mr-16 -mt-16 h-64 w-64 rounded-full bg-emerald-500/10 blur-3xl"></div>
        <div class="absolute left-1/3 bottom-0 -mb-16 h-48 w-48 rounded-full bg-indigo-500/10 blur-2xl"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-400 border border-emerald-500/20">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span>Wali Kelas Hebat • Sistem Realtime Monitoring</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                    Selamat Datang, <span class="bg-gradient-to-r from-emerald-300 via-teal-200 to-indigo-200 bg-clip-text text-transparent">{{ auth()->user()->name }}</span>! 👋
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 max-w-xl">
                    Pantau rasio siswa, kehadiran harian, status kelengkapan biodata, dan portofolio karakter P5 secara realtime.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <a href="{{ route('classes.create') }}" class="h-11 px-5 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold text-xs transition-all shadow-lg shadow-emerald-500/20 active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>Buat Kelas Baru</span>
                </a>
                <a href="{{ route('whatsapp.index') }}" class="h-11 px-4 inline-flex items-center gap-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs border border-white/10 backdrop-blur-md transition-all">
                    <span>💬 Integrasi WA</span>
                </a>
            </div>
        </div>
    </div>

    {{--
        PAPAN TUGAS HARI INI.

        Ditaruh paling atas, sebelum kartu angka mana pun. Kartu angka menjawab
        "bagaimana keadaannya"; pertanyaan yang sebenarnya dibawa wali kelas
        saat membuka aplikasi jauh lebih sempit — "saya harus apa sekarang?" —
        dan sebelumnya ia harus membaca enam kartu, satu grafik, lalu
        menyimpulkan sendiri. Guru yang tidak terbiasa berhenti di langkah
        menyimpulkan.

        Saat tidak ada tugas, papannya TIDAK hilang. Ketiadaan tugas adalah
        kabar baik yang ingin dilihat, dan papan yang lenyap tanpa jejak
        membuat guru bertanya-tanya apakah ia melewatkan sesuatu.
    --}}
    <section aria-labelledby="judul-tugas" class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
        <div class="flex items-baseline justify-between gap-3 mb-4">
            <h2 id="judul-tugas" class="text-base font-extrabold tracking-tight text-slate-900">Yang perlu Anda kerjakan hari ini</h2>
            <span class="text-[11px] font-semibold text-slate-400">{{ now()->translatedFormat('l, d F') }}</span>
        </div>

        @forelse ($tugasHariIni as $t)
            @php
                // Kelas ditulis utuh, bukan dirakit dari potongan: Tailwind
                // memindai berkas ini sebagai teks dan tidak menjalankan PHP-nya.
                $gaya = [
                    'bahaya' => ['border-rose-200 bg-rose-50/60', 'text-rose-700', 'bg-rose-600 hover:bg-rose-700'],
                    'penting' => ['border-amber-200 bg-amber-50/60', 'text-amber-800', 'bg-amber-600 hover:bg-amber-700'],
                    'tenang' => ['border-slate-200 bg-slate-50/60', 'text-slate-600', 'bg-slate-700 hover:bg-slate-800'],
                ][$t['nada']];
            @endphp
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-2xl border {{ $gaya[0] }} p-4 mb-2.5 last:mb-0">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-slate-900">{{ $t['judul'] }}</p>
                    @if ($t['rinci'])
                        <p class="mt-0.5 text-xs {{ $gaya[1] }}">{{ $t['rinci'] }}</p>
                    @endif
                </div>
                <a href="{{ $t['tautan'] }}" class="shrink-0 h-10 px-5 inline-flex items-center justify-center rounded-xl {{ $gaya[2] }} text-xs font-bold text-white transition-colors active:scale-95">
                    {{ $t['aksi'] }}
                </a>
            </div>
        @empty
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 text-center">
                <p class="text-sm font-bold text-emerald-800">Tidak ada yang tertunda.</p>
                <p class="mt-1 text-xs text-emerald-700">Absensi hari ini sudah beres dan tidak ada siswa yang menunggu dibina.</p>
            </div>
        @endforelse
    </section>

    {{-- Saringan jenis kelas. Bentuk dan warnanya sengaja sama persis dengan
         saringan di Daftar Kelas: guru yang sudah mengenalinya di sana tidak
         perlu mempelajarinya lagi di sini. Baru muncul bila guru memang
         memegang kedua-duanya. --}}
    @if ($jumlahPerwalian > 0 && $jumlahAjar > 0)
        @php
            $saringan = [
                [null, 'Semua Kelas', $jumlahPerwalian + $jumlahAjar, 'border-slate-800 bg-slate-800 text-white'],
                ['perwalian', '🏫 Perwalian', $jumlahPerwalian, 'border-indigo-300 bg-indigo-600 text-white'],
                ['ajar', '📚 Guru Mapel', $jumlahAjar, 'border-teal-300 bg-teal-600 text-white'],
            ];
        @endphp
        <div class="flex flex-wrap gap-2">
            @foreach ($saringan as [$nilai, $label, $jumlah, $kelasAktif])
                @php $ini = $jenisDipilih === $nilai; @endphp
                <a href="{{ route('dashboard', $nilai ? ['jenis' => $nilai] : []) }}"
                   class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-bold transition-all {{ $ini ? $kelasAktif : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                    <span class="rounded-full px-2 py-0.5 text-[10px] tabular-nums {{ $ini ? 'bg-white/20' : 'bg-slate-100 text-slate-600' }}">{{ $jumlah }}</span>
                </a>
            @endforeach
        </div>
    @endif

    <!-- REALTIME STATS CARDS GRID -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        
        <!-- CARD 1: TOTAL KELAS -->
        <div class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md hover:border-indigo-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Kelas Binaan</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 font-bold text-sm">
                    🏫
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-1.5">
                <span class="text-2xl font-black text-slate-900">{{ $stats['classes'] ?? 0 }}</span>
                <span class="text-[11px] text-slate-500 font-medium">kelas</span>
            </div>
            <p class="mt-1 text-[10px] text-slate-400">Terdaftar di sistem</p>
        </div>

        <!-- CARD 2: SISWA & RASIO GENDER -->
        <div class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md hover:border-emerald-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Siswa</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 font-bold text-sm">
                    👨‍🎓
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-1.5">
                <span class="text-2xl font-black text-slate-900">{{ $stats['students'] ?? 0 }}</span>
                <span class="text-[11px] text-slate-500 font-medium">siswa</span>
            </div>
            <p class="mt-1 text-[10px] text-emerald-700 font-bold">
                👨 {{ $stats['siswa_laki'] ?? 0 }} L  &middot;  👩 {{ $stats['siswa_perempuan'] ?? 0 }} P
            </p>
        </div>

        <!-- CARD 3: REKAP KEHADIRAN HARI INI -->
        <div class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md hover:border-teal-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Presensi Hari Ini</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-600 font-bold text-sm">
                    📊
                </div>
            </div>
            @php($persenHariIni = $stats['persen'] ?? null)
            <div class="mt-3 flex items-baseline gap-1.5">
                {{-- Tanpa data, tampilkan strip. Angka apa pun di sini — 100% maupun
                     0% — adalah klaim tentang kehadiran yang belum pernah didata. --}}
                <span class="text-2xl font-black text-slate-900">{{ $persenHariIni === null ? '—' : $persenHariIni.'%' }}</span>
                @if ($persenHariIni === null)
                    <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded-full">Belum ada absensi</span>
                @else
                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full">Hadir {{ $stats['masuk'] ?? 0 }}</span>
                @endif
            </div>
            <p class="mt-1 text-[10px] font-semibold text-slate-500">
                💊 Sakit: <strong class="text-amber-600">{{ $stats['sakit'] ?? 0 }}</strong> | ✉️ Izin: <strong class="text-indigo-600">{{ $stats['izin'] ?? 0 }}</strong> | ❌ Alfa: <strong class="text-rose-600">{{ $stats['alfa'] ?? 0 }}</strong>
            </p>
        </div>

        {{--
            Tiga kartu berikut khas perwalian dan tidak dirender di mode Guru Mapel.

            Biodata orang tua, refleksi P5, dan buku poin adalah pekerjaan wali
            kelas; siswa kelas ajar bahkan tidak punya kolomnya terisi karena
            template impornya sengaja hanya NIS dan nama. Penyebutnya pun kelas
            perwalian saja — lihat DashboardController::angkaHariIni().
        --}}
        @if ($adaPerwalian)
        <!-- CARD 4: STATUS BIODATA MANDIRI % -->
        <div class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md hover:border-purple-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Biodata Terisi</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 font-bold text-sm">
                    📝
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-1.5">
                <span class="text-2xl font-black text-purple-950">{{ $stats['biodata_percent'] ?? 0 }}%</span>
                <span class="text-[10px] text-purple-700 font-bold">Terverifikasi</span>
            </div>
            <div class="mt-2 w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                <div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ $stats['biodata_percent'] ?? 0 }}%"></div>
            </div>
            {{-- Penyebutnya disebut terang-terangan. Persentase tanpa penyebut
                 tidak bisa diperiksa, dan angka inilah yang dulu salah. --}}
            <p class="mt-1.5 text-[10px] text-slate-400">dari {{ $stats['siswa_perwalian'] }} siswa perwalian</p>
        </div>

        <!-- CARD 5: PORTOFOLIO KARAKTER P5 % -->
        <div class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md hover:border-amber-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Portofolio P5</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 font-bold text-sm">
                    🎨
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-1.5">
                <span class="text-2xl font-black text-amber-950">{{ $stats['character_percent'] ?? 0 }}%</span>
                <span class="text-[10px] text-amber-700 font-bold">Refleksi P5</span>
            </div>
            <div class="mt-2 w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ $stats['character_percent'] ?? 0 }}%"></div>
            </div>
            <p class="mt-1.5 text-[10px] text-slate-400">dari {{ $stats['siswa_perwalian'] }} siswa perwalian</p>
        </div>

        <!-- CARD 6: SISWA PERLU PERHATIAN (EWS) -->
        <div class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition-all hover:shadow-md hover:border-rose-300">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">EWS Riskan</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 font-bold text-sm">
                    ⚠️
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-1.5">
                <span class="text-2xl font-black text-rose-950">{{ $perluPerhatian->count() }}</span>
                <span class="text-[11px] text-rose-600 font-semibold">siswa</span>
            </div>
            <p class="mt-1 text-[10px] text-slate-400">Alfa >= 3x / poin rendah</p>
        </div>
        @endif

    </div>

    <!-- INTERACTIVE CHART.JS ANALYTICS -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span class="text-indigo-600">📈</span> Grafik Tren Kehadiran 7 Hari Terakhir
            </h3>
            <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-full">Realtime Analytics</span>
        </div>
        <div class="h-64 relative">
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>

    <!-- QUICK ACTIONS HUB -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <span>⚡ Pintasan Akses Cepat</span>
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs font-bold">
            <a href="{{ route('classes.index') }}" class="flex flex-col items-center justify-center p-3.5 rounded-xl bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 border border-slate-200/70 transition-all text-slate-700 text-center gap-2">
                <span class="text-2xl">📋</span>
                <span>Daftar Kelas</span>
            </a>
            <a href="{{ route('whatsapp.index') }}" class="flex flex-col items-center justify-center p-3.5 rounded-xl bg-slate-50 hover:bg-emerald-50 hover:text-emerald-700 border border-slate-200/70 transition-all text-slate-700 text-center gap-2">
                <span class="text-2xl">💬</span>
                <span>WhatsApp WA</span>
            </a>
            <a href="{{ route('classes.index') }}" class="flex flex-col items-center justify-center p-3.5 rounded-xl bg-slate-50 hover:bg-indigo-50 hover:text-indigo-700 border border-slate-200/70 transition-all text-slate-700 text-center gap-2">
                <span class="text-2xl">📅</span>
                <span>Kelola Kelas</span>
            </a>
            <a href="{{ route('violation-types.index') }}" class="flex flex-col items-center justify-center p-3.5 rounded-xl bg-slate-50 hover:bg-rose-50 hover:text-rose-700 border border-slate-200/70 transition-all text-slate-700 text-center gap-2">
                <span class="text-2xl">⚖️</span>
                <span>Jenis Pelanggaran</span>
            </a>
            <a href="{{ $statusKelas->isNotEmpty() ? route('classes.reports.full', $statusKelas->first()['kelas']) : route('classes.index') }}" class="flex flex-col items-center justify-center p-3.5 rounded-xl bg-slate-50 hover:bg-amber-50 hover:text-amber-700 border border-slate-200/70 transition-all text-slate-700 text-center gap-2">
                <span class="text-2xl">📊</span>
                <span>Cetak Laporan</span>
            </a>
            <a href="{{ route('subscription.index') }}" class="flex flex-col items-center justify-center p-3.5 rounded-xl bg-slate-50 hover:bg-purple-50 hover:text-purple-700 border border-slate-200/70 transition-all text-slate-700 text-center gap-2">
                <span class="text-2xl">👑</span>
                <span>Langganan VIP</span>
            </a>
        </div>
    </div>

    {{-- Kedua panel ini membaca buku pembinaan wali kelas (EWS dan pelanggaran),
         jadi ikut disembunyikan di mode Guru Mapel bersama tiga kartu di atas.
         Menampilkannya sebagai panel kosong justru mengesankan datanya belum
         diisi, padahal memang bukan urusannya. --}}
    @if ($adaPerwalian)
    <!-- MAIN TWO-COLUMN SECTIONS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        {{-- id dipakai papan tugas di atas untuk melompat ke sini. --}}
        <!-- SECTION LEFT: SISWA PERLU PERHATIAN (EWS) -->
        <div id="perlu-perhatian" class="scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span class="text-rose-500">⚠️</span> Siswa Perlu Perhatian (EWS)
                </h3>
                <span class="text-xs font-semibold text-slate-400">{{ $perluPerhatian->count() }} Terdeteksi</span>
            </div>

            @if($perluPerhatian->isEmpty())
                <div class="py-8 text-center bg-emerald-50/50 rounded-xl border border-emerald-100">
                    <span class="text-3xl block mb-2">🎉</span>
                    <p class="text-xs font-bold text-emerald-800">Semua Siswa Dalam Kondisi Baik</p>
                    <p class="text-[11px] text-emerald-600 mt-0.5">Tidak ada indikasi siswa alfa >= 3x atau poin disiplin di bawah ambang batas.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($perluPerhatian->take(5) as $item)
                        <div class="py-3 flex items-center justify-between gap-3 text-xs">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900 truncate">{{ $item['siswa']->name }}</p>
                                <p class="text-[11px] text-slate-500 truncate">{{ $item['siswa']->classroom->name ?? '' }}</p>
                            </div>
                            <div class="flex flex-wrap gap-1 shrink-0">
                                @foreach($item['alasan'] as $alasan)
                                    <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-[10px] font-bold text-rose-700">{{ $alasan }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- SECTION RIGHT: PELANGGARAN TERBARU -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span class="text-indigo-600">📜</span> Catatan Disiplin &amp; Apresiasi Terbaru
                </h3>
                <a href="{{ route('violation-types.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">Kelola Jenis →</a>
            </div>

            @if($pelanggaranTerbaru->isEmpty())
                <div class="py-8 text-center bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-3xl block mb-2">✨</span>
                    <p class="text-xs font-bold text-slate-700">Belum Ada Catatan Disiplin</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Catatan poin pelanggaran &amp; apresiasi siswa akan muncul di sini.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($pelanggaranTerbaru->take(5) as $v)
                        <div class="py-3 flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $v->points >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-700' }} font-black text-xs">
                                    {{ $v->points >= 0 ? '+' : '' }}{{ $v->points }}
                                </span>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900 truncate">{{ $v->student->name ?? 'Siswa' }}</p>
                                    <p class="text-[11px] text-slate-500 truncate">{{ $v->type->name ?? $v->note ?: 'Catatan Disiplin' }}</p>
                                </div>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-400 shrink-0">{{ $v->occurred_on?->format('d M Y') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
    @endif

</div>

{{-- Chart.js dimuat di sini, bukan di layout: hanya halaman ini dan
     analitik yang punya grafik. Versi terpaku + integrity supaya isi
     yang berubah di CDN ditolak peramban, bukan dijalankan. --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
        integrity="sha384-e6nUZLBkQ86NJ6TVVKAeSaK8jWa3NhkYWZFomE39AvDbQWeie9PlQqM3pmYW5d1g"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('attendanceChart');
    if (!ctx) return;

    const rawData = @json($chartTrend ?? []);
    const labels = rawData.map(d => d.tanggal);
    const values = rawData.map(d => d.persen);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Persentase Kehadiran (%)',
                data: values,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#059669',
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    grid: { color: '#f1f5f9' },
                    ticks: { callback: v => v + '%' }
                },
                x: {
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>
@endsection
