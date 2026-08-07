<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portofolio Karakter - {{ $student->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 16px; margin-bottom: 5px; }
        .header p { font-size: 10px; color: #666; }
        .student-info { background: #f8f8f8; border: 1px solid #ddd; padding: 12px; margin-bottom: 20px; border-radius: 4px; }
        .student-info table { width: 100%; }
        .student-info td { padding: 3px 8px; }
        .student-info .label { font-weight: bold; width: 120px; }
        .dimension-scores { margin-bottom: 20px; }
        .dimension-scores h3 { font-size: 12px; margin-bottom: 10px; }
        .dimension-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .dimension-item { background: #f0f0f0; border: 1px solid #ddd; padding: 8px 12px; border-radius: 4px; min-width: 120px; }
        .dimension-item .name { font-weight: bold; font-size: 10px; }
        .dimension-item .score { font-size: 14px; }
        .positive { color: #16a34a; }
        .negative { color: #dc2626; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 6px 8px; }
        th { background: #f0f0f0; font-weight: bold; }
        .type-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; }
        .type-positive { background: #dcfce7; color: #16a34a; }
        .type-negative { background: #fee2e2; color: #dc2626; }
        .type-observation { background: #fef3c7; color: #d97706; }
        .type-achievement { background: #dbeafe; color: #2563eb; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; }
        .signature-table { width: 100%; margin-top: 40px; }
        .signature-table td { text-align: center; vertical-align: top; padding: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PORTOFOLIO KARAKTER SISWA</h1>
        <p>Profil Pelajar Pancasila</p>
        <p>{{ $class->name }} - {{ $class->major }}</p>
    </div>

    <div class="student-info">
        <table>
            <tr>
                <td class="label">Nama Siswa</td>
                <td>: {{ $student->name }}</td>
                <td class="label">NIS</td>
                <td>: {{ $student->nis ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Kelas</td>
                <td>: {{ $class->name }}</td>
                <td class="label">Semester</td>
                <td>: {{ $periode["label"] ?? "Semester 1" }}</td>
            </tr>
        </table>
    </div>

    <div class="dimension-scores">
        <h3>Skor per Dimensi (Semester Ini)</h3>
        <div class="dimension-grid">
            @foreach($dimensionScores as $score)
                <div class="dimension-item">
                    <div class="name">{{ $score['name'] }}</div>
                    <div class="score {{ $score['score'] >= 0 ? 'positive' : 'negative' }}">
                        {{ $score['score'] > 0 ? '+' : '' }}{{ $score['score'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <h3 style="font-size: 12px; margin-bottom: 10px;">Riwayat Catatan Karakter</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 80px;">Tanggal</th>
                <th style="width: 120px;">Dimensi</th>
                <th style="width: 70px;">Tipe</th>
                <th>Judul</th>
                <th style="width: 70px;">Skor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                <tr>
                    <td style="text-align: center;">{{ $record['no'] }}</td>
                    <td>{{ $record['date']->format('d/m/Y') }}</td>
                    <td>{{ $record['dimension_name'] }}</td>
                    <td style="text-align: center;">
                        <span class="type-badge type-{{ $record['type'] }}">
                            @if($record['type'] === 'positive') Positif
                            @elseif($record['type'] === 'negative') Negatif
                            @elseif($record['type'] === 'observation') Observasi
                            @elseif($record['type'] === 'achievement') Prestasi
                            @endif
                        </span>
                    </td>
                    <td>
                        {{ $record['title'] }}
                        @if($record['description'])
                            <br><small style="color: #666;">{{ Str::limit($record['description'], 80) }}</small>
                        @endif
                    </td>
                    <td style="text-align: center; font-weight: bold;" class="{{ str_contains($record['score_display'], '+') ? 'positive' : (str_contains($record['score_display'], '-') ? 'negative' : '') }}">
                        {{ $record['score_display'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">Belum ada catatan karakter</td>
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
                <p>{{ $class->city ?? 'Kota' }}, {{ now()->format('d M Y') }}</p>
                <p>Wali Kelas</p>
                <div style="height: 60px;"></div>
                <p>{{ ($user->name ?? $class->user->name ?? "Wali Kelas") }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
