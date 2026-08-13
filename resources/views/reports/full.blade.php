@extends('layouts.app')

@section('title', 'Laporan Administrasi - ' . $classroom->name)

@section('content')
<div class="space-y-6 pb-12">
    <!-- Header Bar -->
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-slate-600">{{ $classroom->name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Laporan Administrasi</span>
            </nav>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">
                Laporan Administrasi Lengkap {{ $classroom->name }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">Ekspor dan cetak berkas administrasi wali kelas komprehensif.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('classes.reports.full.pdf', array_merge(['class' => $classroom->id], request()->query())) }}" target="_blank"
               class="btn-primary btn-primary--sm">Cetak PDF Laporan
            </a>
        </div>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $classroom])

    <!-- Flash Messages -->
    @include('partials.flash')

    @php
        $ada = fn ($n) => in_array($n, $sections ?? [], true);
        $rp = fn ($v) => 'Rp '.number_format((int) $v, 0, ',', '.');
    @endphp

    <!-- Filter & Section Selection Card -->
    <div class="card space-y-4">
        <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-200 pb-3">Pengaturan Filter &amp; Bagian Laporan</h3>

        <form method="GET" x-data="{ mode: '{{ $periode['mode'] ?? 'bulan' }}' }" class="space-y-4">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="mode" class="form-label">Jenis Periode</label>
                    <select id="mode" name="mode" x-model="mode" class="h-9 rounded border border-slate-200 bg-slate-50 px-3 text-xs font-medium text-slate-800 focus:border-indigo-500 focus:outline-none">
                        <option value="bulan">Per Bulan</option>
                        <option value="semester">Per Semester</option>
                        <option value="rentang">Rentang Tanggal</option>
                    </select>
                </div>

                <div x-show="mode === 'bulan'">
                    <label for="bulan" class="form-label">Pilih Bulan</label>
                    <input type="month" id="bulan" name="bulan" value="{{ isset($periode['bulan']) ? $periode['bulan']->format('Y-m') : date('Y-m') }}"
                           class="h-9 rounded border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                </div>

                <template x-if="mode === 'semester'">
                    <div class="flex items-end gap-2">
                        <div>
                            <label for="sub_periode" class="form-label">Periode Evaluasi Semester</label>
                            <select id="sub_periode" name="sub_periode" class="h-9 rounded border border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-800 focus:border-indigo-500">
                                <optgroup label="Semester 1 (Ganjil)">
                                    <option value="1_tengah" @selected(($periode['sub_periode'] ?? '') === '1_tengah')>Tengah Semester 1 (PTS / STS 1)</option>
                                    <option value="1_akhir" @selected(($periode['sub_periode'] ?? '') === '1_akhir')>Akhir Semester 1 (PAS / SAS 1)</option>
                                    <option value="1_penuh" @selected(($periode['sub_periode'] ?? '') === '1_penuh' || (($periode['semester'] ?? 1) == 1 && empty($periode['sub_periode'])))>Semester 1 Penuh (Juli - Des)</option>
                                </optgroup>
                                <optgroup label="Semester 2 (Genap)">
                                    <option value="2_tengah" @selected(($periode['sub_periode'] ?? '') === '2_tengah')>Tengah Semester 2 (PTS / STS 2)</option>
                                    <option value="2_akhir" @selected(($periode['sub_periode'] ?? '') === '2_akhir')>Akhir Semester 2 (PAS / SAS 2)</option>
                                    <option value="2_penuh" @selected(($periode['sub_periode'] ?? '') === '2_penuh' || (($periode['semester'] ?? 1) == 2 && empty($periode['sub_periode'])))>Semester 2 Penuh (Jan - Juni)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label for="tahun" class="form-label">Tahun TA</label>
                            <input type="number" id="tahun" name="tahun" min="2000" max="2100" value="{{ $periode['tahun'] ?? date('Y') }}"
                                   class="h-9 w-24 rounded border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800 focus:border-indigo-500">
                        </div>
                    </div>
                </template>

                <template x-if="mode === 'rentang'">
                    <div class="flex items-end gap-2">
                        <div>
                            <label for="start_date" class="form-label">Dari Tanggal</label>
                            <input type="date" id="start_date" name="start_date" value="{{ isset($periode['awal']) ? $periode['awal']->format('Y-m-d') : date('Y-m-01') }}"
                                   class="h-9 rounded border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800">
                        </div>
                        <div>
                            <label for="end_date" class="form-label">Sampai Tanggal</label>
                            <input type="date" id="end_date" name="end_date" value="{{ isset($periode['akhir']) ? $periode['akhir']->format('Y-m-d') : date('Y-m-t') }}"
                                   class="h-9 rounded border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800">
                        </div>
                    </div>
                </template>

                <button type="submit" class="h-9 rounded bg-slate-800 px-4 text-xs font-semibold text-white hover:bg-slate-900 transition-colors">
                    Terapkan Filter
                </button>
            </div>

            <!-- Sections Checkboxes -->
            <div class="pt-3 border-t border-slate-200 space-y-2">
                {{--
                    Kunci di sini WAJIB sama dengan ClassReportBuilder::SECTIONS, dan
                    nama field WAJIB "bagian[]" karena itu yang dibaca resolveBagian().
                    Sebelumnya form ini mengirim "sec[]" dengan kunci karangan sendiri
                    ("ringkasan", "absensi"), sehingga tidak ada satu pun bagian yang
                    pernah cocok dan seluruh isi laporan tidak pernah tampil.
                --}}
                <span class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Bagian Yang Ditampilkan:</span>
                <input type="hidden" name="pilih_bagian" value="1">
                <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-slate-700">
                    @foreach ($semuaBagian as $kunci => $judul)
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="bagian[]" value="{{ $kunci }}" @checked($ada($kunci))
                                   class="rounded border-slate-300 text-indigo-600"> {{ $judul }}
                        </label>
                    @endforeach
                </div>
            </div>
        </form>
    </div>

    <!-- Report Preview Section Cards -->
    <div class="space-y-6">

        <p class="text-xs text-slate-500">Periode: <strong class="text-slate-700">{{ $periode['label'] }}</strong></p>

        @if (empty($sections))
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-xs font-semibold text-amber-800">
                Tidak ada bagian yang dipilih. Centang minimal satu bagian di atas.
            </div>
        @endif

        @if($ada('siswa'))
            <div data-bagian="siswa" class="card space-y-3">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-200 pb-2">Data Siswa</h3>
                <p class="text-xs text-slate-500">
                    Total {{ $ringkasanSiswa['total'] }} siswa &middot; {{ $ringkasanSiswa['lk'] }} L &middot; {{ $ringkasanSiswa['pr'] }} P
                </p>
                <ul class="text-xs text-slate-700 space-y-1">
                    @foreach ($siswa as $s)
                        <li class="flex justify-between border-b border-slate-50 py-1">
                            <span>{{ $loop->iteration }}. {{ $s->name }}</span>
                            <span class="text-slate-400">{{ $s->nis }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($ada('biodata'))
            <div data-bagian="biodata" class="card space-y-2">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-200 pb-2">Buku Induk (Biodata)</h3>
                <p class="text-xs text-slate-500">{{ $ringkasanSiswa['total'] }} siswa tercatat pada buku induk kelas ini.</p>
            </div>
        @endif

        @if($ada('kehadiran'))
            <div data-bagian="kehadiran" class="card space-y-3">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-200 pb-2">Rekap Kehadiran</h3>
                <p class="text-xs text-slate-500">
                    {{ $jumlahPertemuan }} pertemuan &middot; tingkat kehadiran kelas {{ $persenKelas }}%
                </p>
                <ul class="text-xs text-slate-700 space-y-1">
                    @foreach ($kehadiran as $baris)
                        <li class="flex justify-between border-b border-slate-50 py-1">
                            <span>{{ $baris['siswa']->name }}</span>
                            <span class="text-slate-500">{{ $baris['persen'] }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($ada('poin'))
            <div data-bagian="poin" class="card space-y-2">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-200 pb-2">Rekap Poin per Siswa</h3>
                <ul class="text-xs text-slate-700 space-y-1">
                    @foreach ($poin as $baris)
                        <li class="flex justify-between border-b border-slate-50 py-1">
                            <span>{{ $baris['siswa']->name }}</span>
                            <span class="text-slate-500">{{ $baris['poin_sekarang'] ?? 0 }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($ada('perhatian'))
            <div data-bagian="perhatian" class="card space-y-2">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-200 pb-2">Siswa Perlu Perhatian</h3>
                @forelse ($perhatian as $baris)
                    <p class="text-xs text-slate-700">{{ $baris['siswa']->name }}</p>
                @empty
                    <p class="text-xs text-slate-500">Tidak ada siswa yang perlu perhatian pada periode ini.</p>
                @endforelse
            </div>
        @endif

        @if($ada('pelanggaran'))
            <div data-bagian="pelanggaran" class="card space-y-2">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-200 pb-2">Catatan Pelanggaran</h3>
                <p class="text-xs text-slate-500">{{ $pelanggaran->count() }} catatan pada periode ini.</p>
            </div>
        @endif

        @if($ada('struktur'))
            <div data-bagian="struktur" class="card space-y-2">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-200 pb-2">Struktur Organisasi</h3>
                @forelse ($struktur as $baris)
                    <p class="text-xs text-slate-700">{{ $baris->roleLabel() }}: {{ $baris->student->name ?? "—" }}</p>
                @empty
                    <p class="text-xs text-slate-500">Struktur organisasi belum diisi.</p>
                @endforelse
            </div>
        @endif

        @if($ada('jadwal'))
            <div data-bagian="jadwal" class="card space-y-2">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-200 pb-2">Jadwal Pelajaran</h3>
                <p class="text-xs text-slate-500">{{ $jadwal->flatten()->count() }} entri jadwal.</p>
            </div>
        @endif

        @if($ada('denah'))
            <div data-bagian="denah" class="card space-y-2">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-200 pb-2">Denah Tempat Duduk</h3>
                <p class="text-xs text-slate-500">Denah tersedia pada berkas PDF.</p>
            </div>
        @endif

        @if($ada('kas'))
            <div data-bagian="kas" class="card space-y-2">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-200 pb-2">Buku Kas</h3>
                <p class="text-xs text-slate-500">Saldo akhir: {{ $rp($kas['saldo_akhir'] ?? 0) }}</p>
            </div>
        @endif

        {{-- Bagian ini menambah satu halaman penuh per siswa, jadi ukurannya
             disebut di depan: mencetak tanpa tahu akan keluar 40 halaman adalah
             cara tercepat menghabiskan tinta orang. --}}
        @if($ada('profil'))
            @php $belumBerfoto = collect($lembarProfil)->filter(fn ($l) => ! $l['siswa']->foto_path)->count(); @endphp
            <div data-bagian="profil" class="card space-y-2">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-200 pb-2">Profil &amp; Analisis Siswa</h3>
                <p class="text-xs text-slate-500">
                    {{ count($lembarProfil) }} lembar — satu halaman penuh untuk tiap siswa aktif, tersedia pada berkas PDF.
                </p>
                @if ($belumBerfoto)
                    <p class="text-xs font-semibold text-amber-700">
                        {{ $belumBerfoto }} siswa belum punya pas foto; kotaknya akan terisi inisial.
                    </p>
                @endif
            </div>
        @endif

    </div>

</div>
@endsection
