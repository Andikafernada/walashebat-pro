@extends('layouts.app')
@section('title', 'Detail Siswa')
@section('content')
    @include('partials.class-nav')

@php
    $gayaStatus = [
        'hadir' => ['Hadir', 'text-emerald-700', 'bg-emerald-50 border-emerald-200'],
        'terlambat' => ['Terlambat', 'text-orange-700', 'bg-orange-50 border-orange-200'],
        'sakit' => ['Sakit', 'text-amber-700', 'bg-amber-50 border-amber-200'],
        'izin' => ['Izin', 'text-sky-700', 'bg-sky-50 border-sky-200'],
        'alfa' => ['Alfa', 'text-rose-700', 'bg-rose-50 border-rose-200'],
    ];

    $q = request()->query();

    /*
     * Kelas ajar: guru mapel melihat kehadiran, bukan biodata dan bukan poin.
     *
     * Buku poin dipegang wali kelas; menampilkannya di sini membuat guru mapel
     * mengira dirinya ikut menegakkan sanksi, padahal angkanya berasal dari
     * catatan orang lain dan tidak bisa ia perbaiki. Biodata pun bukan haknya —
     * alamat, HP orang tua, dan pekerjaan ayah/ibu milik wali kelas aslinya.
     */
    $ajar = $classroom->kelasAjar();
@endphp

