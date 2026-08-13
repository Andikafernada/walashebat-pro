@extends('layouts.app')

@section('title', 'Jenis Pelanggaran')

@section('content')
<div class="space-y-6 pb-12">
    <!-- Header Bar -->
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600">Dashboard</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Master Jenis Pelanggaran</span>
            </nav>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">
                Jenis &amp; Bobot Pelanggaran
            </h1>
            <p class="mt-1 text-sm text-slate-500">Kelola daftar master jenis pelanggaran siswa dan akumulasi poin bobot kedisiplinan.</p>
        </div>
    </div>

    <!-- Include Flash Messages -->
    @include('partials.flash')

    <!-- Top Metric Cards -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="card">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Jenis</span>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $types->total() }}</p>
            <p class="mt-1 text-[11px] text-slate-400">Pelanggaran terdaftar</p>
        </div>

        <div class="rounded-lg border border-emerald-200/80 bg-emerald-50/50 p-4">
            <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wider block">Kategori Ringan</span>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-emerald-950">{{ $types->filter(fn($t) => strtolower($t->category) === 'ringan')->count() }}</p>
            <p class="mt-1 text-[11px] text-emerald-600">Bobot poin 1 - 10</p>
        </div>

        <div class="rounded-lg border border-amber-200/80 bg-amber-50/50 p-4">
            <span class="text-xs font-semibold text-amber-800 uppercase tracking-wider block">Kategori Sedang</span>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-amber-950">{{ $types->filter(fn($t) => strtolower($t->category) === 'sedang')->count() }}</p>
            <p class="mt-1 text-[11px] text-amber-600">Bobot poin 11 - 30</p>
        </div>

        <div class="rounded-lg border border-rose-200/80 bg-rose-50/50 p-4">
            <span class="text-xs font-semibold text-rose-800 uppercase tracking-wider block">Kategori Berat</span>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-rose-950">{{ $types->filter(fn($t) => strtolower($t->category) === 'berat')->count() }}</p>
            <p class="mt-1 text-[11px] text-rose-600">Bobot poin &gt; 30</p>
        </div>
    </div>

    <!-- Main Two-Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3" x-data="{ search: '', categoryFilter: 'all' }">

        <!-- LEFT COLUMN: Types List (2/3 width) -->
        <div class="space-y-4 lg:col-span-2">
            <div class="card">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 pb-4 mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Daftar Jenis Pelanggaran</h3>
                        <p class="text-xs text-slate-500">Master poin dan pengelompokan tingkat pelanggaran</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <input type="text" x-model="search" placeholder="Cari pelanggaran..."
                                   class="h-9 w-44 rounded border border-slate-200 bg-slate-50 pl-8 pr-3 text-xs text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none">
                            <svg class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <select x-model="categoryFilter" class="h-9 rounded border border-slate-200 bg-slate-50 px-3 text-xs font-medium text-slate-700 focus:border-indigo-500">
                            <option value="all">Semua Kategori</option>
                            <option value="ringan">Ringan</option>
                            <option value="sedang">Sedang</option>
                            <option value="berat">Berat</option>
                        </select>
                    </div>
                </div>

                @if ($types->isNotEmpty())
                    <div class="divide-y divide-slate-200">
                        @foreach ($types as $t)
                            @php
                                $cat = strtolower($t->category ?? 'ringan');
                            @endphp
                            <div class="flex items-center justify-between gap-4 py-3.5 hover:bg-slate-50 px-2 rounded transition-colors"
                                 x-show="(search === '' || '{{ strtolower($t->name) }}'.includes(search.toLowerCase())) && (categoryFilter === 'all' || categoryFilter === '{{ $cat }}')">

                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded font-semibold text-xs {{ $cat === 'ringan' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : '' }} {{ $cat === 'sedang' ? 'bg-amber-100 text-amber-800 border border-amber-200' : '' }} {{ $cat === 'berat' ? 'bg-rose-100 text-rose-800 border border-rose-200' : '' }}">
                                        {{ $t->points > 0 ? '+' : '' }}{{ $t->points }}
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-sm text-slate-900">{{ $t->name }}</h4>
                                        <p class="text-xs text-slate-400">Poin Bobot: {{ abs($t->points) }} Poin</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    @switch($cat)
                                        @case('ringan')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                                                Ringan
                                            </span>
                                            @break
                                        @case('sedang')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                                                Sedang
                                            </span>
                                            @break
                                        @case('berat')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-800">
                                                Berat
                                            </span>
                                            @break
                                        @default
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 capitalize">
                                                {{ $t->category }}
                                            </span>
                                    @endswitch

                                    @if(Route::has('violation-types.destroy'))
                                        <form method="POST" action="{{ route('violation-types.destroy', $t) }}" onsubmit="return confirm('Hapus jenis pelanggaran ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="h-8 w-8 rounded-lg border border-slate-200 hover:bg-rose-50 hover:text-rose-600 flex items-center justify-center text-slate-400 transition-colors">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>

                    @if ($types->hasPages())
                        <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                            <span>Menampilkan {{ $types->firstItem() }}–{{ $types->lastItem() }} dari {{ $types->total() }} data</span>
                            <div>{{ $types->links() }}</div>
                        </div>
                    @endif
                @else
                    <div class="my-10 text-center">
                        <p class="text-sm font-semibold text-slate-800">Belum Ada Jenis Pelanggaran</p>
                        <p class="mt-1 text-xs text-slate-500">Gunakan formulir di samping untuk menambahkan master pelanggaran baru.</p>
                    </div>
                @endif

            </div>
        </div>

        <!-- RIGHT COLUMN: Add Form (1/3 width) -->
        <div class="space-y-4" id="add-type-form">
            <div class="card space-y-4">

                <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
                    <div class="stat-icon">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Tambah Jenis Pelanggaran</h3>
                        <p class="text-xs text-slate-500">Input Nama &amp; Poin Pelanggaran Baru</p>
                    </div>
                </div>

                @if(Route::has('violation-types.store'))
                    <form method="POST" action="{{ route('violation-types.store') }}" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                        @csrf

                        <div>
                            <label for="name" class="form-label">Nama Pelanggaran</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="cth: Terlambat Masuk Sekolah, Tidak Pakai Seragam..." required
                                   class="form-input form-input--sm">
                        </div>

                        <div>
                            <label for="category" class="form-label">Kategori Pelanggaran</label>
                            <select id="category" name="category" required class="form-input form-input--sm">
                                <option value="ringan">Ringan (1 - 10 Poin)</option>
                                <option value="sedang">Sedang (11 - 30 Poin)</option>
                                <option value="berat">Berat (&gt; 30 Poin)</option>
                            </select>
                        </div>

                        <div>
                            <label for="points" class="form-label">Bobot Poin</label>
                            <input type="number" id="points" name="points" value="5" min="1" max="100" required
                                   class="form-input form-input--sm">
                        </div>

                        <button type="submit" :disabled="loading"
                                class="btn-primary w-full">
                            <template x-if="!loading">
                                <span class="flex items-center gap-1.5">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Simpan Jenis Pelanggaran
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
                @endif

            </div>
        </div>

    </div>
</div>
@endsection
