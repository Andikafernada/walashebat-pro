<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { font-family: 'DejaVu Serif', Georgia, serif; }
        body { margin: 0; }
        .sheet {
            box-sizing: border-box;
            width: 100%; height: 540pt;
            padding: 24pt;
        }
        .frame {
            box-sizing: border-box;
            width: 100%; height: 100%;
            border: 4pt solid #b45309;
            padding: 6pt;
        }
        .inner {
            box-sizing: border-box;
            width: 100%; height: 100%;
            border: 1pt solid #b45309;
            padding: 22pt 40pt;
            text-align: center;
            position: relative;
        }
        .school { font-size: 13pt; font-weight: bold; color: #1e293b; letter-spacing: 1pt; text-transform: uppercase; }
        .school-sub { font-size: 9pt; color: #64748b; margin-top: 2pt; }
        .rule { border: 0; border-top: 1.5pt solid #cbd5e1; margin: 10pt 0 16pt; }
        .title { font-size: 30pt; font-weight: bold; color: #b45309; letter-spacing: 3pt; margin: 4pt 0; }
        .subtitle { font-size: 12pt; color: #475569; letter-spacing: 4pt; text-transform: uppercase; }
        .given { font-size: 10pt; color: #64748b; margin-top: 14pt; }
        .name { font-size: 26pt; font-weight: bold; color: #0f172a; margin: 6pt 0 2pt; }
        .name-rule { width: 55%; margin: 0 auto; border: 0; border-top: 1pt solid #94a3b8; }
        .reason { font-size: 11pt; color: #334155; margin-top: 12pt; line-height: 1.5; }
        .badge { font-size: 12pt; font-weight: bold; color: #b45309; }
        .sign { position: absolute; bottom: 8pt; width: 86%; }
        .sign td { font-size: 10pt; color: #334155; text-align: center; vertical-align: top; width: 33%; }
        .sign .space { height: 46pt; }
        .sign .nm { font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="frame">
            <div class="inner">
                <div class="school">{{ $guru->school_name ?: 'SEKOLAH' }}</div>
                <div class="school-sub">
                    {{ $guru->school_address ? $guru->school_address.' · ' : '' }}{{ $guru->school_city }}
                </div>
                <hr class="rule">

                <div class="title">SERTIFIKAT</div>
                <div class="subtitle">Penghargaan Siswa Terajin</div>

                <div class="given">Diberikan dengan bangga kepada:</div>
                <div class="name">{{ $nama }}</div>
                <hr class="name-rule">

                <div class="reason">
                    atas kerajinan dan kedisiplinan kehadiran sebagai
                    <strong>peringkat {{ $peringkatKe }}</strong> di kelas {{ $kelas }}<br>
                    pada <strong>{{ $periode['label'] }}</strong>
                    dengan <span class="badge">{{ $poin }} poin kerajinan</span>.
                </div>

                <table class="sign" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            {{ $guru->school_city ?: '' }}, {{ $periode['akhir']->translatedFormat('d F Y') }}<br>
                            Kepala Sekolah
                            <div class="space"></div>
                            <span class="nm">{{ $guru->principal_name ?: '(..............................)' }}</span><br>
                            {{ $guru->principal_nip ? 'NIP. '.$guru->principal_nip : '' }}
                        </td>
                        <td></td>
                        <td>
                            &nbsp;<br>
                            Wali Kelas
                            <div class="space"></div>
                            <span class="nm">{{ $guru->name }}</span><br>
                            {{ $guru->nip ? 'NIP. '.$guru->nip : '' }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
