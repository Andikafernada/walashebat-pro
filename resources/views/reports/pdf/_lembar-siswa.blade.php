@if ($kopLembar ?? true)
    <div class="header-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: top; width: 65%;">
                    <div class="school-title">{{ $guru->school_name ?? 'SMK PASUNDAN 2 BANDUNG' }}</div>
                    @if (! empty($guru->school_address))
                        <div class="school-address">{{ $guru->school_address }}</div>
                    @else
                        <div class="school-address">Pusat Laporan &amp; Administrasi Digital WaliKelas Pro</div>
                    @endif
                </td>
                <td style="vertical-align: top; text-align: right; width: 35%;">
                    <div class="doc-badge">Portofolio &amp; Rapor Siswa</div>
                    <div class="doc-period">Periode: {{ $periode['label'] }}</div>
                </td>
            </tr>
        </table>
    </div>
@endif

{{-- ══════════ 1. PROFILE & BIODATA CARD ══════════ --}}
<div class="profile-card">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 54pt; vertical-align: middle;">
                @if ($fotoSiswa = $siswa->photoDataUri())
                    <img class="student-avatar" src="{{ $fotoSiswa }}" alt="Foto">
                @else
                    <div class="student-avatar-empty">
                        <div class="avatar-initials">{{ $siswa->initials() }}</div>
                        <div class="avatar-label">FOTO SISWA</div>
                    </div>
                @endif
            </td>
            <td style="vertical-align: middle; padding-left: 8pt;">
                <div class="student-name-main">{{ $siswa->name }}</div>
                <div style="font-size: 6.8pt; color: #334155; margin-top: 2pt;">
                    NIS: <strong>{{ $siswa->nis ?: '—' }}</strong> &middot; 
                    NISN: <strong>{{ $siswa->nisn ?: '—' }}</strong> &middot; 
                    Kelas: <strong>{{ $classroom->name }}</strong> &middot; 
                    JK: <strong>{{ $siswa->gender === 'L' ? 'Laki-laki' : ($siswa->gender === 'P' ? 'Perempuan' : '—') }}</strong>
                </div>
                <div style="font-size: 6.5pt; color: #64748b; margin-top: 1.5pt;">
                    TTL: {{ $siswa->tempat_lahir ?: '—' }}, {{ $siswa->tanggal_lahir ? \Illuminate\Support\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : '—' }} &middot;
                    Ortu: <strong>{{ $siswa->parent_name ?: ($siswa->father_name ?: ($siswa->mother_name ?: '—')) }}</strong>
                    @if($siswa->parent_phone) (WA: {{ $siswa->parent_phone }}) @endif
                </div>
            </td>
            <td style="width: 75pt; vertical-align: middle; text-align: right;">
                <span class="badge-pill badge-emerald">{{ $classroom->name }}</span>
                @if ($peran?->isNotEmpty())
                    <div style="margin-top: 2pt;">
                        <span class="badge-pill badge-blue">{{ $peran->first()->roleLabel() }}</span>
                    </div>
                @endif
                <div style="margin-top: 2pt;">
                    <span class="badge-pill {{ $siswa->is_active ? 'badge-emerald' : 'badge-rose' }}">
                        {{ $siswa->is_active ? 'SISWA AKTIF' : 'NON-AKTIF' }}
                    </span>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ══════════ 2. 4-KPI SUMMARY METRICS ══════════ --}}
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
                <div class="kpi-title">Jumlah Alfa</div>
            </div>
        </td>
        <td style="width: 25%;">
            <div class="kpi-box" style="background-color: #f0f9ff; border-color: #bae6fd;">
                <div class="kpi-val" style="color: #0369a1;">{{ $poin['sekarang'] ?? 100 }}</div>
                <div class="kpi-title">Poin Disiplin (100)</div>
            </div>
        </td>
        <td style="width: 25%;">
            <div class="kpi-box" style="background-color: #fefce8; border-color: #fef08a;">
                <div class="kpi-val" style="color: #a16207;">{{ $nilai['rata_rapor'] !== null ? $nilai['rata_rapor'] : '—' }}</div>
                <div class="kpi-title">Rata Nilai Rapor</div>
            </div>
        </td>
    </tr>
