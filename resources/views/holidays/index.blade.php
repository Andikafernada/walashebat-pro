@extends('layouts.app')

@section('title', 'Kalender Libur')

@section('content')
<div class="space-y-6 pb-12">
    <!-- Header Bar -->
    <div class="page-header">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600">Dashboard</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Kalender Libur</span>
            </nav>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">
                Kalender Libur &amp; Hari Efektif
            </h1>
            <p class="mt-1 text-sm text-slate-500">Kelola hari libur sekolah dan lihat daftar libur nasional resmi untuk perhitungan hari efektif.</p>
        </div>
    </div>

    <!-- Include Flash Messages -->
    @include('partials.flash')

    <!-- Top Metric Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Libur Sekolah Anda</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $mine->count() }}</p>
            <p class="mt-1 text-[11px] text-slate-400">Hari libur diinput mandiri</p>
        </div>

        <div class="card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Libur Nasional</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $national->count() }}</p>
            <p class="mt-1 text-[11px] text-slate-400">Libur resmi dari sistem</p>
        </div>

        <div class="card">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Hari Libur</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-indigo-600">{{ $mine->count() + $national->count() }}</p>
            <p class="mt-1 text-[11px] text-slate-400">Gabungan seluruh libur</p>
        </div>
    </div>

    <!-- Main Two-Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3" x-data="{ tab: 'mine' }">

        <!-- LEFT COLUMN: Holiday Lists (2/3 width) -->
        <div class="space-y-4 lg:col-span-2">
            <div class="card">

                <!-- Tab Selector Header -->
                <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-4">
                    <div class="flex items-center gap-2">
                        <button type="button" @click="tab = 'mine'"
                                :class="tab === 'mine' ? 'bg-indigo-600 text-white font-semibold rounded px-3.5 py-1.5 text-xs transition-colors' : 'bg-slate-100 text-slate-600 font-semibold hover:bg-slate-200 rounded px-3.5 py-1.5 text-xs transition-colors'">
                            Libur Sekolah Mandiri ({{ $mine->count() }})
                        </button>
                        <button type="button" @click="tab = 'national'"
                                :class="tab === 'national' ? 'bg-indigo-600 text-white font-semibold rounded px-3.5 py-1.5 text-xs transition-colors' : 'bg-slate-100 text-slate-600 font-semibold hover:bg-slate-200 rounded px-3.5 py-1.5 text-xs transition-colors'">
                            Libur Nasional Sistem ({{ $national->count() }})
                        </button>
                    </div>
                </div>

                <!-- Tab 1: Mine -->
                <div x-show="tab === 'mine'">
                    @if ($mine->isNotEmpty())
                        <div class="space-y-3">
                            @foreach ($mine as $h)
                                <div class="flex items-center justify-between gap-4 card hover:border-indigo-200 transition-colors">
                                    <div class="flex items-center gap-3.5">
                                        <!-- Date Icon -->
                                        <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded text-indigo-700 font-semibold border border-indigo-200/60">
                                            <span class="text-xs leading-none">{{ $h->start_date->format('d') }}</span>
                                            <span class="text-[10px] uppercase tracking-wider text-indigo-500 font-semibold mt-0.5">{{ $h->start_date->isoFormat('MMM') }}</span>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-sm text-slate-900">{{ $h->description }}</h4>
                                            <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
                                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                @if ($h->start_date->ne($h->end_date))
                                                    {{ $h->start_date->format('d/m/Y') }} s.d. {{ $h->end_date->format('d/m/Y') }}
                                                @else
                                                    {{ $h->start_date->format('d/m/Y') }} (1 Hari)
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('holidays.destroy', $h) }}"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus libur &quot;{{ $h->description }}&quot;?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="h-8 rounded-lg border border-rose-200 bg-rose-50 px-2.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition-colors flex items-center gap-1">
                                            <svg class="h-3.5 w-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="my-10 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-indigo-50 text-indigo-500 mb-3">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <p class="text-sm font-semibold text-slate-800">Belum Ada Libur Sekolah</p>
                            <p class="mt-1 text-xs text-slate-500">Gunakan formulir di samping untuk menambahkan hari libur sekolah mandiri.</p>
                        </div>
                    @endif
                </div>

                <!-- Tab 2: National -->
                <div x-show="tab === 'national'" x-cloak>
                    @if ($national->isNotEmpty())
                        <div class="space-y-3">
                            @foreach ($national as $h)
                                <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                                    <div class="flex items-center gap-3.5">
                                        <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded bg-emerald-100 text-emerald-800 font-semibold border border-emerald-200">
                                            <span class="text-xs leading-none">{{ $h->start_date->format('d') }}</span>
                                            <span class="text-[10px] uppercase tracking-wider text-emerald-600 font-semibold mt-0.5">{{ $h->start_date->isoFormat('MMM') }}</span>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-semibold text-sm text-slate-900">{{ $h->description }}</h4>
                                                <span class="rounded-sm bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800">Nasional</span>
                                            </div>
                                            <p class="mt-1 text-sm text-slate-500">
                                                @if ($h->start_date->ne($h->end_date))
                                                    {{ $h->start_date->format('d/m/Y') }} s.d. {{ $h->end_date->format('d/m/Y') }}
                                                @else
                                                    {{ $h->start_date->format('d/m/Y') }} (1 Hari)
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="my-10 text-center">
                            <p class="text-sm font-semibold text-slate-800">Belum Ada Daftar Libur Nasional</p>
                        </div>
                    @endif
                </div>

            </div>
        </div>

        <!-- RIGHT COLUMN: Add Form (1/3 width) -->
        <div class="space-y-4">
            <div class="card space-y-4">

                <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
                    <div class="stat-icon">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Tambah Libur Sekolah</h3>
                        <p class="text-xs text-slate-500">Input Hari Libur Khusus / Semester</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('holidays.store') }}" class="space-y-4" x-data="{ start: '', end: '', loading: false }" @submit="loading = true">
                    @csrf

                    <div>
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" id="start_date" name="start_date" x-model="start" @change="if(!end || end < start) end = start" required
                               class="form-input form-input--sm">
                    </div>

                    <div>
                        <label for="end_date" class="form-label">Tanggal Selesai</label>
                        <input type="date" id="end_date" name="end_date" x-model="end" :min="start" required
                               class="form-input form-input--sm">
                        <p class="mt-1 text-[10px] text-slate-400">Untuk libur 1 hari, pilih tanggal yang sama.</p>
                    </div>

                    <div>
                        <label for="description" class="form-label">Keterangan Libur</label>
                        <input type="text" id="description" name="description" value="{{ old('description') }}" placeholder="cth: Libur Semester Ganjil, Hardiknas..." required
                               class="form-input form-input--sm">
                    </div>

                    <button type="submit" :disabled="loading"
                            class="btn-primary w-full">
                        <template x-if="!loading">
                            <span class="flex items-center gap-1.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Tambahkan Hari Libur
                            </span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center gap-1.5">
                                <span class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                Menyimpan...
                            </span>
                        </template>
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection
