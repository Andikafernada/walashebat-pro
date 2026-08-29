@extends('layouts.app')
@section('title', 'Impor Data Siswa — ' . $classroom->name)
@section('content')

@include('partials.class-nav', ['classroom' => $classroom])

<div class="space-y-6 pb-12">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-slate-600">{{ $classroom->name }}</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.students.index', $classroom) }}" class="hover:text-slate-600">Siswa</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Impor Excel</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                Impor Data Siswa dari Excel
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-600 font-medium">
                Tambah siswa baru sekaligus perbarui biodata di kelas {{ $classroom->name }} secara massal.
            </p>
        </div>
        <a href="{{ route('classes.students.index', $classroom) }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all">
            ← Kembali ke Daftar Siswa
        </a>
    </div>

    @include('partials.flash')

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- LEFT COLUMN: FORM UPLOAD (2/3) --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-3xl border border-emerald-200 bg-white p-6 sm:p-8 shadow-xs">
                <form method="POST"
                      action="{{ route('classes.students.import', $classroom) }}"
                      enctype="multipart/form-data"
                      x-data="{ nama: '', loading: false }"
                      @submit="loading = true"
                      class="space-y-6">
                    @csrf

                    <div>
                        <label for="file" class="block text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">Pilih Berkas Excel / CSV *</label>

                        <label for="file"
                               class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-emerald-300 bg-emerald-50/50 px-6 py-12 text-center transition hover:border-emerald-500 hover:bg-emerald-100/50">
                            <span class="text-4xl">📊</span>
                            <span class="text-sm font-extrabold text-slate-900" x-text="nama || 'Klik untuk memilih berkas Excel'"></span>
                            <span class="text-xs text-slate-500 font-medium">Format didukung: .xlsx, .xls, atau .csv (Maksimal 5 MB)</span>
                        </label>

                        <input id="file" name="file" type="file" class="sr-only" required
                               accept=".xlsx,.xls,.csv"
                               @change="nama = $event.target.files[0]?.name ?? ''">

                        @error('file')
                            <p class="mt-2 text-xs font-bold text-slate-900" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-3 border-t border-emerald-100 pt-6 sm:flex-row">
                        <button type="submit"
                                class="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm shadow-sm shadow-emerald-200 transition-all flex items-center justify-center gap-2"
                                :disabled="loading"
                                :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
                            <span x-show="!loading">🚀 Unggah &amp; Proses Data</span>
                            <span x-show="loading" x-cloak class="flex items-center gap-2">
                                <span class="spinner"></span>
                                Memproses Data...
                            </span>
                        </button>
                        <a href="{{ route('classes.students.index', $classroom) }}"
                           class="px-5 py-3 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 font-bold text-xs sm:text-sm transition-colors flex items-center justify-center">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- RIGHT COLUMN: TEMPLATE DOWNLOAD & GUIDE (1/3) --}}
        <div class="space-y-4">
            {{-- Download Template Card --}}
            <div class="rounded-3xl border border-emerald-200 bg-white p-5 sm:p-6 shadow-xs space-y-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Unduh Template</h3>
                <p class="text-xs text-slate-600 font-medium leading-relaxed">
                    Unduh data kelas yang sudah ada atau gunakan template kosong standar sistem.
                </p>

                <div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-slate-900 font-medium">
                    @if ($classroom->kelasAjar())
                        Kelas guru mapel: Format hanya membutuhkan <strong>NIS</strong> dan <strong>Nama Siswa</strong>.
                    @else
                        Kelas perwalian: Template mencakup seluruh kolom biodata lengkap siap impor.
                    @endif
                </div>

                <div class="space-y-2 pt-2">
                    <a href="{{ route('classes.students.export', $classroom) }}"
                       class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-900 text-xs font-bold shadow-2xs transition-all">
                        📥 Unduh Data Kelas Ini (.xlsx)
                    </a>
                    <a href="{{ route('classes.students.template', $classroom) }}"
                       class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-emerald-100 text-emerald-950 border border-emerald-300 hover:bg-emerald-200 text-xs font-bold transition-all">
                        📄 Unduh template kosong
                    </a>
                </div>
            </div>

            {{-- Guide Card --}}
            <div class="rounded-3xl border border-emerald-200 bg-white p-5 sm:p-6 shadow-xs space-y-3">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Petunjuk Impor</h3>
                <ul class="space-y-2.5 text-xs text-slate-700 font-medium leading-relaxed">
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-800 font-bold shrink-0">✓</span>
                        <span><strong>NIS</strong> digunakan untuk mencocokkan data siswa yang ada.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-800 font-bold shrink-0">✓</span>
                        <span>Kolom kosong di Excel tidak akan menghapus data yang sudah ada di aplikasi.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-800 font-bold shrink-0">✓</span>
                        <span>Format tanggal lahir: <span class="font-mono text-[11px] bg-emerald-50 px-1 py-0.5 rounded border border-emerald-200 font-bold text-slate-900">YYYY-MM-DD</span> (Contoh: 2008-05-17).</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-emerald-800 font-bold shrink-0">✓</span>
                        <span>Nomor WhatsApp diawali <span class="font-mono text-[11px] bg-emerald-50 px-1 py-0.5 rounded border border-emerald-200 font-bold text-slate-900">08...</span> otomatis dikonversi ke <span class="font-mono text-[11px] bg-emerald-50 px-1 py-0.5 rounded border border-emerald-200 font-bold text-slate-900">628...</span>.</span>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</div>
@endsection