</table>

{{-- ══════════ 3. RINCIAN PRESENSI & KEHADIRAN ══════════ --}}
<div class="section-header">I. Rincian Presensi &amp; Rekapitulasi Kehadiran</div>
<div style="background-color: #f8fafc; border: 0.5pt solid #e2e8f0; border-radius: 4pt; padding: 4pt 6pt; margin-bottom: 6pt;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="font-size: 7pt; font-weight: 700;">
                🟢 Hadir: <strong>{{ $kehadiran['jumlah']['hadir'] }}</strong> &nbsp;|&nbsp;
                🟡 Terlambat: <strong>{{ $kehadiran['jumlah']['terlambat'] }}</strong> &nbsp;|&nbsp;
                🔵 Sakit: <strong>{{ $kehadiran['jumlah']['sakit'] }}</strong> &nbsp;|&nbsp;
                🟣 Izin: <strong>{{ $kehadiran['jumlah']['izin'] }}</strong> &nbsp;|&nbsp;
                🔴 Alfa: <strong style="color: #e11d48;">{{ $kehadiran['jumlah']['alfa'] }}</strong>
            </td>
            <td style="text-align: right; font-size: 6.8pt; color: #64748b;">
                Total Pertemuan: <strong>{{ $kehadiran['total'] }} Sesi</strong>
            </td>
        </tr>
    </table>
</div>

{{-- ══════════ 4. TWO-COLUMN: NILAI AKADEMIK & DISIPLIN/P5 ══════════ --}}
<table class="col-table">
    <tr>
        {{-- KOLOM KIRI: NILAI RAPOR & ASESMEN (50%) --}}
        <td class="col-cell" style="width: 52%;">
            <div class="section-header">II. Capaian Nilai Rapor &amp; Akademik (Sem. {{ $semester }})</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 45%;">Mata Pelajaran</th>
                        <th style="width: 15%;" class="text-center">PTS</th>
                        <th style="width: 15%;" class="text-center">PAS</th>
                        <th style="width: 25%;" class="text-right">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($nilai['rapor'] as $b)
                        @php
                            $skorTampil = $b['pas'] ?? $b['pts'];
                            $lulus = $skorTampil !== null && $skorTampil >= 75;
                        @endphp
                        <tr>
                            <td class="font-bold">{{ $b['mapel'] }}</td>
                            <td class="text-center font-bold" style="color: {{ $b['pts'] !== null && $b['pts'] < 75 ? '#e11d48' : '#0f172a' }};">
                                {{ $b['pts'] ?? '—' }}
                            </td>
                            <td class="text-center font-bold" style="color: {{ $b['pas'] !== null && $b['pas'] < 75 ? '#e11d48' : '#0f172a' }};">
                                {{ $b['pas'] ?? '—' }}
                            </td>
                            <td class="text-right">
                                @if($skorTampil !== null)
                                    <span class="badge-pill {{ $lulus ? 'badge-emerald' : 'badge-rose' }}">
                                        {{ $lulus ? 'TUNTAS' : 'REMIDI' }}
                                    </span>
                                @else
                                    <span style="font-size: 6pt; color: #94a3b8;">Belum ada</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="kosong">Belum ada data nilai asesmen semester ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </td>

        {{-- KOLOM KANAN: DISIPLIN & KARAKTER P5 (48%) --}}
        <td class="col-cell" style="width: 48%;">
            <div class="section-header">III. Kedisiplinan &amp; Karakter P5</div>
            
            {{-- Poin Disiplin Ringkas --}}
            <div style="background-color: #f8fafc; border: 0.5pt solid #e2e8f0; border-radius: 4pt; padding: 3pt 5pt; margin-bottom: 4pt;">
                <table style="width: 100%;">
                    <tr>
                        <td style="font-size: 6.8pt; font-weight: 700;">Status Disiplin:</td>
                        <td style="text-align: right; font-size: 6.8pt;">
                            <span class="badge-pill {{ $poin['sekarang'] >= 85 ? 'badge-emerald' : ($poin['sekarang'] >= 60 ? 'badge-amber' : 'badge-rose') }}">
                                {{ $poin['sekarang'] >= 85 ? 'Sangat Baik (A)' : ($poin['sekarang'] >= 60 ? 'Cukup (B)' : 'Perlu Bimbingan (C)') }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Observasi P5 --}}
            <div style="font-size: 6.8pt; font-weight: 800; color: #475569; margin-bottom: 2pt;">Observasi Profil Pelajar Pancasila:</div>
            <table class="data-table" style="margin-bottom: 4pt;">
                <thead>
                    <tr>
                        <th>Dimensi Karakter</th>
                        <th style="width: 35%;" class="text-center">Indikator Sikap</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($karakter['dimensi'] as $d)
                        <tr>
                            <td>{{ $d['dimensi'] }}</td>
                            <td class="text-center font-bold">
                                <span style="color: #059669;">+{{ $d['positif'] }} Baik</span>
                                @if($d['negatif'] > 0)
                                    &middot; <span style="color: #e11d48;">-{{ $d['negatif'] }} Catatan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="kosong">Karakter kondusif &amp; stabil.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </td>
    </tr>
