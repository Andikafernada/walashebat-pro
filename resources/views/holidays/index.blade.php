@extends('layouts.app')

@section('title', 'Kalender Libur — ' . config('app.name'))

@section('content')
<div class="space-y-6 pb-12">
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600">Dashboard</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Kalender Libur</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                Kalender Libur &amp; Hari Efektif Sekolah
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-600 font-medium">Kelola jadwal libur sekolah dan pantau daftar hari libur nasional resmi.</p>
        </div>
    </div>

    @include('partials.flash')

    {{-- 3 STATS CARDS --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-3xl border border-emerald-200 bg-white p-5 shadow-xs space-y-1">
            <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider text-[10.5px]">Libur Khusus Sekolah</span>
            <p class="text-2xl font-extrabold text-slate-900">{{ $mine->count() }} <span class="text-xs font-semibold text-slate-500">hari/agenda</span></p>
            <p class="text-[11px] text-slate-500 font-medium">Diinput mandiri oleh sekolah</p>
        </div>

        <div class="rounded-3xl border border-emerald-200 bg-white p-5 shadow-xs space-y-1">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider text-[10.5px]">Libur Nasional Resmi</span>
            <p class="text-2xl font-extrabold text-slate-900">{{ $national->count() }} <span class="text-xs font-semibold text-slate-500">hari</span></p>
            <p class="text-[11px] text-slate-500 font-medium">Sinkronisasi kalender pemerintah</p>
        </div>

        <div class="rounded-3xl border border-emerald-200 bg-white p-5 shadow-xs space-y-1">
            <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider text-[10.5px]">Total Hari Libur</span>
            <p class="text-2xl font-extrabold text-slate-900">{{ $mine->count() + $national->count() }} <span class="text-xs font-semibold text-slate-500">hari</span></p>
            <p class="text-[11px] text-slate-500 font-medium">Tidak terhitung dalam hari efektif</p>
        </div>
    </div>

    {{-- DUAL-COLUMN LAYOUT: DAFTAR LIBUR (2/3) + FORM INPUT (1/3) --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- LEFT COLUMN: TABS DAFTAR LIBUR --}}
        <div class="space-y-4 lg:col-span-2" x-data="{ tab: 'mine' }">
            <div class="rounded-3xl border border-emerald-200 bg-white p-5 sm:p-6 shadow-xs space-y-4">

                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-emerald-100 pb-3">
                    <div class="flex items-center gap-1 bg-emerald-100/70 p-1 rounded-2xl border border-emerald-200">
                        <button type="button" @click="tab = 'mine'"
                                :class="tab === 'mine' ? 'bg-white text-emerald-950 shadow-xs' : 'text-slate-700 hover:text-slate-900'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all">
                            Libur Sekolah ({{ $mine->count() }})
                        </button>
                        <button type="button" @click="tab = 'national'"
                                :class="tab === 'national' ? 'bg-white text-emerald-950 shadow-xs' : 'text-slate-700 hover:text-slate-900'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all">
                            Libur Nasional ({{ $national->count() }})
                        </button>
                    </div>
                </div>

                {{-- Tab 1: Libur Sekolah Mandiri --}}
                <div x-show="tab === 'mine'">
                    @if ($mine->isNotEmpty())
                        <div class="divide-y divide-emerald-100/70">
                            @foreach ($mine as $h)
                                <div class="flex items-center justify-between gap-3 py-3.5 hover:bg-emerald-50/40 px-3 rounded-2xl transition-colors">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-950 font-bold text-xs flex flex-col items-center justify-center shrink-0 border border-emerald-200">
                                            <span class="text-sm font-extrabold leading-none">{{ $h->start_date->format('d') }}</span>
                                            <span class="text-[9px] uppercase tracking-wider text-emerald-800 font-bold mt-0.5">{{ $h->start_date->isoFormat('MMM') }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="font-bold text-sm text-slate-900 truncate">{{ $h->description }}</h4>
                                                <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-950 border border-emerald-200 text-[10px] font-extrabold">Sekolah</span>
                                            </div>
                                            <p class="text-xs text-slate-500 font-medium mt-0.5">
                                                @if ($h->start_date->ne($h->end_date))
                                                    {{ $h->start_date->format('d/m/Y') }} s.d. {{ $h->end_date->format('d/m/Y') }}
                                                @else
                                                    {{ $h->start_date->format('d/m/Y') }} (1 Hari)
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('holidays.destroy', $h) }}"
                                          onsubmit="return confirm('Hapus libur &quot;{{ $h->description }}&quot;?')" class="shrink-0">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-800 text-xs font-bold transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-10 text-center space-y-2">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl mx-auto border border-emerald-200">📅</div>
                            <p class="text-sm font-bold text-slate-900">Belum Ada Libur Sekolah Khusus</p>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto font-medium">Gunakan formulir di sebelah kanan untuk menambahkan hari libur sekolah mandiri.</p>
                        </div>
                    @endif
                </div>

                {{-- Tab 2: Libur Nasional --}}
                <div x-show="tab === 'national'" x-cloak>
                    @if ($national->isNotEmpty())
                        <div class="divide-y divide-emerald-100/70">
                            @foreach ($national as $h)
                                <div class="flex items-center justify-between gap-3 py-3.5 hover:bg-emerald-50/40 px-3 rounded-2xl transition-colors">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-950 font-bold text-xs flex flex-col items-center justify-center shrink-0 border border-emerald-200">
                                            <span class="text-sm font-extrabold leading-none">{{ $h->start_date->format('d') }}</span>
                                            <span class="text-[9px] uppercase tracking-wider text-emerald-800 font-bold mt-0.5">{{ $h->start_date->isoFormat('MMM') }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="font-bold text-sm text-slate-900 truncate">{{ $h->description }}</h4>
                                                <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-slate-800 border border-emerald-200 text-[10px] font-bold">Nasional</span>
                                            </div>
                                            <p class="text-xs text-slate-500 font-medium mt-0.5">
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
                        <div class="py-10 text-center">
                            <p class="text-sm font-bold text-slate-900">Belum Ada Daftar Libur Nasional</p>
                        </div>
                    @endif
                </div>

            </div>
        </div>

        {{-- RIGHT COLUMN: FORM TAMBAH LIBUR SEKOLAH (1/3) --}}
        <div class="space-y-4">
            <div class="rounded-3xl border border-emerald-200 bg-white p-5 sm:p-6 shadow-xs space-y-4">

                <div class="border-b border-emerald-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Tambah Libur Sekolah</h3>
                    <p class="text-xs text-slate-500 font-medium">Input hari libur khusus / semester</p>
                </div>

                <form method="POST" action="{{ route('holidays.store') }}" class="space-y-4" x-data="{ start: '', end: '', loading: false }" @submit="loading = true">
                    @csrf

                    <div>
                        <label for="start_date" class="block text-xs font-bold text-slate-900 mb-1.5">Tanggal Mulai</label>
                        <input type="date" id="start_date" name="start_date" x-model="start" @change="if(!end || end < start) end = start" required
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-emerald-200 bg-white text-slate-900 font-bold focus:outline-none focus:border-emerald-600">
                    </div>

                    <div>
                        <label for="end_date" class="block text-xs font-bold text-slate-900 mb-1.5">Tanggal Selesai</label>
                        <input type="date" id="end_date" name="end_date" x-model="end" :min="start" required
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-emerald-200 bg-white text-slate-900 font-bold focus:outline-none focus:border-emerald-600">
                        <p class="mt-1 text-[10px] text-slate-500 font-medium">Untuk libur 1 hari, samakan tanggal selesai.</p>
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-bold text-slate-900 mb-1.5">Keterangan Libur</label>
                        <input type="text" id="description" name="description" value="{{ old('description') }}" placeholder="cth: Libur Semester Ganjil, Ujian..." required
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-emerald-200 bg-white text-slate-900 font-semibold focus:outline-none focus:border-emerald-600">
                    </div>

                    <button type="submit" :disabled="loading"
                            class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs sm:text-sm shadow-sm shadow-emerald-200 transition-all flex items-center justify-center gap-1.5">
                        <template x-if="!loading">
                            <span>+ Tambahkan Hari Libur</span>
                        </template>
                        <template x-if="loading">
                            <span>Menyimpan Libur...</span>
                        </template>
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection
