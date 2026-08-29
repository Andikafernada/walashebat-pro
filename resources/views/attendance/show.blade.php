@extends('layouts.app')

@section('title', 'Detail Absensi - ' . $classroom->name)

@section('content')
<script src="/vendor/qrcode.min.js?v=1.4.4"></script>

@php
    $rupaStatus = [
        'open' => ['Sesi Terbuka', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
        'submitted' => ['Selesai', 'bg-slate-100 text-slate-800 border-slate-200'],
        'expired' => ['Kadaluarsa', 'bg-amber-50 text-amber-800 border-amber-200'],
        'cancelled' => ['Dibatalkan', 'bg-rose-50 text-rose-800 border-rose-200'],
    ];
    [$labelStatus, $gayaStatus] = $rupaStatus[$session->status] ?? [$session->status, 'bg-slate-100 text-slate-800 border-slate-200'];

    $kodeStatus = [
        'hadir' => ['H', 'bg-emerald-100 text-emerald-800 border-emerald-200'],
        'terlambat' => ['T', 'bg-amber-100 text-amber-800 border-amber-200'],
        'sakit' => ['S', 'bg-amber-100 text-amber-800 border-amber-200'],
        'izin' => ['I', 'bg-sky-100 text-sky-800 border-sky-200'],
        'alfa' => ['A', 'bg-rose-100 text-rose-800 border-rose-200'],
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
            <nav class="text-xs font-semibold uppercase tracking-wider text-slate-400 flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.attendance.index', $classroom) }}" class="hover:text-slate-600">{{ $classroom->name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Detail Absensi</span>
            </nav>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-bold tracking-tight text-slate-900">
                    {{ $session->title ?: 'Absensi ' . $session->session_date->isoFormat('D MMMM YYYY') }}
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $gayaStatus }}">{{ $labelStatus }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">
                {{ $session->session_date->isoFormat('dddd, D MMMM YYYY') }}
                &middot; sesi #{{ $session->sequence ?? 1 }}
                &middot; berlaku s/d {{ $session->expires_at ? $session->expires_at->format('H:i') . ' WIB' : '—' }}
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a href="{{ route('classes.attendance.edit', [$classroom, $session]) }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Koreksi Manual</a>
            <a href="{{ route('classes.exports.attendance.pdf', $classroom) }}" target="_blank" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Cetak PDF</a>
        </div>
    </div>

    @include('partials.class-nav', ['classroom' => $classroom])
    @include('partials.flash')

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Terisi</span>
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">{{ $totalTerisi }}<span class="text-xs font-normal text-slate-400">/{{ $totalSiswa }}</span></p>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2">
                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $persenTerisi }}%"></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
            <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wider block">Hadir</span>
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-emerald-700">{{ $cntHadir }}</p>
            <p class="mt-1 text-[11px] text-emerald-600 font-mono">{{ $totalSiswa > 0 ? round(($cntHadir / $totalSiswa) * 100) : 0 }}% total</p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
            <span class="text-xs font-semibold text-amber-800 uppercase tracking-wider block">Sakit / Izin</span>
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">{{ $cntSakit }} <span class="text-xs font-normal text-slate-400">/</span> {{ $cntIzin }}</p>
            <p class="mt-1 text-[11px] text-slate-400">berkabar</p>
        </div>
        <div class="bg-white rounded-2xl border border-rose-200 shadow-xs p-4">
            <span class="text-xs font-semibold text-rose-800 uppercase tracking-wider block">Alfa</span>
            <p class="mt-2 text-2xl font-extrabold tracking-tight {{ $cntAlfa > 0 ? 'text-rose-700' : 'text-slate-900' }}">{{ $cntAlfa }}</p>
            <p class="mt-1 text-[11px] text-rose-600 font-mono">{{ $totalSiswa > 0 ? round(($cntAlfa / $totalSiswa) * 100) : 0 }}% total</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3" x-data="{ search: '', filterStatus: 'all' }">

        <section class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden lg:col-span-2">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between px-4 pt-4 pb-3 border-b border-emerald-100">
                <h2 class="text-sm font-extrabold text-slate-900">Daftar Kehadiran Siswa</h2>

                <div class="flex items-center gap-1.5">
                    <label for="cari-siswa" class="sr-only">Cari nama siswa</label>
                    <input id="cari-siswa" type="search" x-model="search" placeholder="Cari nama atau NIS" 
                           class="block rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 w-36 sm:w-44">
                    <label for="saring-status" class="sr-only">Saring status</label>
                    <select id="saring-status" x-model="filterStatus" class="block rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 w-24">
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
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                <th scope="col" class="py-2.5 px-3 w-10 text-right">No</th>
                                <th scope="col" class="py-2.5 px-3">Nama Siswa</th>
                                <th scope="col" class="py-2.5 px-3">NIS</th>
                                <th scope="col" class="py-2.5 px-3">Keterangan</th>
                                <th scope="col" class="py-2.5 px-3 w-14 text-center">Jam</th>
                                <th scope="col" class="py-2.5 px-3 w-12 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($session->attendances as $attendance)
                                @php
                                    $student = $attendance->student;
                                    $statusName = strtolower($attendance->status);
                                    [$huruf, $gayaKode] = $kodeStatus[$statusName] ?? [Str::upper(Str::substr($statusName, 0, 1)), 'bg-slate-100 text-slate-800'];
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition-colors" x-show="(search === '' || '{{ strtolower($student->name ?? '') }}'.includes(search.toLowerCase()) || '{{ $student->nis ?? '' }}'.includes(search)) && (filterStatus === 'all' || filterStatus === '{{ $statusName }}')">
                                    <td class="py-2.5 px-3 text-right font-mono text-xs text-slate-400">{{ $loop->iteration }}</td>
                                    <td class="py-2.5 px-3">
                                        <span class="font-bold text-slate-900">{{ $student->name ?? 'Siswa' }}</span>
                                        @if ($attendance->revisions_count > 0)
                                            <span class="ml-1 inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold bg-amber-100 text-amber-800">Dikoreksi</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-xs text-slate-500">{{ $student->nis ?? '—' }}</td>
                                    <td class="py-2.5 px-3 text-xs text-slate-500 max-w-[14rem] truncate" title="{{ $attendance->note }}">{{ $attendance->note ?: '—' }}</td>
                                    <td class="py-2.5 px-3 text-center font-mono text-xs text-slate-400">{{ $attendance->created_at?->format('H:i') ?? '—' }}</td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-lg text-xs font-bold border {{ $gayaKode }}" title="{{ Str::title($statusName) }}">{{ $huruf }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-6 text-center">
                    <p class="text-sm font-semibold text-slate-900">Belum ada data kehadiran terisi</p>
                    <p class="mt-1 text-xs text-slate-500">Siswa atau Seksi Absensi belum mengisi kehadiran lewat Magic Link.</p>
                </div>
            @endif
        </section>

        <!-- Access / Magic link side card -->
        <section class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
            <div class="flex items-center justify-between px-4 pt-4 pb-3 border-b border-emerald-100">
                <h2 class="text-sm font-extrabold text-slate-900">Akses Absensi Siswa</h2>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Magic link</span>
            </div>

            <div class="p-4 space-y-4">
                @if (!empty($pin))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-3.5 text-center">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">PIN harian siswa</p>
                        <p class="mt-1.5 select-all font-mono text-3xl font-extrabold tracking-[0.2em] text-slate-900">{{ $pin }}</p>
                        <p class="mt-1.5 text-xs text-slate-500">Bacakan atau bagikan PIN 6 digit ini kepada Seksi Absensi.</p>
                        @if (!empty($waTarget))
                            <p class="mt-1.5 text-xs text-slate-500">Tujuan WA: <span class="font-bold text-slate-700">{{ $waTarget }}</span></p>
                        @endif
                    </div>
                @else
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 text-center">
                        <p class="text-xs font-bold text-slate-700">PIN harian sudah terbit</p>
                        <p class="mt-1 text-xs text-slate-500">PIN sudah dikirim lewat WhatsApp. Kirim ulang bila memerlukan PIN baru.</p>
                    </div>
                @endif

                <div class="flex flex-col items-center rounded-2xl border border-slate-200 bg-white p-3" x-data="qrRenderer()">
                    <canvas id="attendance-qr-canvas" width="120" height="120"></canvas>
                    <p class="mt-2 text-xs text-slate-500">Pindai QR ini di depan kelas</p>
                </div>

                <div x-data="{ copied: false }">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block mb-1">Tautan magic link</span>
                    <div class="flex items-center gap-1.5">
                        <label for="magic-link" class="sr-only">Tautan magic link</label>
                        <input id="magic-link" type="text" readonly value="{{ $session->magicLink() }}" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-mono truncate text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <button type="button" class="shrink-0 inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                                @click="navigator.clipboard.writeText('{{ $session->magicLink() }}'); copied=true; setTimeout(()=>copied=false, 2500)">
                            <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                        </button>
                    </div>
                </div>

                @if ($session->delivery_status === 'failed')
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-3 flex gap-2">
                        <div>
                            <p class="text-xs font-bold text-slate-900">Pengiriman WA gagal</p>
                            <p class="text-xs text-slate-600 mt-0.5">{{ $session->delivery_error }}</p>
                        </div>
                    </div>
                @elseif ($session->delivery_status === 'pending')
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-3 flex gap-2 sm:items-center">
                        <p class="text-xs text-slate-600">Menunggu antrean pengiriman WA…</p>
                    </div>
                @elseif ($session->delivery_status === 'sent')
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3 flex gap-2 sm:items-center">
                        <p class="text-xs text-slate-600">Terkirim via WA {{ $session->delivered_at ? $session->delivered_at->format('H:i') : '' }}</p>
                    </div>
                @elseif ($session->delivery_status === 'skipped')
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-3 flex gap-2">
                        <div>
                            <p class="text-xs font-bold text-slate-900">Tidak dikirim ke WhatsApp</p>
                            <p class="text-xs text-slate-600 mt-0.5">{{ $session->delivery_error }}</p>
                            <a href="{{ route('subscription.index') }}" class="text-xs font-semibold text-emerald-700 underline underline-offset-2 mt-1.5 inline-block">Perpanjang langganan</a>
                        </div>
                    </div>
                @endif

                <div class="space-y-2 border-t border-slate-200 pt-3">
                    @if ($session->isOpen() && $session->delivery_status !== 'sent')
                        <form method="POST" action="{{ route('classes.attendance.resend', [$classroom, $session]) }}">
                            @csrf
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Kirim Ulang WA (PIN Baru)</button>
                        </form>
                    @endif

                    @if ($session->status === 'open')
                        <form method="POST" action="{{ route('classes.attendance.cancel', [$classroom, $session]) }}"
                              onsubmit="return confirm('Batalkan sesi absensi ini? Seluruh rekap akan mengabaikannya.')">
                            @csrf @method('PATCH')
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors">Batalkan Sesi Absensi</button>
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
