@extends('layouts.app')

@section('title', 'Detail Absensi - ' . $classroom->name)

@section('content')
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"
        integrity="sha384-lQXOAyZwHXE55JFyrOMB7nY2Wv+m5ZWNtJcHrd1rceRQXAYNLak8ukN5TjBTcIwz"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

@php
    $rupaStatus = [
        'open' => ['Sesi Terbuka', 'kode--hadir'],
        'submitted' => ['Selesai', 'kode--aksen'],
        'expired' => ['Kadaluarsa', 'kode--izin'],
        'cancelled' => ['Dibatalkan', 'kode--alfa'],
    ];
    [$labelStatus, $gayaStatus] = $rupaStatus[$session->status] ?? [$session->status, ''];

    /*
     * Kode margin absensi. Hurufnya persis Attendance::KODE — yang sama dipakai
     * di rekap, ekspor, dan cetakan, sehingga guru hanya perlu menghafalnya
     * sekali.
     */
    $kodeStatus = [
        'hadir' => ['H', 'kode--hadir'],
        'terlambat' => ['T', 'kode--telat'],
        'sakit' => ['S', 'kode--sakit'],
        'izin' => ['I', 'kode--izin'],
        'alfa' => ['A', 'kode--alfa'],
    ];

    $sumLower = collect($summary)->keyBy(fn($v, $k) => strtolower($k));
    $totalSiswa = $summary['total'] ?? ($attendances->count() ?: ($classroom->students()->count()));
    $cntHadir = $sumLower->get('hadir', 0);
    $cntSakit = $sumLower->get('sakit', 0);
    $cntIzin = $sumLower->get('izin', 0);
    $cntAlfa = $sumLower->get('alfa', 0);
    $cntTerlambat = $sumLower->get('terlambat', 0);

    $totalTerisi = $cntHadir + $cntSakit + $cntIzin + $cntAlfa + $cntTerlambat;
    $persenTerisi = $totalSiswa > 0 ? round(($totalTerisi / $totalSiswa) * 100) : 0;
@endphp

