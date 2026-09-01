{{-- ══════════════════════════════════════════════════════════════════════════════════
     HALAMAN 1: IDENTITAS LENGKAP SISWA, DATA ORANG TUA, PRESENSI & KEDISIPLINAN
     ══════════════════════════════════════════════════════════════════════════════════ --}}

@if ($kopLembar ?? true)
    <div class="header-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: top; width: 60%;">
                    <div class="school-title">{{ $guru->school_name ?? 'SMK PASUNDAN 2 BANDUNG' }}</div>
                    <div class="school-address">
                        {{ $guru->school_address ?: 'Jl. Pelita Karya I No.2, Maleber, Kec. Andir, Kota Bandung, Jawa Barat 40184' }}
                    </div>
                </td>
                <td style="vertical-align: top; text-align: right; width: 40%;">
                    <div class="doc-badge">Portofolio Perkembangan Siswa</div>
                    <div class="doc-period">Periode Evaluasi: <strong>{{ $periode['label'] }}</strong></div>
                </td>
            </tr>
        </table>
    </div>
@endif

{{-- 1. KARTU PROFIL UTAMA SISWA --}}
<div class="profile-card">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 60pt; vertical-align: middle;">
                @if ($fotoSiswa = $siswa->photoDataUri())
                    <img class="student-avatar" src="{{ $fotoSiswa }}" alt="Foto Siswa">
                @else
                    <div class="student-avatar-empty">
                        <div class="avatar-initials">{{ $siswa->initials() }}</div>
                        <div class="avatar-label">PAS FOTO</div>
                    </div>
                @endif
            </td>
            <td style="vertical-align: middle; padding-left: 10pt;">
                <div class="student-name-main">{{ $siswa->name }}</div>
                <div style="font-size: 7.5pt; color: #334155; margin-top: 2pt;">
                    NIS: <strong>{{ $siswa->nis ?: '—' }}</strong> &nbsp;|&nbsp; 
                    NISN: <strong>{{ $siswa->nisn ?: '—' }}</strong> &nbsp;|&nbsp; 
                    NIK: <strong>{{ $siswa->nik ?: '—' }}</strong> &nbsp;|&nbsp; 
                    Kelas: <strong>{{ $classroom->name }}</strong>
                </div>
                <div style="font-size: 7.2pt; color: #64748b; margin-top: 2pt;">
                    Tempat, Tgl Lahir: <strong>{{ $siswa->tempat_lahir ?: '—' }}, {{ $siswa->tanggal_lahir ? \Illuminate\Support\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : '—' }}</strong> &middot; 
                    JK: <strong>{{ $siswa->gender === 'L' ? 'Laki-laki' : ($siswa->gender === 'P' ? 'Perempuan' : '—') }}</strong> &middot;
                    Agama: <strong>{{ $siswa->agama ?: 'Islam' }}</strong>
                </div>
            </td>
            <td style="width: 80pt; vertical-align: middle; text-align: right;">
                <span class="badge-pill badge-emerald">{{ $classroom->name }}</span>
                @if ($peran?->isNotEmpty())
                    <div style="margin-top: 2.5pt;">
                        <span class="badge-pill badge-blue">{{ $peran->first()->roleLabel() }}</span>
                    </div>
                @endif
                <div style="margin-top: 2.5pt;">
                    <span class="badge-pill {{ $siswa->is_active ? 'badge-emerald' : 'badge-rose' }}">
                        {{ $siswa->is_active ? 'SISWA AKTIF' : 'NON-AKTIF' }}
                    </span>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- 2. 4-KPI SUMMARY METRICS --}}
