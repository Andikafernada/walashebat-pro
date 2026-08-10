@extends('layouts.app')

@section('title', 'Riwayat Absensi - ' . $classroom->name)

@section('content')
<div class="space-y-6 pb-12">
    <!-- Header Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('classes.index') }}" class="hover:text-indigo-600 transition-colors">Kelas</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">{{ $classroom->name }}</span>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">Riwayat Absensi</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Sesi &amp; Riwayat Absensi
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                @if ($classroom->kelasAjar())
                    Riwayat kehadiran mapel yang Anda ampu di kelas {{ $classroom->name }}
                @else
                    Kelola sesi absensi harian dan tautan Magic Link untuk kelas {{ $classroom->name }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{--
                Rekap Kehadiran ditautkan dari sini, bukan dari tab navigasi
                yang sudah berisi 12 butir. Halamannya sudah lengkap tetapi
                selama ini tidak punya satu pun tautan, dan orang mencarinya
                justru di sini — bersama riwayat absensi.
            --}}
            <a href="{{ route('classes.reports.attendance', $classroom) }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 transition-all">
                📋 Rekap Kehadiran
            </a>
            <a href="{{ route('classes.attendance.manual.create', $classroom) }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-emerald-500/20 hover:bg-emerald-700 active:scale-95 transition-all">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                {{-- Di kelas ajar ini bukan jalur cadangan untuk menambal tanggal
                     lampau, melainkan SATU-SATUNYA cara mengabsen. Labelnya
                     jangan membuatnya terbaca sebagai pilihan kedua. --}}
                ✏️ {{ $classroom->kelasAjar() ? 'Isi Absensi' : 'Input Absensi Manual (Tanggal Lampau)' }}
            </a>
        </div>
    </div>

    <!-- Class Navigation -->
    @include('partials.class-nav', ['classroom' => $classroom])

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- Top Metrics Row -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <!-- Kuota Hari Ini -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kuota Hari Ini</span>
                <span @class([
                    'inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold',
                    'bg-emerald-100 text-emerald-800' => $terpakaiHariIni < $kuotaHarian,
                    'bg-amber-100 text-amber-800' => $terpakaiHariIni >= $kuotaHarian,
                ])>
                    {{ $terpakaiHariIni }}/{{ $kuotaHarian }} Sesi
                </span>
            </div>
            <p class="mt-2 text-2xl font-black tracking-tight text-slate-900">{{ $terpakaiHariIni }}</p>
            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                <div @class([
                    'h-full rounded-full transition-all duration-300',
                    'bg-emerald-500' => $terpakaiHariIni < $kuotaHarian,
                    'bg-amber-500' => $terpakaiHariIni >= $kuotaHarian,
                ]) style="width: {{ min(100, ($terpakaiHariIni / max(1, $kuotaHarian)) * 100) }}%"></div>
            </div>
        </div>

        <!-- Total Sesi -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Total Sesi</span>
            <p class="mt-2 text-2xl font-black tracking-tight text-slate-900">{{ $sessions->total() }}</p>
            <p class="mt-1 text-[11px] text-slate-400">Seluruh sesi tercatat</p>
        </div>

        <!-- Total Siswa -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Total Siswa Aktif</span>
            <p class="mt-2 text-2xl font-black tracking-tight text-indigo-600">{{ $totalSiswa }}</p>
            <p class="mt-1 text-[11px] text-slate-400">Siswa di kelas ini</p>
        </div>

        <!-- Status Sesi Hari Ini -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Sesi Aktif</span>
            <div class="mt-2 flex items-center gap-2">
                @if ($adaSesiTerbuka)
                    <span class="h-3 w-3 rounded-full bg-emerald-500 animate-ping"></span>
                    <span class="text-sm font-bold text-emerald-700">Ada Sesi Terbuka</span>
                @else
                    <span class="h-3 w-3 rounded-full bg-slate-300"></span>
                    <span class="text-sm font-semibold text-slate-500">Tidak ada sesi aktif</span>
                @endif
            </div>
            <p class="mt-1 text-[11px] text-slate-400">Status hari ini</p>
        </div>
    </div>

    <!-- Main Two-Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        <!-- LEFT COLUMN: List Riwayat Sesi (2/3 width) -->
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Daftar Sesi Absensi</h3>
                        <p class="text-xs text-slate-500">Riwayat sesi absensi yang dibuat untuk kelas ini</p>
                    </div>
                    <span class="text-xs font-semibold text-slate-400">{{ $sessions->total() }} Sesi</span>
                </div>

                @if ($sessions->isNotEmpty())
                    <div class="space-y-3">
                        @foreach ($sessions as $s)
                            @php
                                $terisi = $s->terisi_count ?? 0;
                                $hadir = $s->hadir_count ?? 0;
                                $persen = $totalSiswa > 0 ? round(($terisi / $totalSiswa) * 100) : 0;
                            @endphp
                            <div class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs hover:shadow-md hover:border-indigo-300 transition-all">
                                
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap mb-1">
                                            <span class="font-bold text-slate-900 text-sm">
                                                {{ $s->title ?: 'Absensi ' . $s->session_date->isoFormat('D MMMM YYYY') }}
                                            </span>

                                            <!-- Sequence Tag -->
                                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                                                #{{ $s->sequence ?? 1 }}
                                            </span>

                                            <!-- Status Pill -->
                                            @switch($s->status)
                                                @case('open')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 border border-emerald-200">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                                        Terbuka
                                                    </span>
                                                    @break
                                                @case('submitted')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-bold text-blue-700 border border-blue-200">
                                                        Selesai
                                                    </span>
                                                    @break
                                                @case('expired')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700 border border-amber-200">
                                                        Kadaluarsa
                                                    </span>
                                                    @break
                                                @case('cancelled')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-700 border border-rose-200">
                                                        Dibatalkan
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-700">
                                                        {{ $s->status }}
                                                    </span>
                                            @endswitch
                                        </div>

                                        <div class="flex items-center gap-4 text-xs text-slate-500 flex-wrap mt-1.5">
                                            <span class="flex items-center gap-1">
                                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                {{ $s->session_date->format('d/m/Y') }}
                                            </span>
                                            @if ($s->expires_at)
                                                <span class="flex items-center gap-1">
                                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    s/d {{ $s->expires_at->format('H:i') }} WIB
                                                </span>
                                            @endif
                                            <!-- WA Delivery -->
                                            @if ($s->delivery_status === 'sent')
                                                <span class="inline-flex items-center gap-1 text-emerald-600 font-semibold text-[11px]">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    WA Terkirim
                                                </span>
                                            @elseif ($s->delivery_status === 'failed')
                                                <span class="inline-flex items-center gap-1 text-rose-600 font-semibold text-[11px]">
                                                    WA Gagal
                                                </span>
                                            @elseif ($s->delivery_status === 'skipped')
                                                <span class="inline-flex items-center gap-1 text-amber-600 font-semibold text-[11px]" title="{{ $s->delivery_error }}">
                                                    WA Nonaktif
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Fill Rate Progress & Action Buttons -->
                                    <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                        
                                        <!-- Progress Bar Pill -->
                                        {{-- "Terisi" dan "hadir" WAJIB terbaca terpisah. Sesi dengan
                                             2 siswa terisi tapi hanya 1 hadir pernah tampil sebagai
                                             "2/2 Siswa" saja — terbaca seolah semuanya masuk. --}}
                                        <div class="text-right">
                                            <span class="text-xs font-bold text-slate-800">Terisi {{ $terisi }}/{{ $totalSiswa }}</span>
                                            <span class="block text-[10px] font-semibold text-emerald-700">Hadir {{ $hadir }}</span>
                                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-100 mt-1">
                                                <div class="h-full rounded-full bg-indigo-600" style="width: {{ $persen }}%"></div>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <a href="{{ route('classes.attendance.edit', [$classroom, $s]) }}" 
                                               class="h-8 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1 shadow-xs transition-colors">
                                                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                Koreksi
                                            </a>
                                            <a href="{{ route('classes.attendance.show', [$classroom, $s]) }}" 
                                               class="h-8 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white px-3 text-xs font-bold flex items-center gap-1 shadow-xs transition-colors">
                                                Detail &amp; PIN
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </a>
                                        </div>

                                    </div>

                                </div>

                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if ($sessions->hasPages())
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Menampilkan {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} dari {{ $sessions->total() }} sesi</span>
                            <div>{{ $sessions->links() }}</div>
                        </div>
                    @endif
                @else
                    <div class="my-10 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-500 mb-3">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-800">Belum ada sesi absensi yang dibuat</p>
                        <p class="mt-1 text-xs text-slate-500">Gunakan formulir di samping untuk membuat sesi absensi baru.</p>
                    </div>
                @endif

            </div>
        </div>

        <!-- RIGHT COLUMN: Form Buat Sesi Baru (1/3 width) -->
        <div class="space-y-4">
            @if ($classroom->kelasAjar())
                {{--
                    Kelas ajar tidak menerbitkan magic link.

                    Alur magic link mengirim tautan + PIN ke Seksi Absensi lewat
                    grup atau nomor kelas — dan pada kelas ajar keduanya milik
                    wali kelas LAIN. Struktur organisasi pun tidak ada di sini
                    (disembunyikan di class-nav), sehingga tidak ada Seksi
                    Absensi yang bisa dituju: formulirnya menjanjikan pengiriman
                    yang tidak punya penerima, dengan centang "Kirim Otomatis
                    via WhatsApp" yang bahkan menyala secara bawaan.

                    Guru mapel hadir sendiri di jam pelajarannya, jadi jalur yang
                    benar memang mengisi kehadiran langsung. Penjagaan
                    sesungguhnya ada di controller (bolehKirimWa), bukan di sini.
                --}}
                <div class="rounded-2xl border border-teal-200 bg-teal-50/60 p-5 shadow-sm space-y-4">
                    <div class="flex items-center gap-3 border-b border-teal-200 pb-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-600 text-white shadow-md shadow-teal-500/20">
                            📚
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-teal-950">Absensi Kelas Ajar</h3>
                            <p class="text-xs text-teal-800">Isi kehadiran langsung di jam pelajaran</p>
                        </div>
                    </div>

                    <p class="text-xs text-teal-900/80">
                        Kelas ini Anda ajar mapelnya, bukan Anda walikelasi. Magic link absensi
                        dikirim ke Seksi Absensi lewat grup kelas — grup itu milik wali kelasnya,
                        jadi sesi otomatis tidak diterbitkan dari sini.
                    </p>

                    <a href="{{ route('classes.attendance.manual.create', $classroom) }}"
                       class="flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-teal-600 text-xs font-bold text-white shadow-md shadow-teal-500/20 transition-all hover:bg-teal-700 active:scale-95">
                        ✏️ Isi Absensi Sekarang
                    </a>

                    <p class="text-[11px] text-teal-800/70">
                        Mapel dan materi yang Anda isi di sana sekaligus menjadi catatan
                        <a href="{{ route('classes.jurnal.index', $classroom) }}" class="font-bold underline">Jurnal Mengajar</a>.
                    </p>
                </div>
            @else
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-4">

                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-md shadow-indigo-500/20">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Buat Sesi Absensi Baru</h3>
                        <p class="text-xs text-slate-500">Terbitkan Magic Link &amp; PIN Harian</p>
                    </div>
                </div>

                <!-- Form -->
                <form method="POST" action="{{ route('classes.attendance.store', $classroom) }}" class="space-y-4" x-data="{ wa: true, loading: false }" @submit="loading = true">
                    @csrf

                    <div>
                        <label for="title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Judul Sesi <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                        <input id="title" name="title" value="{{ old('title') }}" type="text" placeholder="cth: Absensi Pagi, Khusus..." 
                               class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="ttl_minutes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Masa Berlaku (Menit)</label>
                        <input id="ttl_minutes" name="ttl_minutes" type="number" value="{{ config('walikelas.session_ttl_minutes', 90) }}" min="5" max="720" 
                               class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <p class="mt-1 text-[11px] text-slate-400">Default 90 menit (min 5, max 720 menit)</p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3 border border-slate-100 space-y-3">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="send_wa" value="1" x-model="wa" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-xs font-bold text-slate-800">Kirim Otomatis via WhatsApp</span>
                                <p class="text-[11px] text-slate-500">Magic link dikirim ke Seksi Absensi</p>
                            </div>
                        </label>

                        <div x-show="wa" x-cloak class="pt-2 border-t border-slate-200/60">
                            <label for="target_number" class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Nomor Tujuan WA <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                            <input id="target_number" name="target_number" value="{{ old('target_number') }}" type="tel" placeholder="6281234567890" 
                                   class="h-8 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                            <p class="mt-1 text-[10px] text-slate-400">Kosongkan = Seksi Absensi / Ketua Kelas</p>
                        </div>
                    </div>

                    @if ($adaSesiTerbuka && $terpakaiHariIni < $kuotaHarian)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900 space-y-1">
                            <label class="flex cursor-pointer items-start gap-2">
                                <input type="checkbox" name="force_new" value="1" class="mt-0.5 h-4 w-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                <div>
                                    <span class="font-bold">Buat Sesi Tambahan</span>
                                    <p class="text-[11px] text-amber-700">Sesi hari ini masih terbuka. Centang bila perlu tautan baru.</p>
                                </div>
                            </label>
                        </div>
                    @endif

                    <button type="submit" :disabled="loading" 
                            class="h-10 w-full rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-md shadow-indigo-500/20">
                        <template x-if="!loading">
                            <span class="flex items-center gap-1.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Buat &amp; Terbitkan Link
                            </span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center gap-1.5">
                                <span class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                Memproses...
                            </span>
                        </template>
                    </button>
                </form>

            </div>
            @endif
        </div>

    </div>
</div>
@endsection
