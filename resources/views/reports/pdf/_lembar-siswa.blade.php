{{--
    Satu lembar profil siswa.

    Dipakai dua kali: berkas satu siswa (reports/pdf/siswa) dan lampiran buku
    administrasi (reports/pdf/administrasi, bagian "profil"). Yang berdiri
    sendiri memakai kop sekolah karena lembarnya dirobek lalu diserahkan
    terpisah ke BK atau orang tua, dan lembar tanpa kop tidak bisa dipakai
    sebagai dokumen; di dalam jilidan kopnya dimatikan lewat $kopLembar.

    Semua "grafik" di sini adalah <div> berlebar persen: lihat _gaya-lembar.
--}}

{{-- Kop hanya untuk lembar yang berdiri sendiri. Di dalam buku administrasi
     yang dijilid, sampulnya sudah membawa identitas sekolah — mengulangnya di
     setiap lembar cuma menambah kebisingan pada 39 halaman berturut-turut. --}}
@if ($kopLembar ?? true)
    <div class="kop">
        <table style="width: 100%;">
            <tr>
                <td style="vertical-align: top;">
                    <div class="school-name">{{ $guru->school_name ?? 'WALI KELAS HEBAT' }}</div>
                    @if (! empty($guru->school_address))
                        <div class="school-sub">{{ $guru->school_address }}</div>
                    @endif
                </td>
                <td style="vertical-align: top;" class="r">
                    <div class="doc-title">Laporan Perkembangan Siswa</div>
                    <div class="doc-subtitle">{{ $periode['label'] }}</div>
                </td>
            </tr>
        </table>
    </div>
@endif

<div class="banner">
    <table style="width: 100%;">
        <tr>
            <td style="width: 74pt; vertical-align: top;">
                @if ($fotoSiswa = $siswa->photoDataUri())
                    <img class="foto" src="{{ $fotoSiswa }}" alt="">
                @else
                    <div class="foto-kosong">
                        <div class="foto-inisial">{{ $siswa->initials() }}</div>
                        <div class="foto-catatan">Foto belum ada</div>
                    </div>
                @endif
            </td>
            <td style="vertical-align: top;">
                <div class="student-name">{{ $siswa->name }}</div>
                <div class="student-meta">
                    NIS: <strong>{{ $siswa->nis ?: '-' }}</strong> |
                    NISN: <strong>{{ $siswa->nisn ?: '-' }}</strong> |
                    Kelas: <strong>{{ $classroom->name }}</strong>
                    @unless ($siswa->is_active)
                        | <span class="badge badge-danger">NONAKTIF</span>
                    @endunless
                </div>
                <div class="student-meta">
                    {{ $siswa->tempat_lahir ?: '-' }},
                    {{ $siswa->tanggal_lahir ? \Illuminate\Support\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : '-' }}
                    &middot; {{ $siswa->gender === 'L' ? 'Laki-laki' : ($siswa->gender === 'P' ? 'Perempuan' : '-') }}
                    {{-- roleLabel(), BUKAN ->position: tabel organization_structures
                         menyimpan kolom `role`, dan `position` tidak pernah ada.
                         Eloquent mengembalikan NULL diam-diam untuk atribut yang
                         tidak ada, jadi badge jabatannya selalu kosong tanpa galat. --}}
                    @if ($peran?->isNotEmpty())
                        &middot; <span class="badge badge-info">{{ $peran->map(fn ($p) => $p->roleLabel())->join(', ') }}</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

<table class="stats-grid">
    <tr>
        <td style="width: 25%;">
            <div class="stat-card">
                <div class="stat-val" style="color: #059669;">{{ $kehadiran['persen'] === null ? '-' : $kehadiran['persen'].'%' }}</div>
                <div class="stat-label">Tingkat Kehadiran</div>
            </div>
        </td>
        <td style="width: 25%;">
            <div class="stat-card">
                <div class="stat-val" style="color: #e11d48;">{{ $kehadiran['jumlah']['alfa'] }}</div>
                <div class="stat-label">Jumlah Alfa</div>
            </div>
        </td>
        <td style="width: 25%;">
            <div class="stat-card">
                <div class="stat-val" style="color: #4f46e5;">{{ $poin['sekarang'] }}</div>
                <div class="stat-label">Poin Disiplin</div>
            </div>
        </td>
        <td style="width: 25%;">
            <div class="stat-card">
                <div class="stat-val" style="color: #d97706;">{{ $poin['kejadian'] }}</div>
                <div class="stat-label">Catatan Pelanggaran</div>
            </div>
        </td>
    </tr>
</table>

<div class="section-title">I. Tren Kehadiran (6 Bulan Terakhir)</div>
{{--
    Dulu tabel tiga baris berisi enam angka persen. Angka menuntut dibandingkan
    satu per satu; batang menunjukkan arahnya dalam sekali lihat — dan yang
    dicari orang tua di halaman ini justru arahnya: membaik atau memburuk.
--}}
<table class="bar-tbl">
    @foreach ($tren as $t)
        @php
            $persen = $t['persen'];
            $warna = $persen === null ? '#cbd5e1' : ($persen >= 90 ? '#059669' : ($persen >= 75 ? '#d97706' : '#e11d48'));
        @endphp
        <tr>
            <td class="bar-label" style="width: 16%;">{{ $t['label'] }}</td>
            <td style="width: 54%;">
                <div class="rel"><div class="rel-isi" style="width: {{ $persen ?? 0 }}%; background: {{ $warna }};"></div></div>
            </td>
            <td class="bar-angka" style="width: 12%; color: {{ $warna }};">{{ $persen === null ? '—' : $persen.'%' }}</td>
            <td class="bar-label" style="width: 18%;">
                {{-- Angka mentahnya tetap dicetak: batang bagus untuk melihat
                     bentuk, tidak bisa dipakai membuktikan apa pun. --}}
                Telat {{ $t['terlambat'] }} &middot; Alfa {{ $t['alfa'] }}
            </td>
        </tr>
    @endforeach
</table>

<p style="font-size: 7.5pt; color: #475569; margin-bottom: 8pt;">
    <strong>Rincian periode ini:</strong>
    Hadir <strong>{{ $kehadiran['jumlah']['hadir'] }}</strong> &middot;
    Terlambat <strong>{{ $kehadiran['jumlah']['terlambat'] }}</strong> &middot;
    Sakit <strong>{{ $kehadiran['jumlah']['sakit'] }}</strong> &middot;
    Izin <strong>{{ $kehadiran['jumlah']['izin'] }}</strong> &middot;
    Alfa <strong>{{ $kehadiran['jumlah']['alfa'] }}</strong>
    (dari {{ $kehadiran['total'] }} kali tercatat).
</p>

<div class="section-title">II. Biodata &amp; Data Orang Tua</div>
<div class="biodata-box">
    <table class="biodata-tbl">
        <tr>
            <td style="width: 20%;" class="biodata-label">Agama</td>
            <td style="width: 30%;">{{ $siswa->agama ?: '-' }}</td>
            <td style="width: 20%;" class="biodata-label">No. HP Siswa</td>
            <td style="width: 30%;">{{ $siswa->phone ?: '-' }}</td>
        </tr>
        <tr>
            <td class="biodata-label">Nama Ayah</td>
            <td>{{ $siswa->nama_ayah ?: '-' }}</td>
            <td class="biodata-label">Pekerjaan Ayah</td>
            <td>{{ $siswa->pekerjaan_ayah ?: '-' }}</td>
        </tr>
        <tr>
            <td class="biodata-label">Nama Ibu</td>
            <td>{{ $siswa->nama_ibu ?: '-' }}</td>
            <td class="biodata-label">Pekerjaan Ibu</td>
            <td>{{ $siswa->pekerjaan_ibu ?: '-' }}</td>
        </tr>
        <tr>
            <td class="biodata-label">No. HP Ortu/Wali</td>
            <td>{{ $siswa->parent_phone ?: '-' }}</td>
            <td class="biodata-label">Jarak / Transportasi</td>
            <td>{{ $siswa->jarak_rumah_km ? $siswa->jarak_rumah_km.' km' : '-' }} / {{ $siswa->moda_transportasi ?: '-' }}</td>
        </tr>
        <tr>
            <td class="biodata-label">Alamat Lengkap</td>
            <td colspan="3">{{ $siswa->address ?: '-' }} (RT/RW: {{ $siswa->rt_rw ?: '-' }}, Kel. {{ $siswa->kelurahan ?: '-' }}, Kec. {{ $siswa->kecamatan ?: '-' }})</td>
        </tr>
    </table>
</div>

<div class="section-title">III. Kedisiplinan &amp; Apresiasi</div>
{{-- Poin disiplin adalah angka yang berjalan turun dari 100. Ditulis sebagai
     angka saja, "62" tidak memberi tahu seberapa dekat ia ke ambang pembinaan;
     sebagai batang, jaraknya terlihat. --}}
@php
    $warnaPoin = $poin['sekarang'] >= 75 ? '#059669' : ($poin['sekarang'] >= 50 ? '#d97706' : '#e11d48');
