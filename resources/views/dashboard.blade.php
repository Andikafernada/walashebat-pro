@extends('layouts.app')

@section('content')
<div class="space-y-8">
    
    {{-- BARIS 1: GAMIFIKASI & PROFIL KELAS SUPER --}}
    <div class="animate-in animate-in-1 card-colorful p-6 sm:p-8 bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 text-white relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-black bg-purple-500/30 border border-purple-400/40 text-purple-200">
                    <span class="pulse-rainbow"></span>
                    <span>Wali Kelas Super — Level 5 (Pro)</span>
                </div>
                <h1 class="text-2xl sm:text-4xl font-black" style="font-family:'Plus Jakarta Sans',sans-serif;">
                    Selamat Datang, <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-400 via-purple-300 to-cyan-300">{{ auth()->user()->name }}</span>! 👋
                </h1>
                <p class="text-xs sm:text-sm text-slate-300">
                    Sistem otomatisasi presensi WA &amp; administrasi kelas Anda aktif dan siap digunakan.
                </p>
            </div>

            {{-- Progress Bar Kelengkapan Administrasi — mulai dari 0% lalu
                 bergerak ke angka aslinya sesaat setelah halaman dimuat,
                 bukan langsung diam di posisi akhir. --}}
            <div class="w-full md:w-72 bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 space-y-2.5"
                 x-data="{ lebar: 0 }" x-init="setTimeout(() => lebar = {{ max($stats['biodata_percent'] ?? 85, 15) }}, 150)">
                <div class="flex items-center justify-between text-xs font-bold">
                    <span class="text-purple-300">Kelengkapan Administrasi</span>
                    <span class="text-amber-400 font-extrabold">{{ $stats['biodata_percent'] ?? 85 }}%</span>
                </div>
                <div class="w-full bg-slate-700 rounded-full h-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-400 via-purple-400 to-pink-500 h-3 rounded-full transition-all duration-1000 ease-out motion-reduce:transition-none" :style="'width: ' + lebar + '%'"></div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-slate-400 font-semibold">
                    <span>🏆 3 Lencana Diraih</span>
                    <span>Siap Cetak PDF ✓</span>
                </div>
            </div>
        </div>
    </div>

    {{-- BARIS 2: STATISTIK RINGKASAN HARIAN — angka persen berhitung naik
         dari 0 ke nilai aslinya, bukan langsung muncul jadi. --}}
    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="animate-in animate-in-2 papan-tugas space-y-2">
            <dt class="stat-label">Kehadiran Hari Ini</dt>
            <dd class="stat-value text-emerald-600" x-data="hitungNaik({{ (int) ($stats['hadir_percent'] ?? 0) }})" x-init="mulai()" x-text="tampil + '%'">{{ $stats['hadir_percent'] ?? 0 }}%</dd>
            <p class="stat-sub">
                Hadir {{ $stats['hadir'] ?? 0 }} · Sakit/Izin {{ ($stats['sakit'] ?? 0) + ($stats['izin'] ?? 0) }} ·
                <strong class="{{ ($stats['alfa'] ?? 0) > 0 ? 'font-semibold text-rose-600' : '' }}">Alfa {{ $stats['alfa'] ?? 0 }}</strong>
            </p>
        </div>

        <div class="animate-in animate-in-3 papan-tugas space-y-2">
            <dt class="stat-label">Biodata Mandiri Siswa</dt>
            <dd class="stat-value text-indigo-600" x-data="hitungNaik({{ (int) ($stats['biodata_percent'] ?? 0) }})" x-init="mulai()" x-text="tampil + '%'">{{ $stats['biodata_percent'] ?? 0 }}%</dd>
            <p class="stat-sub">dari {{ $stats['siswa_perwalian'] ?? 0 }} siswa perwalian</p>
        </div>

        <div class="animate-in animate-in-4 papan-tugas space-y-2">
            <dt class="stat-label">Portofolio Karakter P5</dt>
            <dd class="stat-value text-amber-600" x-data="hitungNaik({{ (int) ($stats['character_percent'] ?? 0) }})" x-init="mulai()" x-text="tampil + '%'">{{ $stats['character_percent'] ?? 0 }}%</dd>
            <p class="stat-sub">dari {{ $stats['siswa_perwalian'] ?? 0 }} siswa perwalian</p>
        </div>

        <div class="animate-in animate-in-5 papan-tugas space-y-2">
            <dt class="stat-label">Status Gateway WhatsApp</dt>
            <dd class="text-xl font-black text-emerald-600 flex items-center gap-2 pt-1">
                {{-- Titik hidup ala siaran langsung: cincin memudar keluar
                     terus-menerus di belakang titik solid. --}}
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping motion-reduce:animate-none absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500 shadow-[0_0_10px_#10b981]"></span>
                </span>
                <span>Terhubung ✓</span>
            </dd>
            <p class="stat-sub">OTP &amp; Rekap WA Siap Kirim</p>
        </div>
    </dl>

    {{-- BARIS 3: TREN KEHADIRAN & RADAR CHART 6 DIMENSI P5 --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">

        {{-- Tren Kehadiran Line Chart --}}
        <section class="animate-in animate-in-6 blok lg:col-span-7">
            <div class="blok__kepala flex items-center justify-between">
                <h2 class="blok__judul text-base">📈 Tren Kehadiran (7 Hari Terakhir)</h2>
                <span class="text-xs font-bold text-indigo-600 bg-indigo-50 dark:bg-indigo-950 px-3 py-1 rounded-full">Realtime</span>
            </div>
            <div class="p-5">
                <div class="relative h-64">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </section>

        {{-- Radar Chart 6 Dimensi P5 Karakter --}}
        <section class="animate-in animate-in-7 blok lg:col-span-5">
            <div class="blok__kepala flex items-center justify-between">
                <h2 class="blok__judul text-base">🌟 Radar 6 Dimensi P5 Merdeka</h2>
                <span class="text-xs font-bold text-amber-600 bg-amber-50 dark:bg-amber-950 px-3 py-1 rounded-full">Profil Pancasila</span>
            </div>
            <div class="p-5 flex items-center justify-center">
                <div class="relative h-64 w-full">
                    <canvas id="p5RadarChart"></canvas>
                </div>
            </div>
        </section>

    </div>

    {{-- BARIS 4: SISWA PERLU PERHATIAN (EWS) & CATATAN DISIPLIN --}}
    @if ($adaPerwalian ?? true)
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- EWS Siswa Perlu Perhatian --}}
        <section id="perlu-perhatian" class="animate-in animate-in-8 blok scroll-mt-24">
            <div class="blok__kepala flex items-center justify-between">
                <h2 class="blok__judul text-base">🛡️ Early Warning System (Siswa Berisiko)</h2>
                <span class="badge {{ ($perluPerhatian ?? collect())->isEmpty() ? 'badge--emerald' : 'badge--rose' }}">{{ ($perluPerhatian ?? collect())->count() }} Siswa</span>
            </div>

            @if(($perluPerhatian ?? collect())->isEmpty())
                <p class="p-6 text-xs sm:text-sm text-slate-500 font-semibold text-center">
                    ✨ Luar biasa! Tidak ada siswa dengan Alfa ≥ 3× maupun poin disiplin di bawah ambang.
                </p>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach(($perluPerhatian ?? collect())->take(5) as $item)
                        <div class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $item['siswa']->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $item['siswa']->classroom->name ?? '' }}</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-1">
                                @foreach($item['alasan'] as $alasan)
                                    <span class="badge badge--rose">{{ $alasan }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Catatan Disiplin & Apresiasi --}}
        <section class="animate-in animate-in-8 blok">
            <div class="blok__kepala flex items-center justify-between">
                <h2 class="blok__judul text-base">🏅 Catatan Disiplin &amp; Apresiasi Siswa</h2>
                <a href="{{ route('violation-types.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">Kelola Jenis &rsaquo;</a>
            </div>

            @if(($pelanggaranTerbaru ?? collect())->isEmpty())
                <p class="p-6 text-xs sm:text-sm text-slate-500 font-semibold text-center">
                    Belum ada catatan disiplin. Poin pelanggaran dan apresiasi siswa akan muncul di sini.
                </p>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach(($pelanggaranTerbaru ?? collect())->take(5) as $v)
                        <div class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="badge {{ $v->points >= 0 ? 'badge--emerald' : 'badge--rose' }} shrink-0">
                                    {{ $v->points >= 0 ? '+' : '' }}{{ $v->points }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $v->student->name ?? 'Siswa' }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $v->type->name ?? $v->note ?: 'Catatan disiplin' }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 font-mono text-[10px] text-slate-400">{{ $v->occurred_on?->format('d/m/y') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

    </div>
    @endif

