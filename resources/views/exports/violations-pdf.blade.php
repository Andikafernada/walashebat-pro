<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pelanggaran - {{ $student ? $student->name : $class->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 16px; margin-bottom: 5px; }
        .header p { font-size: 10px; color: #666; }
        .info-box { background: #f8f8f8; border: 1px solid #ddd; padding: 12px; margin-bottom: 20px; border-radius: 4px; }
        .info-box table { width: 100%; }
        .info-box td { padding: 3px 8px; }
        .info-box .label { font-weight: bold; width: 120px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 8px 10px; }
        th { background: #f0f0f0; font-weight: bold; text-align: left; }
        .points { text-align: center; font-weight: bold; }
        .points.high { color: #9a3527; }
        .points.medium { color: #8a6317; }
        .points.low { color: #2e6446; }
        .total-box { background: #fee2e2; border: 2px solid #9a3527; padding: 12px; text-align: center; border-radius: 4px; }
        .total-box .number { font-size: 24px; font-weight: bold; color: #9a3527; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; }
        .signature-table { width: 100%; margin-top: 40px; }
        .signature-table td { text-align: center; vertical-align: top; padding: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PELANGGARAN SISWA</h1>
        <p>{{ $student ? $student->name . ' - ' . $student->nis : $class->name }}</p>
        <p>Periode: {{ $periode['awal']->format('d M Y') }} - {{ $periode['akhir']->format('d M Y') }}</p>
    </div>

    @if($student)
    <div class="info-box">
        <table>
            <tr>
                <td class="label">Nama Siswa</td>
                <td>: {{ $student->name }}</td>
                <td class="label">NIS</td>
                <td>: {{ $student->nis }}</td>
            </tr>
            <tr>
                <td class="label">Kelas</td>
                <td>: {{ $class->name }}</td>
                <td class="label">Jurusan</td>
                <td>: {{ $class->major }}</td>
            </tr>
        </table>
    </div>
    @endif

    @php
        $totalPoints = $violations->sum('points');
        $pointsClass = $totalPoints >= 75 ? 'high' : ($totalPoints >= 50 ? 'medium' : 'low');
    @endphp

    <div class="total-box">
        <p>Total Poin Pelanggaran</p>
        <p class="number">{{ $totalPoints }}</p>
    </div>

    <h3 style="margin: 20px 0 10px; font-size: 12px;">Detail Pelanggaran</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 80px;">Tanggal</th>
                <th style="width: 150px;">Jenis Pelanggaran</th>
                <th>Poin</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($violations as $index => $violation)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $violation->occurred_on->format('d/m/Y') }}</td>
                    <td>{{ $violation->type->name ?? 'Lainnya' }}</td>
                    <td class="points {{ $violation->points >= 10 ? 'high' : ($violation->points >= 5 ? 'medium' : 'low') }}">
                        {{ $violation->points }}
                    </td>
                    <td>{{ $violation->note ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">Tidak ada data pelanggaran</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d M Y, H:i') }} WIB</p>
        <p>Dicetak oleh: {{ ($user->name ?? $class->user->name ?? "Wali Kelas") }}</p>
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