<table class="kpi-table">
    <tr>
        <td style="width: 25%;">
            <div class="kpi-box" style="background-color: #ecfdf5; border-color: #a7f3d0;">
                <div class="kpi-val" style="color: #047857;">{{ $kehadiran['persen'] === null ? '100%' : $kehadiran['persen'].'%' }}</div>
                <div class="kpi-title">Tingkat Kehadiran</div>
            </div>
        </td>
        <td style="width: 25%;">
            <div class="kpi-box" style="background-color: {{ $kehadiran['jumlah']['alfa'] > 0 ? '#fff1f2' : '#f8fafc' }}; border-color: {{ $kehadiran['jumlah']['alfa'] > 0 ? '#fecdd3' : '#e2e8f0' }};">
                <div class="kpi-val" style="color: {{ $kehadiran['jumlah']['alfa'] > 0 ? '#e11d48' : '#334155' }};">{{ $kehadiran['jumlah']['alfa'] }}</div>
                <div class="kpi-title">Jumlah Alfa / Bolos</div>
            </div>
        </td>
        <td style="width: 25%;">
            <div class="kpi-box" style="background-color: #f0f9ff; border-color: #bae6fd;">
                <div class="kpi-val" style="color: #0369a1;">{{ $poin['sekarang'] ?? 100 }} / 100</div>
                <div class="kpi-title">Poin Disiplin</div>
            </div>
        </td>
        <td style="width: 25%;">
            <div class="kpi-box" style="background-color: #fefce8; border-color: #fef08a;">
                <div class="kpi-val" style="color: #a16207;">{{ $nilai['rata_rapor'] !== null ? $nilai['rata_rapor'] : '—' }}</div>
                <div class="kpi-title">Rata-rata Rapor</div>
            </div>
        </td>
    </tr>
</table>

{{-- 3. BIODATA DETAIL & DATA ORANG TUA (2 KOLOM LENGKAP) --}}
<div class="section-header">I. Biodata Lengkap &amp; Informasi Keluarga</div>
<table class="col-table">
    <tr>
        {{-- Sisi Kiri: Biodata Siswa --}}
        <td class="col-cell" style="width: 50%;">
            <table class="data-table">
                <tr><td style="width: 38%; font-weight: bold; background: #f8fafc;">Anak Ke- / Saudara</td><td>{{ $siswa->anak_ke ?: '1' }} dari {{ $siswa->jumlah_saudara ?: '1' }} bersaudara</td></tr>
                <tr><td style="font-weight: bold; background: #f8fafc;">Golongan Darah</td><td>{{ $siswa->golongan_darah ?: '—' }} &middot; TB/BB: {{ $siswa->tinggi_badan_cm ? $siswa->tinggi_badan_cm.' cm' : '—' }} / {{ $siswa->berat_badan_kg ? $siswa->berat_badan_kg.' kg' : '—' }}</td></tr>
                <tr><td style="font-weight: bold; background: #f8fafc;">Alamat Tinggal</td><td>{{ $siswa->alamat ?: '—' }} @if($siswa->rt_rw) (RT/RW: {{ $siswa->rt_rw }}) @endif</td></tr>
                <tr><td style="font-weight: bold; background: #f8fafc;">Kelurahan / Kec.</td><td>{{ $siswa->kelurahan ?: '—' }} / {{ $siswa->kecamatan ?: '—' }}</td></tr>
                <tr><td style="font-weight: bold; background: #f8fafc;">No. HP Siswa</td><td>{{ $siswa->phone ?: '—' }}</td></tr>
                <tr><td style="font-weight: bold; background: #f8fafc;">Transportasi / Jarak</td><td>{{ $siswa->moda_transportasi ?: 'Jalan Kaki' }} ({{ $siswa->jarak_rumah_km ? $siswa->jarak_rumah_km.' km' : '0.5 km' }})</td></tr>
            </table>
        </td>

        {{-- Sisi Kanan: Data Orang Tua / Wali --}}
        <td class="col-cell" style="width: 50%;">
            <table class="data-table">
                <tr><td style="width: 38%; font-weight: bold; background: #f8fafc;">Nama Ayah</td><td class="font-bold">{{ $siswa->nama_ayah ?: ($siswa->father_name ?: '—') }}</td></tr>
                <tr><td style="font-weight: bold; background: #f8fafc;">Pekerjaan Ayah</td><td>{{ $siswa->pekerjaan_ayah ?: '—' }}</td></tr>
                <tr><td style="font-weight: bold; background: #f8fafc;">Nama Ibu</td><td class="font-bold">{{ $siswa->nama_ibu ?: ($siswa->mother_name ?: '—') }}</td></tr>
                <tr><td style="font-weight: bold; background: #f8fafc;">Pekerjaan Ibu</td><td>{{ $siswa->pekerjaan_ibu ?: 'IRT' }}</td></tr>
                <tr><td style="font-weight: bold; background: #f8fafc;">WhatsApp Ortu</td><td class="font-bold text-emerald-800">{{ $siswa->parent_phone ?: '—' }}</td></tr>
                <tr><td style="font-weight: bold; background: #f8fafc;">Bantuan Sekolah</td><td>{{ $siswa->penerima_kip ? 'Penerima KIP / PIP' : ($siswa->penerima_pkh ? 'Penerima PKH' : 'Reguler / Mandiri') }}</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- 4. REKAPITULASI PRESENSI & GRAFIK TREN KEHADIRAN --}}
