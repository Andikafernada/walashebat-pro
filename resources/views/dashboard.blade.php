@extends('layouts.app')

@section('title', 'Dashboard Utama - ' . config('app.name'))

@section('content')
@php
    $hour = now()->hour;
    $sapaan = match(true) {
        $hour >= 4 && $hour < 11 => 'Selamat Pagi',
        $hour >= 11 && $hour < 15 => 'Selamat Siang',
        $hour >= 15 && $hour < 18 => 'Selamat Sore',
        default => 'Selamat Malam',
    };
@endphp

<div class="space-y-6 pb-12">

    {{-- ══════════ 1. HERO GREETING CARD (Bright Soft Emerald Gradient) ══════════ --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-700 text-white p-5 sm:p-7 shadow-lg shadow-emerald-600/15">
        {{-- Soft ambient glow --}}
        <div class="absolute -top-16 -right-16 w-64 h-64 bg-white/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-16 w-64 h-64 bg-emerald-200/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="space-y-2.5 max-w-xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-white/20 text-white backdrop-blur-md border border-white/25">
                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                    <span>WaliKelas Pro &middot; TA {{ now()->format('Y') }}/{{ now()->addYear()->format('Y') }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                    {{ $sapaan }}, {{ auth()->user()->name }}! 👋
                </h1>
                <p class="text-xs sm:text-sm text-emerald-50 leading-relaxed font-medium">
                    Sistem otomatisasi presensi WhatsApp, rekap nilai harian, dan administrasi kelas siap digunakan hari ini.
                </p>

                {{-- Aksi Pintas Hero --}}
                @if($kelasUtama ?? null)
                <div class="pt-2 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold text-emerald-100">Aksi Pintas ({{ $kelasUtama->name }}):</span>
                    <a href="{{ route('classes.attendance.index', $kelasUtama) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white text-emerald-950 hover:bg-emerald-50 text-xs font-extrabold shadow-xs transition-all hover:scale-105">
                        <span>📋</span> Absensi
                    </a>
                    <a href="{{ route('classes.students.index', $kelasUtama) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/20 text-white hover:bg-white/30 text-xs font-bold backdrop-blur-md transition-all hover:scale-105">
                        <span>👥</span> Siswa
                    </a>
                    <a href="{{ route('classes.character-portfolio.index', $kelasUtama) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/20 text-white hover:bg-white/30 text-xs font-bold backdrop-blur-md transition-all hover:scale-105">
                        <span>🌱</span> Karakter P5
                    </a>
                </div>
                @endif
            </div>

            {{-- Progress Box Kelengkapan Administrasi --}}
            <div class="w-full lg:w-72 bg-white/15 backdrop-blur-md border border-white/25 rounded-2xl p-4 space-y-3 shrink-0"
                 x-data="{ lebar: 0 }" x-init="setTimeout(() => lebar = {{ max($stats['biodata_percent'] ?? 85, 15) }}, 200)">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-base">📊</span>
                        <span class="text-xs font-bold text-white">Kelengkapan Data</span>
                    </div>
                    <span class="text-xs font-extrabold text-emerald-950 bg-white px-2 py-0.5 rounded-md shadow-2xs">{{ $stats['biodata_percent'] ?? 85 }}%</span>
                </div>
                <div class="w-full bg-black/20 rounded-full h-2.5 overflow-hidden p-0.5">
                    <div class="bg-white h-full rounded-full transition-all duration-1000 ease-out shadow-xs"
                         :style="'width: ' + lebar + '%'"></div>
                </div>
                <p class="text-[11px] text-emerald-50 leading-tight font-medium">
                    {{ $stats['siswa_perwalian'] ?? $stats['students'] ?? 0 }} siswa terdaftar di seluruh kelas Anda.
                </p>
            </div>
        </div>
    </div>

    {{-- ══════════ 2. STATISTIK UTAMA (4 KPI CARDS) ══════════ --}}
    <dl class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">

        {{-- KPI 1: Presensi Hari Ini --}}
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs hover:shadow-md transition-all space-y-2 group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Presensi Hari Ini</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-950 border border-emerald-200 flex items-center justify-center text-base group-hover:scale-110 transition-transform">📋</div>
            </div>
            <dd class="text-2xl sm:text-3xl font-extrabold text-slate-900"
                x-data="hitungNaik({{ (int) ($stats['persen'] ?? 0) }})" x-init="mulai()" x-text="tampil + '%'">
                {{ $stats['persen'] ?? 0 }}%
            </dd>
            <p class="text-[11px] text-slate-700 font-bold truncate">
                <span class="text-emerald-700">H {{ $stats['masuk'] ?? 0 }}</span> &middot;
                <span>S/I {{ ($stats['sakit'] ?? 0) + ($stats['izin'] ?? 0) }}</span> &middot;
                <span class="{{ ($stats['alfa'] ?? 0) > 0 ? 'text-slate-900 underline' : 'text-slate-500' }}">A {{ $stats['alfa'] ?? 0 }}</span>
            </p>
        </div>

        {{-- KPI 2: Biodata Siswa --}}
        @if($adaPerwalian ?? false)
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs hover:shadow-md transition-all space-y-2 group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Biodata Terisi</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-950 border border-emerald-200 flex items-center justify-center text-base group-hover:scale-110 transition-transform">👥</div>
            </div>
            <dd class="text-2xl sm:text-3xl font-extrabold text-slate-900"
                x-data="hitungNaik({{ (int) ($stats['biodata_percent'] ?? 0) }})" x-init="mulai()" x-text="tampil + '%'">
                {{ $stats['biodata_percent'] ?? 0 }}%
            </dd>
            <p class="text-[11px] text-slate-600 font-semibold truncate">
                dari {{ $stats['siswa_perwalian'] ?? 0 }} siswa perwalian
            </p>
        </div>

        {{-- KPI 3: Portofolio P5 --}}
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs hover:shadow-md transition-all space-y-2 group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Portofolio P5</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-950 border border-emerald-200 flex items-center justify-center text-base group-hover:scale-110 transition-transform">🌱</div>
            </div>
            <dd class="text-2xl sm:text-3xl font-extrabold text-slate-900"
                x-data="hitungNaik({{ (int) ($stats['character_percent'] ?? 0) }})" x-init="mulai()" x-text="tampil + '%'">
                {{ $stats['character_percent'] ?? 0 }}%
            </dd>
            <p class="text-[11px] text-slate-600 font-semibold truncate">
                dari {{ $stats['siswa_perwalian'] ?? 0 }} siswa perwalian
            </p>
        </div>
        @endif

        {{-- KPI 4: Total Siswa --}}
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs hover:shadow-md transition-all space-y-2 group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Total Siswa</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-950 border border-emerald-200 flex items-center justify-center text-base group-hover:scale-110 transition-transform">🏫</div>
            </div>
            <dd class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                {{ $stats['students'] ?? 0 }}
            </dd>
            <p class="text-[11px] text-slate-600 font-semibold truncate">
                terbagi dalam <span class="font-bold text-slate-900">{{ $stats['classes'] ?? 0 }} kelas</span> aktif
            </p>
        </div>

    </dl>

    {{-- SARINGAN JENIS KELAS --}}
    @if(($jumlahPerwalian ?? 0) > 0 && ($jumlahAjar ?? 0) > 0)
    <div class="flex items-center gap-2">
        <span class="text-xs text-slate-600 font-bold">Filter Dashboard:</span>
        <div class="inline-flex bg-emerald-100/70 p-1 rounded-2xl border border-emerald-200">
            <a href="{{ route('dashboard', ['jenis' => 'perwalian']) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all
                      {{ ($jenisDipilih ?? null) === 'perwalian' ? 'bg-white text-emerald-950 shadow-xs' : 'text-slate-700 hover:text-slate-900' }}">
                Perwalian ({{ $jumlahPerwalian }})
            </a>
            <a href="{{ route('dashboard', ['jenis' => 'ajar']) }}"
               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all
                      {{ ($jenisDipilih ?? null) === 'ajar' ? 'bg-white text-emerald-950 shadow-xs' : 'text-slate-700 hover:text-slate-900' }}">
                Guru Mapel ({{ $jumlahAjar }})
            </a>
        </div>
    </div>
    @endif

    {{-- ══════════ 3. TREN KEHADIRAN & RADAR P5 ══════════ --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">

        {{-- Line Chart Tren Kehadiran --}}
        <section class="bg-white rounded-3xl border border-emerald-200/80 p-5 shadow-xs lg:col-span-7">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-lg">📈</span>
                    <h2 class="text-sm font-bold text-slate-900">Tren Kehadiran Kelas</h2>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-950 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    Realtime
                </span>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="attendanceChart"></canvas>
            </div>
        </section>

        {{-- Radar Chart 6 Dimensi P5 --}}
        <section class="bg-white rounded-3xl border border-emerald-200/80 p-5 shadow-xs lg:col-span-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🌟</span>
                    <h2 class="text-sm font-bold text-slate-900">6 Dimensi Karakter P5</h2>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-950 border border-emerald-200">
                    Profil Pancasila
                </span>
            </div>
            <div class="relative h-64 w-full flex items-center justify-center">
                <canvas id="p5RadarChart"></canvas>
            </div>
        </section>

    </div>

    {{-- ══════════ 4. SISWA PERLU PERHATIAN (EWS) & DISIPLIN TERBARU ══════════ --}}
    @if ($adaPerwalian ?? true)
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- EWS Siswa Perlu Perhatian --}}
        <section class="bg-white rounded-3xl border border-emerald-200/80 overflow-hidden shadow-xs">
            <div class="px-5 py-4 border-b border-emerald-100 flex items-center justify-between bg-emerald-50/50">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🛡️</span>
                    <h2 class="text-sm font-bold text-slate-900">Siswa Perlu Perhatian (EWS)</h2>
                </div>
                @if(isset($kelasAktif) && $kelasAktif)
                    <a href="{{ route('classes.ews.index', $kelasAktif) }}" class="text-xs font-bold text-emerald-800 hover:underline">Buka EWS Penuh &rsaquo;</a>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-950 border border-emerald-200">
                        {{ ($perluPerhatian ?? collect())->count() }} Siswa
                    </span>
                @endif
            </div>

            @if(($perluPerhatian ?? collect())->isEmpty())
                <div class="p-8 text-center space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-800 flex items-center justify-center text-2xl mx-auto border border-emerald-200">✨</div>
                    <p class="text-sm font-bold text-slate-900">Kondisi Kelas Sangat Baik!</p>
                    <p class="text-xs text-slate-500">Tidak ada siswa dengan ketidakhadiran berisiko maupun poin rendah.</p>
                </div>
            @else
                <div class="divide-y divide-emerald-100">
                    @foreach(($perluPerhatian ?? collect())->take(5) as $item)
                        <div class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-emerald-50/40 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-950 font-bold text-xs flex items-center justify-center shrink-0 border border-emerald-200">
                                    {{ Str::upper(Str::substr($item['siswa']->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs sm:text-sm font-bold text-slate-900 truncate">{{ $item['siswa']->name }}</p>
                                    <p class="text-[11px] text-slate-500 truncate">{{ $item['siswa']->classroom->name ?? '' }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-1">
                                @foreach($item['alasan'] as $alasan)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-950 border border-emerald-200">
                                        {{ $alasan }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Catatan Disiplin Terbaru --}}
        <section class="bg-white rounded-3xl border border-emerald-200/80 overflow-hidden shadow-xs">
            <div class="px-5 py-4 border-b border-emerald-100 flex items-center justify-between bg-emerald-50/50">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🏅</span>
                    <h2 class="text-sm font-bold text-slate-900">Catatan Disiplin &amp; Poin</h2>
                </div>
                <a href="{{ route('violation-types.index') }}" class="text-xs font-bold text-emerald-800 hover:underline">Master Poin &rsaquo;</a>
            </div>

            @if(($pelanggaranTerbaru ?? collect())->isEmpty())
                <div class="p-8 text-center space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-800 flex items-center justify-center text-2xl mx-auto border border-emerald-200">🌱</div>
                    <p class="text-sm font-bold text-slate-900">Belum Ada Catatan Pelanggaran</p>
                    <p class="text-xs text-slate-500">Seluruh siswa berada dalam batas poin kedisiplinan yang baik.</p>
                </div>
            @else
                <div class="divide-y divide-emerald-100">
                    @foreach(($pelanggaranTerbaru ?? collect())->take(5) as $v)
                        <div class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-emerald-50/40 transition-colors">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-xs font-bold shrink-0 bg-emerald-100 text-emerald-950 border border-emerald-200">
                                    {{ ($v->points ?? 0) >= 0 ? '+' : '' }}{{ $v->points ?? 0 }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs sm:text-sm font-bold text-slate-900 truncate">{{ $v->student->name ?? 'Siswa' }}</p>
                                    <p class="text-[11px] text-slate-500 truncate">{{ $v->type->name ?? $v->note ?: 'Catatan Disiplin' }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 font-mono text-[10px] text-slate-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md font-bold">
                                {{ $v->occurred_on?->format('d/m/Y') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

    </div>
    @endif

    {{-- ══════════ 5. TUGAS & AGENDA HARI INI ══════════ --}}
    <section class="bg-white rounded-3xl border border-emerald-200/80 p-5 shadow-xs">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <span class="text-lg">📌</span>
                <h2 class="text-sm font-bold text-slate-900">Agenda &amp; Tugas Hari Ini</h2>
            </div>
            <span class="text-xs text-slate-500 font-semibold">{{ now()->translatedFormat('l, d F Y') }}</span>
        </div>

        <div class="divide-y divide-emerald-100">
            @forelse($tugasHariIni as $t)
                <div class="flex items-start gap-3 py-3 hover:bg-emerald-50/50 rounded-2xl px-2 transition-colors">
                    <span class="shrink-0 mt-1.5 w-2.5 h-2.5 rounded-full bg-emerald-600 shadow-2xs"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs sm:text-sm font-bold text-slate-900 truncate">{{ $t['judul'] }}</p>
                        @if(!empty($t['rinci']))
                            <p class="text-[11px] text-slate-600 truncate mt-0.5 font-medium">{{ $t['rinci'] }}</p>
                        @endif
                    </div>
                    @if(!empty($t['tautan']))
                        <a href="{{ $t['tautan'] }}"
                           class="shrink-0 text-xs font-bold text-emerald-950 hover:text-emerald-900 bg-emerald-100 hover:bg-emerald-200 px-3 py-1 rounded-xl transition-colors border border-emerald-200">
                            {{ $t['aksi'] ?? 'Buka' }} &rsaquo;
                        </a>
                    @endif
                </div>
            @empty
                <div class="text-center py-6 text-slate-400 space-y-1">
                    <p class="text-xl">🎉</p>
                    <p class="text-xs font-bold text-slate-700">Semua tugas hari ini sudah tuntas!</p>
                </div>
            @endforelse
        </div>
    </section>

</div>

<script src="/vendor/chart.umd.min.js?v=4.4.0"></script>

<script>
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
    // Line Chart Tren Kehadiran
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
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.12)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4.5,
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        min: 0, max: 100,
                        grid: { color: 'rgba(209, 250, 229, 0.6)' },
                        ticks: { callback: v => v + '%', font: { size: 10.5, weight: '700' }, color: '#064e3b' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10.5, weight: '700' }, color: '#064e3b' }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    // Radar Chart 6 Dimensi P5
    const ctxRadar = document.getElementById('p5RadarChart');
    if (ctxRadar) {
        new Chart(ctxRadar, {
            type: 'radar',
            data: {
                labels: ['Beriman', 'Berkebinekaan', 'Gotong Royong', 'Mandiri', 'Bernalar Kritis', 'Kreatif'],
                datasets: [{
                    label: 'Pencapaian P5 (%)',
                    data: [88, 92, 95, 80, 86, 90],
                    backgroundColor: 'rgba(16, 185, 129, 0.22)',
                    borderColor: '#10b981',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#047857',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1.5,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: { color: 'rgba(209, 250, 229, 0.8)' },
                        grid: { color: 'rgba(209, 250, 229, 0.8)' },
                        suggestedMin: 0,
                        suggestedMax: 100,
                        ticks: { display: false },
                        pointLabels: {
                            font: { size: 11, weight: '700' },
                            color: '#064e3b'
                        }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>
@endsection
