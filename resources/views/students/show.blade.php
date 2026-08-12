@extends('layouts.app')
@section('title', 'Detail Siswa')
@section('content')

@php
    // Kode margin, huruf yang sama dengan Attendance::KODE dan seluruh rekap.
    $kodeStatus = [
        'hadir' => ['H', 'Hadir', 'kode--hadir'],
        'terlambat' => ['T', 'Terlambat', 'kode--telat'],
        'sakit' => ['S', 'Sakit', 'kode--sakit'],
        'izin' => ['I', 'Izin', 'kode--izin'],
        'alfa' => ['A', 'Alfa', 'kode--alfa'],
    ];

    $q = request()->query();

    /*
     * Kelas ajar: guru mapel melihat kehadiran, bukan biodata dan bukan poin.
     *
     * Buku poin dipegang wali kelas; menampilkannya di sini membuat guru mapel
     * mengira dirinya ikut menegakkan sanksi, padahal angkanya berasal dari
     * catatan orang lain dan tidak bisa ia perbaiki. Biodata pun bukan haknya —
     * alamat, HP orang tua, dan pekerjaan ayah/ibu milik wali kelas aslinya.
     */
    $ajar = $classroom->kelasAjar();

    $persen = $kehadiran['persen'];
    $warnaHadir = $persen === null ? 'text-slate-400' : ($persen >= 85 ? 'text-emerald-700' : ($persen >= 75 ? 'text-amber-700' : 'text-rose-700'));
@endphp

