<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $mode === 'ujian' ? 'Kartu Peserta Ujian' : 'Kartu Pelajar & Presensi QR' }} - {{ $classroom->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 8mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #0f172a;
            -webkit-print-color-adjust: exact;
        }
        .page-container {
            width: 100%;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
        }
        .grid-table td {
            width: 50%;
            padding: 3.5mm;
            vertical-align: top;
        }
        .card {
            border: 1.5px solid {{ $colors['border'] }};
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
            height: 52mm;
            box-sizing: border-box;
            position: relative;
        }
        .card-header {
            background-color: {{ $colors['primary'] }};
            color: #ffffff;
            padding: 4px 8px;
        }
        .card-header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .card-header-table td {
            padding: 0;
            vertical-align: middle;
        }
        .school-name {
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: {{ $colors['light'] }};
        }
        .card-title {
            font-size: 8.5pt;
            font-weight: 900;
            letter-spacing: -0.2px;
            color: #ffffff;
        }
        .class-badge {
            font-size: 7.5pt;
            font-weight: bold;
            background-color: {{ $colors['accent'] }};
            color: #ffffff;
            padding: 1.5px 5px;
            border-radius: 4px;
            text-align: right;
        }
        .card-body {
            padding: 6px 8px;
        }
        .card-body-table {
            width: 100%;
            border-collapse: collapse;
        }
        .card-body-table td {
            padding: 0;
            vertical-align: middle;
        }
        .student-name {
            font-size: 9.5pt;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.15;
            margin-bottom: 3px;
        }
        .student-info {
            font-size: 7.5pt;
            color: #475569;
            line-height: 1.35;
            font-weight: 500;
        }
        .qr-cell {
            width: 54px;
            text-align: center;
        }
        .qr-code {
            width: 50px;
            height: 50px;
            border: 1px solid #cbd5e1;
            padding: 1.5px;
            border-radius: 4px;
        }
        .scan-hint {
            font-size: 5.5pt;
            font-weight: bold;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-top: 1.5px;
        }
        .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 2.5px 8px;
            font-size: 6pt;
            color: #64748b;
            font-weight: 600;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @php
        $chunks = $students->chunk(8); // 8 kartu per halaman A4 (2 kolom x 4 baris)
    @endphp

    @foreach($chunks as $pageIndex => $pageStudents)
        <div class="page-container {{ !$loop->last ? 'page-break' : '' }}">
            <table class="grid-table">
                @foreach($pageStudents->chunk(2) as $rowStudents)
                    <tr>
                        @foreach($rowStudents as $st)
                            <td>
                                <div class="card">
                                    <!-- Header Kartu -->
                                    <div class="card-header">
                                        <table class="card-header-table">
                                            <tr>
                                                <td>
                                                    <div class="school-name">{{ $schoolName }}</div>
                                                    <div class="card-title">
                                                        {{ $mode === 'ujian' ? 'KARTU PESERTA ASESMEN' : 'KARTU PRESENSI DIGITAL' }}
                                                    </div>
                                                </td>
                                                <td style="text-align: right; width: 60px;">
                                                    <span class="class-badge">{{ $classroom->name }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Isi Kartu -->
                                    <div class="card-body">
                                        <table class="card-body-table">
                                            <tr>
                                                <td>
                                                    <div class="student-name">{{ $st->name }}</div>
                                                    <div class="student-info">
                                                        <strong>NIS:</strong> {{ $st->nis ?: '—' }}<br>
                                                        <strong>JK:</strong> {{ $st->gender === 'L' ? 'Laki-laki' : ($st->gender === 'P' ? 'Perempuan' : '—') }}<br>
                                                        <strong>Tahun Ajaran:</strong> {{ date('Y') }}/{{ date('Y') + 1 }}
                                                    </div>
                                                </td>
                                                <td class="qr-cell">
                                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($st->nis ?: $st->id) }}"
                                                         class="qr-code"
                                                         alt="QR">
                                                    <div class="scan-hint">{{ $mode === 'ujian' ? 'VERIFIKASI' : 'SCAN QR' }}</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Footer Kartu -->
                                    <div class="card-footer">
                                        <table style="width: 100%;">
                                            <tr>
                                                <td>{{ $mode === 'ujian' ? 'Asesmen & Evaluasi Belajar' : 'WaliKelas Digital ID' }}</td>
                                                <td style="text-align: right;">Gunting sesuai batas garis</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        @endforeach

                        @if($rowStudents->count() === 1)
                            <td></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach
</body>
</html>
