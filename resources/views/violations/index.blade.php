@extends('layouts.app')

@section('title', 'Catatan Pelanggaran - ' . ($class->name ?? ''))

@section('content')
<div class="space-y-6 pb-12">
    <!-- Header Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('classes.index') }}" class="hover:text-indigo-600 transition-colors">Kelas</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('classes.show', $class) }}" class="hover:text-indigo-600 transition-colors">{{ $class->name }}</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">Catatan Pelanggaran</span>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                Catatan Pelanggaran Siswa {{ $class->name }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Pencatatan pelanggaran kedisiplinan siswa dan pemantauan akumulasi poin bobot.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('classes.exports.violations.excel', $class) }}"
               class="btn-secondary btn-secondary--sm">
                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                Excel
            </a>
            <a href="{{ route('classes.exports.violations.pdf', $class) }}" target="_blank"
               class="inline-flex items-center gap-1.5 rounded border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                Cetak PDF
            </a>
        </div>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $class])

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- Main Two-Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <!-- LEFT COLUMN: Violations List (2/3 width) -->
        <div class="space-y-4 lg:col-span-2">
            <div class="card space-y-4">

                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Riwayat Catatan Pelanggaran</h3>
                        <p class="text-xs text-slate-500">Catatan insiden dan akumulasi poin siswa</p>
                    </div>
                    <span class="text-xs font-semibold text-slate-400">{{ $violations->total() }} Catatan</span>
                </div>

                @if ($violations->isNotEmpty())
                    <div class="divide-y divide-slate-200">
                        @foreach ($violations as $v)
                            <div class="flex items-center justify-between gap-4 py-3 hover:bg-slate-50 px-2 rounded transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-rose-100 text-rose-800 font-semibold text-xs border border-rose-200">
                                        {{-- Tanda ikut nilainya: pelanggaran mengurangi poin, penghargaan menambah.
                                             Awalan "+" yang dipaku membuat pengurangan tampil sebagai penambahan. --}}
                                        {{ $v->points > 0 ? '+' : '' }}{{ $v->points }}
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-sm text-slate-900">{{ $v->student->name ?? 'Siswa' }}</h4>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            {{ $v->type->name ?? $v->note ?: 'Pelanggaran' }} &middot; <span class="font-mono text-slate-400">{{ $v->occurred_on->format('d/m/Y') }}</span>
                                        </p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('classes.violations.destroy', [$class, $v]) }}"
                                      onsubmit="return confirm('Hapus catatan pelanggaran ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="h-8 w-8 rounded-lg border border-slate-200 hover:bg-rose-50 hover:text-rose-600 flex items-center justify-center text-slate-400 transition-colors">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    @if ($violations->hasPages())
                        <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                            <span>Menampilkan {{ $violations->firstItem() }}–{{ $violations->lastItem() }} dari {{ $violations->total() }} catatan</span>
                            <div>{{ $violations->links() }}</div>
                        </div>
                    @endif
                @else
                    <div class="my-10 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-emerald-50 text-emerald-500 mb-3">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-800">Belum Ada Pelanggaran Catatan</p>
                        <p class="mt-1 text-xs text-slate-500">Seluruh siswa di kelas ini memiliki catatan kedisiplinan yang bersih.</p>
                    </div>
                @endif

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
                        <h3 class="text-sm font-semibold text-slate-900">Catat Pelanggaran</h3>
                        <p class="text-xs text-slate-500">Pilih Siswa &amp; Jenis Pelanggaran</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('classes.violations.store', $class) }}" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <div>
                        <label for="student_id" class="form-label">Siswa</label>
                        <select id="student_id" name="student_id" required class="form-input form-input--sm">
                            {{-- Tanpa pilihan kosong, peramban menyorot siswa pertama
                                 pada daftar dan `required` jadi tidak berarti: wali
                                 kelas yang terlewat mengisi kolom ini mencatatkan
                                 pelanggaran — berikut poinnya — atas nama siswa yang
                                 tidak melakukan apa-apa. --}}
                            <option value="">-- Pilih Siswa --</option>
                            @foreach ($students as $st)
                                <option value="{{ $st->id }}" @selected(old('student_id') == $st->id)>{{ $st->name }} (NIS: {{ $st->nis ?: '-' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="violation_type_id" class="form-label">Jenis Pelanggaran</label>
                        <select id="violation_type_id" name="violation_type_id" class="form-input form-input--sm"
                                onchange="document.getElementById('blok-poin-custom').classList.toggle('hidden', this.value !== '')">
                            <option value="">— Pelanggaran Lainnya / Custom —</option>
                            @foreach ($types as $vt)
                                <option value="{{ $vt->id }}" @selected(old('violation_type_id') == $vt->id)>{{ $vt->name }} ({{ $vt->points > 0 ? '+' : '' }}{{ $vt->points }} Poin)</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Hanya untuk "Pelanggaran Lainnya". Bila jenis pelanggaran dipilih,
                         poin diambil dari jenis itu di server, jadi input ini disembunyikan. --}}
                    <div id="blok-poin-custom">
                        <label for="points" class="form-label">Poin</label>
                        <input type="number" id="points" name="points" value="{{ old('points') }}" step="1" min="-100" max="100" placeholder="cth: -5 untuk mengurangi poin"
                               class="form-input form-input--sm">
                        <p class="mt-1 text-[10px] text-slate-500">Isi negatif untuk mengurangi poin kedisiplinan, mis. <span class="font-semibold">-5</span>.</p>
                    </div>

                    <div>
                        <label for="occurred_on" class="form-label">Tanggal Kejadian</label>
                        <input type="date" id="occurred_on" name="occurred_on" value="{{ date('Y-m-d') }}" required class="form-input form-input--sm">
                    </div>

                    <div>
                        <label for="note" class="form-label">Catatan Tambahan <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                        <textarea id="note" name="note" rows="2" placeholder="Tuliskan keterangan detail kejadian..." class="form-input form-input--sm"></textarea>
                    </div>

                    <button type="submit" :disabled="loading"
                            class="h-10 w-full rounded bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition-colors flex items-center justify-center gap-2">
                        <template x-if="!loading">
                            <span class="flex items-center gap-1.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Simpan Pelanggaran
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
