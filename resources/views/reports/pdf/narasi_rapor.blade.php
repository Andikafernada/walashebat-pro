<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Narasi Rapor — {{ $classroom->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #000; line-height: 1.4; margin: 15px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 15px; }
        .header h1 { font-size: 14px; margin: 0; text-transform: uppercase; }
        .header p { margin: 2px 0 0; font-size: 10px; }
        .student-box { border: 1px solid #ccc; border-radius: 8px; padding: 10px; margin-bottom: 15px; page-break-inside: avoid; }
        .student-name { font-size: 12px; font-weight: bold; margin-bottom: 5px; }
        .section-title { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #333; margin-top: 6px; }
        .text { font-size: 10px; margin: 2px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rekap Deskripsi Narasi Capaian Rapor (Kurikulum Merdeka)</h1>
        <p>Kelas: {{ $classroom->name }} &middot; Semester {{ $semester }} &middot; Tahun Ajaran {{ $classroom->academic_year ?? '2026/2027' }} &middot; Wali Kelas: {{ $guru->name }}</p>
    </div>

    @foreach ($narratives as $idx => $s)
        <div class="student-box">
            <div class="student-name">{{ $idx + 1 }}. {{ $s['name'] }} (NIS: {{ $s['nis'] ?: '—' }})</div>
            
            <div class="section-title">A. Capaian Kompetensi Pembelajaran</div>
            <div class="text">{{ $s['academic_narrative'] }}</div>

            <div class="section-title">B. Dimensi Profil Pelajar Pancasila (P5)</div>
            <div class="text">{{ $s['character_narrative'] }}</div>

            <div class="section-title">C. Catatan Resmi Wali Kelas</div>
            <div class="text">{{ $s['homeroom_notes'] }}</div>
        </div>
    @endforeach
</body>
</html>
