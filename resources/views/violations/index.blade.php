@extends('layouts.app')

@section('title', 'Catatan Pelanggaran - ' . ($class->name ?? ''))

@section('content')
<div class="space-y-6 pb-12">
    <!-- Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-emerald-800 transition-colors font-medium">Kelas</a>
                <span aria-hidden="true" class="text-slate-400">/</span>
                <a href="{{ route('classes.show', $class) }}" class="hover:text-emerald-800 transition-colors font-medium">{{ $class->name }}</a>
                <span aria-hidden="true" class="text-slate-400">/</span>
                <span class="text-slate-500 font-bold">Catatan Pelanggaran</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900">
                Catatan Pelanggaran Siswa {{ $class->name }}
            </h1>
            <p class="mt-1 text-xs sm:text-sm text-slate-600">Pencatatan pelanggaran kedisiplinan siswa dan pemantauan akumulasi poin bobot.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a href="{{ route('classes.exports.violations.excel', $class) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all hover:scale-105">
                <span>📊</span>
                <span>Excel</span>
            </a>
            <a href="{{ route('classes.exports.violations.pdf', $class) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all hover:scale-105">
                <span>📄</span>
                <span>Cetak PDF</span>
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
            <div class="bg-white rounded-2xl border border-emerald-200 p-5 shadow-xs space-y-4">

                <div class="flex items-center justify-between border-b border-emerald-100 pb-3">
                    <h3 class="font-extrabold text-sm text-slate-900">Riwayat Pelanggaran Siswa</h3>
                    <span class="rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-extrabold text-emerald-950 border border-emerald-200">
                        {{ $violations->total() }} Catatan
                    </span>
                </div>

                @if ($violations->count() > 0)
                    <div class="divide-y divide-emerald-50">
                        @foreach ($violations as $v)
                            <div class="flex items-center justify-between gap-4 py-3 hover:bg-[#f0fdf4]/50 px-2 rounded-xl transition-colors">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-700 font-extrabold text-xs shrink-0 border border-rose-200 shadow-2xs">
                                        {{ $v->points > 0 ? '+' : '' }}{{ $v->points }}
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-extrabold text-sm text-slate-900 truncate">{{ $v->student->name ?? 'Siswa' }}</h4>
                                        <p class="mt-0.5 text-xs text-slate-600 font-medium truncate">
                                            {{ $v->type->name ?? $v->note ?: 'Pelanggaran' }} &middot; <span class="font-mono text-slate-500 font-bold">{{ $v->occurred_on->format('d/m/Y') }}</span>
                                        </p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('classes.violations.destroy', [$class, $v]) }}"
                                      onsubmit="return confirm('Hapus catatan pelanggaran ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="h-8 w-8 rounded-xl border border-slate-200 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 flex items-center justify-center text-slate-400 transition-colors shadow-2xs" title="Hapus Catatan">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    @if ($violations->hasPages())
                        <div class="mt-4 pt-3 border-t border-emerald-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Menampilkan {{ $violations->firstItem() }}–{{ $violations->lastItem() }} dari {{ $violations->total() }} catatan</span>
                            <div>{{ $violations->links() }}</div>
                        </div>
                    @endif
                @else
                    <div class="my-10 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-950 font-bold text-2xl mb-3 border border-emerald-200">
                            🛡️
                        </div>
                        <p class="text-sm font-extrabold text-slate-900">Belum Ada Catatan Pelanggaran</p>
                        <p class="mt-1 text-xs text-slate-500 font-medium">Seluruh siswa di kelas ini memiliki catatan kedisiplinan yang bersih.</p>
                    </div>
                @endif

            </div>
        </div>

        <!-- RIGHT COLUMN: Add Form (1/3 width) -->
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-emerald-200 p-5 shadow-xs space-y-4">

                <div class="flex items-center gap-3 border-b border-emerald-100 pb-3">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-950 flex items-center justify-center text-sm font-bold border border-emerald-200">
                        ⚠️
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Catat Pelanggaran</h3>
                        <p class="text-xs text-slate-500 font-medium">Pilih Siswa &amp; Jenis Pelanggaran</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('classes.violations.store', $class) }}" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <div>
                        <label for="student_id" class="block text-xs font-bold text-slate-800 mb-1">Siswa <span class="text-rose-500">*</span></label>
                        <select id="student_id" name="student_id" required class="w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">-- Pilih Siswa --</option>
                            @foreach ($students as $st)
                                <option value="{{ $st->id }}" @selected(old('student_id') == $st->id)>{{ $st->name }} (NIS: {{ $st->nis ?: '-' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="violation_type_id" class="block text-xs font-bold text-slate-800 mb-1">Jenis Pelanggaran</label>
                        <select id="violation_type_id" name="violation_type_id" class="w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                onchange="document.getElementById('blok-poin-custom').classList.toggle('hidden', this.value !== '')">
                            <option value="">— Pelanggaran Lainnya / Custom —</option>
                            @foreach ($types as $vt)
                                <option value="{{ $vt->id }}" @selected(old('violation_type_id') == $vt->id)>{{ $vt->name }} ({{ $vt->points > 0 ? '+' : '' }}{{ $vt->points }} Poin)</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="blok-poin-custom">
                        <label for="points" class="block text-xs font-bold text-slate-800 mb-1">Poin</label>
                        <input type="number" id="points" name="points" value="{{ old('points') }}" step="1" min="-100" max="100" placeholder="cth: -5 untuk mengurangi poin"
                               class="w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <p class="mt-1 text-[10px] text-slate-500">Isi negatif untuk mengurangi poin kedisiplinan, mis. <span class="font-semibold">-5</span>.</p>
                    </div>

                    <div>
                        <label for="occurred_on" class="block text-xs font-bold text-slate-800 mb-1">Tanggal Kejadian <span class="text-rose-500">*</span></label>
                        <input type="date" id="occurred_on" name="occurred_on" value="{{ date('Y-m-d') }}" required class="w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label for="note" class="block text-xs font-bold text-slate-800 mb-1">Catatan Tambahan <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                        <textarea id="note" name="note" rows="2" placeholder="Tuliskan keterangan detail kejadian..." class="w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>

                    <button type="submit" :disabled="loading"
                            class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-sm shadow-emerald-200 transition-all flex items-center justify-center gap-1.5">
                        <template x-if="!loading">
                            <span class="flex items-center gap-1.5">
                                <span>💾</span>
                                <span>Simpan Pelanggaran</span>
                            </span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center gap-1.5">
                                <span class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span>Menyimpan...</span>
                            </span>
                        </template>
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection
