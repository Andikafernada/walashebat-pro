<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jurnal Mengajar Guru - {{ $classroom->name }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #0f172a;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .school-name {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-title {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 2px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 10px;
            font-size: 8.5pt;
        }
        .meta-table td {
            padding: 2px 4px;
        }
        .journal-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .journal-table th, .journal-table td {
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            vertical-align: top;
        }
        .journal-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
            font-size: 8pt;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .signatures {
            width: 100%;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ $user->school_name ?: 'SEKOLAH INDONESIA' }}</div>
        <div class="doc-title">AGENDA &amp; JURNAL MENGAJAR GURU (KURIKULUM MERDEKA)</div>
        <div style="font-size: 8pt; color: #475569;">Tahun Ajaran {{ date('Y') }}/{{ date('Y') + 1 }}</div>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 15%;"><strong>Kelas / Fase</strong></td>
            <td style="width: 35%;">: {{ $classroom->name }}</td>
            <td style="width: 15%;"><strong>Nama Guru</strong></td>
            <td style="width: 35%;">: {{ $user->name }}</td>
        </tr>
        <tr>
            <td><strong>NIP Guru</strong></td>
            <td>: {{ $user->nip ?: '—' }}</td>
            <td><strong>Total Pertemuan</strong></td>
            <td>: {{ $journals->count() }} Sesi Terlaksana</td>
        </tr>
    </table>

    <table class="journal-table">
        <thead>
            <tr>
                <th style="width: 30px;">Ptm</th>
                <th style="width: 65px;">Hari / Tgl</th>
                <th style="width: 100px;">Mata Pelajaran</th>
                <th style="width: 140px;">Materi / Topik</th>
                <th>Tujuan Pembelajaran &amp; Skenario Aktivitas</th>
                <th style="width: 100px;">Dimensi P5</th>
                <th style="width: 130px;">Refleksi / Catatan Guru</th>
            </tr>
        </thead>
        <tbody>
            @forelse($journals as $j)
                <tr>
                    <td class="text-center font-bold">#{{ $j->meeting_number }}</td>
                    <td class="text-center">{{ $j->session_date->format('d/m/Y') }}</td>
                    <td class="font-bold">{{ $j->subject }}</td>
                    <td class="font-bold">{{ $j->topic }}</td>
                    <td>
                        @if($j->learning_objective)
                            <div style="margin-bottom: 4px;"><strong>TP:</strong> {{ $j->learning_objective }}</div>
                        @endif
                        @if($j->activity)
                            <div style="font-size: 7.5pt; color: #334155; white-space: pre-line;">{{ $j->activity }}</div>
                        @endif
                    </td>
                    <td class="text-center" style="font-size: 7.5pt;">{{ $j->p5_dimension ?: '—' }}</td>
                    <td style="font-size: 7.5pt; color: #334155;">{{ $j->reflection ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px;">Belum ada rekaman jurnal mengajar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                Mengetahui,<br>
                Kepala Sekolah
                <br><br><br><br>
                <strong><u>{{ $user->principal_name ?: '( .................................................... )' }}</u></strong><br>
                NIP. {{ $user->principal_nip ?: '....................................................' }}
            </td>
            <td>
                {{ $user->school_city ?: 'Kota' }}, {{ date('d F Y') }}<br>
                Guru Mata Pelajaran
                <br><br><br><br>
                <strong><u>{{ $user->name }}</u></strong><br>
                NIP. {{ $user->nip ?: '....................................................' }}
            </td>
        </tr>
    </table>
</body>
</html>
