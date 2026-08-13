@extends('layouts.app')

@section('title', 'Riwayat Absensi - ' . $classroom->name)

@section('content')
@php
    /*
     * Kata dan warna status sesi ditulis satu kali di sini, bukan lima cabang
     * @switch bersarang di tengah markup. Statusnya sama di mana pun ia muncul.
     */
    $rupaStatus = [
        'open' => ['Terbuka', 'kode--hadir'],
        'submitted' => ['Selesai', 'kode--aksen'],
        'expired' => ['Kadaluarsa', 'kode--izin'],
        'cancelled' => ['Dibatalkan', 'kode--alfa'],
    ];
@endphp

<div class="space-y-5 pb-12">

    <div class="page-header">
        <div class="min-w-0">
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-slate-600">{{ $classroom->name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Riwayat Absensi</span>
            </nav>
            <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Sesi &amp; Riwayat Absensi</h1>
            <p class="mt-1 text-sm text-slate-500">
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
                yang sudah berisi 13 butir. Halamannya sudah lengkap tetapi
                selama ini tidak punya satu pun tautan, dan orang mencarinya
                justru di sini — bersama riwayat absensi.
            --}}
            <a href="{{ route('classes.reports.attendance', $classroom) }}" class="btn-secondary btn-secondary--sm">Rekap Kehadiran</a>

            {{-- Di kelas ajar ini bukan jalur cadangan untuk menambal tanggal
                 lampau, melainkan SATU-SATUNYA cara mengabsen. Labelnya
                 jangan membuatnya terbaca sebagai pilihan kedua. --}}
            <a href="{{ route('classes.attendance.manual.create', $classroom) }}" class="btn-primary btn-primary--sm">
                {{ $classroom->kelasAjar() ? 'Isi Absensi' : 'Input Absensi Manual (Tanggal Lampau)' }}
            </a>
        </div>
    </div>

    @include('partials.class-nav', ['classroom' => $classroom])

    @include('partials.flash')

    <dl class="deret-angka">
        <div>
            <dt class="stat-label">Kuota Hari Ini</dt>
            <dd class="stat-value">{{ $terpakaiHariIni }}<span class="text-base font-normal text-slate-400">/{{ $kuotaHarian }}</span></dd>
            <div class="bar mt-2">
                <div @class([
                    'bar__isi',
                    'bar__isi--emerald' => $terpakaiHariIni < $kuotaHarian,
                    'bar__isi--amber' => $terpakaiHariIni >= $kuotaHarian,
                ]) style="width: {{ min(100, ($terpakaiHariIni / max(1, $kuotaHarian)) * 100) }}%"></div>
            </div>
        </div>

        <div>
            <dt class="stat-label">Total Sesi</dt>
            <dd class="stat-value">{{ $sessions->total() }}</dd>
            <p class="stat-sub">Seluruh sesi tercatat</p>
        </div>

        <div>
            <dt class="stat-label">Total Siswa Aktif</dt>
            <dd class="stat-value">{{ $totalSiswa }}</dd>
            <p class="stat-sub">Siswa di kelas ini</p>
        </div>

        <div>
            <dt class="stat-label">Sesi Aktif</dt>
            {{-- Titik berdenyut diganti kode diam. Sesi terbuka bukan keadaan
                 darurat yang perlu berkedip terus-menerus di sudut mata guru
                 selama ia mengerjakan hal lain di halaman yang sama. --}}
            <dd class="mt-1.5 flex items-center gap-2">
                <span class="kode {{ $adaSesiTerbuka ? 'kode--hadir' : 'kode--kosong' }}">{{ $adaSesiTerbuka ? '●' : '○' }}</span>
                <span class="text-sm font-medium {{ $adaSesiTerbuka ? 'text-emerald-700' : 'text-slate-500' }}">
                    {{ $adaSesiTerbuka ? 'Ada Sesi Terbuka' : 'Tidak ada sesi aktif' }}
                </span>
            </dd>
            <p class="stat-sub">Status hari ini</p>
        </div>
    </dl>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        <section class="blok lg:col-span-2">
            <div class="blok__kepala">
                <h2 class="blok__judul">Daftar Sesi Absensi</h2>
                <span class="kode">{{ $sessions->total() }}</span>
            </div>

            @if ($sessions->isNotEmpty())
                <div class="blok__daftar">
                    @foreach ($sessions as $s)
                        @php
                            $terisi = $s->terisi_count ?? 0;
                            $hadir = $s->hadir_count ?? 0;
                            $persen = $totalSiswa > 0 ? round(($terisi / $totalSiswa) * 100) : 0;
                            [$labelStatus, $gayaStatus] = $rupaStatus[$s->status] ?? [$s->status, ''];
                        @endphp
                        <div class="flex flex-col gap-3 p-3.5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="kode kode--urut">#{{ $s->sequence ?? 1 }}</span>
                                    <span class="text-sm font-medium text-slate-900">
                                        {{ $s->title ?: 'Absensi ' . $s->session_date->isoFormat('D MMMM YYYY') }}
                                    </span>
                                    <span class="kode {{ $gayaStatus }}">{{ $labelStatus }}</span>
                                </div>

                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                    <span class="angka">{{ $s->session_date->format('d/m/Y') }}</span>
                                    @if ($s->expires_at)
                                        <span class="angka">s/d {{ $s->expires_at->format('H:i') }} WIB</span>
                                    @endif
                                    @if ($s->delivery_status === 'sent')
                                        <span class="text-emerald-700">WA Terkirim</span>
                                    @elseif ($s->delivery_status === 'failed')
                                        <span class="text-rose-700">WA Gagal</span>
                                    @elseif ($s->delivery_status === 'skipped')
                                        <span class="text-amber-700" title="{{ $s->delivery_error }}">WA Nonaktif</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-3 sm:justify-end">
                                {{-- "Terisi" dan "hadir" WAJIB terbaca terpisah. Sesi dengan
                                     2 siswa terisi tapi hanya 1 hadir pernah tampil sebagai
                                     "2/2 Siswa" saja — terbaca seolah semuanya masuk. --}}
                                <div class="w-28 shrink-0 text-right">
                                    <p class="angka text-xs font-medium text-slate-900">Terisi {{ $terisi }}/{{ $totalSiswa }}</p>
                                    <p class="angka text-xs text-emerald-700">Hadir {{ $hadir }}</p>
                                    <div class="bar mt-1"><div class="bar__isi" style="width: {{ $persen }}%"></div></div>
                                </div>

                                <div class="flex shrink-0 items-center gap-1.5">
                                    <a href="{{ route('classes.attendance.edit', [$classroom, $s]) }}" class="btn-ghost btn-ghost--sm">Koreksi</a>
                                    <a href="{{ route('classes.attendance.show', [$classroom, $s]) }}" class="btn-secondary btn-secondary--sm">Detail &amp; PIN</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($sessions->hasPages())
                    <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-2.5 text-xs text-slate-500">
                        <span class="angka">Menampilkan {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} dari {{ $sessions->total() }} sesi</span>
                        <div>{{ $sessions->links() }}</div>
                    </div>
                @endif
            @else
                <div class="p-6 text-center">
                    <p class="text-sm font-medium text-slate-900">Belum ada sesi absensi yang dibuat</p>
                    <p class="mt-1 text-sm text-slate-500">Gunakan formulir di samping untuk membuat sesi absensi baru.</p>
                </div>
            @endif
        </section>

        <div>
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
                <section class="blok">
                    <div class="blok__kepala">
                        <h2 class="blok__judul">Absensi Kelas Ajar</h2>
                        <span class="badge badge--emerald">Guru Mapel</span>
                    </div>
                    <div class="blok__isi space-y-3">
                        <p class="text-sm text-slate-600">
                            Kelas ini Anda ajar mapelnya, bukan Anda walikelasi. Magic link absensi
                            dikirim ke Seksi Absensi lewat grup kelas — grup itu milik wali kelasnya,
                            jadi sesi otomatis tidak diterbitkan dari sini.
                        </p>

                        <a href="{{ route('classes.attendance.manual.create', $classroom) }}" class="btn-primary w-full">Isi Absensi Sekarang</a>

                        <p class="text-xs text-slate-500">
                            Mapel dan materi yang Anda isi di sana sekaligus menjadi catatan
                            <a href="{{ route('classes.jurnal.index', $classroom) }}" class="font-medium text-indigo-700 underline">Jurnal Mengajar</a>.
                        </p>
                    </div>
                </section>
            @else
                <section class="blok">
                    <div class="blok__kepala">
                        <h2 class="blok__judul">Buat Sesi Absensi Baru</h2>
                        <span class="eyebrow">Magic link + PIN</span>
                    </div>

                    <form method="POST" action="{{ route('classes.attendance.store', $classroom) }}"
                          class="blok__isi space-y-4" x-data="{ wa: true, loading: false }" @submit="loading = true">
                        @csrf

                        <div class="form-group">
                            <label for="title" class="form-label">Judul sesi <span class="font-normal text-slate-400">(opsional)</span></label>
                            <input id="title" name="title" value="{{ old('title') }}" type="text" placeholder="cth: Absensi Pagi" class="form-input form-input--sm">
                        </div>

                        <div class="form-group">
                            <label for="ttl_minutes" class="form-label">Masa berlaku (menit)</label>
                            <input id="ttl_minutes" name="ttl_minutes" type="number" value="{{ config('walikelas.session_ttl_minutes', 90) }}" min="5" max="720" class="form-input form-input--sm">
                            <p class="form-hint">Bawaan 90 menit (min 5, maks 720).</p>
                        </div>

                        <div class="space-y-3 rounded border border-slate-200 bg-slate-50 p-3">
                            <label class="flex cursor-pointer items-start gap-2.5">
                                <input type="checkbox" name="send_wa" value="1" x-model="wa" class="form-checkbox mt-0.5">
                                <span>
                                    <span class="block text-xs font-medium text-slate-800">Kirim otomatis via WhatsApp</span>
                                    <span class="block text-xs text-slate-500">Magic link dikirim ke Seksi Absensi.</span>
                                </span>
                            </label>

                            <div x-show="wa" x-cloak class="form-group border-t border-slate-200 pt-3">
                                <label for="target_number" class="form-label">Nomor tujuan <span class="font-normal text-slate-400">(opsional)</span></label>
                                <input id="target_number" name="target_number" value="{{ old('target_number') }}" type="tel" placeholder="6281234567890" class="form-input form-input--sm font-mono">
                                <p class="form-hint">Kosongkan untuk mengirim ke Seksi Absensi atau Ketua Kelas.</p>
                            </div>
                        </div>

                        @if ($adaSesiTerbuka && $terpakaiHariIni < $kuotaHarian)
                            <label class="alert alert--warning cursor-pointer">
                                <input type="checkbox" name="force_new" value="1" class="form-checkbox mt-0.5">
                                <span>
                                    <span class="alert__title block">Buat sesi tambahan</span>
                                    <span class="alert__body block">Sesi hari ini masih terbuka. Centang bila perlu tautan baru.</span>
                                </span>
                            </label>
                        @endif

                        <button type="submit" :disabled="loading" class="btn-primary w-full">
                            <span x-show="!loading">Buat &amp; Terbitkan Link</span>
                            <span x-show="loading" x-cloak class="flex items-center gap-2">
                                <span class="spinner spinner--white"></span> Memproses…
                            </span>
                        </button>
                    </form>
                </section>
            @endif
        </div>

    </div>
</div>
@endsection