<div class="section-header">II. Rekapitulasi Presensi &amp; Tren Kehadiran</div>
<div style="background-color: #f8fafc; border: 0.5pt solid #cbd5e1; border-radius: 5pt; padding: 5pt 7pt; margin-bottom: 6pt;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="font-size: 7.5pt; font-weight: bold;">
                🟢 Hadir: <strong>{{ $kehadiran['jumlah']['hadir'] }} Hari</strong> &nbsp;|&nbsp;
                🟡 Terlambat: <strong>{{ $kehadiran['jumlah']['terlambat'] }}</strong> &nbsp;|&nbsp;
                🔵 Sakit: <strong>{{ $kehadiran['jumlah']['sakit'] }}</strong> &nbsp;|&nbsp;
                🟣 Izin: <strong>{{ $kehadiran['jumlah']['izin'] }}</strong> &nbsp;|&nbsp;
                🔴 Alfa: <strong style="color: #e11d48;">{{ $kehadiran['jumlah']['alfa'] }}</strong>
            </td>
            <td style="text-align: right; font-size: 7.5pt; color: #475569;">
                Total Pertemuan Tercatat: <strong>{{ $kehadiran['total'] }} Sesi</strong>
            </td>
        </tr>
    </table>
</div>

{{-- Tren Bulanan --}}
@if(!empty($tren))
    <table class="data-table" style="margin-bottom: 6pt;">
        <thead>
            <tr>
                <th style="width: 25%;">Bulan</th>
                <th style="width: 45%;">Tingkat Kehadiran Visual</th>
                <th style="width: 15%;" class="text-center">% Hadir</th>
                <th style="width: 15%;" class="text-center">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tren as $t)
                <tr>
                    <td class="font-bold">{{ $t['nama_bulan'] ?? $t['label'] ?? 'Bulan' }}</td>
                    <td style="vertical-align: middle;">
                        <div style="background-color: #e2e8f0; height: 5pt; border-radius: 2.5pt; overflow: hidden; width: 100%;">
                            <div style="background-color: {{ ($t['persen'] ?? 100) >= 85 ? '#059669' : (($t['persen'] ?? 100) >= 75 ? '#d97706' : '#e11d48') }}; height: 5pt; width: {{ $t['persen'] ?? 100 }}%;"></div>
                        </div>
                    </td>
                    <td class="text-center font-bold" style="color: {{ ($t['persen'] ?? 100) >= 85 ? '#059669' : '#e11d48' }};">
                        {{ $t['persen'] ?? 100 }}%
                    </td>
                    <td class="text-center" style="font-size: 6.8pt; color: #64748b;">
                        Telat: {{ $t['terlambat'] ?? 0 }} &middot; Alfa: {{ $t['alfa'] ?? 0 }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- 5. KEDISIPLINAN & PRESTASI --}}
<div class="section-header">III. Catatan Sikap, Kedisiplinan &amp; Pelanggaran</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 18%;">Tanggal</th>
            <th>Uraian Kejadian / Pelanggaran / Apresiasi Prestasi</th>
            <th style="width: 15%;" class="text-center">Perubahan Poin</th>
            <th style="width: 15%;" class="text-right">Sisa Skor</th>
        </tr>
    </thead>
    <tbody>
        @forelse($pelanggaran as $v)
            <tr>
                <td>{{ $v->occurred_on->format('d/m/Y') }}</td>
                <td>
                    <strong class="text-slate-900">{{ $v->type->name ?? 'Catatan Kedisiplinan' }}</strong>
                    @if($v->note) <div style="font-size: 6.8pt; color: #64748b;">{{ $v->note }}</div> @endif
                </td>
                <td class="text-center font-bold" style="color: {{ $v->points >= 0 ? '#059669' : '#e11d48' }};">
                    {{ $v->points >= 0 ? '+' : '' }}{{ $v->points }}
                </td>
                <td class="text-right font-bold text-slate-800">
                    {{ $poin['sekarang'] ?? 100 }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="kosong">Catatan disiplin bersih. Siswa mematuhi seluruh tata tertib sekolah dengan baik.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- PEMBATAS HALAMAN 1 KE HALAMAN 2 --}}
<div class="page-break"></div>