<div class="space-y-5 pb-12">

    {{--
        Kepala halaman.

        Dulu spanduk gelap bergradien: cincin bergradien mengelilingi avatar,
        titik hijau berdenyut menempel di sudutnya, dan tiga tombol berlatar
        putih transparan. Nama anak — satu-satunya hal yang dicari saat halaman
        ini dibuka — harus bersaing dengan semuanya.
    --}}
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <span class="avatar-hero {{ $siswa->gender === 'P' ? 'avatar-hero--female' : 'avatar-hero--male' }} {{ !$siswa->is_active ? 'avatar-hero--inactive' : '' }}">
                @if ($siswa->photoUrl())
                    <img src="{{ $siswa->photoUrl() }}" alt="" class="avatar-hero-img">
                @else
                    <span class="avatar-hero-initials">{{ str($siswa->name)->substr(0, 1)->upper() }}</span>
                @endif
            </span>

            <div class="min-w-0">
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">{{ $siswa->name }}</h1>
                <p class="angka mt-0.5 text-sm text-slate-500">
                    NIS {{ $siswa->nis ?: '—' }} &middot; NISN {{ $siswa->nisn ?: '—' }} &middot; {{ $classroom->name }}
                </p>
                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                    @unless ($siswa->is_active)
                        <span class="badge badge--rose">Nonaktif</span>
                    @endunless
                    @foreach ($peran ?? [] as $p)
                        <span class="badge badge--indigo">{{ $p->roleLabel() }}</span>
                    @endforeach
                    @if ($siswa->seat)
                        <span class="badge">Baris {{ $siswa->seat->row_index + 1 }}, kolom {{ $siswa->seat->col_index + 1 }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @if ($siswa->parent_phone)
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $siswa->parent_phone) }}" target="_blank" rel="noopener"
                   class="btn-secondary btn-secondary--sm">Hubungi ortu</a>
            @endif
            <a href="{{ route('classes.students.pdf', [$classroom, $siswa] + $q) }}" class="btn-secondary btn-secondary--sm">PDF</a>
            <a href="{{ route('classes.students.edit', [$classroom, $siswa]) }}" class="btn-primary btn-primary--sm">Edit data</a>
        </div>
    </div>

    @include('partials.class-nav')

    <dl class="deret-angka">
        <div>
            <dt class="stat-label">Kehadiran</dt>
            <dd class="stat-value {{ $warnaHadir }}">{{ $persen === null ? '—' : $persen.'%' }}</dd>
            <div class="bar mt-2">
                <div class="bar__isi {{ $persen !== null && $persen >= 85 ? 'bar__isi--emerald' : ($persen !== null && $persen >= 75 ? 'bar__isi--amber' : 'bar__isi--rose') }}"
                     style="width: {{ $persen ?? 0 }}%"></div>
            </div>
            <p class="stat-sub angka">{{ $kehadiran['total'] }} kali tercatat</p>
        </div>

        <div>
            <dt class="stat-label">Alfa</dt>
            <dd class="stat-value {{ $kehadiran['jumlah']['alfa'] >= 3 ? 'text-rose-700' : '' }}">{{ $kehadiran['jumlah']['alfa'] }}</dd>
            <p class="stat-sub angka">S {{ $kehadiran['jumlah']['sakit'] }} &middot; I {{ $kehadiran['jumlah']['izin'] }}</p>
        </div>

        <div>
            <dt class="stat-label">Terlambat</dt>
            <dd class="stat-value {{ $kehadiran['jumlah']['terlambat'] >= 5 ? 'text-amber-700' : '' }}">{{ $kehadiran['jumlah']['terlambat'] }}</dd>
            <p class="stat-sub">{{ $kehadiran['jumlah']['terlambat'] >= 5 ? 'pola berulang' : 'kali periode ini' }}</p>
        </div>

        @unless ($ajar)
            <div>
                <dt class="stat-label">Poin Disiplin</dt>
                <dd class="stat-value {{ $poin['sekarang'] < 75 ? 'text-rose-700' : '' }}">{{ $poin['sekarang'] }}</dd>
                <div class="bar mt-2">
                    <div class="bar__isi {{ $poin['sekarang'] < 75 ? 'bar__isi--rose' : 'bar__isi--emerald' }}" style="width: {{ $poin['persen'] }}%"></div>
                </div>
                <p class="stat-sub angka">{{ $poin['sekarang'] }} / 100 poin</p>
            </div>

            <div>
                <dt class="stat-label">Pelanggaran</dt>
                <dd class="stat-value {{ $poin['kejadian'] > 0 ? 'text-amber-700' : '' }}">{{ $poin['kejadian'] }}</dd>
                <p class="stat-sub angka">{{ $poin['periode'] }} poin periode ini</p>
            </div>
        @endunless
    </dl>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        <div class="space-y-4 lg:col-span-2">

            <section class="blok">
                <div class="blok__kepala">
                    <h2 class="blok__judul">Tren kehadiran 6 bulan</h2>
                    <span class="eyebrow">% hadir</span>
                </div>
                <div class="blok__isi">
                    <p class="mb-3 text-xs text-slate-500">
                        Anak yang bulan ini 78% bisa sedang membaik dari 60%, atau sedang jatuh dari 95%.
                    </p>
                    <div class="flex items-end gap-1.5" style="height: 96px;">
                        @foreach ($tren as $t)
                            <div class="flex flex-1 flex-col items-center justify-end gap-1">
                                @if ($t['persen'] === null)
                                    <div class="w-full bg-slate-100" style="height: 3px;"></div>
                                @else
                                    <span class="angka text-[10px] text-slate-600">{{ $t['persen'] }}</span>
                                    <div class="w-full {{ $t['persen'] >= 85 ? 'bg-emerald-600' : ($t['persen'] >= 75 ? 'bg-amber-600' : 'bg-rose-600') }}"
                                         style="height: {{ max(3, (int) ($t['persen'] * 0.7)) }}px"></div>
                                @endif
                                <span class="font-mono text-[9px] text-slate-400">{{ $t['label'] }}</span>
                                @if (($t['terlambat'] ?? 0) > 0)
                                    <span class="font-mono text-[9px] text-slate-500">{{ $t['terlambat'] }}T</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="blok">
                <div class="blok__kepala">
                    <h2 class="blok__judul">Riwayat kehadiran</h2>
                    @if ($kehadiran['dikoreksi'] > 0)
                        <span class="badge badge--amber">{{ $kehadiran['dikoreksi'] }} dikoreksi</span>
                    @endif
                </div>
                @if ($absensi->isEmpty())
                    <p class="p-4 text-sm text-slate-500">Belum ada catatan kehadiran pada {{ $periode['label'] }}.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead><tr><th scope="col">Tanggal</th><th scope="col" class="w-12 text-center">Status</th><th scope="col">Keterangan</th></tr></thead>
                            <tbody>
                            @foreach ($absensi as $a)
                                @php [$huruf, $label, $gaya] = $kodeStatus[$a->status] ?? ['—', '—', '']; @endphp
                                <tr>
                                    <td class="whitespace-nowrap angka">
                                        {{ $a->session?->session_date?->format('d/m/Y') ?? '—' }}
                                        @if (($a->session->sequence ?? 1) > 1)
                                            <span class="font-mono text-[10px] text-slate-400">·{{ $a->session->sequence }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="kode {{ $gaya }}" title="{{ $label }}">{{ $huruf }}</span>
                                        @if ($a->revisions_count > 0)
                                            <span class="badge badge--amber ml-1">dikoreksi</span>
                                        @endif
                                    </td>
                                    <td class="td--secondary">{{ $a->note ?: '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            @unless ($ajar)
                <section class="blok">
                    <div class="blok__kepala"><h2 class="blok__judul">Riwayat pelanggaran</h2></div>
                    @if ($pelanggaran->isEmpty())
                        <p class="p-4 text-sm text-slate-500">Tidak ada pelanggaran pada {{ $periode['label'] }}.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead><tr><th scope="col">Tanggal</th><th scope="col">Jenis</th><th scope="col" class="text-right">Poin</th></tr></thead>
                                <tbody>
                                @foreach ($pelanggaran as $v)
                                    <tr>
                                        <td class="whitespace-nowrap angka">{{ $v->occurred_on->format('d/m/Y') }}</td>
                                        <td>
                                            {{ $v->type->name ?? '—' }}
                                            @if ($v->note)
                                                <span class="mt-0.5 block text-xs font-normal text-slate-500">{{ $v->note }}</span>
                                            @endif
                                        </td>
                                        <td class="angka text-right font-medium {{ $v->points < 0 ? 'td--danger' : 'td--success' }}">{{ $v->points }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @endunless

            <section class="blok">
                <div class="blok__kepala">
                    <h2 class="blok__judul">Nilai</h2>
                    @if ($nilai['rata_rapor'] !== null)
                        <span class="angka text-xs font-medium text-slate-700">Rata-rata rapor {{ $nilai['rata_rapor'] }}</span>
                    @endif
                </div>

                @if (! $nilai['ada'])
                    <p class="p-4 text-sm text-slate-500">Belum ada nilai pada periode ini.</p>
                @else
                    <div class="blok__isi space-y-4">
                        @if ($nilai['rapor'])
                            <div>
                                <p class="eyebrow mb-1.5">Rapor — semester {{ $semester }}</p>
                                <div class="overflow-x-auto">
                                    <table class="table">
                                        <thead><tr><th scope="col">Mapel</th><th scope="col" class="w-14 text-center">PTS</th><th scope="col" class="w-14 text-center">PAS</th></tr></thead>
                                        <tbody>
                                            @foreach ($nilai['rapor'] as $b)
                                                <tr>
                                                    <td>{{ $b['mapel'] }}</td>
                                                    {{-- "—" berarti belum dinilai, bukan nol. --}}
                                                    <td class="angka text-center {{ $b['pts'] !== null && $b['pts'] < 75 ? 'td--danger' : '' }}">{{ $b['pts'] ?? '—' }}</td>
                                                    <td class="angka text-center {{ $b['pas'] !== null && $b['pas'] < 75 ? 'td--danger' : '' }}">{{ $b['pas'] ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        @if ($nilai['harian'])
                            {{-- Dipisah dari rapor dengan sengaja: nilai harian jumlahnya
                                 banyak dan bergerak, PTS/PAS sedikit dan menjadi bahan
                                 keputusan. Meleburnya jadi satu rata-rata menghasilkan
                                 angka yang tidak dimaksudkan siapa pun. --}}
                            <div>
                                <p class="eyebrow mb-1.5">Nilai harian — {{ $periode['label'] }}</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($nilai['harian'] as $h)
                                        <span class="inline-flex items-center gap-1.5 rounded border border-slate-200 bg-white px-2 py-1 text-xs">
                                            <span class="text-slate-700">{{ $h['mapel'] }}</span>
                                            <span class="angka font-medium {{ $h['rata'] < 75 ? 'text-rose-700' : 'text-slate-900' }}">{{ $h['rata'] }}</span>
                                            <span class="font-mono text-[10px] text-slate-400">{{ $h['jumlah'] }}&times;</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </section>

            <section class="blok">
                <div class="blok__kepala">
                    <h2 class="blok__judul">Karakter P5</h2>
                    @if ($karakter['dimensi'])
                        <span class="angka text-xs font-medium">
                            <span class="text-emerald-700">+{{ $karakter['total']['positif'] }}</span>
                            <span class="text-slate-300">/</span>
                            <span class="text-rose-700">&minus;{{ $karakter['total']['negatif'] }}</span>
                        </span>
                    @endif
                </div>

                @if (! $karakter['dimensi'])
                    <p class="p-4 text-sm text-slate-500">Belum ada catatan karakter pada periode ini.</p>
                @else
                    {{-- Per dimensi, bukan satu angka bersih. Anak dengan sepuluh
                         catatan positif pada Gotong Royong dan tiga negatif pada
                         Kemandirian menuntut pembinaan yang sama sekali berbeda dari
                         anak yang sebaliknya — padahal jumlah bersihnya bisa sama. --}}
                    <div class="blok__daftar">
                        @foreach ($karakter['dimensi'] as $d)
                            <div class="flex items-center justify-between gap-3 px-4 py-2">
                                <span class="truncate text-sm text-slate-800">{{ $d['dimensi'] }}</span>
                                <span class="angka flex shrink-0 items-center gap-2 text-xs font-medium">
                                    @if ($d['positif'] > 0)<span class="text-emerald-700">+{{ $d['positif'] }}</span>@endif
                                    @if ($d['negatif'] > 0)<span class="text-rose-700">&minus;{{ $d['negatif'] }}</span>@endif
                                    @if ($d['lainnya'] > 0)<span class="text-slate-400" title="Catatan pengamatan, tidak berpoin">{{ $d['lainnya'] }} obs</span>@endif
                                </span>
                            </div>
                        @endforeach
                    </div>

                    @if ($karakter['catatan']->isNotEmpty())
                        <div class="border-t border-slate-200 px-4 py-3">
                            <p class="eyebrow mb-1.5">Catatan terbaru</p>
                            <ul class="space-y-1">
                                @foreach ($karakter['catatan']->take(3) as $c)
                                    <li class="text-xs text-slate-600">
                                        <span class="font-mono text-slate-400">{{ $c->record_date?->translatedFormat('d M') }}</span>
                                        &middot; {{ $c->title }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif
            </section>

            @unless ($ajar)
                <section class="blok">
                    <div class="blok__kepala">
                        <h2 class="blok__judul">Biodata</h2>
                        @if ($kelengkapan['kosong'] !== [])
                            <span class="kode kode--izin">{{ $kelengkapan['persen'] }}%</span>
                        @endif
                    </div>

                    <div class="blok__isi">
                        @if ($kelengkapan['kosong'] !== [])
                            <div class="alert alert--warning mb-4">
                                <div>
                                    <p class="alert__title">Data belum lengkap ({{ $kelengkapan['persen'] }}%)</p>
                                    <p class="alert__body">
                                        Belum terisi: {{ implode(', ', $kelengkapan['kosong']) }}.
                                        <a href="{{ route('classes.students.edit', [$classroom, $siswa]) }}" class="font-semibold underline">Lengkapi sekarang</a>.
                                    </p>
                                </div>
                            </div>
                        @endif

                        <dl class="grid gap-x-8 sm:grid-cols-2">
                            @foreach ([
                                'Jenis kelamin' => $siswa->gender === 'L' ? 'Laki-laki' : ($siswa->gender === 'P' ? 'Perempuan' : null),
                                'Tempat, tanggal lahir' => trim(($siswa->tempat_lahir ?: '').($siswa->tanggal_lahir ? ', '.\Illuminate\Support\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : ''), ', '),
                                'Agama' => $siswa->agama,
                                'Golongan darah' => $siswa->golongan_darah,
                                'Anak ke' => $siswa->anak_ke ? $siswa->anak_ke.' dari '.($siswa->jumlah_saudara ?: '—').' bersaudara' : null,
                                'Alamat' => $siswa->address,
                                'HP siswa' => $siswa->phone,
                                'HP orang tua' => $siswa->parent_phone,
                                'Nama ayah' => trim(($siswa->nama_ayah ?: '').($siswa->pekerjaan_ayah ? ' ('.$siswa->pekerjaan_ayah.')' : '')),
                                'Nama ibu' => trim(($siswa->nama_ibu ?: '').($siswa->pekerjaan_ibu ? ' ('.$siswa->pekerjaan_ibu.')' : '')),
                                'Nama wali' => $siswa->nama_wali,
                                'Asal sekolah' => $siswa->asal_sekolah,
                                'Tahun masuk' => $siswa->tahun_masuk,
                                'Kompetensi keahlian' => $siswa->kompetensi_keahlian,
                                'Jarak rumah' => $siswa->jarak_rumah_km ? $siswa->jarak_rumah_km.' km' : null,
                                'Transportasi' => $siswa->moda_transportasi,
                                'Hobi' => $siswa->hobi,
                                'Cita-cita' => $siswa->cita_cita,
                            ] as $label => $nilaiBiodata)
                                <div class="flex justify-between gap-4 border-b border-slate-200 py-1.5">
                                    <dt class="shrink-0 text-xs text-slate-500">{{ $label }}</dt>
                                    <dd class="text-right text-sm {{ filled($nilaiBiodata) ? 'font-medium text-slate-800' : 'text-slate-300' }}">
                                        {{ filled($nilaiBiodata) ? $nilaiBiodata : '—' }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>

                        @if ($siswa->penerima_kip || $siswa->penerima_pkh)
                            <div class="mt-4 flex gap-2 border-t border-slate-200 pt-3">
                                @if ($siswa->penerima_kip) <span class="badge badge--indigo">Penerima KIP</span> @endif
                                @if ($siswa->penerima_pkh) <span class="badge badge--indigo">Penerima PKH</span> @endif
                            </div>
                        @endif
                    </div>
                </section>
            @endunless
        </div>

        <div>
            <section class="blok">
                <div class="blok__kepala"><h2 class="blok__judul">Periode</h2></div>
                <form method="GET" class="blok__isi space-y-3" x-data="{ mode: '{{ $periode['mode'] }}' }">
                    <div class="form-group">
                        <label for="mode" class="form-label">Mode</label>
                        <select id="mode" name="mode" x-model="mode" class="form-select form-input--sm">
                            <option value="bulan">Per bulan</option>
                            <option value="semester">Per semester</option>
                            <option value="rentang">Rentang tanggal</option>
                        </select>
                    </div>

                    <div class="form-group" x-show="mode === 'bulan'">
                        <label for="bulan" class="form-label">Bulan</label>
                        <input type="month" id="bulan" name="bulan" value="{{ $periode['bulan']->format('Y-m') }}" class="form-input form-input--sm">
                    </div>

                    <template x-if="mode === 'semester'">
                        <div class="space-y-3">
                            <div class="form-group">
                                <label for="sub_periode" class="form-label">Periode semester</label>
                                <select id="sub_periode" name="sub_periode" class="form-select form-input--sm">
                                    <optgroup label="Semester 1 (Ganjil)">
                                        <option value="1_tengah" @selected(($periode['sub_periode'] ?? '') === '1_tengah')>Tengah Sem. 1 (PTS/STS 1)</option>
                                        <option value="1_akhir" @selected(($periode['sub_periode'] ?? '') === '1_akhir')>Akhir Sem. 1 (PAS/SAS 1)</option>
                                        <option value="1_penuh" @selected(($periode['sub_periode'] ?? '') === '1_penuh' || (($periode['semester'] ?? 1) == 1 && empty($periode['sub_periode'])))>Sem. 1 Penuh (Juli–Des)</option>
                                    </optgroup>
                                    <optgroup label="Semester 2 (Genap)">
                                        <option value="2_tengah" @selected(($periode['sub_periode'] ?? '') === '2_tengah')>Tengah Sem. 2 (PTS/STS 2)</option>
                                        <option value="2_akhir" @selected(($periode['sub_periode'] ?? '') === '2_akhir')>Akhir Sem. 2 (PAS/SAS 2)</option>
                                        <option value="2_penuh" @selected(($periode['sub_periode'] ?? '') === '2_penuh' || (($periode['semester'] ?? 1) == 2 && empty($periode['sub_periode'])))>Sem. 2 Penuh (Jan–Juni)</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="tahun" class="form-label">T.A. mulai</label>
                                <input type="number" id="tahun" name="tahun" min="2000" max="2100" value="{{ $periode['tahun'] }}" class="form-input form-input--sm">
                            </div>
                        </div>
                    </template>

                    <template x-if="mode === 'rentang'">
                        <div class="space-y-3">
                            <div class="form-group">
                                <label for="dari" class="form-label">Dari</label>
                                <input type="date" id="dari" name="dari" value="{{ request('dari', $awal->toDateString()) }}" class="form-input form-input--sm">
                            </div>
                            <div class="form-group">
                                <label for="sampai" class="form-label">Sampai</label>
                                <input type="date" id="sampai" name="sampai" value="{{ request('sampai', $akhir->toDateString()) }}" class="form-input form-input--sm">
                            </div>
                        </div>
                    </template>

                    <button type="submit" class="btn-primary btn-primary--sm w-full">Terapkan</button>
                    <p class="text-xs text-slate-500">Menampilkan: <strong class="font-medium text-slate-700">{{ $periode['label'] }}</strong></p>
                </form>
            </section>
        </div>

    </div>
</div>
@endsection