</table>

{{-- ══════════ 5. SUARA SISWA — REFLEKSI MANDIRI ══════════ --}}
@if($refleksi->isNotEmpty())
    <div class="section-header">IV. Suara Siswa &amp; Refleksi Perkembangan Belajar</div>
    <div style="display: block;">
        @foreach($refleksi->take(1) as $r)
            <div class="quote-box quote-self">
                <span style="font-weight: 800; color: #1e40af; text-transform: uppercase; font-size: 6pt;">Evaluasi Diri Siswa:</span>
                <span style="color: #334155;">
                    "{{ $r->what_went_well ?: 'Belajar dengan tekun dan tertib' }}
                    @if($r->what_to_improve) &middot; Perlu ditingkatkan: {{ $r->what_to_improve }} @endif"
                </span>
            </div>
            @if($r->kesan_teman || $r->pesan_ortu)
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        @if($r->kesan_teman)
                            <td style="width: 50%; padding-right: 2pt;">
                                <div class="quote-box quote-peer">
                                    <span style="font-weight: 800; color: #065f46; font-size: 5.8pt;">KATA TEMAN KELAS:</span>
                                    <div style="font-style: italic;">"{{ $r->kesan_teman }}"</div>
                                </div>
                            </td>
                        @endif
                        @if($r->pesan_ortu)
                            <td style="width: 50%; padding-left: 2pt;">
                                <div class="quote-box quote-parent">
                                    <span style="font-weight: 800; color: #92400e; font-size: 5.8pt;">PESAN KE ORANG TUA:</span>
                                    <div style="font-style: italic;">"{{ $r->pesan_ortu }}"</div>
                                </div>
                            </td>
                        @endif
                    </tr>
                </table>
            @endif
        @endforeach
    </div>
@endif

{{-- ══════════ 6. LEMBAR PENGESAHAN (SIGNATURE) ══════════ --}}
<div style="page-break-inside: avoid;">
    <table class="sig-table">
        <tr>
            <td class="sig-td">
                <div>Mengetahui,</div>
                <div style="font-weight: 800; color: #0f172a; margin-top: 1pt;">Orang Tua / Wali Siswa</div>
                <div class="sig-space"></div>
                <div class="sig-underline">...................................................</div>
                <div style="font-size: 6.5pt; color: #64748b; margin-top: 1pt;">Tanda Tangan &amp; Nama Terang</div>
            </td>
            <td class="sig-td">
                <div>{{ $guru->school_city ? $guru->school_city.', ' : 'Bandung, ' }}{{ now()->translatedFormat('d F Y') }}</div>
                <div style="font-weight: 800; color: #0f172a; margin-top: 1pt;">Wali Kelas {{ $classroom->name }}</div>
                <div class="sig-space"></div>
                <div class="sig-underline">{{ $guru->name }}</div>
                <div style="font-size: 6.5pt; color: #64748b; margin-top: 1pt;">NIP: {{ $guru->nip ?: '—' }}</div>
            </td>
        </tr>
    </table>
</div>
