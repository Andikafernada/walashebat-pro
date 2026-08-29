@extends('layouts.app')

@section('title', 'Riwayat Absensi - ' . $classroom->name)

@section('content')
@php
    $rupaStatus = [
        'open' => ['Sedang Berjalan', 'bg-emerald-100 text-emerald-950 border-emerald-300'],
        'submitted' => ['Selesai', 'bg-emerald-50 text-emerald-900 border-emerald-200'],
        'expired' => ['Kadaluarsa', 'bg-slate-100 text-slate-800 border-slate-200'],
        'cancelled' => ['Dibatalkan', 'bg-slate-200 text-slate-900 border-slate-300'],
    ];
@endphp

<div class="space-y-6 pb-12">

    {{-- ══════════ 1. HEADER & ACTIONS ══════════ --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600 transition-colors">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-slate-600 transition-colors">{{ $classroom->name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500 font-medium">Presensi</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                Sesi &amp; Presensi Kelas {{ $classroom->name }}
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500">
                @if ($classroom->kelasAjar())
                    Riwayat presensi mata pelajaran yang Anda ampu di kelas {{ $classroom->name }}.
                @else
                    Kelola sesi presensi harian, broadcast link WhatsApp orang tua, dan PIN pengaman.
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a href="{{ route('classes.reports.attendance', $classroom) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all hover:scale-105">
                <span>📊</span>
                <span>Rekap Kehadiran</span>
            </a>

            <a href="{{ route('classes.attendance.manual.create', $classroom) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm shadow-emerald-200 transition-all hover:scale-105">
                <span>+</span>
                <span>{{ $classroom->kelasAjar() ? 'Isi Absensi Baru' : 'Input Absensi Manual' }}</span>
            </a>
        </div>
    </div>

    {{-- NAVIGASI KELAS --}}
    @include('partials.class-nav', ['classroom' => $classroom])

    @include('partials.flash')

    {{-- ══════════ 2. STATISTIK SESI & KUOTA (4 KPI CARDS) ══════════ --}}
    <dl class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">

        {{-- Kuota Hari Ini --}}
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Kuota Hari Ini</span>
                <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-950 border border-emerald-200 flex items-center justify-center text-xs font-bold">📲</div>
            </div>
            <dd class="text-xl sm:text-2xl font-extrabold text-slate-900">
                {{ $terpakaiHariIni }} <span class="text-sm font-normal text-slate-400">/ {{ $kuotaHarian }}</span>
            </dd>
            <div class="w-full bg-emerald-100 rounded-full h-1.5 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500 bg-emerald-600"
                     style="width: {{ min(100, ($terpakaiHariIni / max(1, $kuotaHarian)) * 100) }}%"></div>
            </div>
        </div>

        {{-- Total Sesi --}}
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Total Sesi</span>
                <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-950 border border-emerald-200 flex items-center justify-center text-xs font-bold">📋</div>
            </div>
            <dd class="text-xl sm:text-2xl font-extrabold text-slate-900">
                {{ $sessions->total() }}
            </dd>
            <p class="text-[11px] text-slate-500 truncate font-semibold">Sesi tercatat di kelas ini</p>
        </div>

        {{-- Total Siswa --}}
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Siswa Aktif</span>
                <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-950 border border-emerald-200 flex items-center justify-center text-xs font-bold">👥</div>
            </div>
            <dd class="text-xl sm:text-2xl font-extrabold text-slate-900">
                {{ $totalSiswa }}
            </dd>
            <p class="text-[11px] text-slate-500 truncate font-semibold">Total target absensi</p>
        </div>

        {{-- Status Sesi Hari Ini --}}
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Status Hari Ini</span>
                <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-950 border border-emerald-200 flex items-center justify-center text-xs font-bold">⚡</div>
            </div>
            <dd class="text-sm sm:text-base font-extrabold truncate flex items-center gap-1.5 text-slate-900">
                <span class="w-2 h-2 rounded-full shrink-0 {{ $adaSesiTerbuka ? 'bg-emerald-600 animate-pulse' : 'bg-slate-300' }}"></span>
                <span>{{ $adaSesiTerbuka ? 'Sesi Terbuka' : 'Belum Ada Sesi' }}</span>
            </dd>
            <p class="text-[11px] text-slate-500 truncate font-semibold">
                {{ $adaSesiTerbuka ? 'Siap diisi / diverifikasi' : 'Siap mulai presensi' }}
            </p>
        </div>

    </dl>

    {{-- ══════════ 3. GRID DAFTAR SESI & FORM BUAT SESI ══════════ --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Daftar Sesi --}}
        <section class="bg-white rounded-3xl border border-emerald-200/80 shadow-xs overflow-hidden lg:col-span-2">
            <div class="px-5 py-4 border-b border-emerald-100 bg-emerald-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-lg">📜</span>
                    <h2 class="text-sm font-bold text-slate-900">Riwayat Sesi Presensi</h2>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-950 border border-emerald-200">
                    {{ $sessions->total() }} Sesi
                </span>
            </div>

            @if ($sessions->isNotEmpty())
                <div class="divide-y divide-emerald-100">
                    @foreach ($sessions as $s)
                        @php
                            $terisi = $s->terisi_count ?? 0;
                            $hadir = $s->hadir_count ?? 0;
                            $persen = $totalSiswa > 0 ? round(($terisi / $totalSiswa) * 100) : 0;
                            [$labelStatus, $gayaStatus] = $rupaStatus[$s->status] ?? [$s->status, 'bg-slate-100 text-slate-800 border-slate-200'];
                        @endphp
                        <div class="p-4 hover:bg-emerald-50/30 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs font-bold bg-emerald-50 text-slate-800 border border-emerald-200 px-2 py-0.5 rounded-md">
                                        #{{ $s->sequence ?? 1 }}
                                    </span>
                                    <h3 class="text-sm font-bold text-slate-900 truncate">
                                        {{ $s->title ?: 'Presensi ' . $s->session_date->isoFormat('D MMMM YYYY') }}
                                    </h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $gayaStatus }}">
                                        {{ $labelStatus }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 font-medium">
                                    <span class="font-mono">{{ $s->session_date->format('d/m/Y') }}</span>
                                    @if ($s->expires_at)
                                        <span class="text-slate-400">&middot; s/d {{ $s->expires_at->format('H:i') }} WIB</span>
                                    @endif
                                    @if ($s->delivery_status === 'sent')
                                        <span class="inline-flex items-center gap-1 text-emerald-800 font-bold"><span>✓</span> WA Terkirim</span>
                                    @elseif ($s->delivery_status === 'failed')
                                        <span class="inline-flex items-center gap-1 text-slate-700 font-bold"><span>✕</span> WA Gagal</span>
                                    @elseif ($s->delivery_status === 'skipped')
                                        <span class="text-slate-600 font-medium">WA Manual</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-emerald-100">
                                <div class="w-24 text-right">
                                    <p class="font-mono text-xs font-bold text-slate-900">Terisi {{ $terisi }}/{{ $totalSiswa }}</p>
                                    <p class="text-[10px] font-bold text-emerald-800">Hadir {{ $hadir }}</p>
                                    <div class="w-full bg-slate-100 rounded-full h-1 mt-1 overflow-hidden">
                                        <div class="bg-emerald-600 h-full rounded-full" style="width: {{ $persen }}%"></div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('classes.attendance.edit', [$classroom, $s]) }}"
                                       class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition-colors">
                                        Koreksi
                                    </a>
                                    <a href="{{ route('classes.attendance.show', [$classroom, $s]) }}"
                                       class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-950 border border-emerald-200 text-xs font-bold transition-colors">
                                        Detail &amp; PIN &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($sessions->hasPages())
                    <div class="flex items-center justify-between border-t border-emerald-100 px-5 py-3.5 text-xs text-slate-500 bg-emerald-50/50">
                        <span class="font-medium text-slate-700">Menampilkan {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} dari {{ $sessions->total() }} sesi</span>
                        <div>{{ $sessions->links() }}</div>
                    </div>
                @endif
            @else
                <div class="p-10 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl mx-auto shadow-xs border border-emerald-200">📋</div>
                    <p class="text-sm font-bold text-slate-900">Belum ada sesi absensi yang dibuat</p>
                    <p class="text-xs text-slate-500 max-w-xs mx-auto">
                        Mulai presensi hari ini menggunakan formulir di samping untuk mengirimkan Magic Link otomatis.
                    </p>
                </div>
            @endif
        </section>

        {{-- Form Pembuatan Sesi Baru / Magic Link --}}
        <div>
            @if ($classroom->kelasAjar())
                <div class="bg-white rounded-3xl border border-emerald-200/80 p-6 shadow-xs space-y-4 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl mx-auto border border-emerald-200">✍️</div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-900">Presensi Guru Mapel</h3>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Sebagai guru mapel, Anda dapat langsung mencatat kehadiran siswa per jam pelajaran melalui form input cepat.
                        </p>
                    </div>
                    <a href="{{ route('classes.attendance.manual.create', $classroom) }}"
                       class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-200 transition-all flex items-center justify-center gap-1.5">
                        <span>📝</span>
                        <span>Isi Absensi Sekarang</span>
                    </a>
                </div>
            @else
                <div class="bg-white rounded-3xl border border-emerald-200/80 p-5 sm:p-6 shadow-xs space-y-4">
                    <div class="flex items-center gap-2 border-b border-emerald-100 pb-3">
                        <span class="text-xl">⚡</span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Buat Sesi Absensi Baru</h3>
                            <p class="text-[11px] text-slate-500 font-medium">Kirim otomatis via WhatsApp</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('classes.attendance.store', $classroom) }}" class="space-y-4">
                        @csrf

                        <div class="space-y-1.5">
                            <label for="session_date" class="block text-xs font-bold text-slate-900">Tanggal Presensi:</label>
                            <input id="session_date" name="session_date" type="date"
                                   value="{{ old('session_date', now()->toDateString()) }}"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            @error('session_date')
                                <p class="text-[11px] text-slate-900 font-bold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="title" class="block text-xs font-bold text-slate-900">Judul Sesi (Opsional):</label>
                            <input id="title" name="title" type="text"
                                   placeholder="Contoh: Presensi Pagi {{ now()->format('d/m/Y') }}"
                                   value="{{ old('title') }}"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>

                        <div class="space-y-1.5">
                            <label for="duration_minutes" class="block text-xs font-bold text-slate-900">Batas Waktu Buka:</label>
                            <select id="duration_minutes" name="duration_minutes"
                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 bg-white font-semibold text-slate-900">
                                <option value="60" {{ old('duration_minutes', 60) == 60 ? 'selected' : '' }}>1 Jam (Rekomendasi Pagi)</option>
                                <option value="120" {{ old('duration_minutes') == 120 ? 'selected' : '' }}>2 Jam</option>
                                <option value="240" {{ old('duration_minutes') == 240 ? 'selected' : '' }}>4 Jam</option>
                                <option value="480" {{ old('duration_minutes') == 480 ? 'selected' : '' }}>Sepanjang Hari (8 Jam)</option>
                            </select>
                        </div>

                        <button type="submit"
                                class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-200 transition-all flex items-center justify-center gap-1.5">
                            <span>🚀</span>
                            <span>Terbitkan Sesi &amp; Buat PIN</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
