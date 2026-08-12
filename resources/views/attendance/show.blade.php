@extends('layouts.app')

@section('title', 'Detail Absensi - ' . $classroom->name)

@section('content')
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"
        integrity="sha384-lQXOAyZwHXE55JFyrOMB7nY2Wv+m5ZWNtJcHrd1rceRQXAYNLak8ukN5TjBTcIwz"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<div class="space-y-6 pb-12" x-data="{ copied: false }">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('classes.index') }}" class="hover:text-indigo-600 transition-colors">Kelas</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('classes.attendance.index', $classroom) }}" class="hover:text-indigo-600 transition-colors">{{ $classroom->name }}</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">Detail Absensi</span>
            </nav>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    {{ $session->title ?: 'Absensi ' . $session->session_date->isoFormat('D MMMM YYYY') }}
                </h1>
                
                @switch($session->status)
                    @case('open')
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200 shadow-sm">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                            Sesi Terbuka
                        </span>
                        @break
                    @case('submitted')
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 border border-blue-200">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Selesai
                        </span>
                        @break
                    @case('expired')
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 border border-amber-200">
                            Kadaluarsa
                        </span>
                        @break
                    @case('cancelled')
                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 border border-rose-200">
                            Dibatalkan
                        </span>
                        @break
                    @default
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            {{ $session->status }}
                        </span>
                @endswitch
            </div>
        </div>

        <!-- Action Header Buttons -->
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('classes.attendance.edit', [$classroom, $session]) }}" 
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:border-slate-400 active:scale-95">
                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Koreksi Manual
            </a>
            
            <a href="{{ route('classes.exports.attendance.pdf', $classroom) }}" target="_blank"
               class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-xs font-semibold text-indigo-700 shadow-sm transition-all hover:bg-indigo-100 active:scale-95">
                <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Cetak PDF
            </a>
        </div>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $classroom])

    <!-- Flash Messages -->
    @include('partials.flash')

    @php
        $sumLower = collect($summary)->keyBy(fn($v, $k) => strtolower($k));
        $totalSiswa = $summary['total'] ?? ($attendances->count() ?: ($classroom->students()->count()));
        $cntHadir = $sumLower->get('hadir', 0);
        $cntSakit = $sumLower->get('sakit', 0);
        $cntIzin = $sumLower->get('izin', 0);
        $cntAlfa = $sumLower->get('alfa', 0);
        $cntTerlambat = $sumLower->get('terlambat', 0);

        $totalTerisi = $cntHadir + $cntSakit + $cntIzin + $cntAlfa + $cntTerlambat;
        $persenTerisi = $totalSiswa > 0 ? round(($totalTerisi / $totalSiswa) * 100) : 0;
    @endphp

    <!-- Top Summary Banner & Progress -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 text-white shadow-xl">
        <div class="absolute right-0 top-0 -mr-16 -mt-16 h-64 w-64 rounded-full bg-indigo-500/10 blur-3xl"></div>
        <div class="relative z-10 grid grid-cols-1 gap-6 lg:grid-cols-4 lg:items-center">
            
            <div class="lg:col-span-1 border-b lg:border-b-0 lg:border-r border-white/10 pb-4 lg:pb-0 lg:pr-6">
                <p class="text-xs font-semibold tracking-wider text-indigo-300 uppercase">Sesi {{ $session->session_date->isoFormat('dddd') }}</p>
                <h3 class="mt-1 text-xl font-extrabold text-white">{{ $session->session_date->format('d/m/Y') }}</h3>
                <p class="mt-1 text-xs text-slate-300 flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Berlaku s/d {{ $session->expires_at ? $session->expires_at->format('H:i') . ' WIB' : '-' }}
                </p>
            </div>

            <div class="lg:col-span-3 space-y-3">
                <div class="flex items-center justify-between text-xs font-semibold">
                    <span class="text-indigo-200">Kemajuan Pengisian Kehadiran</span>
                    <span class="text-white bg-indigo-600/50 px-2.5 py-0.5 rounded-full border border-indigo-400/30">{{ $totalTerisi }} dari {{ $totalSiswa }} Siswa ({{ $persenTerisi }}%)</span>
                </div>
                <!-- Progress Bar -->
                <div class="h-3.5 w-full overflow-hidden rounded-full bg-white/10 p-0.5 backdrop-blur-sm border border-white/10 flex">
                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-teal-300 transition-all duration-500 shadow-sm" style="width: {{ $persenTerisi }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400">
                    <span>Target: Seluruh Siswa Kelas {{ $classroom->name }}</span>
                    <span>Urutan Sesi: #{{ $session->sequence ?? 1 }}</span>
                </div>
            </div>

        </div>
    </div>

    <!-- 4 Core Metrics Cards -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <!-- Hadir -->
        <div class="group relative overflow-hidden rounded-2xl border border-emerald-200/80 bg-gradient-to-b from-emerald-50/50 to-white p-4 shadow-sm transition-all hover:shadow-md hover:border-emerald-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Hadir</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-md shadow-emerald-500/20 group-hover:scale-110 transition-transform">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-black tracking-tight text-emerald-950">{{ $cntHadir }}</span>
                <span class="text-xs font-semibold text-emerald-700">siswa</span>
            </div>
            <p class="mt-2 text-[11px] font-medium text-emerald-600">
                {{ $totalSiswa > 0 ? round(($cntHadir / $totalSiswa) * 100) : 0 }}% dari total siswa
            </p>
        </div>

        <!-- Sakit -->
        <div class="group relative overflow-hidden rounded-2xl border border-amber-200/80 bg-gradient-to-b from-amber-50/50 to-white p-4 shadow-sm transition-all hover:shadow-md hover:border-amber-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-amber-800 uppercase tracking-wider">Sakit</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500 text-white shadow-md shadow-amber-500/20 group-hover:scale-110 transition-transform">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-black tracking-tight text-amber-950">{{ $cntSakit }}</span>
                <span class="text-xs font-semibold text-amber-700">siswa</span>
            </div>
            <p class="mt-2 text-[11px] font-medium text-amber-600">
                {{ $totalSiswa > 0 ? round(($cntSakit / $totalSiswa) * 100) : 0 }}% dari total siswa
            </p>
        </div>

        <!-- Izin -->
        <div class="group relative overflow-hidden rounded-2xl border border-blue-200/80 bg-gradient-to-b from-blue-50/50 to-white p-4 shadow-sm transition-all hover:shadow-md hover:border-blue-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-blue-800 uppercase tracking-wider">Izin</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-500 text-white shadow-md shadow-blue-500/20 group-hover:scale-110 transition-transform">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-black tracking-tight text-blue-950">{{ $cntIzin }}</span>
                <span class="text-xs font-semibold text-blue-700">siswa</span>
            </div>
            <p class="mt-2 text-[11px] font-medium text-blue-600">
                {{ $totalSiswa > 0 ? round(($cntIzin / $totalSiswa) * 100) : 0 }}% dari total siswa
            </p>
        </div>

        <!-- Alfa -->
        <div class="group relative overflow-hidden rounded-2xl border border-rose-200/80 bg-gradient-to-b from-rose-50/50 to-white p-4 shadow-sm transition-all hover:shadow-md hover:border-rose-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-rose-800 uppercase tracking-wider">Alfa</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500 text-white shadow-md shadow-rose-500/20 group-hover:scale-110 transition-transform">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-black tracking-tight text-rose-950">{{ $cntAlfa }}</span>
                <span class="text-xs font-semibold text-rose-700">siswa</span>
            </div>
            <p class="mt-2 text-[11px] font-medium text-rose-600">
                {{ $totalSiswa > 0 ? round(($cntAlfa / $totalSiswa) * 100) : 0 }}% dari total siswa
            </p>
        </div>
    </div>

    <!-- Main Two-Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3" x-data="{ search: '', filterStatus: 'all' }">
        
        <!-- LEFT COLUMN: Roster Kehadiran Siswa (2/3 width) -->
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                
                <!-- Table Header & Filters -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Daftar Kehadiran Siswa</h3>
                        <p class="text-xs text-slate-500">Hasil pengisian absensi siswa untuk kelas {{ $classroom->name }}</p>
                    </div>

                    <!-- Search & Filter Controls -->
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" x-model="search" placeholder="Cari nama siswa..." 
                                   class="h-9 w-48 rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3 text-xs text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>

                        <!-- Filter Status Tabs -->
                        <select x-model="filterStatus" class="h-9 rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs font-medium text-slate-700 focus:border-indigo-500 focus:outline-none">
                            <option value="all">Semua Status</option>
                            <option value="hadir">Hadir</option>
                            <option value="sakit">Sakit</option>
                            <option value="izin">Izin</option>
                            <option value="alfa">Alfa</option>
                        </select>
                    </div>
                </div>

                <!-- Student List -->
                @if ($session->attendances->isNotEmpty())
                    <div class="divide-y divide-slate-100 mt-2">
                        @foreach ($session->attendances as $index => $attendance)
                            @php
                                $student = $attendance->student;
                                $statusName = strtolower($attendance->status);
                            @endphp
                            <div class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between hover:bg-slate-50/80 rounded-xl px-2 transition-colors"
                                 x-show="(search === '' || '{{ strtolower($student->name ?? '') }}'.includes(search.toLowerCase()) || '{{ $student->nis ?? '' }}'.includes(search)) && (filterStatus === 'all' || filterStatus.toLowerCase() === '{{ $statusName }}')">
                                
                                <div class="flex items-center gap-3">
                                    <!-- Number / Avatar -->
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-slate-100 to-indigo-50 font-bold text-slate-700 text-xs border border-slate-200/60 shadow-xs">
                                        {{ $loop->iteration }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-sm text-slate-900">{{ $student->name ?? 'Siswa' }}</span>
                                            @if ($attendance->revisions_count > 0)
                                                <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-[10px] font-semibold text-purple-700 border border-purple-200">
                                                    Dikoreksi
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-500">NIS: {{ $student->nis ?? '-' }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-3 pl-13 sm:pl-0">
                                    {{--
                                        `note` (tunggal), bukan `notes`; dan jamnya dari
                                        `created_at`, bukan `filled_at` yang tidak pernah ada
                                        di tabel attendances. Eloquent mengembalikan NULL
                                        diam-diam untuk atribut yang tidak ada, jadi kedua blok
                                        ini tidak pernah muncul sekali pun — keterangan yang
                                        sudah susah payah diketik petugas ("Tidak ada kabar",
                                        dsb.) tersimpan rapi lalu tak pernah terbaca siapa pun.
                                    --}}
                                    @if ($attendance->note)
                                        <span class="text-xs italic text-slate-500 max-w-[180px] truncate" title="{{ $attendance->note }}">
                                            "{{ $attendance->note }}"
                                        </span>
                                    @endif

                                    @if ($attendance->created_at)
                                        <span class="text-[11px] font-mono text-slate-400">
                                            {{ $attendance->created_at->format('H:i') }}
                                        </span>
                                    @endif

                                    <!-- Status Badge -->
                                    @switch($statusName)
                                        @case('hadir')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100/80 px-3 py-1 text-xs font-bold text-emerald-800 border border-emerald-300/60">
                                                <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                Hadir
                                            </span>
                                            @break
                                        @case('sakit')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100/80 px-3 py-1 text-xs font-bold text-amber-800 border border-amber-300/60">
                                                Sakit
                                            </span>
                                            @break
                                        @case('izin')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100/80 px-3 py-1 text-xs font-bold text-blue-800 border border-blue-300/60">
                                                Izin
                                            </span>
                                            @break
                                        @case('alfa')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100/80 px-3 py-1 text-xs font-bold text-rose-800 border border-rose-300/60">
                                                Alfa
                                            </span>
                                            @break
                                        @case('terlambat')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-purple-100/80 px-3 py-1 text-xs font-bold text-purple-800 border border-purple-300/60">
                                                Terlambat
                                            </span>
                                            @break
                                        @default
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 capitalize">
                                                {{ $statusName }}
                                            </span>
                                    @endswitch
                                </div>

                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="my-10 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-500 mb-3">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 022 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-800">Belum ada data kehadiran terisi</p>
                        <p class="mt-1 text-xs text-slate-500">Siswa atau Seksi Absensi belum mengisi kehadiran via Magic Link.</p>
                    </div>
                @endif

            </div>
        </div>

        <!-- RIGHT COLUMN: Control Panel, Magic Link & PIN (1/3 width) -->
        <div class="space-y-5">
            
            <!-- Magic Link & PIN Card -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-md shadow-indigo-500/20">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Akses Absensi Siswa</h4>
                        <p class="text-xs text-slate-500">Magic Link &amp; Kode PIN Harian</p>
                    </div>
                </div>

                <!-- PIN DISPLAY BOX -->
                @if (!empty($pin))
                    <div class="relative overflow-hidden rounded-2xl border-2 border-amber-300 bg-gradient-to-b from-amber-50 to-amber-100/60 p-4 text-center shadow-xs">
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-200/80 px-2.5 py-0.5 text-[10px] font-bold text-amber-900 tracking-wider uppercase">
                            <svg class="h-3 w-3 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            PIN HARIAN SISWA
                        </span>
                        <div class="mt-2 text-4xl font-black tracking-widest text-amber-950 font-mono select-all">
                            {{ $pin }}
                        </div>
                        <p class="mt-1 text-[11px] font-medium text-amber-700">Tampilkan atau bagikan PIN 6-digit ini kepada siswa/seksi absensi.</p>
                        @if (!empty($waTarget))
                            <p class="mt-2 text-[11px] text-slate-500">Tujuan WA: <span class="font-bold text-slate-700">{{ $waTarget }}</span></p>
                        @endif
                    </div>
                @else
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-center">
                        <div class="flex items-center justify-center gap-2 text-xs font-semibold text-slate-600">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            PIN Harian Sudah Terbit
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">PIN sudah dikirimkan via WhatsApp. Klik "Kirim Ulang" bila memerlukan PIN baru.</p>
                    </div>
                @endif

                <!-- QR CODE CONTAINER -->
                <div class="flex flex-col items-center justify-center p-2 rounded-xl bg-slate-50 border border-slate-200/60" x-data="qrRenderer()">
                    <div class="p-2 bg-white rounded-lg shadow-xs border border-slate-100">
                        <canvas id="attendance-qr-canvas" width="120" height="120"></canvas>
                    </div>
                    <p class="mt-2 text-[11px] text-slate-500 font-medium">Pindai QR ini di depan kelas</p>
                </div>

                <!-- LINK COPY BOX -->
                <div class="rounded-xl bg-slate-50 p-3 border border-slate-200/60" x-data="{ copied: false }">
                    <label class="text-[11px] font-bold text-slate-600 uppercase tracking-wider block mb-1">Tautan Magic Link</label>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ $session->magicLink() }}" 
                               class="h-8 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-600 focus:outline-none truncate">
                        <button @click="navigator.clipboard.writeText('{{ $session->magicLink() }}'); copied=true; setTimeout(()=>copied=false, 2500)"
                                class="h-8 shrink-0 rounded-lg bg-indigo-600 px-3 text-xs font-bold text-white transition-all hover:bg-indigo-700 active:scale-95 flex items-center gap-1 shadow-xs">
                            <template x-if="!copied">
                                <span class="flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    Salin
                                </span>
                            </template>
                            <template x-if="copied">
                                <span class="flex items-center gap-1 text-emerald-200">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Tersalin!
                                </span>
                            </template>
                        </button>
                    </div>
                </div>

                <!-- WA DELIVERY STATUS CARD -->
                <div>
                    @if ($session->delivery_status === 'failed')
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-800">
                            <div class="flex items-center gap-2 font-bold text-rose-900 mb-1">
                                <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Pengiriman WA Gagal
                            </div>
                            <p class="text-rose-700 leading-snug">{{ $session->delivery_error }}</p>
                        </div>
                    @elseif ($session->delivery_status === 'pending')
                        <div class="flex items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-semibold text-amber-800">
                            <span class="h-2 w-2 rounded-full bg-amber-500 animate-ping"></span>
                            Menunggu Antrean Pengiriman WA...
                        </div>
                    @elseif ($session->delivery_status === 'sent')
                        <div class="flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-800">
                            <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Terkirim via WA {{ $session->delivered_at ? $session->delivered_at->format('H:i') : '' }}
                        </div>
                    {{--
                        Dibedakan tegas dari 'failed'. Pesan yang tidak dikirim
                        karena masa otomasi habis bukan kerusakan, dan menuliskan
                        "gagal" akan mengirim wali kelas mengejar masalah teknis
                        yang tidak ada — lalu menghubungi dukungan.
                    --}}
                    @elseif ($session->delivery_status === 'skipped')
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-900">
                            <div class="flex items-center gap-2 font-bold mb-1">
                                <svg class="h-4 w-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Tidak Dikirim ke WhatsApp
                            </div>
                            <p class="leading-snug">{{ $session->delivery_error }}</p>
                            <a href="{{ route('subscription.index') }}" class="mt-2 inline-flex items-center gap-1 font-bold text-amber-900 underline underline-offset-2">
                                Perpanjang langganan
                            </a>
                        </div>
                    @endif
                </div>

                <!-- SESSION MANAGEMENT ACTIONS -->
                <div class="pt-2 space-y-2 border-t border-slate-100">
                    @if ($session->isOpen() && $session->delivery_status !== 'sent')
                        <form method="POST" action="{{ route('classes.attendance.resend', [$classroom, $session]) }}">
                            @csrf
                            <button type="submit" class="w-full h-9 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-all active:scale-95 flex items-center justify-center gap-2 shadow-xs">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Kirim Ulang WA (PIN Baru)
                            </button>
                        </form>
                    @endif

                    @if ($session->status === 'open')
                        <form method="POST" action="{{ route('classes.attendance.cancel', [$classroom, $session]) }}"
                              onsubmit="return confirm('Apakah Anda yakin ingin membatalkan sesi absensi ini?')">
                            @csrf @method('PATCH')
                            <button type="submit" class="w-full h-9 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition-all active:scale-95 flex items-center justify-center gap-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                Batalkan Sesi Absensi
                            </button>
                        </form>
                    @endif
                </div>

            </div>

        </div>

    </div>
</div>

<script>
    function qrRenderer() {
        return {
            init() {
                const magicLink = '{{ $session->magicLink() }}';
                const canvas = document.getElementById('attendance-qr-canvas');
                if (canvas && typeof qrcode === 'function' && magicLink) {
                    const typeNumber = 0;
                    const errorCorrectionLevel = 'M';
                    const qr = qrcode(typeNumber, errorCorrectionLevel);
                    qr.addData(magicLink);
                    qr.make();

                    const cellSize = Math.floor(120 / qr.getModuleCount());
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
            }
        }
    }
</script>
@endsection
