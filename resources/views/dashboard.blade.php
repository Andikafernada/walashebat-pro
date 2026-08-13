@extends('layouts.app')

@section('title', 'Beranda Wali Kelas Hebat')

@section('content')
<div class="space-y-5 pb-12">

    {{--
        Dulu di sini berdiri spanduk gradien setinggi ~180px: dua lingkaran
        buram, satu titik berkedip, lencana "Sistem Realtime Monitoring", nama
        guru berwarna pelangi, dan satu kalimat yang mendaftar ulang isi
        aplikasi. Tak satu pun menjawab pertanyaan yang dibawa guru saat
        membuka aplikasi — tetapi semuanya berteriak lebih keras daripada papan
        tugas di bawahnya, yang justru berisi jawabannya.

        Tombol "Integrasi WA" dan "Buat Kelas Baru" sudah ada di menu akun. Yang
        kedua tetap disisakan di sini hanya untuk guru yang belum punya kelas
        sama sekali: baginya halaman ini kosong dan ia perlu satu jalan keluar
        yang jelas.
    --}}
    <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-4">
        <div>
            <p class="eyebrow">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Halo, {{ auth()->user()->name }}</h1>
        </div>

        @if (! ($stats['classes'] ?? 0))
            <a href="{{ route('classes.create') }}" class="btn-primary">Buat kelas pertama</a>
        @endif
    </div>

    {{--
        PAPAN TUGAS HARI INI.

        Ditaruh paling atas, sebelum angka mana pun. Angka menjawab "bagaimana
        keadaannya"; pertanyaan yang sebenarnya dibawa wali kelas saat membuka
        aplikasi jauh lebih sempit — "saya harus apa sekarang?" — dan sebelumnya
        ia harus membaca enam kartu, satu grafik, lalu menyimpulkan sendiri.
        Guru yang tidak terbiasa berhenti di langkah menyimpulkan.

        Saat tidak ada tugas, papannya TIDAK hilang. Ketiadaan tugas adalah
        kabar baik yang ingin dilihat, dan papan yang lenyap tanpa jejak
        membuat guru bertanya-tanya apakah ia melewatkan sesuatu.
    --}}
    <section aria-labelledby="judul-tugas" class="blok">
        <div class="blok__kepala">
            <h2 id="judul-tugas" class="blok__judul">Yang perlu Anda kerjakan hari ini</h2>
            {{-- array, bukan Collection: DashboardController::tugasHariIni()
                 mengembalikan array biasa, jadi ->count() di sini akan 500. --}}
            <span class="kode">{{ count($tugasHariIni) }}</span>
        </div>

        <div class="blok__daftar">
            @forelse ($tugasHariIni as $t)
                @php
                    // Kelas ditulis utuh, bukan dirakit dari potongan: Tailwind
                    // memindai berkas ini sebagai teks dan tidak menjalankan PHP-nya.
                    $gaya = [
                        'bahaya' => ['border-l-rose-600 bg-rose-50/50', 'kode--alfa', '!'],
                        'penting' => ['border-l-amber-600 bg-amber-50/50', 'kode--izin', '•'],
                        'tenang' => ['border-l-slate-300', 'kode', '·'],
                    ][$t['nada']];
                @endphp
                <div class="flex flex-col gap-3 border-l-[3px] {{ $gaya[0] }} p-3.5 sm:flex-row sm:items-center">
                    <span class="kode {{ $gaya[1] }} shrink-0" aria-hidden="true">{{ $gaya[2] }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-900">{{ $t['judul'] }}</p>
                        @if ($t['rinci'])
                            <p class="mt-0.5 text-xs text-slate-500">{{ $t['rinci'] }}</p>
                        @endif
                    </div>
                    <a href="{{ $t['tautan'] }}" class="btn-secondary btn-secondary--sm shrink-0 self-start sm:self-auto">{{ $t['aksi'] }}</a>
                </div>
            @empty
                <div class="flex items-center gap-3 p-4">
                    <span class="kode kode--hadir shrink-0" aria-hidden="true">✓</span>
                    <div>
                        <p class="text-sm font-medium text-slate-900">Tidak ada yang tertunda</p>
                        <p class="mt-0.5 text-xs text-slate-500">Absensi hari ini sudah beres dan tidak ada siswa yang menunggu dibina.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Saringan jenis kelas. Bentuknya sama persis dengan saringan di Daftar
         Kelas: guru yang sudah mengenalinya di sana tidak perlu mempelajarinya
         lagi di sini. Baru muncul bila guru memang memegang kedua-duanya. --}}
    @if ($jumlahPerwalian > 0 && $jumlahAjar > 0)
        @php
            $saringan = [
                [null, 'Semua', $jumlahPerwalian + $jumlahAjar],
                ['perwalian', 'Perwalian', $jumlahPerwalian],
                ['ajar', 'Guru Mapel', $jumlahAjar],
            ];
        @endphp
        <div class="inline-flex overflow-hidden rounded border border-slate-200 bg-white">
            @foreach ($saringan as [$nilai, $label, $jumlah])
                @php $ini = $jenisDipilih === $nilai; @endphp
                <a href="{{ route('dashboard', $nilai ? ['jenis' => $nilai] : []) }}"
                   @if($ini) aria-current="page" @endif
                   class="flex items-center gap-1.5 border-r border-slate-200 px-3 py-1.5 text-xs font-medium transition-colors last:border-r-0 {{ $ini ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                    <span class="font-mono text-[10px] {{ $ini ? 'text-slate-300' : 'text-slate-400' }}">{{ $jumlah }}</span>
                </a>
            @endforeach
        </div>
    @endif

    {{--
        RINGKASAN ANGKA.

        Dulu enam kartu terpisah, masing-masing dengan bingkai, bayangan, ubin
        ikon, dan warna aksen sendiri — indigo, emerald, teal, ungu, kuning,
        merah — berjajar berebut perhatian. Enam kotak yang sama menonjolnya
        berarti tidak ada satu pun yang menonjol.

        Kini satu deret, dipisah garis tegak. Yang berhak berwarna cuma Alfa:
        itulah satu-satunya angka di baris ini yang menuntut tindakan hari ini.

        Kartu "EWS Riskan" dibuang seluruhnya — jumlah yang sama sudah disebut
        papan tugas di atas ("N siswa perlu dibina") dan panel "Siswa Perlu
        Perhatian" di bawah, lengkap dengan namanya.
    --}}
    @php($persenHariIni = $stats['persen'] ?? null)
    <dl class="deret-angka">
        <div>
            <dt class="stat-label">Kelas Binaan</dt>
            <dd class="stat-value">{{ $stats['classes'] ?? 0 }}</dd>
        </div>

        <div>
            <dt class="stat-label">Total Siswa</dt>
            <dd class="stat-value">{{ $stats['students'] ?? 0 }}</dd>
            <p class="stat-sub angka">{{ $stats['siswa_laki'] ?? 0 }} L &middot; {{ $stats['siswa_perempuan'] ?? 0 }} P</p>
        </div>

        <div>
            <dt class="stat-label">Presensi Hari Ini</dt>
            {{-- Tanpa data, tampilkan strip. Angka apa pun di sini — 100% maupun
                 0% — adalah klaim tentang kehadiran yang belum pernah didata. --}}
            <dd class="stat-value">{{ $persenHariIni === null ? '—' : $persenHariIni.'%' }}</dd>
            @if ($persenHariIni === null)
                <p class="stat-sub">Belum ada absensi</p>
            @else
                {{-- Persentase saja ambigu: cacah yang masuk harus ikut terbaca.

                     Ditulis utuh, tidak disingkat H/S/I/A seperti di tabel
                     absensi. Kode satu huruf terbaca karena ada kepala kolom
                     tepat di atasnya; baris ini berdiri sendiri tanpa apa pun
                     yang menjelaskan hurufnya. --}}
                <p class="stat-sub angka">
                    Hadir {{ $stats['masuk'] ?? 0 }} &middot; Sakit {{ $stats['sakit'] ?? 0 }} &middot; Izin {{ $stats['izin'] ?? 0 }} &middot;
                    <strong class="{{ ($stats['alfa'] ?? 0) > 0 ? 'font-semibold text-rose-700' : '' }}">Alfa {{ $stats['alfa'] ?? 0 }}</strong>
                </p>
            @endif
        </div>

        {{--
            Dua angka berikut khas perwalian dan tidak dirender di mode Guru
            Mapel. Biodata orang tua dan refleksi P5 adalah pekerjaan wali
            kelas; siswa kelas ajar bahkan tidak punya kolomnya terisi karena
            template impornya sengaja hanya NIS dan nama. Penyebutnya pun
            kelas perwalian saja — lihat DashboardController::angkaHariIni().
        --}}
        @if ($adaPerwalian)
            <div>
                <dt class="stat-label">Biodata Terisi</dt>
                <dd class="stat-value">{{ $stats['biodata_percent'] ?? 0 }}%</dd>
                {{-- Penyebutnya disebut terang-terangan. Persentase tanpa penyebut
                     tidak bisa diperiksa, dan angka inilah yang dulu salah. --}}
                <p class="stat-sub">dari {{ $stats['siswa_perwalian'] }} siswa perwalian</p>
            </div>

            <div>
                <dt class="stat-label">Portofolio P5</dt>
                <dd class="stat-value">{{ $stats['character_percent'] ?? 0 }}%</dd>
                <p class="stat-sub">dari {{ $stats['siswa_perwalian'] }} siswa perwalian</p>
            </div>
        @endif
    </dl>

    <section class="blok">
        <div class="blok__kepala">
            <h2 class="blok__judul">Tren kehadiran</h2>
            <span class="eyebrow">7 hari terakhir</span>
        </div>
        <div class="blok__isi">
            <div class="relative h-56">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
    </section>

    {{-- Kedua panel ini membaca buku pembinaan wali kelas (EWS dan pelanggaran),
         jadi ikut disembunyikan di mode Guru Mapel bersama dua angka di atas.
         Menampilkannya sebagai panel kosong justru mengesankan datanya belum
         diisi, padahal memang bukan urusannya. --}}
    @if ($adaPerwalian)
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

        {{-- id dipakai papan tugas di atas untuk melompat ke sini. --}}
        <section id="perlu-perhatian" class="blok scroll-mt-24">
            <div class="blok__kepala">
                <h2 class="blok__judul">Siswa perlu perhatian</h2>
                <span class="kode {{ $perluPerhatian->isEmpty() ? '' : 'kode--alfa' }}">{{ $perluPerhatian->count() }}</span>
            </div>

            @if($perluPerhatian->isEmpty())
                <p class="p-4 text-sm text-slate-500">
                    Tidak ada siswa dengan alfa 3&times; atau lebih, maupun poin disiplin di bawah ambang.
                </p>
            @else
                <div class="blok__daftar">
                    @foreach($perluPerhatian->take(5) as $item)
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $item['siswa']->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $item['siswa']->classroom->name ?? '' }}</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-1">
                                @foreach($item['alasan'] as $alasan)
                                    <span class="badge badge--rose">{{ $alasan }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="blok">
            <div class="blok__kepala">
                <h2 class="blok__judul">Catatan disiplin &amp; apresiasi</h2>
                <a href="{{ route('violation-types.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Kelola jenis</a>
            </div>

            @if($pelanggaranTerbaru->isEmpty())
                <p class="p-4 text-sm text-slate-500">
                    Belum ada catatan. Poin pelanggaran dan apresiasi siswa akan muncul di sini.
                </p>
            @else
                <div class="blok__daftar">
                    @foreach($pelanggaranTerbaru->take(5) as $v)
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="kode {{ $v->points >= 0 ? 'kode--hadir' : 'kode--alfa' }} shrink-0">
                                    {{ $v->points >= 0 ? '+' : '' }}{{ $v->points }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $v->student->name ?? 'Siswa' }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $v->type->name ?? $v->note ?: 'Catatan disiplin' }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 font-mono text-[10px] text-slate-400">{{ $v->occurred_on?->format('d/m/y') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

    </div>
    @endif

</div>

{{-- Chart.js dimuat di sini, bukan di layout: hanya halaman ini dan
     analitik yang punya grafik. 205 KB tidak boleh ikut terunduh di
     halaman absensi yang dibuka tiap pagi.

     Dilayani sendiri dari /vendor/, bukan cdn.jsdelivr.net. Berkasnya
     byte-identik dengan yang dulu diambil dari CDN (sha384 dicocokkan
     dengan integrity lama saat disalin). Alasan pindahnya sama dengan
     rupa huruf: host asing = DNS + TLS baru sebelum grafik bisa muncul.

     Versinya di query string, bukan di nama berkas — nginx menyimpan
     seluruh .js 30 hari immutable, jadi naik versi TANPA mengubah ?v=
     berarti guru yang sudah pernah membuka halaman ini tetap memakai
     yang lama sampai cache-nya kedaluwarsa. --}}
<script src="/vendor/chart.umd.min.js?v=4.4.0"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('attendanceChart');
    if (!ctx) return;

    const rawData = @json($chartTrend ?? []);

    /*
     * Grafiknya ikut ditenangkan: garis tipis tinta, lengkung nyaris lurus,
     * titik kecil. Lengkung 0.4 dengan titik radius 5 membuat naik-turun
     * kehadiran tampak lebih dramatis daripada datanya — dan angka inilah yang
     * dibaca guru untuk memutuskan siapa yang perlu ditelepon.
     */
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: rawData.map(d => d.tanggal),
            datasets: [{
                label: 'Kehadiran (%)',
                data: rawData.map(d => d.persen),
                borderColor: '#23486b',
                backgroundColor: 'rgba(35, 72, 107, 0.06)',
                borderWidth: 1.5,
                fill: true,
                tension: 0.15,
                pointBackgroundColor: '#23486b',
                pointRadius: 2,
                pointHoverRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            font: { family: 'Instrument Sans' },
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    border: { display: false },
                    grid: { color: '#e3ded4' },
                    ticks: { callback: v => v + '%', color: '#7c7466', font: { size: 10 } }
                },
                x: {
                    border: { color: '#e3ded4' },
                    grid: { display: false },
                    ticks: { color: '#7c7466', font: { size: 10 } }
                }
            },
            plugins: { legend: { display: false } }
        }
    });
});
</script>
@endsection
