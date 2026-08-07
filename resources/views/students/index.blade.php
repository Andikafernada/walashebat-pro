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
<div class="space-y-6 pb-12" x-data="{ showShareModal: false }">
    <!-- Header Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('classes.index') }}" class="hover:text-indigo-600 transition-colors">Kelas</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-indigo-600 transition-colors">{{ $classroom->name }}</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">Daftar Siswa</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Daftar Siswa Kelas {{ $classroom->name }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                @if ($ajar)
                    Kelas yang Anda ajar sebagai guru mapel — datanya cukup <strong class="font-semibold text-teal-700">NIS dan nama</strong>.
                @else
                    Kelola data biodata siswa, poin kedisiplinan, dan nomor HP orang tua.
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('classes.students.qr-cards', $classroom) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 shadow-xs hover:bg-emerald-100 transition-all">🎫 Cetak Kartu QR</a>
            {{-- Form biodata mandiri mengumpulkan alamat, HP ortu, dan data keluarga —
                 seluruhnya di luar hak guru mapel, dan grup orang tuanya pun milik
                 wali kelas lain. Salah kirim tautan ini tidak memunculkan galat apa
                 pun, jadi penjagaannya harus ada di sini. --}}
            @if (! $ajar)
            <button type="button" @click="showShareModal = true"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-xs font-bold text-indigo-700 shadow-xs hover:bg-indigo-100 transition-all">
                <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                📲 Bagikan Form Mandiri Siswa
            </button>
            @endif
            <a href="{{ route('classes.students.create', $classroom) }}" 
               class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition-all hover:bg-indigo-700 active:scale-95">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Siswa
            </a>
            @if(Route::has('classes.students.import'))
                <a href="{{ route('classes.students.import', $classroom) }}" 
                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Impor Excel
                </a>
            @endif
        </div>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $classroom])

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- Student Table Card -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-4">
        
        <!-- Filter & Search Controls -->
        <form method="GET" action="{{ route('classes.students.index', $classroom) }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-4">
            <div class="relative flex-1 max-w-sm">
                <input type="text" name="cari" value="{{ $cari ?? '' }}" placeholder="Cari nama siswa atau NIS..." 
                       class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3 text-xs text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none">
                <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="h-9 rounded-xl bg-slate-800 px-4 text-xs font-bold text-white hover:bg-slate-900 transition-colors">
                    Cari
                </button>
                @if($cari)
                    <a href="{{ route('classes.students.index', $classroom) }}" class="h-9 rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-600 flex items-center hover:bg-slate-100">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        <!-- Student Roster Table -->
        @if ($students->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-y border-slate-100">
                        <tr>
                            <th scope="col" class="py-3 px-3">No</th>
                            <th scope="col" class="py-3 px-3">Nama Siswa</th>
                            <th scope="col" class="py-3 px-3">{{ $ajar ? 'NIS' : 'NIS / NISN' }}</th>
                            @unless ($ajar)
                                <th scope="col" class="py-3 px-3">L/P</th>
                                <th scope="col" class="py-3 px-3">HP Orang Tua</th>
                            @endunless
                            <th scope="col" class="py-3 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($students as $index => $s)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-3 font-bold text-slate-400">
                                    {{ $students->firstItem() + $index }}
                                </td>
                                <td class="py-3 px-3 font-bold text-slate-900 text-sm">
                                    <a href="{{ route('classes.students.show', [$classroom, $s]) }}" class="hover:text-indigo-600 transition-colors">
                                        {{ $s->name }}
                                    </a>
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-600">
                                    @if ($ajar)
                                        {{ $s->nis ?: '-' }}
                                    @else
                                        {{ $s->nis ?: '-' }} / {{ $s->nisn ?: '-' }}
                                    @endif
                                </td>
                                @unless ($ajar)
                                    <td class="py-3 px-3">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold {{ $s->gender === 'L' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-pink-50 text-pink-700 border border-pink-200' }}">
                                            {{ $s->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 font-mono text-slate-600">
                                        {{ $s->parent_phone ?: '-' }}
                                    </td>
                                @endunless
                                <td class="py-3 px-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('classes.students.show', [$classroom, $s]) }}" class="h-7 rounded-lg border border-slate-200 bg-white px-2.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 flex items-center shadow-xs">
                                            Detail
                                        </a>
                                        <a href="{{ route('classes.students.edit', [$classroom, $s]) }}" class="h-7 rounded-lg border border-slate-200 bg-white px-2.5 text-[11px] font-bold text-indigo-600 hover:bg-indigo-50 flex items-center shadow-xs">
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($students->hasPages())
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Menampilkan {{ $students->firstItem() }}–{{ $students->lastItem() }} dari {{ $students->total() }} siswa</span>
                    <div>{{ $students->links() }}</div>
                </div>
            @endif
        @else
            <div class="my-10 text-center">
                <p class="text-sm font-bold text-slate-800">Tidak ada siswa ditemukan</p>
                <p class="mt-1 text-xs text-slate-500">
                    @if ($ajar)
                        Tambahkan siswa baru, atau impor dari Excel — cukup kolom NIS dan Nama.
                    @else
                        Tambahkan siswa baru atau bagikan link form mandiri di bawah ini.
                    @endif
                </p>
            </div>
        @endif

    </div>

    {{--
        Mengosongkan kelas.

        Tersedia di kedua jenis kelas: guru mapel pun mengimpor daftar siswanya
        sendiri, dan salah berkas sama mungkinnya terjadi di sana.

        Dipisah ke kartunya sendiri di paling bawah, jauh dari tombol Impor dan
        Ekspor. Tombol yang menghapus puluhan baris tidak boleh bertetangga
        dengan tombol yang dipakai sehari-hari.
    --}}
    @if ($totalKelas > 0)
        <div class="rounded-2xl border border-rose-200 bg-rose-50/40 p-5 shadow-sm"
             x-data="{ buka: {{ $errors->has('nama_kelas') ? 'true' : 'false' }}, ketikan: '' }">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-rose-950">Kosongkan Kelas</h3>
                    <p class="text-xs text-rose-700/80 mt-0.5">
                        Memindahkan seluruh {{ $totalKelas }} siswa di kelas ini ke Arsip. Riwayat absensi,
                        nilai, dan pelanggarannya tetap tersimpan, dan daftarnya masih bisa dipulihkan.
                    </p>
                </div>
                <button type="button" x-show="!buka" @click="buka = true"
                        class="h-9 shrink-0 rounded-xl border border-rose-300 bg-white px-3.5 text-xs font-bold text-rose-700 shadow-xs hover:bg-rose-50">
                    Kosongkan Kelas…
                </button>
            </div>

            <div x-show="buka" x-cloak class="mt-4 border-t border-rose-200 pt-4">
                <form method="POST" action="{{ route('classes.students.destroy-all', $classroom) }}" class="space-y-3">
                    @csrf
                    @method('DELETE')

                    <label for="nama_kelas" class="block text-xs font-semibold text-rose-900">
                        Ketik <span class="font-mono font-bold">{{ $classroom->name }}</span> untuk mengonfirmasi:
                    </label>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input id="nama_kelas" name="nama_kelas" type="text" x-model="ketikan" autocomplete="off"
                               placeholder="{{ $classroom->name }}"
                               class="h-10 w-full rounded-xl border border-rose-300 bg-white px-3 text-sm text-slate-900 placeholder:text-rose-300 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 sm:max-w-xs">

                        {{-- Tombolnya dimatikan sampai ketikannya cocok, tetapi kecocokan
                             itu diperiksa ULANG di server: atribut disabled hanya menahan
                             salah tekan, bukan permintaan yang dikirim langsung. --}}
                        <button type="submit"
                                :disabled="ketikan.trim().replace(/\s+/g, ' ').toLowerCase() !== @js(\Illuminate\Support\Str::lower(\Illuminate\Support\Str::squish($classroom->name)))"
                                class="h-10 shrink-0 rounded-xl bg-rose-600 px-4 text-xs font-bold text-white shadow-xs transition-all hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-rose-300">
                            Pindahkan {{ $totalKelas }} Siswa ke Arsip
                        </button>

                        <button type="button" @click="buka = false; ketikan = ''"
                                class="h-10 shrink-0 rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Batal
                        </button>
                    </div>

                    @error('nama_kelas')
                        <p class="text-xs font-semibold text-rose-700">{{ $message }}</p>
                    @enderror

                    <p class="text-[11px] text-rose-700/70">
                        Setelah dikosongkan, impor ulang dengan NIS yang sama tetap bisa dilakukan.
                        Untuk memulihkan, buka <a href="{{ route('classes.students.trashed', $classroom) }}" class="font-bold underline">Arsip Siswa</a>.
                    </p>
                </form>
            </div>
        </div>
    @endif

    {{-- Tidak dirender untuk kelas ajar: tanpa tombol pemanggilnya, modal ini
         hanya jadi markup mati yang tetap memuat tautan biodata publik kelas
         orang lain di halaman yang justru tidak mengurus biodata. --}}
    @unless ($ajar)
    <!-- Share Modal -->
    <div x-show="showShareModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4" @click.away="showShareModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Bagikan Form Mandiri Biodata Siswa</h3>
                <button type="button" @click="showShareModal = false" class="text-slate-400 hover:text-slate-600 font-bold">×</button>
            </div>

            <p class="text-xs text-slate-600">
                Kirimkan tautan di bawah ini ke WhatsApp grup siswa/orang tua agar mereka dapat mengisi NIS, alamat, HP ortu, dan biodata dari HP masing-masing:
            </p>

            <div class="space-y-2">
                <input type="text" readonly value="{{ route('public.biodata.show', $classroom->tokenPublik()) }}"
                       class="h-10 w-full rounded-xl border border-indigo-200 bg-indigo-50/50 px-3 text-xs font-mono text-indigo-900 focus:outline-none select-all">
                <button type="button" onclick="navigator.clipboard.writeText('{{ route('public.biodata.show', $classroom->tokenPublik()) }}'); alert('Tautan disalin ke Clipboard!');"
                        class="h-10 w-full rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition-all flex items-center justify-center gap-1.5">
                    📋 Salin Tautan Ke Clipboard
                </button>
            </div>
        </div>
    </div>
    @endunless
</div>
@endsection
