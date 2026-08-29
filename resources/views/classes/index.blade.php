@extends('layouts.app')

@section('title', 'Daftar Kelas')

@section('content')
<div class="space-y-6 pb-12" x-data="{
    showModal: false,
    shareClassId: '',
    className: '',
    shareLink: '',
    shareKind: 'biodata'
}">

    {{-- ══════════ 1. HEADER HALAMAN ══════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600">Beranda</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Kelas</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                Kelola Kelas &amp; Perwalian
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500 font-medium">
                Pilih kelas untuk mengelola absensi harian, jurnal, pelanggaran, buku kas, dan laporan.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('classes.trashed') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition-all">
                <span>🗑️</span>
                <span>Arsip Kelas</span>
            </a>
            <a href="{{ route('classes.create') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm shadow-emerald-200 transition-all hover:scale-105">
                <span>+</span>
                <span>Buat Kelas Baru</span>
            </a>
        </div>
    </div>

    @include('partials.flash')

    {{-- ══════════ 2. FILTER JENIS KELAS ══════════ --}}
    @if ($jumlahPerwalian > 0 && $jumlahAjar > 0)
        @php
            $saringan = [
                [null, 'Semua Kelas', $jumlahPerwalian + $jumlahAjar],
                ['perwalian', 'Wali Kelas', $jumlahPerwalian],
                ['ajar', 'Guru Mapel', $jumlahAjar],
            ];
        @endphp
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500 font-bold">Tampilkan:</span>
            <div class="inline-flex bg-emerald-100/70 p-1 rounded-2xl border border-emerald-200">
                @foreach ($saringan as [$nilai, $label, $jumlah])
                    @php $ini = $jenisDipilih === $nilai; @endphp
                    <a href="{{ route('classes.index', $nilai ? ['jenis' => $nilai] : []) }}"
                       class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all
                              {{ $ini ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-700 hover:text-slate-900' }}">
                        <span>{{ $label }}</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $ini ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-300 text-slate-700' }}">{{ $jumlah }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ══════════ 3. DAFTAR KELAS (CARD LIST MODERN) ══════════ --}}
    @if ($classes->isEmpty())
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-12 text-center space-y-4 shadow-xs">
            <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-3xl mx-auto shadow-xs border border-emerald-200">🏫</div>
            <div class="space-y-1 max-w-sm mx-auto">
                <p class="text-base font-bold text-slate-900">Belum Ada Kelas Terdaftar</p>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Buat kelas pertama Anda untuk mulai mengelola presensi harian, biodata siswa, dan laporan siap cetak.
                </p>
            </div>
            <a href="{{ route('classes.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-200 transition-all">
                + Buat Kelas Pertama
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($classes as $class)
                @php
                    $ajar = $class->kelasAjar();
                    $pct = $class->today_attendance_percent;
                @endphp
                <div class="bg-white rounded-3xl border border-emerald-200/80 p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between group">
                    <div>
                        {{-- Top Badges --}}
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-extrabold bg-emerald-100 text-emerald-950 border border-emerald-200">
                                    {{ $ajar ? 'Guru Mapel' : 'Wali Kelas' }}
                                </span>
                                @if ($jumlahPerwalian > 0 && $jumlahAjar > 0 && ! $ajar && $jenisDipilih === null)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                        Kelas Wali Anda
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('classes.edit', $class) }}"
                                   class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-100 flex items-center justify-center text-slate-700 hover:text-slate-950 transition-colors"
                                   title="Pengaturan Kelas">
                                    ⚙️
                                </a>
                            </div>
                        </div>

                        {{-- Class Name & Details --}}
                        <div class="space-y-1 mb-4">
                            <a href="{{ route('classes.show', $class) }}"
                               class="text-lg font-extrabold text-slate-900 group-hover:text-emerald-700 transition-colors block truncate">
                                {{ $class->name }}
                            </a>
                            <p class="text-xs text-slate-500 font-medium truncate">
                                @if ($ajar)
                                    Mapel: <strong class="text-emerald-800">{{ implode(', ', $class->mapelDiampu()) ?: 'Mapel Diampu' }}</strong>
                                @else
                                    Jurusan: <strong class="text-slate-700">{{ $class->major ?: 'Umum / Reguler' }}</strong>
                                @endif
                                &middot; TA {{ $class->academic_year ?? '2026/2027' }}
                            </p>
                        </div>

                        {{-- Stats Quick View --}}
                        <div class="grid grid-cols-2 gap-2 p-3 rounded-2xl bg-emerald-50/50 border border-emerald-100 mb-4">
                            <div>
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Total Siswa</span>
                                <span class="text-sm font-extrabold text-slate-900">
                                    {{ $class->students_count ?? 0 }} <span class="text-xs font-semibold text-slate-500">Siswa</span>
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Presensi Hari Ini</span>
                                @if ($pct !== null)
                                    <span class="text-sm font-extrabold text-emerald-800">
                                        {{ $pct }}%
                                    </span>
                                @else
                                    <span class="text-xs font-semibold text-slate-400">Belum absen</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Actions Bottom --}}
                    <div class="space-y-2 pt-2 border-t border-emerald-100">
                        <div class="grid grid-cols-2 gap-2">
                            <a href="{{ route('classes.show', $class) }}"
                               class="w-full py-2 px-3 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-950 text-xs font-bold text-center transition-colors border border-emerald-200">
                                Masuk Kelas &rarr;
                            </a>
                            <a href="{{ $ajar ? route('classes.attendance.manual.create', $class) : route('classes.attendance.index', $class) }}"
                               class="w-full py-2 px-3 rounded-xl bg-slate-100 hover:bg-emerald-100 text-slate-800 text-xs font-bold text-center transition-colors border border-slate-200">
                                {{ $ajar ? 'Absen Manual' : '📋 Absensi' }}
                            </a>
                        </div>

                        @unless ($ajar)
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button"
                                    @click="shareClassId = '{{ $class->id }}'; className = '{{ $class->name }}'; shareLink = '{{ route('public.biodata.show', $class->tokenPublik()) }}'; shareKind = 'biodata'; showModal = true"
                                    class="w-full py-1.5 px-2 rounded-xl bg-white hover:bg-emerald-50 border border-emerald-200 text-slate-700 text-[11px] font-bold transition-colors truncate">
                                🔗 Form Biodata
                            </button>
                            <button type="button"
                                    @click="shareClassId = '{{ $class->id }}'; className = '{{ $class->name }}'; shareLink = '{{ route('public.excuse.show', $class->tokenPublik()) }}'; shareKind = 'excuse'; showModal = true"
                                    class="w-full py-1.5 px-2 rounded-xl bg-white hover:bg-emerald-50 border border-emerald-200 text-slate-700 text-[11px] font-bold transition-colors truncate">
                                💬 Link Izin/Sakit
                            </button>
                        </div>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($classes->hasPages())
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-500 pt-2">
                <span class="font-medium">Menampilkan {{ $classes->firstItem() }}–{{ $classes->lastItem() }} dari {{ $classes->total() }} kelas</span>
                <div>{{ $classes->links() }}</div>
            </div>
        @endif
    @endif

    {{-- ══════════ 4. MODAL BAGIKAN TAUTAN WHATSAPP ══════════ --}}
    @if ($classes->contains(fn ($k) => ! $k->kelasAjar()))
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>

        {{-- Modal Dialog in Soft Bright Green --}}
        <div class="relative w-full max-w-lg rounded-3xl border border-emerald-200 bg-[#f0fdf4] p-6 shadow-2xl space-y-4 animate-naik">
            <div class="flex items-start justify-between border-b border-emerald-200 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900" x-text="shareKind === 'excuse' ? 'Bagikan Link Izin/Sakit' : 'Bagikan Form Mandiri Siswa'"></h2>
                    <p class="text-xs text-slate-600 mt-0.5 font-medium">Kelas <span x-text="className" class="font-extrabold text-emerald-900"></span></p>
                </div>
                <button type="button" @click="showModal = false" class="w-8 h-8 rounded-full bg-emerald-100 hover:bg-emerald-200 flex items-center justify-center text-emerald-900 font-bold">
                    ✕
                </button>
            </div>

            <div class="space-y-3.5">
                <div class="p-3.5 rounded-2xl bg-white border border-emerald-200 text-xs leading-relaxed text-slate-800 space-y-2">
                    <template x-if="shareKind === 'excuse'">
                        <div class="space-y-1.5">
                            <p class="font-bold text-slate-900">📌 Format Pesan WhatsApp:</p>
                            <p>Assalamu'alaikum Wr. Wb. / Selamat Pagi Bapak/Ibu Wali Siswa Kelas <strong x-text="className"></strong>,</p>
                            <p>Mulai sekarang, pengajuan izin/sakit ananda dapat dilaporkan langsung melalui tautan resmi sekolah:</p>
                            <p class="break-all font-mono text-emerald-950 bg-emerald-50 p-2 rounded-xl border border-emerald-200 font-bold" x-text="shareLink"></p>
                        </div>
                    </template>
                    <template x-if="shareKind !== 'excuse'">
                        <div class="space-y-1.5">
                            <p class="font-bold text-slate-900">📌 Format Pesan WhatsApp:</p>
                            <p>Assalamu'alaikum Wr. Wb. / Selamat Pagi Bapak/Ibu Wali Siswa Kelas <strong x-text="className"></strong>,</p>
                            <p>Sehubungan dengan pembaruan data kesiswaan, mohon kesediaan mengisi Form Biodata Mandiri melalui tautan resmi:</p>
                            <p class="break-all font-mono text-emerald-950 bg-emerald-50 p-2 rounded-xl border border-emerald-200 font-bold" x-text="shareLink"></p>
                        </div>
                    </template>
                </div>

                <div class="space-y-2 border-t border-emerald-200 pt-3">
                    <form :action="'/classes/' + shareClassId + '/share-' + (shareKind === 'excuse' ? 'excuse' : 'biodata') + '-wa'" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold text-center transition-all flex items-center justify-center gap-1.5 shadow-md shadow-emerald-200">
                            <span>💬</span>
                            <span>Kirim Langsung ke Grup WhatsApp Kelas</span>
                        </button>
                    </form>

                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="navigator.clipboard.writeText(shareLink); window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Tautan berhasil disalin!', type: 'success' } }));"
                                class="w-full py-2 px-3 rounded-xl bg-white hover:bg-emerald-50 border border-emerald-200 text-slate-800 text-xs font-bold transition-all">
                            📋 Salin Tautan
                        </button>
                        <button type="button" @click="showModal = false"
                                class="w-full py-2 px-3 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-950 text-xs font-bold transition-all">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
