@extends('layouts.app')

@section('title', 'Cetak Kartu QR Presensi Siswa - ' . $class->name)

@section('content')
<script src="/vendor/qrcode.min.js?v=1.4.4"></script>
<div class="space-y-6 pb-12">
    <!-- Header Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <nav class="text-xs font-semibold uppercase tracking-wider text-slate-400 flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.students.index', $class) }}" class="hover:text-slate-600">{{ $class->name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Cetak Kartu QR Siswa</span>
            </nav>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900">
                Kartu QR Code Presensi Siswa — {{ $class->name }}
            </h1>
            <p class="mt-1 text-xs text-slate-500">Cetak kartu ID ber-QR Code untuk presensi kilat di kelas. 8 kartu per lembar A4.</p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">Cetak Kartu Sekarang</button>
        </div>
    </div>

    <!-- CARDS GRID CONTAINER -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 print:grid-cols-2 print:gap-3">
        @foreach($students as $s)
            <div class="relative overflow-hidden rounded-2xl border-2 border-slate-900 bg-white p-4 text-slate-900 flex flex-col justify-between h-56 print:h-48 print:border-slate-800 shadow-xs">

                <!-- CARD HEADER -->
                <div class="flex items-center justify-between border-b-2 border-slate-900 pb-2">
                    <div class="flex items-center gap-2">
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400 leading-tight">WALIKELAS PRO</p>
                            <p class="text-xs font-extrabold text-emerald-950 uppercase leading-tight">{{ $class->name }}</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-extrabold text-emerald-800">PRESENSI</span>
                </div>

                <!-- CARD BODY: QR & DATA -->
                <div class="flex items-center justify-between gap-3 my-auto">
                    <div class="min-w-0 space-y-1">
                        <p class="text-xs font-bold text-slate-900 leading-tight uppercase truncate" title="{{ $s->name }}">{{ $s->name }}</p>
                        <p class="text-[10px] text-slate-600 font-semibold">NIS: <span class="font-bold text-slate-900">{{ $s->nis ?: '-' }}</span></p>
                        <p class="text-[10px] text-slate-600 font-semibold">NISN: <span class="font-bold text-slate-900">{{ $s->nisn ?: '-' }}</span></p>
                    </div>

                    <div class="shrink-0 p-1.5 bg-white border border-slate-200 rounded-xl">
                        <canvas id="qr-student-{{ $s->id }}" width="80" height="80" class="h-20 w-20 print:h-16 print:w-16"></canvas>
                        <script>
                            (function() {
                                const canvas = document.getElementById('qr-student-{{ $s->id }}');
                                if (canvas && typeof qrcode === 'function') {
                                    const data = 'STUDENT-{{ $s->id }}-{{ $s->nis }}';
                                    const typeNumber = 0;
                                    const errorCorrectionLevel = 'M';
                                    const qr = qrcode(typeNumber, errorCorrectionLevel);
                                    qr.addData(data);
                                    qr.make();

                                    const cellSize = Math.floor(80 / qr.getModuleCount());
                                    const qrSize = cellSize * qr.getModuleCount();
                                    canvas.width = qrSize;
                                    canvas.height = qrSize;

                                    const ctx = canvas.getContext('2d');
                                    ctx.fillStyle = '#ffffff';
                                    ctx.fillRect(0, 0, qrSize, qrSize);
                                    ctx.fillStyle = '#000000';

                                    for (let row = 0; row < qr.getModuleCount(); row++) {
                                        for (let col = 0; col < qr.getModuleCount(); col++) {
                                            if (qr.isDark(row, col)) {
                                                ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize);
                                            }
                                        }
                                    }
                                }
                            })();
                        </script>
                    </div>
                </div>

                <!-- CARD FOOTER -->
                <div class="border-t border-slate-100 pt-1.5 flex items-center justify-between text-[9px] text-slate-400 font-semibold">
                    <span>Kartu Presensi Resmi Siswa</span>
                    <span class="font-mono">ID: #{{ $s->id }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
@media print {
    body { background: white !important; font-size: 10pt; }
    .no-print { display: none !important; }
    .page-break { page-break-after: always; }
}
</style>
@endsection