@endphp
<table class="bar-tbl">
    <tr>
        <td class="bar-label" style="width: 16%;">Poin sekarang</td>
        <td style="width: 54%;">
            <div class="rel"><div class="rel-isi" style="width: {{ $poin['persen'] }}%; background: {{ $warnaPoin }};"></div></div>
        </td>
        <td class="bar-angka" style="width: 12%; color: {{ $warnaPoin }};">{{ $poin['sekarang'] }}</td>
        <td class="bar-label" style="width: 18%;">dari {{ $poin['awal'] }} &middot; ambang 50</td>
    </tr>
</table>
<table class="tbl">
    <thead>
        <tr>
            <th style="width: 18%;" class="c">Tanggal</th>
            <th>Jenis Catatan / Pelanggaran / Apresiasi</th>
            <th style="width: 15%;" class="c">Poin</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($pelanggaran as $v)
        <tr>
            <td class="c">{{ $v->occurred_on->format('d/m/Y') }}</td>
            <td>
                <strong>{{ $v->type->name ?? 'Catatan Disiplin' }}</strong>
                @if ($v->note) <br><span style="font-size: 7.5pt; color: #64748b;">{{ $v->note }}</span> @endif
            </td>
            <td class="c tebal" style="color: {{ $v->points >= 0 ? '#166534' : '#991b1b' }};">
                {{ $v->points >= 0 ? '+' : '' }}{{ $v->points }}
            </td>
        </tr>
    @empty
        <tr><td colspan="3" class="kosong">Tidak ada catatan pelanggaran pada periode ini. Catatan bersih.</td></tr>
    @endforelse
    </tbody>
</table>

{{-- Disaring per SEMESTER, bukan per rentang tanggal: nilai akhir semester 1
     lazim baru dimasukkan pada Januari, yang menurut kalender sudah semester 2.
     Menyaringnya dengan tanggal membuat nilai itu hilang dari berkas yang
     justru diserahkan ke orang tua. --}}
<div class="section-title">IV. Nilai Rapor — Semester {{ $semester }}</div>
<table class="tbl">
    <thead>
        <tr>
            <th style="width: 30%;">Mata Pelajaran</th>
            <th style="width: 10%;" class="c">PTS</th>
            <th style="width: 10%;" class="c">PAS</th>
            <th>Sebaran (garis = KKM 75)</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($nilai['rapor'] as $b)
        @php
            // Batang menggambarkan yang terakhir tersedia: PAS bila sudah ada,
            // kalau belum PTS. Merata-ratakan keduanya menyembunyikan kenaikan
            // maupun penurunan, padahal justru itu yang ditanyakan orang tua.
            $tampil = $b['pas'] ?? $b['pts'];
        @endphp
        <tr>
            <td>{{ $b['mapel'] }}</td>
            {{-- "-" berarti belum dinilai, bukan nol. --}}
            <td class="c tebal" style="color: {{ $b['pts'] !== null && $b['pts'] < 75 ? '#991b1b' : '#0f172a' }};">{{ $b['pts'] ?? '-' }}</td>
            <td class="c tebal" style="color: {{ $b['pas'] !== null && $b['pas'] < 75 ? '#991b1b' : '#0f172a' }};">{{ $b['pas'] ?? '-' }}</td>
            <td>
                @if ($tampil === null)
                    <span style="font-size: 7pt; color: #94a3b8; font-style: italic;">belum dinilai</span>
                @else
                    <div class="rel-tipis">
                        <div class="rel-tipis-isi" style="width: {{ max(0, min(100, (int) $tampil)) }}%; background: {{ $tampil < 75 ? '#e11d48' : '#059669' }};"></div>
                    </div>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="4" class="kosong">Belum ada nilai PTS/PAS pada semester ini.</td></tr>
    @endforelse
    @if ($nilai['rata_rapor'] !== null)
        <tr>
            <td class="tebal">Rata-rata seluruh nilai rapor</td>
            <td colspan="3" class="tebal">{{ $nilai['rata_rapor'] }}</td>
        </tr>
    @endif
    </tbody>
</table>

<div class="section-title">V. Perkembangan Karakter (P5) — Pengamatan Wali Kelas</div>
{{--
    Yang dicari di sini bukan satu angka melainkan bentuknya: anak dengan
    sepuluh catatan positif pada Gotong Royong dan tiga catatan negatif pada
    Kemandirian menuntut pembinaan yang sama sekali berbeda dari anak yang
    sebaliknya — padahal jumlah bersihnya bisa sama persis. Dua batang
    berdampingan menunjukkan bentuk itu; dua kolom angka tidak.
--}}
@php
    $puncak = max(1, collect($karakter['dimensi'])->flatMap(fn ($d) => [$d['positif'], $d['negatif']])->max() ?: 1);