<div class="space-y-5 pb-12">

    <div class="page-header">
        <div class="min-w-0">
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.attendance.index', $classroom) }}" class="hover:text-slate-600">{{ $classroom->name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Detail Absensi</span>
            </nav>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">
                    {{ $session->title ?: 'Absensi ' . $session->session_date->isoFormat('D MMMM YYYY') }}
                </h1>
                <span class="kode {{ $gayaStatus }}">{{ $labelStatus }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">
                {{ $session->session_date->isoFormat('dddd, D MMMM YYYY') }}
                &middot; sesi #{{ $session->sequence ?? 1 }}
                &middot; berlaku s/d {{ $session->expires_at ? $session->expires_at->format('H:i') . ' WIB' : '—' }}
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a href="{{ route('classes.attendance.edit', [$classroom, $session]) }}" class="btn-secondary btn-secondary--sm">Koreksi Manual</a>
            <a href="{{ route('classes.exports.attendance.pdf', $classroom) }}" target="_blank" class="btn-secondary btn-secondary--sm">Cetak PDF</a>
        </div>
    </div>

    @include('partials.class-nav', ['classroom' => $classroom])

    @include('partials.flash')

    {{--
        Ringkasan sesi.

        Dulu: satu spanduk bergradien setinggi 150px berisi bilah kemajuan
        bergradien emerald-ke-teal, lalu EMPAT kartu bergradien di bawahnya —
        satu per status, masing-masing dengan ubin ikon berwarna penuh yang
        membesar saat disentuh tetikus. Empat kotak yang sama menonjolnya untuk
        empat angka yang sangat berbeda pentingnya: yang menuntut tindakan hari
        ini cuma Alfa.
    --}}
    <dl class="deret-angka">
        <div>
            <dt class="stat-label">Terisi</dt>
            <dd class="stat-value">{{ $totalTerisi }}<span class="text-base font-normal text-slate-400">/{{ $totalSiswa }}</span></dd>
            <div class="bar mt-2"><div class="bar__isi" style="width: {{ $persenTerisi }}%"></div></div>
        </div>
        <div>
            <dt class="stat-label">Hadir</dt>
            <dd class="stat-value text-emerald-700">{{ $cntHadir }}</dd>
            <p class="stat-sub angka">{{ $totalSiswa > 0 ? round(($cntHadir / $totalSiswa) * 100) : 0 }}% dari total siswa</p>
        </div>
        <div>
            <dt class="stat-label">Sakit / Izin</dt>
            <dd class="stat-value">{{ $cntSakit }} / {{ $cntIzin }}</dd>
            <p class="stat-sub">berkabar</p>
        </div>
        <div>
            <dt class="stat-label">Alfa</dt>
            <dd class="stat-value {{ $cntAlfa > 0 ? 'text-rose-700' : '' }}">{{ $cntAlfa }}</dd>
            <p class="stat-sub angka">{{ $totalSiswa > 0 ? round(($cntAlfa / $totalSiswa) * 100) : 0 }}% dari total siswa</p>
        </div>
    </dl>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3" x-data="{ search: '', filterStatus: 'all' }">

        <section class="blok lg:col-span-2">
            <div class="blok__kepala">
                <h2 class="blok__judul">Daftar Kehadiran Siswa</h2>

                <div class="flex items-center gap-1.5">
                    <label for="cari-siswa" class="sr-only">Cari nama siswa</label>
                    <input id="cari-siswa" type="search" x-model="search" placeholder="Cari nama atau NIS" class="form-input form-input--sm w-40 sm:w-48">
                    <label for="saring-status" class="sr-only">Saring status</label>
                    <select id="saring-status" x-model="filterStatus" class="form-select form-input--sm w-28">
                        <option value="all">Semua</option>
                        <option value="hadir">Hadir</option>
                        <option value="terlambat">Terlambat</option>
                        <option value="sakit">Sakit</option>
                        <option value="izin">Izin</option>
                        <option value="alfa">Alfa</option>
                    </select>
                </div>
            </div>

            @if ($session->attendances->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col" class="w-10 text-right">No</th>
                                <th scope="col">Nama Siswa</th>
                                <th scope="col">NIS</th>
                                <th scope="col">Keterangan</th>
                                <th scope="col" class="w-14 text-center">Jam</th>
                                <th scope="col" class="w-12 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($session->attendances as $attendance)
                                @php
                                    $student = $attendance->student;
                                    $statusName = strtolower($attendance->status);
                                    [$huruf, $gayaKode] = $kodeStatus[$statusName] ?? [Str::upper(Str::substr($statusName, 0, 1)), ''];
                                @endphp
                                <tr x-show="(search === '' || '{{ strtolower($student->name ?? '') }}'.includes(search.toLowerCase()) || '{{ $student->nis ?? '' }}'.includes(search)) && (filterStatus === 'all' || filterStatus === '{{ $statusName }}')">
                                    <td class="text-right font-mono text-xs text-slate-400">{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $student->name ?? 'Siswa' }}
                                        @if ($attendance->revisions_count > 0)
                                            <span class="badge ml-1">Dikoreksi</span>
                                        @endif
                                    </td>
                                    <td class="td--mono">{{ $student->nis ?? '—' }}</td>
                                    {{--
                                        `note` (tunggal), bukan `notes`; dan jamnya dari
                                        `created_at`, bukan `filled_at` yang tidak pernah ada
                                        di tabel attendances. Eloquent mengembalikan NULL
                                        diam-diam untuk atribut yang tidak ada, jadi kedua kolom
                                        ini tidak pernah terisi sekali pun — keterangan yang
                                        sudah susah payah diketik petugas ("Tidak ada kabar",
                                        dsb.) tersimpan rapi lalu tak pernah terbaca siapa pun.
                                    --}}
                                    <td class="td--secondary max-w-[14rem] truncate" title="{{ $attendance->note }}">{{ $attendance->note ?: '—' }}</td>
                                    <td class="text-center font-mono text-xs text-slate-400">{{ $attendance->created_at?->format('H:i') ?? '—' }}</td>
                                    <td class="text-center">
                                        <span class="kode {{ $gayaKode }}" title="{{ Str::title($statusName) }}">{{ $huruf }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-6 text-center">
                    <p class="text-sm font-medium text-slate-900">Belum ada data kehadiran terisi</p>
                    <p class="mt-1 text-sm text-slate-500">Siswa atau Seksi Absensi belum mengisi kehadiran lewat Magic Link.</p>
                </div>
            @endif
        </section>

        <section class="blok">
            <div class="blok__kepala">
                <h2 class="blok__judul">Akses Absensi Siswa</h2>
                <span class="eyebrow">Magic link</span>
            </div>

            <div class="blok__isi space-y-4">
                @if (!empty($pin))
                    {{-- PIN dicetak sebesar mungkin dan hanya ini yang boleh besar
                         di kolom ini: ia dibacakan keras-keras di depan kelas. --}}
                    <div class="rounded border border-slate-300 bg-slate-50 p-3 text-center">
                        <p class="eyebrow">PIN harian siswa</p>
                        <p class="mt-1.5 select-all font-mono text-3xl font-medium tracking-[0.2em] text-slate-900">{{ $pin }}</p>
                        <p class="mt-1.5 text-xs text-slate-500">Bacakan atau bagikan PIN 6 digit ini kepada Seksi Absensi.</p>
                        @if (!empty($waTarget))
                            <p class="mt-1.5 text-xs text-slate-500">Tujuan WA: <span class="font-medium text-slate-700">{{ $waTarget }}</span></p>
                        @endif
                    </div>
                @else
                    <div class="rounded border border-slate-200 bg-slate-50 p-3 text-center">
                        <p class="text-xs font-medium text-slate-700">PIN harian sudah terbit</p>
                        <p class="mt-1 text-xs text-slate-500">PIN sudah dikirim lewat WhatsApp. Kirim ulang bila memerlukan PIN baru.</p>
                    </div>
                @endif

                <div class="flex flex-col items-center rounded border border-slate-200 bg-white p-3" x-data="qrRenderer()">
                    <canvas id="attendance-qr-canvas" width="120" height="120"></canvas>
                    <p class="mt-2 text-xs text-slate-500">Pindai QR ini di depan kelas</p>
                </div>

                <div class="form-group" x-data="{ copied: false }">
                    <span class="eyebrow">Tautan magic link</span>
                    <div class="flex items-center gap-1.5">
                        <label for="magic-link" class="sr-only">Tautan magic link</label>
                        <input id="magic-link" type="text" readonly value="{{ $session->magicLink() }}" class="form-input form-input--sm truncate font-mono">
                        <button type="button" class="btn-secondary btn-secondary--sm shrink-0"
                                @click="navigator.clipboard.writeText('{{ $session->magicLink() }}'); copied=true; setTimeout(()=>copied=false, 2500)">
                            <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                        </button>
                    </div>
                </div>

                @if ($session->delivery_status === 'failed')
                    <div class="alert alert--danger">
                        <div>
                            <p class="alert__title">Pengiriman WA gagal</p>
                            <p class="alert__body mt-0.5">{{ $session->delivery_error }}</p>
                        </div>
                    </div>
                @elseif ($session->delivery_status === 'pending')
                    <div class="alert alert--warning sm:items-center">
                        <p class="alert__body">Menunggu antrean pengiriman WA…</p>
                    </div>
                @elseif ($session->delivery_status === 'sent')
                    <div class="alert alert--success sm:items-center">
                        <p class="alert__body">Terkirim via WA {{ $session->delivered_at ? $session->delivered_at->format('H:i') : '' }}</p>
                    </div>
                {{--
                    Dibedakan tegas dari 'failed'. Pesan yang tidak dikirim
                    karena masa otomasi habis bukan kerusakan, dan menuliskan
                    "gagal" akan mengirim wali kelas mengejar masalah teknis
                    yang tidak ada — lalu menghubungi dukungan.
                --}}
                @elseif ($session->delivery_status === 'skipped')
                    <div class="alert alert--warning">
                        <div>
                            <p class="alert__title">Tidak dikirim ke WhatsApp</p>
                            <p class="alert__body mt-0.5">{{ $session->delivery_error }}</p>
                            <a href="{{ route('subscription.index') }}" class="alert__body mt-1.5 inline-block font-semibold underline underline-offset-2">Perpanjang langganan</a>
                        </div>
                    </div>
                @endif

                <div class="space-y-2 border-t border-slate-200 pt-3">
                    @if ($session->isOpen() && $session->delivery_status !== 'sent')
                        <form method="POST" action="{{ route('classes.attendance.resend', [$classroom, $session]) }}">
                            @csrf
                            <button type="submit" class="btn-secondary w-full">Kirim Ulang WA (PIN Baru)</button>
                        </form>
                    @endif

                    @if ($session->status === 'open')
                        <form method="POST" action="{{ route('classes.attendance.cancel', [$classroom, $session]) }}"
                              onsubmit="return confirm('Batalkan sesi absensi ini? Seluruh rekap akan mengabaikannya.')">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn-danger-ghost w-full">Batalkan Sesi Absensi</button>
                        </form>
                    @endif
                </div>
            </div>
        </section>

    </div>
</div>

<script>
    function qrRenderer() {
        return {
            init() {
                const magicLink = '{{ $session->magicLink() }}';
                const canvas = document.getElementById('attendance-qr-canvas');
                if (canvas && typeof qrcode === 'function' && magicLink) {
                    const qr = qrcode(0, 'M');
                    qr.addData(magicLink);
                    qr.make();

                    const cellSize = Math.floor(120 / qr.getModuleCount());
                    const qrSize = cellSize * qr.getModuleCount();
                    canvas.width = qrSize;
                    canvas.height = qrSize;

                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, qrSize, qrSize);
                    // Tinta, bukan hitam pekat: sewarna dengan teks halaman.
                    ctx.fillStyle = '#1a1712';

                    for (let row = 0; row < qr.getModuleCount(); row++) {
                        for (let col = 0; col < qr.getModuleCount(); col++) {
                            if (qr.isDark(row, col)) {
                                ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize);
                            }
                        }
                    }
                }
            }
        }
    }
</script>
@endsection