{{-- ══════════════════════════════════════════════════════════════════════════════════
     HALAMAN 2: NILAI RAPOR, KARAKTER P5, SUARA SISWA & PENGESAHAN LAPORAN
     ══════════════════════════════════════════════════════════════════════════════════ --}}

<div class="header-box">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top; width: 65%;">
                <div style="font-size: 9pt; font-weight: bold; color: #064e3b; text-transform: uppercase;">
                    {{ $guru->school_name ?? 'SMK PASUNDAN 2 BANDUNG' }} &middot; PORTOFOLIO RAPOR
                </div>
                <div style="font-size: 7.2pt; color: #475569; margin-top: 1pt;">
                    Nama Siswa: <strong>{{ $siswa->name }}</strong> &middot; NIS: <strong>{{ $siswa->nis ?: '—' }}</strong> &middot; Kelas: <strong>{{ $classroom->name }}</strong>
                </div>
            </td>
            <td style="vertical-align: top; text-align: right; width: 35%;">
                <div style="font-size: 8pt; font-weight: bold; color: #047857;">Halaman 2 / 2</div>
                <div style="font-size: 7pt; color: #64748b;">Semester {{ $semester }} &middot; TA {{ $classroom->academic_year ?? date('Y').'/'.(date('Y')+1) }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- 1. TABEL NILAI RAPOR & ASESMEN AKADEMIK LENGKAP --}}
<div class="section-header">IV. Rekapitulasi Capaian Nilai Akademik &amp; Asesmen (Semester {{ $semester }})</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 35%;">Mata Pelajaran</th>
            <th style="width: 15%;" class="text-center">Nilai Formatif</th>
            <th style="width: 15%;" class="text-center">Nilai PTS</th>
            <th style="width: 15%;" class="text-center">Nilai PAS</th>
            <th style="width: 20%;" class="text-right">Status Kelulusan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($nilai['rapor'] as $b)
            @php
                $skorAkhir = $b['pas'] ?? $b['pts'];
                $lulus = $skorAkhir !== null && $skorAkhir >= 75;
            @endphp
            <tr>
                <td class="font-bold">{{ $b['mapel'] }}</td>
                <td class="text-center">{{ $b['harian'] ?? '—' }}</td>
                <td class="text-center font-bold" style="color: {{ $b['pts'] !== null && $b['pts'] < 75 ? '#e11d48' : '#0f172a' }};">
                    {{ $b['pts'] ?? '—' }}
                </td>
                <td class="text-center font-bold" style="color: {{ $b['pas'] !== null && $b['pas'] < 75 ? '#e11d48' : '#0f172a' }};">
                    {{ $b['pas'] ?? '—' }}
                </td>
                <td class="text-right">
                    @if($skorAkhir !== null)
                        <span class="badge-pill {{ $lulus ? 'badge-emerald' : 'badge-rose' }}">
                            {{ $lulus ? 'TUNTAS (KKM 75)' : 'BELUM TUNTAS' }}
                        </span>
                    @else
                        <span style="font-size: 6.5pt; color: #94a3b8;">Belum Diisi</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="kosong">Belum ada data nilai asesmen pada semester ini.</td>
            </tr>
        @endforelse

        @if ($nilai['rata_rapor'] !== null)
            <tr style="background-color: #f0fdf4; font-weight: bold;">
                <td colspan="2" class="font-bold text-emerald-950">RATA-RATA NILAI KESELURUHAN</td>
                <td colspan="3" class="text-right font-bold text-emerald-800" style="font-size: 8.5pt;">
                    {{ $nilai['rata_rapor'] }} / 100
                </td>
            </tr>
        @endif
    </tbody>
</table>

{{-- 2. PORTOFOLIO KARAKTER P5 KURIKULUM MERDEKA --}}
<div class="section-header">V. Portofolio Karakter P5 (Profil Pelajar Pancasila)</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 45%;">6 Dimensi Karakter Pancasila</th>
            <th style="width: 25%;" class="text-center">Indikator Sikap</th>
            <th style="width: 30%;" class="text-right">Predikat Perkembangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($karakter['dimensi'] as $d)
            <tr>
                <td class="font-bold text-slate-800">{{ $d['dimensi'] }}</td>
                <td class="text-center font-bold">
                    <span style="color: #059669;">+{{ $d['positif'] }} Positif</span>
                    @if($d['negatif'] > 0)
                        &nbsp;&middot;&nbsp;<span style="color: #e11d48;">-{{ $d['negatif'] }} Pembinaan</span>
                    @endif
                </td>
                <td class="text-right">
                    <span class="badge-pill {{ $d['negatif'] == 0 ? 'badge-emerald' : 'badge-amber' }}">
                        {{ $d['negatif'] == 0 ? 'Berkembang Sangat Baik' : 'Sedang Berkembang' }}
                    </span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="kosong">Belum ada catatan observasi karakter khusus pada periode ini.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- 3. SUARA SISWA & REFLEKSI MANDIRI --}}
