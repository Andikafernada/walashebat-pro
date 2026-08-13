@extends('layouts.app')

@section('title', 'Daftar Siswa - ' . $classroom->name)

@section('content')
@php
    /*
     * Kelas ajar hanya menyimpan NIS dan nama.
     *
     * Guru mapel tidak berhak atas biodata, nomor HP orang tua, maupun jenis
     * kelamin siswa di kelas yang wali kelasnya orang lain. Menampilkan kolom
     * itu bukan sekadar ramai — kolom yang terlihat mengundang untuk diisi,
     * lalu lahir salinan kedua biodata yang menyimpang dari milik wali kelas
     * aslinya, dan tidak ada yang tahu mana yang benar.
     */
    $ajar = $classroom->kelasAjar();
@endphp

<div class="space-y-5 pb-12" x-data="{ showShareModal: false }">

    <div class="page-header">
        <div class="min-w-0">
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-slate-600">{{ $classroom->name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Daftar Siswa</span>
            </nav>
            <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Daftar Siswa Kelas {{ $classroom->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                @if ($ajar)
                    Kelas yang Anda ajar sebagai guru mapel — datanya cukup <strong class="font-medium text-slate-700">NIS dan nama</strong>.
                @else
                    Kelola data biodata siswa, poin kedisiplinan, dan nomor HP orang tua.
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('classes.students.qr-cards', $classroom) }}" class="btn-secondary btn-secondary--sm">Cetak Kartu QR</a>

            {{-- Form biodata mandiri mengumpulkan alamat, HP ortu, dan data keluarga —
                 seluruhnya di luar hak guru mapel, dan grup orang tuanya pun milik
                 wali kelas lain. Salah kirim tautan ini tidak memunculkan galat apa
                 pun, jadi penjagaannya harus ada di sini. --}}
            @if (! $ajar)
                <button type="button" @click="showShareModal = true" class="btn-secondary btn-secondary--sm">Bagikan Form Mandiri Siswa</button>
            @endif

            {{--
                `students.import.form` (GET), BUKAN `students.import` (POST).
                Tombol ini dulu menunjuk rute POST lewat <a href>, sehingga
                menekannya menghasilkan 405 Method Not Allowed — halaman impor
                yang sudah jadi tidak pernah bisa dibuka dari antarmuka.
            --}}
            @if(Route::has('classes.students.import.form'))
                <a href="{{ route('classes.students.import.form', $classroom) }}" class="btn-secondary btn-secondary--sm">Impor Excel</a>
            @endif

            <a href="{{ route('classes.students.create', $classroom) }}" class="btn-primary btn-primary--sm">Tambah Siswa</a>
        </div>
    </div>

    @include('partials.class-nav', ['classroom' => $classroom])

    @include('partials.flash')

    <section class="blok">
        <div class="blok__kepala">
            <h2 class="blok__judul">{{ $students->total() }} siswa</h2>

            <form method="GET" action="{{ route('classes.students.index', $classroom) }}" class="flex items-center gap-1.5">
                <label for="cari" class="sr-only">Cari nama siswa atau NIS</label>
                <input id="cari" type="search" name="cari" value="{{ $cari ?? '' }}" placeholder="Cari nama atau NIS"
                       class="form-input form-input--sm w-44 sm:w-56">
                <button type="submit" class="btn-secondary btn-secondary--sm">Cari</button>
                @if($cari)
                    <a href="{{ route('classes.students.index', $classroom) }}" class="btn-ghost btn-ghost--sm">Reset</a>
                @endif
            </form>
        </div>

        @if ($students->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" class="w-10 text-right">No</th>
                            <th scope="col">Nama Siswa</th>
                            <th scope="col">{{ $ajar ? 'NIS' : 'NIS / NISN' }}</th>
                            @unless ($ajar)
                                <th scope="col" class="w-12">L/P</th>
                                <th scope="col">HP Orang Tua</th>
                            @endunless
                            <th scope="col" class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $index => $s)
                            <tr>
                                {{-- Nomor urut di margin: angka abu rata kanan, tanpa
                                     kotak. Ia penunjuk baris, bukan data. --}}
                                <td class="text-right font-mono text-xs text-slate-400">{{ $students->firstItem() + $index }}</td>
                                <td>
                                    <a href="{{ route('classes.students.show', [$classroom, $s]) }}" class="hover:text-indigo-700 hover:underline">
                                        {{ $s->name }}
                                    </a>
                                </td>
                                <td class="td--mono">
                                    @if ($ajar)
                                        {{ $s->nis ?: '—' }}
                                    @else
                                        {{ $s->nis ?: '—' }} / {{ $s->nisn ?: '—' }}
                                    @endif
                                </td>
                                @unless ($ajar)
                                    {{-- L/P sebagai kode satu huruf, bukan lencana
                                         biru/merah muda. Kepala kolomnya sudah
                                         menerangkan artinya, dan warna berdasarkan
                                         jenis kelamin tidak menambah satu pun
                                         informasi yang tidak sudah tertulis. --}}
                                    <td><span class="kode" title="{{ $s->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}">{{ $s->gender ?: '—' }}</span></td>
                                    <td class="td--mono">{{ $s->parent_phone ?: '—' }}</td>
                                @endunless
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('classes.students.show', [$classroom, $s]) }}" class="btn-secondary btn-secondary--sm">Detail</a>
                                        <a href="{{ route('classes.students.edit', [$classroom, $s]) }}" class="btn-ghost btn-ghost--sm">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($students->hasPages())
                <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-2.5 text-xs text-slate-500">
                    <span class="angka">Menampilkan {{ $students->firstItem() }}–{{ $students->lastItem() }} dari {{ $students->total() }} siswa</span>
                    <div>{{ $students->links() }}</div>
                </div>
            @endif
        @else
            <div class="p-6 text-center">
                <p class="text-sm font-medium text-slate-900">Tidak ada siswa ditemukan</p>
                <p class="mt-1 text-sm text-slate-500">
                    @if ($ajar)
                        Tambahkan siswa baru, atau impor dari Excel — cukup kolom NIS dan Nama.
                    @else
                        Tambahkan siswa baru atau bagikan tautan form mandiri.
                    @endif
                </p>
            </div>
        @endif
    </section>

    {{--
        Mengosongkan kelas.

        Tersedia di kedua jenis kelas: guru mapel pun mengimpor daftar siswanya
        sendiri, dan salah berkas sama mungkinnya terjadi di sana.

        Dipisah ke bloknya sendiri di paling bawah, jauh dari tombol Impor dan
        Ekspor. Tombol yang menghapus puluhan baris tidak boleh bertetangga
        dengan tombol yang dipakai sehari-hari.
    --}}
    @if ($totalKelas > 0)
        <section class="rounded-lg border border-rose-200 bg-white"
                 x-data="{ buka: {{ $errors->has('nama_kelas') ? 'true' : 'false' }}, ketikan: '' }">
            <div class="flex flex-col gap-3 border-b border-rose-200 bg-rose-50 px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold tracking-tight text-rose-900">Kosongkan Kelas</h2>
                    <p class="mt-0.5 text-xs text-rose-800">
                        Memindahkan seluruh {{ $totalKelas }} siswa di kelas ini ke Arsip. Riwayat absensi,
                        nilai, dan pelanggarannya tetap tersimpan, dan daftarnya masih bisa dipulihkan.
                    </p>
                </div>
                <button type="button" x-show="!buka" @click="buka = true" class="btn-danger btn-danger--sm shrink-0">Kosongkan Kelas…</button>
            </div>

            <div x-show="buka" x-cloak class="p-4">
                <form method="POST" action="{{ route('classes.students.destroy-all', $classroom) }}" class="space-y-3">
                    @csrf
                    @method('DELETE')

                    <label for="nama_kelas" class="form-label">
                        Ketik <span class="font-mono font-medium text-slate-900">{{ $classroom->name }}</span> untuk mengonfirmasi:
                    </label>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input id="nama_kelas" name="nama_kelas" type="text" x-model="ketikan" autocomplete="off"
                               placeholder="{{ $classroom->name }}"
                               class="form-input sm:max-w-xs {{ $errors->has('nama_kelas') ? 'form-input--error' : '' }}">

                        {{-- Tombolnya dimatikan sampai ketikannya cocok, tetapi kecocokan
                             itu diperiksa ULANG di server: atribut disabled hanya menahan
                             salah tekan, bukan permintaan yang dikirim langsung. --}}
                        <button type="submit"
                                :disabled="ketikan.trim().replace(/\s+/g, ' ').toLowerCase() !== @js(\Illuminate\Support\Str::lower(\Illuminate\Support\Str::squish($classroom->name)))"
                                class="btn-danger shrink-0">
                            Pindahkan {{ $totalKelas }} Siswa ke Arsip
                        </button>

                        <button type="button" @click="buka = false; ketikan = ''" class="btn-secondary shrink-0">Batal</button>
                    </div>

                    @error('nama_kelas')
                        <p class="form-error">{{ $message }}</p>
                    @enderror

                    <p class="text-xs text-slate-500">
                        Setelah dikosongkan, impor ulang dengan NIS yang sama tetap bisa dilakukan.
                        Untuk memulihkan, buka <a href="{{ route('classes.students.trashed', $classroom) }}" class="font-medium text-indigo-700 underline">Arsip Siswa</a>.
                    </p>
                </form>
            </div>
        </section>
    @endif

    {{-- Tidak dirender untuk kelas ajar: tanpa tombol pemanggilnya, modal ini
         hanya jadi markup mati yang tetap memuat tautan biodata publik kelas
         orang lain di halaman yang justru tidak mengurus biodata. --}}
    @unless ($ajar)
    <div x-show="showShareModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-lg border border-slate-300 bg-white shadow-xl" @click.away="showShareModal = false">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h2 class="text-sm font-semibold tracking-tight text-slate-900">Bagikan Form Mandiri Biodata Siswa</h2>
                <button type="button" @click="showShareModal = false" class="btn-icon" aria-label="Tutup">&times;</button>
            </div>

            <div class="space-y-3 p-4">
                <p class="text-sm text-slate-600">
                    Kirimkan tautan ini ke grup WhatsApp siswa atau orang tua agar mereka dapat mengisi NIS, alamat, HP ortu, dan biodata dari HP masing-masing.
                </p>

                <input type="text" readonly value="{{ route('public.biodata.show', $classroom->tokenPublik()) }}"
                       class="form-input select-all font-mono text-xs">

                <button type="button"
                        onclick="navigator.clipboard.writeText('{{ route('public.biodata.show', $classroom->tokenPublik()) }}'); alert('Tautan disalin.');"
                        class="btn-primary w-full">Salin tautan</button>
            </div>
        </div>
    </div>
    @endunless
</div>
@endsection
