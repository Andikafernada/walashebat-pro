<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Kehadiran {{ $classroom->name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        /* Print-specific styles */
        @page { size: A4 landscape; margin: 12mm; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .lembar { box-shadow: none; padding: 0; border: none; }
            .matriks { font-size: 11pt !important; }
            .matriks th, .matriks td { border: 1px solid #cbd5e1 !important; padding: 3px 5px !important; text-align: center; }
            .matriks th { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .matriks td.nama { text-align: left; white-space: nowrap; }
            .row-attention { background: #fff1f2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }

        /* Screen styles */
        .matriks th, .matriks td { border: 1px solid #e2e8f0; padding: 2px 4px; text-align: center; }
        .matriks th { background: #f8fafc; }
        .matriks td.nama { text-align: left; white-space: nowrap; }
        .row-attention { background: #fff1f2; }

        /* Badge inline in report */
        .report-badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: 600;
        }
        .report-badge--hadir { background: #f1f5f9; color: #475569; }
        .report-badge--sakit { background: #fef3c7; color: #92400e; }
        .report-badge--izin { background: #dbeafe; color: #1e40af; }
        .report-badge--alfa { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800">

<!-- Control Bar (hidden on print) -->
<div class="no-print mx-auto max-w-5xl p-4">
    <div class="flex flex-wrap items-end justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" class="flex items-end gap-3">
            <div class="form-group">
                <label for="bulan" class="form-label">Bulan</label>
                <input type="month"
                       id="bulan"
                       name="bulan"
                       value="{{ $bulan->format('Y-m') }}"
                       class="form-input form-input--sm">
            </div>
            <button type="submit" class="btn-primary btn-primary--sm">Tampilkan</button>
        </form>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('classes.show', $classroom) }}" class="btn-secondary btn-secondary--sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </a>
            <a href="{{ route('classes.exports.attendance.excel', $classroom) }}?bulan={{ $bulan->format('Y-m') }}"
               class="btn-secondary btn-secondary--sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
                Excel
            </a>
            <a href="{{ route('classes.exports.attendance.pdf', $classroom) }}?bulan={{ $bulan->format('Y-m') }}"
               class="btn-primary btn-primary--sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
                Unduh PDF
            </a>
            <button onclick="window.print()" class="btn-primary btn-primary--sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Cetak
            </button>
        </div>
    </div>
    <p class="mt-3 text-xs text-slate-500">
        <strong>Tips:</strong> Pada dialog cetak, pilih orientasi <mark class="bg-amber-100 px-1 rounded">Landscape</mark> dan tujuan <mark class="bg-amber-100 px-1 rounded">Save as PDF</mark>.
    </p>
</div>

<!-- Report Sheet -->
<div class="lembar mx-auto max-w-[1500px] bg-white p-8 shadow-sm">

    <!-- Header -->
    <div class="mb-5 border-b-2 border-slate-800 pb-3 text-center">
        <h1 class="text-lg font-bold uppercase tracking-wide text-slate-900">Rekapitulasi Kehadiran Siswa</h1>
        <p class="text-sm font-semibold uppercase text-slate-700">{{ auth()->user()->school_name ?? 'Nama Sekolah' }}</p>
        @if (auth()->user()->school_address)
            <p class="text-xs text-slate-500">{{ auth()->user()->school_address }}</p>
        @endif
        <p class="mt-1 text-sm font-medium text-slate-800">Bulan {{ $bulan->translatedFormat('F Y') }}</p>
    </div>

    <!-- Meta Info -->
    <table class="mb-5 w-full text-sm text-slate-700">
        <tr>
            <td class="pr-6 font-medium w-32">Kelas</td>
            <td class="pr-8">: <b>{{ $classroom->name }}</b></td>
            <td class="pr-6 font-medium">Jumlah Pertemuan</td>
            <td>: <b>{{ $jumlahPertemuan }}</b> hari</td>
        </tr>
        <tr>
            {{-- Rekap kehadiran terbuka untuk kelas ajar juga; di sana penandatangannya
                 guru mapel, bukan wali kelas. Lihat Classroom::sebutanPeran(). --}}
            <td class="pr-6 font-medium">{{ $classroom->sebutanPeran() }}</td>
            <td class="pr-8">: {{ auth()->user()->name }}</td>
            <td class="pr-6 font-medium">Rata-rata Kehadiran</td>
            <td>: <b>{{ $persenKelas }}%</b></td>
        </tr>
        <tr>
            <td class="pr-6 font-medium">Tahun Ajaran</td>
            <td class="pr-8">: {{ $classroom->academic_year ?? '—' }}</td>
            <td class="pr-6 font-medium">Perlu Perhatian</td>
            <td>: <b class="text-rose-600">{{ $perluPerhatian }}</b> siswa</td>
        </tr>
    </table>

    <!-- Legend -->
    <div class="mb-3 flex flex-wrap items-center gap-4 text-xs text-slate-600">
        <span class="font-semibold text-slate-800">Keterangan:</span>
        <span class="report-badge report-badge--hadir">· Hadir</span>
        <span class="report-badge report-badge--sakit">S Sakit</span>
        <span class="report-badge report-badge--izin">I Izin</span>
        <span class="report-badge report-badge--alfa">A Alfa</span>
        <span class="ml-auto text-rose-600 font-medium">⚠ Baris merah = alfa 3× atau lebih</span>
    </div>

    @if ($jumlahPertemuan === 0)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center text-sm text-amber-800">
            <svg class="mx-auto mb-3 h-10 w-10 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 2a10 10 0 110 20 10 10 0 010-20z"/>
            </svg>
            <p class="font-semibold">Belum ada sesi absensi pada bulan ini</p>
            <p class="mt-1">Buat sesi absensi di menu Absensi untuk melihat rekap kehadiran.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="matriks w-full border-collapse text-xs">
                <thead>
                    <tr>
                        <th rowspan="2" class="w-8 !text-center">No</th>
                        <th rowspan="2" class="!text-left !min-w-[140px]">Nama Siswa</th>
                        <th rowspan="2" class="w-16 !text-center">NIS</th>
                        <th colspan="{{ $jumlahPertemuan }}" class="!text-center">Tanggal</th>
                        <th colspan="6" class="!text-center">Jumlah</th>
                    </tr>
                    <tr>
                        @foreach ($tanggalKolom as $t)
                            <th class="!text-center !w-6">
                                {{ \Illuminate\Support\Carbon::parse($t)->format('d') }}
                            </th>
                        @endforeach
                        <th class="!text-center !w-8">H</th>
                        <th class="!text-center !w-8" title="Terlambat">T</th>
                        <th class="!text-center !w-8">S</th>
                        <th class="!text-center !w-8">I</th>
                        <th class="!text-center !w-8">A</th>
                        <th class="!text-center !w-12">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekap as $i => $r)
                        <tr class="{{ $r['perhatian'] ? 'row-attention' : '' }}">
                            <td class="!text-center">{{ $i + 1 }}</td>
                            <td class="nama !text-left !font-medium !text-slate-900">{{ $r['siswa']->name }}</td>
                            <td class="!text-center !font-mono !text-xs !text-slate-500">{{ $r['siswa']->nis ?? '—' }}</td>
                            @foreach ($tanggalKolom as $t)
                                @php
                                    $s = $r['per_tanggal'][$t] ?? null;
                                    // Hadir sengaja hanya titik: yang perlu menonjol
                                    // di matriks adalah penyimpangannya.
                                    $huruf = ['hadir' => '·', 'terlambat' => 'T', 'sakit' => 'S', 'izin' => 'I', 'alfa' => 'A'][$s] ?? '';
                                    $warna = match($s) {
                                        'alfa' => 'text-rose-700 font-bold',
                                        'hadir' => 'text-slate-300',
                                        'terlambat' => 'text-orange-600 font-semibold',
                                        'sakit' => 'text-amber-600 font-semibold',
                                        'izin' => 'text-blue-600 font-semibold',
                                        default => 'text-slate-300',
                                    };
                                @endphp
                                <td class="!text-center !{{ $warna }}">{{ $huruf }}</td>
                            @endforeach
                            <td class="!text-center !font-semibold !text-slate-700">{{ $r['jumlah']['hadir'] }}</td>
                            <td class="!text-center !text-orange-600">{{ $r['jumlah']['terlambat'] }}</td>
                            <td class="!text-center !text-slate-600">{{ $r['jumlah']['sakit'] }}</td>
                            <td class="!text-center !text-blue-600">{{ $r['jumlah']['izin'] }}</td>
                            <td class="!text-center !font-bold !text-rose-700 {{ $r['jumlah']['alfa'] > 0 ? '' : '!text-slate-300' }}">
                                {{ $r['jumlah']['alfa'] }}
                            </td>
                            <td class="!text-center !font-semibold {{ $r['persen'] >= 80 ? '!text-emerald-600' : ($r['persen'] < 70 ? '!text-rose-600' : '!text-slate-700') }}">
                                {{ $r['persen'] }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Signature Section -->
    <div class="mt-12 flex justify-between text-sm">
        <div class="text-center">
            <p>Mengetahui,</p>
            <p class="mt-2 font-medium">Kepala Sekolah</p>
            <div class="mt-12 h-12 w-40 border-b border-slate-300"></div>
            <p class="mt-1 px-8 pt-1">{{ auth()->user()->principal_name ?: '_______________________' }}</p>
            @if (auth()->user()->principal_nip)
                <p class="text-xs">NIP. {{ auth()->user()->principal_nip }}</p>
            @endif
        </div>
        <div class="text-center">
            <p class="text-slate-700">{{ now()->translatedFormat('d F Y') }}</p>
            <p class="mt-2 font-medium">{{ $classroom->sebutanPeran() }}</p>
            <div class="mt-12 h-12 w-40 border-b border-slate-300"></div>
            <p class="mt-1 px-8 pt-1">{{ auth()->user()->name }}</p>
        </div>
    </div>
</div>

</body>
</html>