<div class="section-header">VI. Suara Siswa &amp; Refleksi Belajar Mandiri</div>
@forelse ($refleksi->take(2) as $r)
    <div class="quote-box quote-self">
        <div style="font-weight: 800; color: #1e40af; text-transform: uppercase; font-size: 6.5pt; margin-bottom: 2pt;">
            🗣️ EVALUASI DIRI SISWA (Tanggal: {{ \Illuminate\Support\Carbon::parse($r->reflection_date)->translatedFormat('d F Y') }}):
        </div>
        <div><strong>Hal yang sudah baik:</strong> {{ $r->what_went_well ?: 'Rajin belajar dan hadir tepat waktu' }}</div>
        <div><strong>Perlu ditingkatkan:</strong> {{ $r->what_to_improve ?: 'Memperdalam pemahaman materi sulit' }}</div>
        <div><strong>Rencana aksi:</strong> {{ $r->action_plan ?: 'Belajar kelompok dan aktif bertanya' }}</div>
    </div>

    @if ($r->kesan_teman || $r->pesan_ortu)
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 3pt;">
            <tr>
                @if ($r->kesan_teman)
                    <td style="width: 50%; padding-right: 3pt; vertical-align: top;">
                        <div class="quote-box quote-peer">
                            <div style="font-weight: 800; color: #065f46; font-size: 6.2pt;">👥 KATA TEMAN KELAS:</div>
                            <div style="font-style: italic; margin-top: 1.5pt;">&ldquo;{{ $r->kesan_teman }}&rdquo;</div>
                        </div>
                    </td>
                @endif
                @if ($r->pesan_ortu)
                    <td style="width: 50%; padding-left: 3pt; vertical-align: top;">
                        <div class="quote-box quote-parent">
                            <div style="font-weight: 800; color: #92400e; font-size: 6.2pt;">💌 PESAN UNTUK ORANG TUA:</div>
                            <div style="font-style: italic; margin-top: 1.5pt;">&ldquo;{{ $r->pesan_ortu }}&rdquo;</div>
                        </div>
                    </td>
                @endif
            </tr>
        </table>
    @endif

    @if ($r->teacher_feedback)
        <div class="quote-box quote-teacher">
            <div style="font-weight: 800; color: #7e22ce; font-size: 6.2pt;">🎓 TANGGAPAN &amp; REKOMENDASI WALI KELAS:</div>
            <div style="font-style: italic; margin-top: 1.5pt;">&ldquo;{{ $r->teacher_feedback }}&rdquo;</div>
        </div>
    @endif
@empty
    <div style="font-size: 7.2pt; color: #94a3b8; font-style: italic; padding: 4pt 0;">
        Siswa belum mengisi lembar refleksi mandiri pada periode ini.
    </div>
@endforelse

{{-- 4. LEMBAR PENGESAHAN TANDA TANGAN --}}
<div style="page-break-inside: avoid; margin-top: 10pt;">
    <table class="sig-table">
        <tr>
            <td class="sig-td">
                <div>Mengetahui,</div>
                <div style="font-weight: 800; color: #0f172a; margin-top: 1pt;">Orang Tua / Wali Siswa</div>
                <div class="sig-space"></div>
                <div class="sig-underline">...................................................</div>
                <div style="font-size: 7pt; color: #64748b; margin-top: 2pt;">Tanda Tangan &amp; Nama Terang</div>
            </td>
            <td class="sig-td">
                <div>{{ $guru->school_city ? $guru->school_city.', ' : 'Bandung, ' }}{{ now()->translatedFormat('d F Y') }}</div>
                <div style="font-weight: 800; color: #0f172a; margin-top: 1pt;">Wali Kelas {{ $classroom->name }}</div>
                <div class="sig-space"></div>
                <div class="sig-underline">{{ $guru->name }}</div>
                <div style="font-size: 7pt; color: #64748b; margin-top: 2pt;">NIP: {{ $guru->nip ?: '............................' }}</div>
            </td>
        </tr>
    </table>
</div>
