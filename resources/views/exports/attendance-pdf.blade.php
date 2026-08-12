<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi - {{ $class->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 16px; margin-bottom: 5px; }
        .header p { font-size: 11px; color: #666; }
        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 3px 0; font-size: 10px; }
        .info-table .label { width: 100px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: center; font-size: 9px; }
        th { background: #f0f0f0; font-weight: bold; }
        .student-name { text-align: left !important; }
        .positive { color: #2e6446; }
        .negative { color: #9a3527; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; }
        .signature-table { width: 100%; margin-top: 40px; }
        .signature-table td { text-align: center; vertical-align: top; padding: 5px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REKAPITULASI KEHADIRAN KELAS</h1>
        <p>{{ $class->name }} - {{ $class->major }}</p>
        <p>Periode: {{ $periode['awal']->format('d M Y') }} - {{ $periode['akhir']->format('d M Y') }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Tahun Ajaran</td>
            <td>: {{ now()->year }}/{{ now()->year + 1 }}</td>
            <td class="label">Semester</td>
            <td>: {{ $periode["label"] ?? "Semester 1" }}</td>
        </tr>
        <tr>
            <td class="label">Wali Kelas</td>
            <td>: {{ ($user->name ?? $class->user->name ?? "Wali Kelas") }}</td>
            <td class="label">Total Pertemuan</td>
            <td>: {{ count($dateRange) }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 150px; text-align: left;">Nama Siswa</th>
                <th style="width: 60px;">NIS</th>
                @foreach($dateRange as $date)
                    <th style="width: 20px;">{{ $date->format('d') }}</th>
                @endforeach
                <th style="width: 25px;">H</th>
                <th style="width: 25px;">T</th>
                <th style="width: 25px;">S</th>
                <th style="width: 25px;">I</th>
                <th style="width: 25px;">A</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendanceData as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="student-name">{{ $row['name'] }}</td>
                    <td>{{ $row['nis'] }}</td>
                    @foreach($dateRange as $date)
                        <?php
                            $dateKey = $date->format('Y-m-d');
                            $status = $row['attendance'][$dateKey] ?? '-';
                        ?>
                        <td style="{{ $status === 'alfa' ? 'background: #fee2e2;' : '' }}">
                            @if($status === 'hadir') ✓
                            @elseif($status === 'sakit') S
                            @elseif($status === 'izin') I
                            @elseif($status === 'alfa') A
                            @elseif($status === 'terlambat') T
                            @else -
                            @endif
                        </td>
                    @endforeach
                    {{-- Urutan WAJIB sama dengan judul kolom di atas (H T S I A). --}}
                    <td>{{ $row['hadir'] }}</td>
                    <td>{{ $row['terlambat'] }}</td>
                    <td>{{ $row['sakit'] }}</td>
                    <td>{{ $row['izin'] }}</td>
                    <td>{{ $row['alfa'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d M Y, H:i') }} WIB</p>
    </div>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <p>Mengetahui,</p>
                <p>Kepala Sekolah</p>
                <div style="height: 60px;"></div>
                <p>_______________________</p>
            </td>
            <td style="width: 50%;">
                {{-- users.school_city, BUKAN $class->city: tabel classes tidak punya
                     kolom kota sama sekali, sehingga keempat berkas ekspor selalu
                     mencetak kata harfiah "Kota" — placeholder yang terkirim
                     sebagai dokumen bertanda tangan. --}}
                @php $kota = $user->school_city ?? $class->user->school_city; @endphp
                <p>{{ $kota ? $kota.', ' : '' }}{{ now()->translatedFormat('d F Y') }}</p>
                <p>Wali Kelas</p>
                <div style="height: 60px;"></div>
                <p>{{ ($user->name ?? $class->user->name ?? "Wali Kelas") }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
