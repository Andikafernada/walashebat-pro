<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Pelajar & Presensi QR - {{ $classroom->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 10mm;
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
            padding: 4.5mm;
            vertical-align: top;
        }
        .card {
            border: 1.5px solid #059669;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
            height: 52mm;
            box-sizing: border-box;
            position: relative;
        }
        .card-header {
            background-color: #047857;
            color: #ffffff;
            padding: 5px 8px;
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
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #a7f3d0;
        }
        .card-title {
            font-size: 9pt;
            font-weight: 900;
            letter-spacing: -0.2px;
            color: #ffffff;
        }
        .class-badge {
            font-size: 8pt;
            font-weight: bold;
            background-color: #064e3b;
            color: #ffffff;
            padding: 2px 6px;
            border-radius: 4px;
            text-align: right;
        }
        .card-body {
            padding: 8px 10px;
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
            font-size: 10.5pt;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.2;
            margin-bottom: 4px;
        }
        .student-info {
            font-size: 8pt;
            color: #475569;
            line-height: 1.4;
            font-weight: 500;
        }
        .qr-cell {
            width: 60px;
            text-align: center;
        }
        .qr-code {
            width: 56px;
            height: 56px;
            border: 1px solid #cbd5e1;
            padding: 2px;
            border-radius: 4px;
        }
        .scan-hint {
            font-size: 6pt;
            font-weight: bold;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
        .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 3px 10px;
            font-size: 6.5pt;
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
                                                    <div class="school-name">{{ $user->school_name ?: 'WaliKelas Pro' }}</div>
                                                    <div class="card-title">KARTU PRESENSI SISWA</div>
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
                                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($st->nis ?: $st->id) }}"
                                                         class="qr-code"
                                                         alt="QR">
                                                    <div class="scan-hint">SCAN PRESENSI</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Footer Kartu -->
                                    <div class="card-footer">
                                        <table style="width: 100%;">
                                            <tr>
                                                <td>WaliKelas Digital ID Card</td>
                                                <td style="text-align: right;">Gunting sesuai garis batas kartu</td>
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
