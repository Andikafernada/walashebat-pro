<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Profil Siswa - {{ $siswa->name }}</title>
    <style>
        @page { margin: 12mm 15mm 12mm 15mm; }
        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #1e293b;
            font-size: 8.5pt;
            line-height: 1.4;
            background: #ffffff;
        }
        h1, h2, h3, h4, p { margin: 0; }

        /* KOP HEADER */
        .kop {
            border-bottom: 2pt solid #0f172a;
            padding-bottom: 8pt;
            margin-bottom: 12pt;
        }
        .school-name {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.5pt;
        }
        .school-sub {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 2pt;
        }
        .doc-title {
            text-align: right;
            font-size: 11pt;
            font-weight: bold;
            color: #059669;
            text-transform: uppercase;
        }
        .doc-subtitle {
            text-align: right;
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 2pt;
        }

        /* STUDENT BANNER */
        .banner {
            background: #f8fafc;
            border: 1pt solid #e2e8f0;
            border-left: 4pt solid #10b981;
            padding: 10pt 12pt;
            border-radius: 6pt;
            margin-bottom: 12pt;
        }
        .student-name {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .student-meta {
            font-size: 8pt;
            color: #475569;
            margin-top: 3pt;
        }

        /* SECTION TITLES */
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            margin-top: 10pt;
            margin-bottom: 5pt;
            padding-bottom: 3pt;
            border-bottom: 1pt solid #cbd5e1;
        }

        /* STATS GRID TABLE */
        .stats-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4pt;
            margin-bottom: 10pt;
        }
        .stat-card {
            background: #f8fafc;
            border: 1pt solid #e2e8f0;
            border-radius: 6pt;
            padding: 8pt;
            text-align: center;
        }
        .stat-val {
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
        }
        .stat-label {
            font-size: 7pt;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 2pt;
        }

        /* TABLES */
        .tbl {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10pt;
        }
        .tbl th {
            background: #0f172a;
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5pt 6pt;
            text-align: left;
            border: 1pt solid #0f172a;
        }
        .tbl td {
            font-size: 8pt;
            padding: 4.5pt 6pt;
            border: 1pt solid #e2e8f0;
        }
        .tbl tr:nth-child(even) { background: #f8fafc; }
        .c { text-align: center; }
        .r { text-align: right; }
        .tebal { font-weight: bold; }

        /* BIODATA BOX */
        .biodata-box {
            background: #ffffff;
            border: 1pt solid #e2e8f0;
            border-radius: 6pt;
            padding: 8pt 10pt;
            margin-bottom: 10pt;
        }
        .biodata-tbl { width: 100%; border-collapse: collapse; }
        .biodata-tbl td { padding: 3.5pt 4pt; font-size: 8pt; vertical-align: top; }
        .biodata-label { font-weight: bold; color: #475569; }

        /* BADGES */
        .badge {
            display: inline-block;
            padding: 1.5pt 5pt;
            border-radius: 3pt;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #ffe4e6; color: #9f1239; }
        .badge-info { background: #e0f2fe; color: #075985; }

        /* SIGNATURE */
        .sig-table { width: 100%; margin-top: 15pt; border-collapse: collapse; }
        .sig-cell { width: 50%; text-align: center; vertical-align: top; font-size: 8.5pt; }
        .sig-space { height: 18mm; }
        .sig-line { border-top: 1pt solid #0f172a; width: 65%; margin: 0 auto; padding-top: 2pt; font-weight: bold; }
    </style>
</head>
<body>

<!-- KOP HEADER -->
<div class="kop">
    <table style="width: 100%;">
        <tr>
            <td style="vertical-align: top;">
                <div class="school-name">{{ $guru->school_name ?? auth()->user()->school_name ?? 'WALI KELAS HEBAT' }}</div>
                @if (!empty($guru->school_address ?? auth()->user()->school_address))
                    <div class="school-sub">{{ $guru->school_address ?? auth()->user()->school_address }}</div>
                @endif
            </td>
            <td style="vertical-align: top;" class="r">
                <div class="doc-title">Laporan Perkembangan Siswa</div>
                <div class="doc-subtitle">{{ $periode['label'] }}</div>
            </td>
        </tr>
    </table>
</div>

<!-- STUDENT BANNER -->
<div class="banner">
    <div class="student-name">{{ $siswa->name }}</div>
    <div class="student-meta">
        NIS: <strong>{{ $siswa->nis ?: '-' }}</strong> |
        NISN: <strong>{{ $siswa->nisn ?: '-' }}</strong> |
        Kelas: <strong>{{ $classroom->name }}</strong>
        @unless ($siswa->is_active)
            | <span class="badge badge-danger">NONAKTIF</span>
        @endunless
    </div>
</div>

<!-- STATS SUMMARY CARDS -->
<table class="stats-grid">
    <tr>
        <td style="width: 25%;">
            <div class="stat-card">
                <div class="stat-val" style="color: #059669;">{{ $kehadiran['persen'] === null ? '-' : $kehadiran['persen'].'%' }}</div>
                <div class="stat-label">Tingkat Kehadiran</div>
            </div>
        </td>
        <td style="width: 25%;">
            <div class="stat-card">
                <div class="stat-val" style="color: #e11d48;">{{ $kehadiran['jumlah']['alfa'] }}</div>
                <div class="stat-label">Jumlah Alfa</div>
            </div>
        </td>
        <td style="width: 25%;">
            <div class="stat-card">
                <div class="stat-val" style="color: #4f46e5;">{{ $poin['sekarang'] }}</div>
                <div class="stat-label">Poin Disiplin</div>
            </div>
        </td>
        <td style="width: 25%;">
            <div class="stat-card">
                <div class="stat-val" style="color: #d97706;">{{ $poin['kejadian'] }}</div>
                <div class="stat-label">Catatan Pelanggaran</div>
            </div>
        </td>
    </tr>
</table>

<!-- REKAP KEHADIRAN RINGKAS -->
<p style="font-size: 7.5pt; color: #475569; margin-bottom: 8pt;">
    <strong>Rincian Kehadiran Periode Ini:</strong>
    Hadir: <strong>{{ $kehadiran['jumlah']['hadir'] }}</strong> |
    Terlambat: <strong>{{ $kehadiran['jumlah']['terlambat'] }}</strong> |
    Sakit: <strong>{{ $kehadiran['jumlah']['sakit'] }}</strong> |
    Izin: <strong>{{ $kehadiran['jumlah']['izin'] }}</strong> |
    Alfa: <strong>{{ $kehadiran['jumlah']['alfa'] }}</strong>
    (dari total {{ $kehadiran['total'] }} kali tercatat).
</p>

<!-- TREN KEHADIRAN BULANAN -->
<div class="section-title">I. TREN KEHADIRAN HARIAN (6 BULAN TERAKHIR)</div>
<table class="tbl">
    <thead>
        <tr>
            <th style="width: 28%;">Kategori</th>
            @foreach ($tren as $t) <th class="c">{{ $t['label'] }}</th> @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="tebal">Kehadiran (%)</td>
            @foreach ($tren as $t)
                <td class="c tebal" style="color: {{ ($t['persen'] ?? 100) >= 90 ? '#166534' : '#991b1b' }};">{{ $t['persen'] === null ? '-' : $t['persen'].'%' }}</td>
            @endforeach
        </tr>
        <tr>
            <td>Terlambat</td>
            @foreach ($tren as $t) <td class="c">{{ $t['terlambat'] }}</td> @endforeach
        </tr>
        <tr>
            <td>Alfa</td>
            @foreach ($tren as $t) <td class="c">{{ $t['alfa'] }}</td> @endforeach
        </tr>
    </tbody>
</table>

<!-- BIODATA KELUARGA -->
<div class="section-title">II. BIODATA & DATA ORANG TUA SISWA</div>
<div class="biodata-box">
    <table class="biodata-tbl">
        <tr>
            <td style="width: 20%;" class="biodata-label">Jenis Kelamin</td>
            <td style="width: 30%;">{{ $siswa->gender === 'L' ? 'Laki-laki (L)' : ($siswa->gender === 'P' ? 'Perempuan (P)' : '-') }}</td>
            <td style="width: 20%;" class="biodata-label">Agama</td>
            <td style="width: 30%;">{{ $siswa->agama ?: '-' }}</td>
        </tr>
        <tr>
            <td class="biodata-label">Tempat, Tgl Lahir</td>
            <td>{{ $siswa->tempat_lahir ?: '-' }}, {{ $siswa->tanggal_lahir ? \Illuminate\Support\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : '-' }}</td>
            <td class="biodata-label">No. HP Siswa</td>
            <td>{{ $siswa->phone ?: '-' }}</td>
        </tr>
        <tr>
            <td class="biodata-label">Nama Ayah</td>
            <td>{{ $siswa->nama_ayah ?: '-' }}</td>
            <td class="biodata-label">Pekerjaan Ayah</td>
            <td>{{ $siswa->pekerjaan_ayah ?: '-' }}</td>
        </tr>
        <tr>
            <td class="biodata-label">Nama Ibu</td>
            <td>{{ $siswa->nama_ibu ?: '-' }}</td>
            <td class="biodata-label">Pekerjaan Ibu</td>
            <td>{{ $siswa->pekerjaan_ibu ?: '-' }}</td>
        </tr>
        <tr>
            <td class="biodata-label">No. HP Ortu/Wali</td>
            <td>{{ $siswa->parent_phone ?: '-' }}</td>
            <td class="biodata-label">Jarak / Transportasi</td>
            <td>{{ $siswa->jarak_rumah_km ? $siswa->jarak_rumah_km.' km' : '-' }} / {{ $siswa->moda_transportasi ?: '-' }}</td>
        </tr>
        <tr>
            <td class="biodata-label">Alamat Lengkap</td>
            <td colspan="3">{{ $siswa->address ?: '-' }} (RT/RW: {{ $siswa->rt_rw ?: '-' }}, Kel. {{ $siswa->kelurahan ?: '-' }}, Kec. {{ $siswa->kecamatan ?: '-' }})</td>
        </tr>
    </table>
</div>

<!-- RIWAYAT PELANGGARAN & APRESIASI -->
<div class="section-title">III. CATATAN KEDISIPLINAN & APRESIASI</div>
<table class="tbl">
    <thead>
        <tr>
            <th style="width: 18%;" class="c">Tanggal</th>
            <th>Jenis Catatan / Pelanggaran / Apresiasi</th>
            <th style="width: 15%;" class="c">Poin</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($pelanggaran as $v)
        <tr>
            <td class="c">{{ $v->occurred_on->format('d/m/Y') }}</td>
            <td>
                <strong>{{ $v->type->name ?? 'Catatan Disiplin' }}</strong>
                @if ($v->note) <br><span style="font-size: 7.5pt; color: #64748b;">{{ $v->note }}</span> @endif
            </td>
            <td class="c tebal" style="color: {{ $v->points >= 0 ? '#166534' : '#991b1b' }};">
                {{ $v->points >= 0 ? '+' : '' }}{{ $v->points }}
            </td>
        </tr>
    @empty
        <tr><td colspan="3" class="c" style="color: #94a3b8; font-style: italic; padding: 8pt;">Tidak ada catatan pelanggaran pada periode ini. Catatan bersih.</td></tr>
    @endforelse
    </tbody>
</table>

<!-- DUAL SIGNATURE BLOCK -->
<div style="page-break-inside: avoid; margin-top: 15pt;">
    <table class="sig-table">
        <tr>
            <td class="sig-cell">
                <p>Mengetahui,</p>
                <p style="font-weight: bold;">Orang Tua / Wali Siswa</p>
                <div class="sig-space"></div>
                <div class="sig-line">...................................................</div>
                <p style="font-size: 7.5pt; color: #64748b;">Tanda Tangan &amp; Nama Terang</p>
            </td>
            <td class="sig-cell">
                <p>{{ ($guru->school_city ?? auth()->user()->school_city) ? ($guru->school_city ?? auth()->user()->school_city).', ' : '' }}{{ now()->translatedFormat('d F Y') }}</p>
                <p style="font-weight: bold;">Wali Kelas {{ $classroom->name }}</p>
                <div class="sig-space"></div>
                <div class="sig-line">{{ $guru->name ?? auth()->user()->name }}</div>
                <p style="font-size: 7.5pt; color: #64748b;">NIP. {{ ($guru->nip ?? auth()->user()->nip) ?: '............................' }}</p>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
