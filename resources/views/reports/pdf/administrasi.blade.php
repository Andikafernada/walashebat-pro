{{--
    Tata letak PDF Buku Administrasi Wali Kelas.

    CSS di sini SENGAJA sederhana. dompdf hanya memahami CSS 2.1 dengan
    sedikit tambahan: tanpa flexbox, tanpa grid, tanpa CSS variable, dan
    tanpa selektor modern. Jadi tata letak memakai table dan float, dan
    tampilan layar (Tailwind) tidak bisa dipakai ulang di sini.

    Font DejaVu Sans dipilih karena satu-satunya font bawaan dompdf yang
    menangani UTF-8 dengan benar. Helvetica akan merusak karakter seperti
    – dan • pada dokumen berbahasa Indonesia.
--}}
@php
    $rp = fn ($v) => 'Rp '.number_format((int) $v, 0, ',', '.');
    $ada = fn ($n) => in_array($n, $sections, true);
    $hariNama = \App\Models\Schedule::DAYS;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Administrasi Wali Kelas {{ $classroom->name }}</title>
    <style>
        @page { margin: 18mm 15mm 20mm 15mm; }

        body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; color: #111; line-height: 1.45; }

        /* Nomor halaman otomatis di kaki setiap lembar. */
        .kaki {
            position: fixed; bottom: -12mm; left: 0; right: 0;
            font-size: 7.5pt; color: #666; border-top: 0.5pt solid #bbb; padding-top: 3pt;
        }
        .kaki .kanan { float: right; }

        h1 { font-size: 20pt; margin: 0; text-transform: uppercase; letter-spacing: 1pt; }
        h2 {
            font-size: 11pt; margin: 0 0 8pt 0; padding-bottom: 4pt;
            border-bottom: 1.5pt solid #111; text-transform: uppercase; letter-spacing: 0.5pt;
        }
        h3 { font-size: 9.5pt; margin: 10pt 0 4pt 0; }

        .bagian { page-break-before: always; }
        .sampul { text-align: center; page-break-after: always; }

        table { width: 100%; border-collapse: collapse; }
        .tbl th, .tbl td { border: 0.5pt solid #777; padding: 3pt 4pt; vertical-align: top; }
        .tbl th { background: #e8e8e8; text-align: center; font-size: 8.5pt; }
        .tbl td { font-size: 8.5pt; }
        /* Baris kepala diulang bila tabel menyeberang halaman. */
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }

        .c { text-align: center; }
        .r { text-align: right; }
        .kecil { font-size: 8pt; color: #555; }
        .tebal { font-weight: bold; }
        .tandai { background: #fde8e8; font-weight: bold; }
        .kosong { text-align: center; color: #888; padding: 12pt; font-style: italic; }

        .kursi { border: 0.5pt solid #777; padding: 5pt 2pt; text-align: center; font-size: 7.5pt; }
        .papan { border: 1pt solid #111; background: #e8e8e8; text-align: center; padding: 5pt; font-size: 8.5pt; font-weight: bold; margin-bottom: 6pt; }

        .kotak { border: 0.5pt solid #777; padding: 6pt 8pt; margin-bottom: 8pt; background: #fafafa; }
    </style>
</head>
<body>

<div class="kaki">
    Administrasi Wali Kelas {{ $classroom->name }} · {{ $periode['label'] }}
</div>

{{-- ================= SAMPUL ================= --}}
<div class="sampul">
    <div style="margin-top: 30mm;">
        <p class="kecil" style="letter-spacing: 2pt; text-transform: uppercase;">Buku Administrasi Wali Kelas</p>
        <div style="width: 45mm; height: 1.5pt; background: #111; margin: 8mm auto;"></div>
        <h1>{{ $classroom->name }}</h1>
        @if ($classroom->major)
            <p style="font-size: 11pt; margin-top: 3pt;">{{ $classroom->major }}</p>
        @endif
    </div>

    <div style="margin-top: 28mm;">
        <p class="kecil">Wali Kelas</p>
        <p style="font-size: 14pt; font-weight: bold; margin: 3pt 0;">{{ ($guru->name ?? $classroom->user->name ?? "Wali Kelas") }}</p>
        @if (($guru->nip ?? $classroom->user->nip ?? ""))
            <p class="kecil">NIP. {{ ($guru->nip ?? $classroom->user->nip ?? "") }}</p>
        @endif
    </div>

    <div style="margin-top: 26mm;">
        <p class="tebal" style="font-size: 11pt; text-transform: uppercase;">
            {{ ($guru->school_name ?? $classroom->user->school_name ?? "WALI KELAS HEBAT") ?: 'Nama Sekolah Belum Diisi' }}
        </p>
        @if (($guru->school_address ?? $classroom->user->school_address ?? ""))
            <p class="kecil">{{ ($guru->school_address ?? $classroom->user->school_address ?? "") }}</p>
        @endif
        @if (($guru->school_npsn ?? $classroom->user->school_npsn ?? ""))
            <p class="kecil">NPSN {{ ($guru->school_npsn ?? $classroom->user->school_npsn ?? "") }}</p>
        @endif
        <p style="margin-top: 6pt;">Tahun Ajaran {{ $classroom->academic_year ?: '—' }}</p>
        <p class="tebal">Periode {{ $periode['label'] }}</p>
    </div>
</div>

{{-- ================= RINGKASAN ================= --}}
<div>
    <h2>Ringkasan Periode</h2>
    <table class="tbl">
        <tr>
            <th style="width: 40%;">Keterangan</th>
            <th>Nilai</th>
        </tr>
        <tr><td>Jumlah siswa</td><td>{{ $ringkasanSiswa['total'] }} orang (L: {{ $ringkasanSiswa['lk'] }}, P: {{ $ringkasanSiswa['pr'] }})</td></tr>
        <tr><td>Jumlah pertemuan tercatat</td><td>{{ $jumlahPertemuan }} pertemuan</td></tr>
        <tr><td>Kehadiran kelas</td><td class="tebal">{{ $persenKelas }}%</td></tr>
        <tr><td>Jumlah pelanggaran</td><td>{{ $pelanggaran->count() }} kejadian</td></tr>
        <tr><td>Siswa perlu perhatian</td><td>{{ $perhatian->count() }} orang</td></tr>
        <tr><td>Saldo kas akhir periode</td><td class="tebal">{{ $rp($kas['saldo_akhir']) }}</td></tr>
    </table>
    <p class="kecil" style="margin-top: 6pt;">
        Dicetak {{ now()->translatedFormat('d F Y, H:i') }} WIB.
        Persentase kehadiran dihitung dari jumlah isian absensi tiap siswa, bukan dari jumlah pertemuan kelas.
    </p>
</div>

{{-- ================= DATA SISWA ================= --}}
@if ($ada('siswa'))
    <div class="bagian" data-bagian="siswa">
        <h2>Data Siswa</h2>
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width: 6%;">No</th>
                    <th style="width: 16%;">NIS</th>
                    <th>Nama Siswa</th>
                    <th style="width: 8%;">L/P</th>
                    <th style="width: 22%;">HP Orang Tua</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($siswa as $i => $s)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $s->nis ?: '—' }}</td>
                    <td>{{ $s->name }}</td>
                    <td class="c">{{ $s->gender ?: '—' }}</td>
                    <td>{{ $s->parent_phone ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="kosong">Belum ada data siswa.</td></tr>
            @endforelse
            </tbody>
        </table>
        <p style="margin-top: 6pt;">
            <span class="tebal">Jumlah:</span> {{ $ringkasanSiswa['total'] }} siswa
            (Laki-laki {{ $ringkasanSiswa['lk'] }}, Perempuan {{ $ringkasanSiswa['pr'] }})
        </p>
    </div>
@endif

{{-- ================= BUKU INDUK ================= --}}
@if ($ada('biodata'))
    <div class="bagian" data-bagian="biodata">
        <h2>Buku Induk Siswa</h2>
        <p class="kecil" style="margin-bottom: 8pt;">
            Satu blok per siswa. Kolom yang belum terisi ditampilkan sebagai tanda hubung agar terlihat apa yang masih perlu dilengkapi.
        </p>
        @foreach ($siswa as $i => $s)
            <div class="kotak" style="page-break-inside: avoid;">
                <p class="tebal" style="margin: 0 0 4pt 0;">
                    {{ $i + 1 }}. {{ $s->name }}
                    <span class="kecil">— NIS {{ $s->nis ?: '—' }} / NISN {{ $s->nisn ?: '—' }}</span>
                </p>
                <table style="font-size: 8pt;">
                    <tr>
                        <td style="width: 25%;">Jenis kelamin</td>
                        <td style="width: 25%;">{{ $s->gender ?: '—' }}</td>
                        <td style="width: 25%;">Agama</td>
                        <td>{{ $s->agama ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td>Tempat lahir</td>
                        <td>{{ $s->tempat_lahir ?: '—' }}</td>
                        <td>Tanggal lahir</td>
                        <td>{{ $s->tanggal_lahir ? \Illuminate\Support\Carbon::parse($s->tanggal_lahir)->translatedFormat('d F Y') : '—' }}</td>
                    </tr>
                    <tr>
                        <td>Nama ayah</td>
                        <td>{{ $s->nama_ayah ?: '—' }}</td>
                        <td>Nama ibu</td>
                        <td>{{ $s->nama_ibu ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td>HP siswa</td>
                        <td>{{ $s->phone ?: '—' }}</td>
                        <td>HP orang tua</td>
                        <td>{{ $s->parent_phone ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td>Alamat</td>
                        <td colspan="3">{{ $s->address ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td>Asal sekolah</td>
                        <td>{{ $s->asal_sekolah ?: '—' }}</td>
                        <td>Poin disiplin</td>
                        <td>{{ $s->discipline_points ?? '—' }}</td>
                    </tr>
                </table>
            </div>
        @endforeach
        @if ($siswa->isEmpty())
            <p class="kosong">Belum ada data siswa.</p>
        @endif
    </div>
@endif

{{-- ================= STRUKTUR ================= --}}
@if ($ada('struktur'))
    <div class="bagian" data-bagian="struktur">
        <h2>Struktur Organisasi Kelas</h2>
        <table class="tbl">
            <thead><tr><th style="width: 8%;">No</th><th style="width: 40%;">Jabatan</th><th>Nama Siswa</th></tr></thead>
            <tbody>
            @forelse ($struktur as $i => $st)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $st->roleLabel() }}</td>
                    <td>{{ $st->student->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="kosong">Belum ada struktur organisasi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endif

{{-- ================= DENAH ================= --}}
@if ($ada('denah'))
    <div class="bagian" data-bagian="denah">
        <h2>Denah Tempat Duduk</h2>
        @if (empty($grid))
            <p class="kosong">Denah belum diatur.</p>
        @else
            <div class="papan">PAPAN TULIS / MEJA GURU</div>
            @foreach ($grid as $baris)
                <table style="margin-bottom: 4pt;">
                    <tr>
                        @foreach ($baris as $nama)
                            <td class="kursi">{{ $nama ?: '' }}</td>
                        @endforeach
                    </tr>
                </table>
            @endforeach
        @endif
    </div>
@endif

{{-- ================= JADWAL ================= --}}
@if ($ada('jadwal'))
    <div class="bagian" data-bagian="jadwal">
        <h2>Jadwal Pelajaran</h2>
        @if ($jadwal->isEmpty())
            <p class="kosong">Jadwal belum diisi.</p>
        @else
            @foreach ($hariNama as $num => $hari)
                @if (isset($jadwal[$num]) && $jadwal[$num]->isNotEmpty())
                    <h3>{{ $hari }}</h3>
                    <table class="tbl">
                        <thead><tr><th style="width: 24%;">Jam</th><th>Mata Pelajaran</th><th style="width: 30%;">Guru</th></tr></thead>
                        <tbody>
                        @foreach ($jadwal[$num] as $j)
                            <tr>
                                <td class="c">{{ substr($j->start_time, 0, 5) }}–{{ substr($j->end_time, 0, 5) }}</td>
                                <td>{{ $j->subject }}</td>
                                <td>{{ $j->teacher_name ?: '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            @endforeach
        @endif
    </div>
@endif

{{-- ================= KEHADIRAN ================= --}}
@if ($ada('kehadiran'))
    <div class="bagian" data-bagian="kehadiran">
        <h2>Rekapitulasi Kehadiran</h2>
        <p class="kecil" style="margin-bottom: 6pt;">
            Periode {{ $periode['label'] }} · {{ $jumlahPertemuan }} pertemuan · kehadiran kelas {{ $persenKelas }}%
        </p>
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width: 6%;">No</th>
                    <th>Nama Siswa</th>
                    <th style="width: 7%;">H</th>
                    <th style="width: 7%;">T</th>
                    <th style="width: 7%;">S</th>
                    <th style="width: 7%;">I</th>
                    <th style="width: 7%;">A</th>
                    <th style="width: 10%;">%</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($kehadiran as $i => $k)
                <tr @class(['tandai' => $k['perhatian']])>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $k['siswa']->name }}</td>
                    <td class="c">{{ $k['jumlah']['hadir'] }}</td>
                    <td class="c">{{ $k['jumlah']['terlambat'] }}</td>
                    <td class="c">{{ $k['jumlah']['sakit'] }}</td>
                    <td class="c">{{ $k['jumlah']['izin'] }}</td>
                    <td class="c">{{ $k['jumlah']['alfa'] }}</td>
                    <td class="c tebal">{{ $k['persen'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="8" class="kosong">Belum ada data kehadiran pada periode ini.</td></tr>
            @endforelse
            </tbody>
        </table>
        <p class="kecil" style="margin-top: 5pt;">
            Baris berlatar diarsir: siswa dengan alfa 3 kali atau lebih, atau terlambat 5 kali atau lebih.
            H = Hadir, T = Terlambat, S = Sakit, I = Izin, A = Alfa (tanpa keterangan).
            Persentase menghitung terlambat sebagai masuk — siswanya hadir, hanya tidak tepat waktu.
        </p>
    </div>
@endif

{{-- ================= PELANGGARAN ================= --}}
@if ($ada('pelanggaran'))
    <div class="bagian" data-bagian="pelanggaran">
        <h2>Catatan Pelanggaran</h2>
        <p class="kecil" style="margin-bottom: 6pt;">Periode {{ $periode['label'] }}</p>
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width: 16%;">Tanggal</th>
                    <th style="width: 28%;">Nama Siswa</th>
                    <th>Jenis Pelanggaran</th>
                    <th style="width: 10%;">Poin</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($pelanggaran as $v)
                <tr>
                    <td class="c">{{ $v->occurred_on->format('d/m/Y') }}</td>
                    <td>{{ $v->student->name ?? '—' }}</td>
                    <td>{{ $v->type->name ?? $v->note ?: '—' }}</td>
                    <td class="c">{{ $v->points }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="kosong">Tidak ada pelanggaran pada periode ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endif

{{-- ================= REKAP POIN ================= --}}
@if ($ada('poin'))
    <div class="bagian" data-bagian="poin">
        <h2>Rekap Poin per Siswa</h2>
        <p class="kecil" style="margin-bottom: 6pt;">
            Hanya siswa yang punya catatan pelanggaran pada periode ini.
            "Poin berjalan" adalah poin disiplin terkini, bukan hanya periode ini.
        </p>
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width: 6%;">No</th>
                    <th>Nama Siswa</th>
                    <th style="width: 14%;">Kejadian</th>
                    <th style="width: 16%;">Poin periode</th>
                    <th style="width: 16%;">Poin berjalan</th>
                    <th style="width: 16%;">Terakhir</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($poin as $i => $b)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $b['siswa']->name }}</td>
                    <td class="c">{{ $b['kejadian'] }}</td>
                    <td class="c">{{ $b['poin_periode'] }}</td>
                    <td class="c tebal">{{ $b['poin_sekarang'] }}</td>
                    <td class="c">{{ $b['terakhir']?->format('d/m/Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="kosong">Tidak ada siswa dengan catatan pelanggaran.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endif

{{-- ================= PERLU PERHATIAN ================= --}}
@if ($ada('perhatian'))
    <div class="bagian" data-bagian="perhatian">
        <h2>Siswa Perlu Perhatian</h2>
        <p class="kecil" style="margin-bottom: 6pt;">
            Disusun otomatis dari ambang berikut: alfa 3 kali atau lebih, kehadiran di bawah 75%,
            poin disiplin di bawah 75, atau 3 pelanggaran atau lebih dalam periode ini.
        </p>
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width: 6%;">No</th>
                    <th style="width: 32%;">Nama Siswa</th>
                    <th>Alasan</th>
                    <th style="width: 20%;">Tindak lanjut</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($perhatian as $i => $p)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $p['siswa']->name }}</td>
                    <td>{{ implode(' · ', $p['alasan']) }}</td>
                    <td></td>
                </tr>
            @empty
                <tr><td colspan="4" class="kosong">Tidak ada siswa yang melewati ambang perhatian. </td></tr>
            @endforelse
            </tbody>
        </table>
        <p class="kecil" style="margin-top: 5pt;">
            Kolom tindak lanjut sengaja dibiarkan kosong untuk diisi tangan saat pembinaan.
        </p>
    </div>
@endif

{{-- ================= BUKU KAS ================= --}}
@if ($ada('kas'))
    <div class="bagian" data-bagian="kas">
        <h2>Buku Kas Kelas</h2>
        <p class="kecil" style="margin-bottom: 6pt;">Periode {{ $periode['label'] }}</p>
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width: 14%;">Tanggal</th>
                    <th>Keterangan</th>
                    <th style="width: 17%;">Masuk</th>
                    <th style="width: 17%;">Keluar</th>
                    <th style="width: 18%;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="c">—</td>
                    <td class="tebal">Saldo awal periode</td>
                    <td></td>
                    <td></td>
                    <td class="r tebal">{{ $rp($kas['saldo_awal']) }}</td>
                </tr>
            @forelse ($kas['transaksi'] as $t)
                <tr>
                    <td class="c">{{ $t->transaction_date->format('d/m/Y') }}</td>
                    <td>{{ $t->description }}</td>
                    <td class="r">{{ $t->type === 'in' ? $rp($t->amount) : '' }}</td>
                    <td class="r">{{ $t->type === 'out' ? $rp($t->amount) : '' }}</td>
                    <td class="r">{{ $rp($t->saldo_berjalan) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="kosong">Tidak ada transaksi pada periode ini.</td></tr>
            @endforelse
                <tr>
                    <td colspan="2" class="tebal r">Jumlah periode ini</td>
                    <td class="r tebal">{{ $rp($kas['masuk']) }}</td>
                    <td class="r tebal">{{ $rp($kas['keluar']) }}</td>
                    <td class="r tebal">{{ $rp($kas['saldo_akhir']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@endif

{{-- ================= TANDA TANGAN ================= --}}
<div style="page-break-inside: avoid; margin-top: 14mm;">
    <table style="font-size: 9pt;">
        <tr>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <p>Mengetahui,</p>
                <p class="tebal">Kepala Sekolah</p>
                <div style="height: 22mm;"></div>
                <p style="border-top: 0.5pt solid #111; display: inline-block; padding: 2pt 14pt 0 14pt;">
                    {{ ($guru->principal_name ?? $classroom->user->principal_name ?? "") ?: '............................................' }}
                </p>
                <p class="kecil">NIP. {{ ($guru->principal_nip ?? $classroom->user->principal_nip ?? "") ?: '............................' }}</p>
            </td>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <p>{{ ($guru->school_city ?? $classroom->user->school_city ?? "") ? ($guru->school_city ?? $classroom->user->school_city ?? "").', ' : '' }}{{ now()->translatedFormat('d F Y') }}</p>
                <p class="tebal">Wali Kelas</p>
                <div style="height: 22mm;"></div>
                <p style="border-top: 0.5pt solid #111; display: inline-block; padding: 2pt 14pt 0 14pt;">
                    {{ ($guru->name ?? $classroom->user->name ?? "Wali Kelas") }}
                </p>
                <p class="kecil">NIP. {{ ($guru->nip ?? $classroom->user->nip ?? "") ?: '............................' }}</p>
            </td>
        </tr>
    </table>
</div>

{{-- Penomoran halaman dompdf: skrip inline yang dieksekusi saat render PDF,
     bukan JavaScript browser. Inilah cara resmi dompdf menulis nomor halaman. --}}
<script type="text/php">
    if (isset($pdf)) {
        $pdf->page_script('
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $pdf->text(495, 812, $PAGE_NUM . " / " . $PAGE_COUNT, $font, 7.5, [0.4, 0.4, 0.4]);
        ');
    }
</script>

</body>
</html>