</div>

<script src="/vendor/chart.umd.min.js?v=4.4.0"></script>

<script>
/*
 * Komponen Alpine kecil: angka persen berhitung naik dari 0 ke nilai
 * aslinya begitu kartu tampil, bukan langsung muncul jadi. Dideklarasikan
 * di lingkup global (bukan di dalam DOMContentLoaded) karena harus sudah
 * ada SEBELUM Alpine memindai atribut x-data="hitungNaik(...)" pada kartu
 * di atas — dan skrip biasa (bukan module) dieksekusi begitu parser
 * sampai ke sini, lebih awal daripada modul Alpine yang ditunda.
 */
function hitungNaik(target, durasiMs = 900) {
    return {
        tampil: 0,
        mulai() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.tampil = target;
                return;
            }
            const mulaiWaktu = performance.now();
            const langkah = (ts) => {
                const progres = Math.min((ts - mulaiWaktu) / durasiMs, 1);
                this.tampil = Math.round(progres * target);
                if (progres < 1) requestAnimationFrame(langkah);
            };
            requestAnimationFrame(langkah);
        },
    };
}

document.addEventListener('DOMContentLoaded', function () {
    // 1. Line Chart Tren Kehadiran
    const ctx = document.getElementById('attendanceChart');
    if (ctx) {
        const rawData = @json($chartTrend ?? []);
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: rawData.map(d => d.tanggal),
                datasets: [{
                    label: 'Kehadiran (%)',
                    data: rawData.map(d => d.persen),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.12)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#ec4899',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        min: 0, max: 100,
                        grid: { color: 'rgba(226, 232, 240, 0.6)' },
                        ticks: { callback: v => v + '%', font: { size: 10, weight: 'bold' } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' } }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    // 2. Radar Chart 6 Dimensi P5 Merdeka
    const ctxRadar = document.getElementById('p5RadarChart');
    if (ctxRadar) {
        new Chart(ctxRadar, {
            type: 'radar',
            data: {
                labels: ['Beriman', 'Berkebinekaan', 'Gotong Royong', 'Mandiri', 'Bernalar Kritis', 'Kreatif'],
                datasets: [{
                    label: 'Pencapaian P5 (%)',
                    data: [88, 92, 95, 80, 86, 90],
                    backgroundColor: 'rgba(245, 158, 11, 0.25)',
                    borderColor: '#f59e0b',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#d97706',
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: { color: 'rgba(226, 232, 240, 0.6)' },
                        grid: { color: 'rgba(226, 232, 240, 0.6)' },
                        suggestedMin: 0,
                        suggestedMax: 100,
                        ticks: { display: false }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>
@endsection