@endphp
@forelse ($karakter['dimensi'] as $d)
    <table class="bar-tbl">
        <tr>
            <td class="bar-label" style="width: 24%;">{{ $d['dimensi'] }}</td>
            <td style="width: 46%;">
                <div class="rel-tipis"><div class="rel-tipis-isi" style="width: {{ (int) round($d['positif'] / $puncak * 100) }}%; background: #059669;"></div></div>
                <div class="rel-tipis" style="margin-top: 2pt;"><div class="rel-tipis-isi" style="width: {{ (int) round($d['negatif'] / $puncak * 100) }}%; background: #e11d48;"></div></div>
            </td>
            <td class="bar-angka" style="width: 30%;">
                <span style="color: #059669;">+{{ $d['positif'] }}</span>
                <span style="color: #94a3b8;"> / </span>
                <span style="color: #e11d48;">−{{ $d['negatif'] }}</span>
                @if ($d['lainnya'])
                    <span style="color: #64748b; font-weight: normal;"> &middot; {{ $d['lainnya'] }} pengamatan</span>
                @endif
            </td>
        </tr>
    </table>
@empty
    <table class="tbl"><tr><td class="kosong">Belum ada catatan karakter pada periode ini.</td></tr></table>
@endforelse

<div class="section-title">VI. Suara Siswa — Refleksi Mandiri</div>
{{--
    Bagian ini sebelumnya tidak ada di berkas mana pun: refleksi yang ditulis
    siswa sendiri tersimpan rapi di aplikasi lalu berhenti di situ. Padahal
    bagian V di atas adalah penilaian GURU tentang anak, dan jarak antara
    keduanya sering lebih memberi tahu daripada salah satunya saja.
--}}
@forelse ($refleksi->take(2) as $r)
    <p style="font-size: 7pt; color: #64748b; margin-bottom: 3pt;">
        {{ \Illuminate\Support\Carbon::parse($r->reflection_date)->translatedFormat('d F Y') }}
        @if ($r->dimension?->name) &middot; Dimensi {{ $r->dimension->name }} @endif
        @if ($r->self_rating) &middot; Penilaian diri {{ $r->self_rating }}/5 @endif
    </p>

    <div class="kutipan kutipan-diri">
        <div class="kutipan-judul">Kata dirinya sendiri</div>
        <div class="kutipan-isi">
            <strong>Sudah baik:</strong> {{ $r->what_went_well ?: '-' }}<br>
            <strong>Perlu ditingkatkan:</strong> {{ $r->what_to_improve ?: '-' }}<br>
            <strong>Rencana aksi:</strong> {{ $r->action_plan ?: '-' }}
        </div>
    </div>

    @if ($r->kesan_teman)
        <div class="kutipan kutipan-teman">
            <div class="kutipan-judul">Kata temannya</div>
            <div class="kutipan-isi">&ldquo;{{ $r->kesan_teman }}&rdquo;</div>
        </div>
    @endif

    @if ($r->pesan_ortu)
        <div class="kutipan kutipan-ortu">
            <div class="kutipan-judul">Pesan untuk orang tua</div>
            <div class="kutipan-isi">&ldquo;{{ $r->pesan_ortu }}&rdquo;</div>
        </div>
    @endif

    @if ($r->teacher_feedback)
        <div class="kutipan kutipan-diri">
            <div class="kutipan-judul">Tanggapan wali kelas</div>
            <div class="kutipan-isi">&ldquo;{{ $r->teacher_feedback }}&rdquo;</div>
        </div>
    @endif
@empty
    <table class="tbl"><tr><td class="kosong">Siswa belum mengisi refleksi mandiri pada periode ini.</td></tr></table>
@endforelse

<div style="page-break-inside: avoid; margin-top: 15pt;">
    <table class="sig-table">
        <tr>
            <td class="sig-cell">
                <p>Mengetahui,</p>
                <p style="font-weight: bold;">Orang Tua / Wali Siswa</p>
                <div class="sig-space"></div>
                <div class="sig-line">...................................................</div>
                <p style="font-size: 7.5pt; color: #64748b;">Tanda Tangan &amp; Nama Terang</p>
            </td>
            <td class="sig-cell">
                <p>{{ $guru->school_city ? $guru->school_city.', ' : '' }}{{ now()->translatedFormat('d F Y') }}</p>
                <p style="font-weight: bold;">Wali Kelas {{ $classroom->name }}</p>
                <div class="sig-space"></div>
                <div class="sig-line">{{ $guru->name }}</div>
                <p style="font-size: 7.5pt; color: #64748b;">NIP. {{ $guru->nip ?: '............................' }}</p>
            </td>
        </tr>
    </table>
</div>
