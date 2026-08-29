@extends('layouts.app')

@section('title', 'Daftar Siswa - ' . $classroom->name)

@section('content')
@php
    $ajar = $classroom->kelasAjar();
@endphp

<div class="space-y-6 pb-12" x-data="{ showShareModal: false }">

    {{-- ══════════ 1. HEADER & ACTION BUTTONS ══════════ --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="min-w-0">
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600 transition-colors">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-slate-600 transition-colors">{{ $classroom->name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500 font-medium">Daftar Siswa</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                Daftar Siswa Kelas {{ $classroom->name }}
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500">
                @if ($ajar)
                    Kelas yang Anda ajar sebagai guru mapel — <span class="font-bold text-slate-900">NIS &amp; Nama Siswa</span>.
                @else
                    Kelola biodata lengkap, nomor kontak orang tua, dan kartu QR siswa.
                @endif
            </p>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <a href="{{ route('classes.students.qr-cards', $classroom) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all hover:scale-105">
                <span>🔖</span>
                <span>Kartu QR</span>
            </a>

            @if (! $ajar)
                <button type="button" @click="showShareModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-100 border border-emerald-200 hover:bg-emerald-200 text-emerald-950 text-xs font-bold shadow-xs transition-all hover:scale-105">
                    <span>🔗</span>
                    <span>Bagikan Form Mandiri</span>
                </button>
            @endif

            @if(Route::has('classes.students.import.form'))
                <a href="{{ route('classes.students.import.form', $classroom) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all hover:scale-105">
                    <span>📥</span>
                    <span>Impor Excel</span>
                </a>
            @endif

            <a href="{{ route('classes.students.create', $classroom) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm shadow-emerald-200 transition-all hover:scale-105">
                <span>+</span>
                <span>Tambah Siswa</span>
            </a>
        </div>
    </div>

    {{-- NAVIGASI KELAS --}}
    @include('partials.class-nav', ['classroom' => $classroom])

    @include('partials.flash')

    {{-- ══════════ 2. CONTAINER DAFTAR SISWA ══════════ --}}
    <section class="bg-white rounded-3xl border border-emerald-200/80 shadow-xs overflow-hidden">

        {{-- Top Bar: Total count & Search Input --}}
        <div class="p-4 sm:px-6 sm:py-4 border-b border-emerald-100 bg-emerald-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="text-lg">👥</span>
                <h2 class="text-sm font-bold text-slate-900">{{ $students->total() }} Siswa Terdaftar</h2>
            </div>

            <form method="GET" action="{{ route('classes.students.index', $classroom) }}" class="flex items-center gap-2">
                <div class="relative flex-1 sm:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">🔍</span>
                    <input id="cari" type="search" name="cari" value="{{ $cari ?? '' }}"
                           placeholder="Cari nama atau NIS..."
                           class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                </div>
                <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition-colors">
                    Cari
                </button>
                @if($cari)
                    <a href="{{ route('classes.students.index', $classroom) }}" class="px-2.5 py-1.5 text-xs text-slate-500 hover:text-slate-800 font-semibold">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        @if ($students->isNotEmpty())
            {{-- A. TAMPILAN DESKTOP (Tabel Modern) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-emerald-100 bg-emerald-50/60 text-[11px] font-bold uppercase tracking-wider text-emerald-950">
                            <th scope="col" class="py-3 px-4 text-center w-12">No</th>
                            <th scope="col" class="py-3 px-4">Nama Siswa</th>
                            <th scope="col" class="py-3 px-4">{{ $ajar ? 'NIS' : 'NIS / NISN' }}</th>
                            @unless ($ajar)
                                <th scope="col" class="py-3 px-4 text-center w-16">Gender</th>
                                <th scope="col" class="py-3 px-4">Kontak Orang Tua</th>
                            @endunless
                            <th scope="col" class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-emerald-100 text-sm">
                        @foreach ($students as $index => $s)
                            <tr class="hover:bg-emerald-50/40 transition-colors group">
                                <td class="py-3.5 px-4 text-center font-mono text-xs text-slate-400 font-semibold">
                                    {{ $students->firstItem() + $index }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl font-bold text-xs flex items-center justify-center shrink-0 bg-emerald-100 text-emerald-950 border border-emerald-200 shadow-2xs">
                                            {{ Str::upper(Str::substr($s->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('classes.students.show', [$classroom, $s]) }}"
                                               class="font-bold text-slate-900 hover:text-emerald-700 transition-colors truncate block">
                                                {{ $s->name }}
                                            </a>
                                            @if(!$s->is_active)
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-slate-100 text-slate-600">Nonaktif</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-xs text-slate-700 font-medium">
                                    @if ($ajar)
                                        {{ $s->nis ?: '—' }}
                                    @else
                                        <span>{{ $s->nis ?: '—' }}</span>
                                        @if($s->nisn)
                                            <span class="text-slate-400 font-normal"> / {{ $s->nisn }}</span>
                                        @endif
                                    @endif
                                </td>
                                @unless ($ajar)
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-950 border border-emerald-200"
                                              title="{{ $s->gender === 'L' ? 'Laki-laki' : ($s->gender === 'P' ? 'Perempuan' : 'Belum diisi') }}">
                                            {{ $s->gender ?: '—' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-xs">
                                        @if($s->parent_phone)
                                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $s->parent_phone) }}"
                                               target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1.5 font-mono text-slate-800 hover:text-emerald-700 font-semibold transition-colors">
                                                <span class="text-emerald-600 font-bold">💬</span>
                                                <span>{{ $s->parent_phone }}</span>
                                            </a>
                                        @else
                                            <span class="text-slate-400 font-mono">—</span>
                                        @endif
                                    </td>
                                @endunless
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('classes.students.show', [$classroom, $s]) }}"
                                           class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-950 border border-emerald-200 text-xs font-bold transition-colors">
                                            Detail
                                        </a>
                                        <a href="{{ route('classes.students.edit', [$classroom, $s]) }}"
                                           class="px-2.5 py-1 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 text-xs font-semibold transition-colors">
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- B. TAMPILAN MOBILE (Card List Adaptif) --}}
            <div class="md:hidden divide-y divide-emerald-100">
                @foreach ($students as $index => $s)
                    <div class="p-4 hover:bg-emerald-50/30 transition-colors flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="w-10 h-10 rounded-2xl font-bold text-sm flex items-center justify-center shrink-0 bg-emerald-100 text-emerald-950 border border-emerald-200 shadow-2xs">
                                {{ Str::upper(Str::substr($s->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('classes.students.show', [$classroom, $s]) }}"
                                   class="font-bold text-sm text-slate-900 truncate block hover:text-emerald-700">
                                    {{ $s->name }}
                                </a>
                                <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-500">
                                    <span class="font-mono">{{ $s->nis ?: 'No NIS' }}</span>
                                    @if($s->gender)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-950 border border-emerald-200">
                                            {{ $s->gender }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            @if($s->parent_phone)
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $s->parent_phone) }}" target="_blank" rel="noopener"
                                   class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 flex items-center justify-center text-sm font-bold border border-emerald-200 transition-colors"
                                   title="Chat WhatsApp Orang Tua">
                                    💬
                                </a>
                            @endif
                            <a href="{{ route('classes.students.show', [$classroom, $s]) }}"
                               class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-950 text-xs font-bold border border-emerald-200 transition-colors">
                                Detail &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if ($students->hasPages())
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-emerald-100 px-5 py-3.5 text-xs text-slate-500 bg-emerald-50/50">
                    <span class="font-medium text-slate-700">Menampilkan {{ $students->firstItem() }}–{{ $students->lastItem() }} dari {{ $students->total() }} siswa</span>
                    <div>{{ $students->links() }}</div>
                </div>
            @endif
        @else
            <div class="p-12 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-3xl mx-auto shadow-xs border border-emerald-200">👥</div>
                <div class="space-y-1 max-w-sm mx-auto">
                    <p class="text-sm font-bold text-slate-900">Belum Ada Siswa Ditemukan</p>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        @if ($ajar)
                            Tambahkan siswa baru atau impor dari Excel (cukup kolom NIS dan Nama).
                        @else
                            Tambahkan siswa baru secara manual atau bagikan tautan form mandiri kepada orang tua.
                        @endif
                    </p>
                </div>
                <a href="{{ route('classes.students.create', $classroom) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-200 transition-all">
                    + Tambah Siswa Pertama
                </a>
            </div>
        @endif
    </section>

    {{-- ══════════ 3. DANGER ZONE ══════════ --}}
    @if ($totalKelas > 0)
        <section class="rounded-3xl border border-emerald-200 bg-white overflow-hidden shadow-xs"
                 x-data="{ buka: {{ $errors->has('nama_kelas') ? 'true' : 'false' }}, ketikan: '' }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-emerald-100 bg-emerald-50/60 px-5 py-3.5">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">⚠️ Area Khusus: Kosongkan Kelas</h2>
                    <p class="mt-0.5 text-xs text-slate-600">
                        Memindahkan seluruh {{ $totalKelas }} siswa ke Arsip. Riwayat absensi dan nilai tetap tersimpan.
                    </p>
                </div>
                <button type="button" x-show="!buka" @click="buka = true"
                        class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition-colors shrink-0 self-start sm:self-auto border border-slate-200">
                    Kosongkan Kelas…
                </button>
            </div>

            <div x-show="buka" x-cloak class="p-5">
                <form method="POST" action="{{ route('classes.students.destroy-all', $classroom) }}" class="space-y-3">
                    @csrf
                    @method('DELETE')

                    <label for="nama_kelas" class="block text-xs font-bold text-slate-900">
                        Ketik <span class="font-mono font-bold text-slate-900 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">{{ $classroom->name }}</span> untuk mengonfirmasi:
                    </label>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <input id="nama_kelas" name="nama_kelas" type="text" x-model="ketikan" autocomplete="off"
                               placeholder="{{ $classroom->name }}"
                               class="px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 sm:max-w-xs {{ $errors->has('nama_kelas') ? 'border-slate-800 ring-1 ring-slate-800' : '' }}">

                        <button type="submit"
                                :disabled="ketikan.trim().replace(/\s+/g, ' ').toLowerCase() !== @js(\Illuminate\Support\Str::lower(\Illuminate\Support\Str::squish($classroom->name)))"
                                class="px-4 py-2 rounded-xl bg-slate-900 disabled:bg-slate-200 disabled:text-slate-400 hover:bg-black text-white text-xs font-bold transition-colors shrink-0">
                            Pindahkan {{ $totalKelas }} Siswa ke Arsip
                        </button>

                        <button type="button" @click="buka = false; ketikan = ''"
                                class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors shrink-0">
                            Batal
                        </button>
                    </div>

                    @error('nama_kelas')
                        <p class="text-xs text-slate-900 font-bold">{{ $message }}</p>
                    @enderror

                    <p class="text-[11px] text-slate-400">
                        Setelah dikosongkan, data dapat dipulihkan kapan saja melalui <a href="{{ route('classes.students.trashed', $classroom) }}" class="font-bold text-emerald-700 underline">Arsip Siswa</a>.
                    </p>
                </form>
            </div>
        </section>
    @endif

    {{-- ══════════ 4. MODAL BAGIKAN FORM MANDIRI ══════════ --}}
    @unless ($ajar)
    <div x-show="showShareModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showShareModal = false"></div>

        {{-- Dialog --}}
        <div class="relative w-full max-w-md rounded-3xl border border-emerald-200 bg-[#f0fdf4] p-6 shadow-2xl space-y-4 animate-naik">
            <div class="flex items-center justify-between border-b border-emerald-200 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📋</span>
                    <h2 class="text-sm font-bold text-slate-900">Form Mandiri Biodata Siswa</h2>
                </div>
                <button type="button" @click="showShareModal = false" class="w-8 h-8 rounded-full bg-emerald-100 hover:bg-emerald-200 flex items-center justify-center text-emerald-950 font-bold">
                    ✕
                </button>
            </div>

            <div class="space-y-3.5">
                <p class="text-xs text-slate-600 leading-relaxed font-medium">
                    Bagikan tautan ini ke grup WhatsApp kelas atau orang tua siswa. Siswa/wali murid dapat mengisi NISN, NIK, alamat lengkap, dan nomor kontak langsung dari HP masing-masing.
                </p>

                <div class="p-3 bg-white rounded-2xl border border-emerald-200 space-y-2">
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">Tautan Form Publik:</label>
                    <input type="text" readonly value="{{ route('public.biodata.show', $classroom->tokenPublik()) }}"
                           class="w-full bg-emerald-50/50 px-3 py-2 text-xs font-mono rounded-xl border border-emerald-200 select-all focus:outline-none font-bold text-emerald-950">
                </div>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <button type="button"
                            onclick="navigator.clipboard.writeText('{{ route('public.biodata.show', $classroom->tokenPublik()) }}'); window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Tautan berhasil disalin!', type: 'success' } }));"
                            class="w-full py-2.5 px-3 rounded-xl bg-white hover:bg-emerald-50 border border-emerald-200 text-slate-800 text-xs font-bold transition-all">
                        📋 Salin Tautan
                    </button>
                    <a href="https://wa.me/?text={{ urlencode('Bapak/Ibu wali siswa kelas ' . $classroom->name . ', mohon melengkapi biodata ananda melalui formulir mandiri berikut: ' . route('public.biodata.show', $classroom->tokenPublik())) }}"
                       target="_blank" rel="noopener"
                       class="w-full py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold text-center transition-all flex items-center justify-center gap-1.5 shadow-md shadow-emerald-200">
                        <span>💬</span>
                        <span>Kirim ke WA</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endunless

</div>
@endsection