<div class="bento-grid">

    {{-- =========================================== --}}
    {{-- 1. HERO CARD (gradient glass-card)       --}}
    {{-- =========================================== --}}
    <div class="bento-wide hero-gradient animate-in">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                {{-- Beautiful Avatar with Gradient Ring --}}
                <div class="relative">
                    <div class="absolute -inset-1 rounded-full bg-gradient-to-br from-white/60 via-white/30 to-white/10 animate-pulse-subtle"></div>
                    <div class="avatar-hero
                        {{ $siswa->gender === 'P' ? 'avatar-hero--female' : 'avatar-hero--male' }}
                        {{ !$siswa->is_active ? 'avatar-hero--inactive' : '' }}">
                        @if ($siswa->photoUrl())
                            <img src="{{ $siswa->photoUrl() }}" alt=""
                                 class="avatar-hero-img">
                        @else
                            <span class="avatar-hero-initials">
                                {{ str($siswa->name)->substr(0, 1)->upper() }}
                            </span>
                        @endif
                    </div>
                    {{-- Status indicator ring --}}
                    @if ($siswa->is_active)
                        <span class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-4 w-4 rounded-full bg-emerald-500"></span>
                        </span>
                    @endif
                </div>
                <div class="min-w-0">
                    <h2 class="text-xl font-bold tracking-tight text-white">{{ $siswa->name }}</h2>
                    <p class="mt-0.5 text-sm text-white/80">
                        <span class="tabular-nums">NIS {{ $siswa->nis ?: '—' }}</span>
                        · <span class="tabular-nums">NISN {{ $siswa->nisn ?: '—' }}</span>
                        · {{ $classroom->name }}
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        @unless ($siswa->is_active)
                            <span class="badge bg-white/20 text-white backdrop-blur-sm border border-white/20">Nonaktif</span>
                        @endunless
                        @foreach ($peran ?? [] as $p)
                            <span class="badge bg-white/20 text-white backdrop-blur-sm border border-white/20">{{ $p->roleLabel() }}</span>
                        @endforeach
                        @if ($siswa->seat)
                            <span class="badge bg-white/20 text-white backdrop-blur-sm border border-white/20">
                                <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                Baris {{ $siswa->seat->row_index + 1 }}, kolom {{ $siswa->seat->col_index + 1 }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($siswa->parent_phone)
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $siswa->parent_phone) }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 rounded-xl bg-white/20 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/30 hover:scale-105 border border-white/10">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.5 14.4c-.3-.2-1.7-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.7 1-.9 1.2-.2.2-.3.2-.6.1-1.7-.9-2.9-1.6-4-3.5-.3-.5.3-.5.9-1.6.1-.2 0-.4 0-.5s-.7-1.6-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.2 5.1 4.5 1.9.8 2.6.9 3.5.7.6-.1 1.7-.7 1.9-1.4.2-.7.2-1.2.2-1.4-.1-.2-.3-.3-.6-.5z"/>
                            <path d="M12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 4.9L2 22l5.3-1.4c1.4.8 3 1.2 4.7 1.2 5.5 0 10-4.5 10-10S17.5 2 12 2zm0 18.2c-1.5 0-3-.4-4.3-1.2l-.3-.2-3.1.8.8-3-.2-.3C4.1 15 3.7 13.5 3.7 12c0-4.6 3.7-8.3 8.3-8.3s8.3 3.7 8.3 8.3-3.7 8.2-8.3 8.2z"/>
                        </svg>
                        Hubungi ortu
                    </a>
                @endif
                <a href="{{ route('classes.students.pdf', [$classroom, $siswa] + $q) }}" class="btn-secondary btn-secondary--sm bg-white/10 border-white/20 text-white hover:bg-white/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                    PDF
                </a>
                <a href="{{ route('classes.students.edit', [$classroom, $siswa]) }}" class="btn-primary btn-primary--sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit data
                </a>
            </div>
        </div>
    </div>

    {{-- =========================================== --}}
    {{-- 2. STAT GRID (5 glass stat cards)         --}}
    {{-- =========================================== --}}
    <div class="glass-card stat-card animate-in stagger-1 hover-lift">
        <div class="flex items-start justify-between">
            <div>
                <p class="bento-label">Kehadiran</p>
                <p class="bento-value--sm {{ $kehadiran['persen'] !== null && $kehadiran['persen'] >= 85 ? 'text-emerald-600' : ($kehadiran['persen'] !== null && $kehadiran['persen'] >= 75 ? 'text-amber-600' : ($kehadiran['persen'] !== null ? 'text-rose-600' : 'text-slate-400')) }}">
                    {{ $kehadiran['persen'] === null ? '—' : $kehadiran['persen'].'%' }}
                </p>
            </div>
            <div class="stat-icon stat-icon--{{ $kehadiran['persen'] !== null && $kehadiran['persen'] >= 85 ? 'emerald' : ($kehadiran['persen'] !== null && $kehadiran['persen'] >= 75 ? 'amber' : 'rose') }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="bento-progress mt-3">
            <div class="bento-progress-bar {{ $kehadiran['persen'] !== null && $kehadiran['persen'] >= 85 ? 'bento-progress-bar--emerald' : ($kehadiran['persen'] !== null && $kehadiran['persen'] >= 75 ? 'bento-progress-bar--amber' : 'bento-progress-bar--rose') }}" style="width: {{ $kehadiran['persen'] ?? 0 }}%"></div>
        </div>
        <p class="bento-sub mt-2">{{ $kehadiran['total'] }} kali tercatat</p>
    </div>

    <div class="glass-card stat-card animate-in stagger-2 hover-lift">
        <div class="flex items-start justify-between">
            <div>
                <p class="bento-label">Alfa</p>
                <p class="bento-value--sm {{ $kehadiran['jumlah']['alfa'] >= 3 ? 'text-rose-600' : 'text-slate-900' }}">
                    {{ $kehadiran['jumlah']['alfa'] }}
                </p>
            </div>
            <div class="stat-icon {{ $kehadiran['jumlah']['alfa'] >= 3 ? 'stat-icon--rose' : 'stat-icon--slate' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="bento-sub mt-4">S {{ $kehadiran['jumlah']['sakit'] }} · I {{ $kehadiran['jumlah']['izin'] }}</p>
    </div>

    <div class="glass-card stat-card animate-in stagger-3 hover-lift">
        <div class="flex items-start justify-between">
            <div>
                <p class="bento-label">Terlambat</p>
                <p class="bento-value--sm {{ $kehadiran['jumlah']['terlambat'] >= 5 ? 'text-orange-600' : 'text-slate-900' }}">
                    {{ $kehadiran['jumlah']['terlambat'] }}
                </p>
            </div>
            <div class="stat-icon {{ $kehadiran['jumlah']['terlambat'] >= 5 ? 'stat-icon--orange' : 'stat-icon--slate' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="bento-sub mt-4">
            {{ $kehadiran['jumlah']['terlambat'] >= 5 ? 'pola berulang' : 'kali periode ini' }}
        </p>
    </div>

    @unless ($ajar)
    <div class="glass-card stat-card animate-in stagger-4 hover-lift">
        <div class="flex items-start justify-between">
            <div>
                <p class="bento-label">Poin Disiplin</p>
                <p class="bento-value--sm {{ $poin['sekarang'] < 75 ? 'text-rose-600' : 'text-slate-900' }}">
                    {{ $poin['sekarang'] }}
                </p>
            </div>
            <div class="stat-icon stat-icon--{{ $poin['sekarang'] < 75 ? 'rose' : 'emerald' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
            </div>
        </div>
        <div class="bento-progress mt-3">
            <div class="bento-progress-bar {{ $poin['sekarang'] < 75 ? 'bento-progress-bar--rose' : 'bento-progress-bar--emerald' }}" style="width: {{ $poin['persen'] }}%"></div>
        </div>
        <p class="bento-sub mt-2">{{ $poin['sekarang'] }} / 100 poin</p>
    </div>

    <div class="glass-card stat-card animate-in stagger-5 hover-lift">
        <div class="flex items-start justify-between">
            <div>
                <p class="bento-label">Pelanggaran</p>
                <p class="bento-value--sm {{ $poin['kejadian'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">
                    {{ $poin['kejadian'] }}
                </p>
            </div>
            <div class="stat-icon {{ $poin['kejadian'] > 0 ? 'stat-icon--amber' : 'stat-icon--slate' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
        <p class="bento-sub mt-4">{{ $poin['periode'] }} poin periode ini</p>
    </div>
    @endunless

    {{-- =========================================== --}}
    {{-- 3. TREN CHART (glass-card)                --}}
    {{-- =========================================== --}}
    <div class="bento-medium glass-card animate-in hover-lift">
        <div class="mb-4 flex items-center gap-3">
            <div class="stat-icon stat-icon--primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Tren kehadiran 6 bulan</h3>
                <p class="text-xs text-slate-500">
                    Anak yang bulan ini 78% bisa sedang membaik dari 60%, atau sedang jatuh dari 95%.
                </p>
            </div>
        </div>
        <div class="flex items-end gap-2" style="height: 100px;">
            @foreach ($tren as $t)
                <div class="flex flex-1 flex-col items-center justify-end gap-1.5">
                    @if ($t['persen'] === null)
                        <div class="w-full rounded-t bg-slate-100" style="height: 4px;"></div>
                    @else
                        <span class="text-[10px] font-bold tabular-nums text-slate-600">{{ $t['persen'] }}</span>
                        <div class="w-full rounded-t transition-all
                            {{ $t['persen'] >= 85 ? 'bg-emerald-500' : ($t['persen'] >= 75 ? 'bg-amber-500' : 'bg-rose-500') }}"
                             style="height: {{ max(4, (int) ($t['persen'] * 0.85)) }}px"></div>
                    @endif
                    <span class="text-[10px] text-slate-400">{{ $t['label'] }}</span>
                    @if (($t['terlambat'] ?? 0) > 0)
                        <span class="text-[9px] font-semibold text-orange-500">{{ $t['terlambat'] }}T</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- =========================================== --}}
    {{-- 4. ATTENDANCE HISTORY (glass-card)         --}}
    {{-- =========================================== --}}
    <div class="bento-medium glass-card animate-in hover-lift">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="stat-icon stat-icon--sky">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Riwayat Kehadiran</h3>
            </div>
            @if ($kehadiran['dikoreksi'] > 0)
                <span class="badge badge--amber">{{ $kehadiran['dikoreksi'] }} dikoreksi</span>
            @endif
        </div>
        @if ($absensi->isEmpty())
            <div class="empty-state empty-state--compact">
                <p class="empty-state__title">Belum ada catatan kehadiran</p>
                <p class="empty-state__description">Pada periode {{ $periode['label'] }}.</p>
            </div>
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Tanggal</th><th>Status</th><th>Keterangan</th></tr></thead>
                    <tbody>
                    @foreach ($absensi as $a)
                        @php [$label, $warna, $latar] = $gayaStatus[$a->status] ?? ['—', 'text-slate-500', 'bg-slate-50 border-slate-200']; @endphp
                        <tr>
                            <td class="whitespace-nowrap td--secondary">
                                {{ $a->session?->session_date?->format('d/m/Y') ?? '—' }}
                                @if (($a->session->sequence ?? 1) > 1)
                                    <span class="text-[10px] text-slate-400">·{{ $a->session->sequence }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="inline-block rounded-lg border px-2 py-0.5 text-[11px] font-semibold {{ $warna }} {{ $latar }}">
                                    {{ $label }}
                                </span>
                                @if ($a->revisions_count > 0)
                                    <span class="badge badge--amber ml-1">dikoreksi</span>
                                @endif
                            </td>
                            <td class="td--secondary">{{ $a->note ?: '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @unless ($ajar)
    {{-- =========================================== --}}
    {{-- 5. VIOLATIONS (glass-card)                  --}}
    {{-- =========================================== --}}
    <div class="bento-medium glass-card animate-in hover-lift">
        <div class="mb-4 flex items-center gap-2">
            <div class="stat-icon stat-icon--red">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-slate-900">Riwayat Pelanggaran</h3>
        </div>
        @if ($pelanggaran->isEmpty())
            <div class="empty-state empty-state--compact">
                <p class="empty-state__title">Tidak ada pelanggaran</p>
                <p class="empty-state__description">Pada periode {{ $periode['label'] }}.</p>
            </div>
        @else
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Tanggal</th><th>Jenis</th><th class="text-right">Poin</th></tr></thead>
                    <tbody>
                    @foreach ($pelanggaran as $v)
                        <tr>
                            <td class="whitespace-nowrap td--secondary">{{ $v->occurred_on->format('d/m/Y') }}</td>
                            <td>
                                <span class="font-medium text-slate-900">{{ $v->type->name ?? '—' }}</span>
                                @if ($v->note)
                                    <span class="mt-0.5 block text-[11px] text-slate-500">{{ $v->note }}</span>
                                @endif
                            </td>
                            <td class="text-right font-semibold tabular-nums {{ $v->points < 0 ? 'td--danger' : 'td--success' }}">
                                {{ $v->points }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @endunless

    {{-- =========================================== --}}
    {{-- 6. PERIOD FILTER (glass-card)               --}}
    {{-- =========================================== --}}
    <div class="bento-item glass-card animate-in hover-lift">
        <form method="GET" class="flex flex-col gap-3" x-data="{ mode: '{{ $periode['mode'] }}' }">
            <div class="flex items-center gap-2">
                <div class="stat-icon stat-icon--primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900">Filter Periode</h3>
            </div>

            <div class="form-group mb-0">
                <label for="mode" class="form-label text-xs">Mode</label>
                <select id="mode" name="mode" x-model="mode" class="form-select form-input--sm">
                    <option value="bulan">Per bulan</option>
                    <option value="semester">Per semester</option>
                    <option value="rentang">Rentang tanggal</option>
                </select>
            </div>
            <div class="form-group mb-0" x-show="mode === 'bulan'">
                <label for="bulan" class="form-label text-xs">Bulan</label>
                <input type="month" id="bulan" name="bulan" value="{{ $periode['bulan']->format('Y-m') }}" class="form-input form-input--sm">
            </div>
            <template x-if="mode === 'semester'">
                <div class="space-y-3">
                    <div class="form-group mb-0">
                        <label for="sub_periode" class="form-label text-xs">Periode Semester</label>
                        <select id="sub_periode" name="sub_periode" class="form-select form-input--sm font-semibold">
                            <optgroup label="Semester 1 (Ganjil)">
                                <option value="1_tengah" @selected(($periode['sub_periode'] ?? '') === '1_tengah')>🌓 Tengah Sem. 1 (PTS/STS 1)</option>
                                <option value="1_akhir" @selected(($periode['sub_periode'] ?? '') === '1_akhir')>🌕 Akhir Sem. 1 (PAS/SAS 1)</option>
                                <option value="1_penuh" @selected(($periode['sub_periode'] ?? '') === '1_penuh' || (($periode['semester'] ?? 1) == 1 && empty($periode['sub_periode'])))>📘 Sem. 1 Penuh (Juli–Des)</option>
                            </optgroup>
                            <optgroup label="Semester 2 (Genap)">
                                <option value="2_tengah" @selected(($periode['sub_periode'] ?? '') === '2_tengah')>🌓 Tengah Sem. 2 (PTS/STS 2)</option>
                                <option value="2_akhir" @selected(($periode['sub_periode'] ?? '') === '2_akhir')>🌕 Akhir Sem. 2 (PAS/SAS 2)</option>
                                <option value="2_penuh" @selected(($periode['sub_periode'] ?? '') === '2_penuh' || (($periode['semester'] ?? 1) == 2 && empty($periode['sub_periode'])))>📘 Sem. 2 Penuh (Jan–Juni)</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label for="tahun" class="form-label text-xs">T.A. mulai</label>
                        <input type="number" id="tahun" name="tahun" min="2000" max="2100" value="{{ $periode['tahun'] }}" class="form-input form-input--sm">
                    </div>
                </div>
            </template>
            <template x-if="mode === 'rentang'">
                <div class="space-y-3">
                    <div class="form-group mb-0">
                        <label for="dari" class="form-label text-xs">Dari</label>
                        <input type="date" id="dari" name="dari" value="{{ request('dari', $awal->toDateString()) }}" class="form-input form-input--sm">
                    </div>
                    <div class="form-group mb-0">
                        <label for="sampai" class="form-label text-xs">Sampai</label>
                        <input type="date" id="sampai" name="sampai" value="{{ request('sampai', $akhir->toDateString()) }}" class="form-input form-input--sm">
                    </div>
                </div>
            </template>
            <button type="submit" class="btn-primary btn-primary--sm w-full justify-center">Terapkan</button>
            <p class="text-xs text-slate-500">Menampilkan: <strong class="text-slate-700">{{ $periode['label'] }}</strong></p>
        </form>
    </div>

    @unless ($ajar)
    {{-- =========================================== --}}
    {{-- 7. BIODATA (glass-card)                    --}}
    {{-- =========================================== --}}
    <div class="bento-medium glass-card animate-in hover-lift">
        <div class="mb-4 flex items-center gap-2">
            <div class="stat-icon stat-icon--indigo">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-slate-900">Biodata</h3>
        </div>

        @if ($kelengkapan['kosong'] !== [])
            <div class="alert alert--warning mb-4">
                <div>
                    <p class="alert__title">Data belum lengkap ({{ $kelengkapan['persen'] }}%)</p>
                    <p class="alert__body">
                        Belum terisi: {{ implode(', ', $kelengkapan['kosong']) }}.
                        <a href="{{ route('classes.students.edit', [$classroom, $siswa]) }}" class="font-semibold underline">Lengkapi sekarang</a>.
                    </p>
                </div>
            </div>
        @endif

        <dl class="grid gap-x-8 gap-y-3 sm:grid-cols-2">
            @foreach ([
                'Jenis kelamin' => $siswa->gender === 'L' ? 'Laki-laki' : ($siswa->gender === 'P' ? 'Perempuan' : null),
                'Tempat, tanggal lahir' => trim(($siswa->tempat_lahir ?: '').($siswa->tanggal_lahir ? ', '.\Illuminate\Support\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : ''), ', '),
                'Agama' => $siswa->agama,
                'Golongan darah' => $siswa->golongan_darah,
                'Anak ke' => $siswa->anak_ke ? $siswa->anak_ke.' dari '.($siswa->jumlah_saudara ?: '—').' bersaudara' : null,
                'Alamat' => $siswa->address,
                'HP siswa' => $siswa->phone,
                'HP orang tua' => $siswa->parent_phone,
                'Nama ayah' => trim(($siswa->nama_ayah ?: '').($siswa->pekerjaan_ayah ? ' ('.$siswa->pekerjaan_ayah.')' : '')),
                'Nama ibu' => trim(($siswa->nama_ibu ?: '').($siswa->pekerjaan_ibu ? ' ('.$siswa->pekerjaan_ibu.')' : '')),
                'Nama wali' => $siswa->nama_wali,
                'Asal sekolah' => $siswa->asal_sekolah,
                'Tahun masuk' => $siswa->tahun_masuk,
                'Kompetensi keahlian' => $siswa->kompetensi_keahlian,
                'Jarak rumah' => $siswa->jarak_rumah_km ? $siswa->jarak_rumah_km.' km' : null,
                'Transportasi' => $siswa->moda_transportasi,
                'Hobi' => $siswa->hobi,
                'Cita-cita' => $siswa->cita_cita,
            ] as $label => $nilai)
                <div class="flex justify-between gap-4 border-b border-slate-100 pb-2 sm:border-0 sm:pb-0">
                    <dt class="shrink-0 text-xs text-slate-500">{{ $label }}</dt>
                    <dd class="text-right text-sm {{ filled($nilai) ? 'font-medium text-slate-800' : 'text-slate-300' }}">
                        {{ filled($nilai) ? $nilai : '—' }}
                    </dd>
                </div>
            @endforeach
        </dl>

        @if ($siswa->penerima_kip || $siswa->penerima_pkh)
            <div class="mt-4 flex gap-2 border-t border-slate-100 pt-4">
                @if ($siswa->penerima_kip) <span class="badge badge--indigo">Penerima KIP</span> @endif
                @if ($siswa->penerima_pkh) <span class="badge badge--indigo">Penerima PKH</span> @endif
            </div>
        @endif
    </div>
    @endunless

</div>
@endsection
