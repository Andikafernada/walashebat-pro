@extends('layouts.app')
@section('title', 'Impor Data Siswa')
@section('content')
    @include('partials.class-nav')

    <div class="page-header mb-5">
        <div>
            <h2 class="page-header__title">Impor siswa dari Excel</h2>
            <p class="page-header__description">
                Menambah siswa baru sekaligus memperbarui yang sudah ada di {{ $classroom->name }}.
            </p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="card-elevated">
                <form method="POST"
                      action="{{ route('classes.students.import', $classroom) }}"
                      enctype="multipart/form-data"
                      x-data="{ nama: '', loading: false }"
                      @submit="loading = true"
                      class="space-y-6">
                    @csrf

                    <div class="form-group">
                        <label for="file" class="form-label form-label--required">Berkas Excel atau CSV</label>

                        <label for="file"
                               class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-indigo-400 hover:bg-indigo-50/40">
                            <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <span class="text-sm font-semibold text-slate-700" x-text="nama || 'Pilih berkas'"></span>
                            <span class="text-xs text-slate-500">.xlsx, .xls, atau .csv — maksimal 5 MB</span>
                        </label>

                        <input id="file" name="file" type="file" class="sr-only" required
                               accept=".xlsx,.xls,.csv"
                               @change="nama = $event.target.files[0]?.name ?? ''">

                        @error('file')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                        <button type="submit"
                                class="btn-primary justify-center"
                                :disabled="loading"
                                :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
                            <span x-show="!loading" class="inline-flex items-center gap-2">Unggah dan proses</span>
                            <span x-show="loading" x-cloak class="flex items-center gap-2">
                                <span class="spinner spinner--white"></span>
                                Memproses...
                            </span>
                        </button>
                        <a href="{{ route('classes.students.index', $classroom) }}" class="btn-secondary justify-center">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="space-y-4">
            <div class="card">
                <h3 class="text-base font-semibold text-slate-900">Mulai dari mana</h3>
                <p class="mt-1 text-sm text-slate-600">
                    Cara paling cepat: unduh data kelas ini, isi kolom yang masih kosong di Excel,
                    lalu unggah berkas yang sama kembali.
                </p>

                {{-- Isi template mengikuti jenis kelas, jadi keterangannya harus ikut
                     juga. Menjanjikan "34 kolom biodata" kepada guru mapel yang akan
                     menerima berkas 2 kolom membuat ia mengira unduhannya gagal. --}}
                <p class="mt-2 rounded-lg border px-3 py-2 text-xs {{ $classroom->kelasAjar() ? 'border-teal-200 bg-teal-50/60 text-teal-900' : 'border-indigo-200 bg-indigo-50/60 text-indigo-900' }}">
                    @if ($classroom->kelasAjar())
                        Kelas ini Anda ajar sebagai <strong>guru mapel</strong>, jadi templatenya hanya
                        <strong>NIS</strong> dan <strong>Nama</strong>. Biodata lengkap adalah urusan wali kelasnya.
                    @else
                        Kelas <strong>perwalian</strong>: templatenya berisi seluruh kolom biodata,
                        lengkap dengan pilihan siap-klik dan petunjuk di tiap judul kolom.
                    @endif
                </p>

                <div class="mt-4 space-y-2">
                    <a href="{{ route('classes.students.export', $classroom) }}"
                       class="btn-secondary btn-secondary--sm w-full justify-center">
                        Unduh data kelas ini
                    </a>
                    <a href="{{ route('classes.students.template', $classroom) }}"
                       class="btn-ghost btn-ghost--sm w-full justify-center">
                        Unduh template kosong
                    </a>
                </div>
            </div>

            <div class="card">
                <h3 class="text-base font-semibold text-slate-900">Yang perlu diketahui</h3>
                <ul class="mt-3 space-y-3 text-sm text-slate-600">
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-400"></span>
                        <span><strong class="font-semibold text-slate-800">NIS</strong> dipakai untuk mencocokkan
                            siswa. Kalau NIS kosong, nama yang dipakai. Kalau keduanya tidak ketemu,
                            siswa baru dibuat.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-400"></span>
                        <span>Sel yang dibiarkan kosong <strong class="font-semibold text-slate-800">tidak
                            menghapus</strong> data yang sudah ada.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-400"></span>
                        <span>Judul kolom di baris pertama jangan diubah atau dihapus. Urutannya boleh berbeda,
                            kolom tambahan diabaikan.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-400"></span>
                        <span>Tanggal lahir pakai format <span class="font-mono text-xs">YYYY-MM-DD</span>,
                            contoh <span class="font-mono text-xs">2010-08-17</span>.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-400"></span>
                        <span>Nomor HP boleh ditulis <span class="font-mono text-xs">08…</span> —
                            akan diubah sendiri ke <span class="font-mono text-xs">628…</span>.</span>
                    </li>
                </ul>
            </div>

            <div class="alert alert--info">
                <div class="min-w-0">
                    <p class="alert__title">NIS berawalan angka nol</p>
                    <p class="alert__body">
                        Excel suka membuang nol di depan. Kolom NIS pada berkas unduhan sudah diatur
                        sebagai teks, jadi biarkan formatnya apa adanya.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
