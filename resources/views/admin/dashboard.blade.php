@extends('layouts.app-operator')

@section('title', 'Panel Operator')

@section('content')
<div class="space-y-6 pb-12">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Panel Operator</h1>
            <p class="mt-0.5 text-xs text-slate-500">Keadaan langganan, gateway WhatsApp, dan antrian di seluruh tenant.</p>
        </div>
        <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-xl">{{ now()->translatedFormat('l, d F Y · H:i') }}</span>
    </div>

    @include('partials.flash')

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('admin.teachers.index') }}" class="block bg-white rounded-2xl border border-emerald-200 hover:border-emerald-400 shadow-xs p-5 transition-all">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Wali Kelas Terdaftar</p>
            <p class="mt-1 text-3xl font-extrabold text-slate-900">{{ number_format($ringkas['guru_aktif']) }}</p>
            <p class="mt-1 text-[11px] font-bold {{ $ringkas['guru_baru_30h'] > 0 ? 'text-emerald-700' : 'text-slate-400' }}">
                +{{ $ringkas['guru_baru_30h'] }} dalam 30 hari
            </p>
            <p class="mt-2 text-xs font-bold text-emerald-700">Lihat daftarnya ›</p>
        </a>

        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Otomasi WA Aktif</p>
            <p class="mt-1 text-3xl font-extrabold text-slate-900">
                {{ number_format($ringkas['otomasi_aktif']) }}<span class="text-lg font-bold text-slate-400">/{{ $ringkas['guru_aktif'] }}</span>
            </p>
            <p class="mt-1 text-[11px] font-semibold text-slate-400">masa gratis + berbayar</p>
        </div>

        <a href="{{ route('admin.subscriptions.index') }}"
           class="rounded-2xl border p-5 shadow-xs transition-all {{ $ringkas['pembayaran_pending'] > 0 ? 'border-amber-300 bg-amber-50/70 hover:border-amber-400' : 'border-emerald-200 bg-white hover:border-emerald-400' }}">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Persetujuan PRO</p>
            <p class="mt-1 text-3xl font-extrabold {{ $ringkas['pembayaran_pending'] > 0 ? 'text-amber-900' : 'text-slate-900' }}">
                {{ number_format($ringkas['pembayaran_pending']) }}
            </p>
            <p class="mt-1 text-[11px] font-bold {{ $ringkas['pembayaran_pending'] > 0 ? 'text-amber-800' : 'text-slate-400' }}">
                @if($ringkas['pembayaran_pending'] > 0)
                    Bukti menunggu verifikasi →
                @else
                    tidak ada antrean verifikasi
                @endif
            </p>
        </a>

        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Gateway WA Tersambung</p>
            <p class="mt-1 text-3xl font-extrabold text-slate-900">
                {{ number_format($ringkas['wa_tersambung']) }}<span class="text-lg font-bold text-slate-400">/{{ $ringkas['guru_aktif'] }}</span>
            </p>
            <p class="mt-1 text-[11px] font-bold {{ $gateway['kirim_gagal_7h'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                {{ $gateway['kirim_gagal_7h'] }} pengiriman gagal / 7 hari
            </p>
        </div>
    </div>

    <!-- Segmen langganan -->
    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
        <h3 class="text-sm font-extrabold text-slate-900">Segmen Langganan</h3>
        <p class="text-xs text-slate-500 mt-0.5">Masa gratis {{ \App\Models\User::BULAN_MASA_GRATIS }} bulan; setelah habis hanya otomasi WhatsApp yang berhenti.</p>

        <div class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach([
                ['Masa Gratis Berjalan', $segmen['masa_gratis'], 'border-sky-200 bg-sky-50/70', 'text-sky-950', 'text-sky-900', 'text-sky-700/90', 'belum pernah membayar'],
                ['Berbayar Aktif', $segmen['berbayar'], 'border-emerald-200 bg-emerald-50/70', 'text-emerald-950', 'text-emerald-900', 'text-emerald-700/90', 'PRO, masa aktif berjalan'],
                ['Masa Gratis Habis', $segmen['gratis_habis'], 'border-amber-200 bg-amber-50/70', 'text-amber-950', 'text-amber-900', 'text-amber-700/90', 'calon konversi pertama'],
                ['Berbayar Lewat Tempo', $segmen['berbayar_lewat_tempo'], 'border-rose-200 bg-rose-50/70', 'text-rose-950', 'text-rose-900', 'text-rose-700/90', 'pernah bayar, tidak diperpanjang'],
            ] as [$label, $jumlah, $kotak, $angka, $judul, $catatanWarna, $catatan])
                <div class="rounded-2xl border p-4 {{ $kotak }}">
                    <p class="text-2xl font-extrabold {{ $angka }}">{{ number_format($jumlah) }}</p>
                    <p class="text-xs font-bold uppercase tracking-wider mt-0.5 {{ $judul }}">{{ $label }}</p>
                    <p class="text-[10px] font-semibold mt-1 {{ $catatanWarna }}">{{ $catatan }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Perlu ditagih -->
    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-emerald-100 pb-3">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Masa Aktif Habis / Segera Habis ({{ $perluDitagih->count() }})</h3>
                <p class="text-xs text-slate-500 mt-0.5">14 hari ke depan dan 30 hari ke belakang, terurut paling mendesak.</p>
            </div>
        </div>

        @if($perluDitagih->isNotEmpty())
            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50/60 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th scope="col" class="px-4 py-2.5">Wali Kelas</th>
                            <th scope="col" class="px-4 py-2.5">Riwayat</th>
                            <th scope="col" class="px-4 py-2.5">Berakhir</th>
                            <th scope="col" class="px-4 py-2.5">Status</th>
                            <th scope="col" class="px-4 py-2.5 text-center">Hubungi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($perluDitagih as $g)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-4 py-2.5">
                                    <span class="font-bold text-slate-900 block">{{ $g['nama'] }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ $g['sekolah'] }}</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-block rounded-md px-2 py-0.5 text-[10px] font-bold {{ $g['pernah_bayar'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $g['pernah_bayar'] ? 'PERNAH BAYAR' : 'MASA GRATIS' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 font-semibold text-slate-700 font-mono">
                                    {{ $g['berakhir']->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-4 py-2.5 font-bold">
                                    @if($g['sisa_hari'] < 0)
                                        <span class="text-rose-600">habis {{ abs($g['sisa_hari']) }} hari lalu</span>
                                    @elseif($g['sisa_hari'] <= 3)
                                        <span class="text-amber-600">sisa {{ $g['sisa_hari'] }} hari</span>
                                    @else
                                        <span class="text-slate-500">sisa {{ $g['sisa_hari'] }} hari</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    @if($g['whatsapp'])
                                        <a href="https://wa.me/{{ ltrim($g['whatsapp'], '+') }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1 font-bold text-emerald-700 hover:underline">
                                            {{ $g['whatsapp'] }}
                                        </a>
                                    @else
                                        <span class="text-slate-300">tanpa nomor</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="rounded-2xl bg-slate-50 py-6 text-center text-xs text-slate-500">Tidak ada masa aktif yang jatuh tempo dalam rentang ini.</p>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Gateway WhatsApp -->
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6 space-y-4">
            <div class="border-b border-emerald-100 pb-3">
                <h3 class="text-sm font-extrabold text-slate-900">Kesehatan Gateway WhatsApp</h3>
                <p class="text-xs text-slate-500 mt-0.5">Sesi per wali kelas — sumber keluhan "absensi saya tidak terkirim".</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach($gateway['per_status'] as $status => $jumlah)
                    <span class="rounded-lg px-2.5 py-1 text-[10px] font-bold {{ match($status) { 'connected' => 'bg-emerald-100 text-emerald-800', 'pairing' => 'bg-amber-100 text-amber-800', default => 'bg-slate-100 text-slate-700', } }}">
                        {{ $status ?: 'tidak diketahui' }}: {{ $jumlah }}
                    </span>
                @endforeach
            </div>

            @if($gateway['bermasalah']->isNotEmpty())
                <ul class="divide-y divide-slate-100">
                    @foreach($gateway['bermasalah'] as $g)
                        <li class="py-2.5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="text-xs font-bold text-slate-900 block truncate">{{ $g['nama'] }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $g['whatsapp'] ?: 'tanpa nomor' }}</span>
                                    @if($g['galat'])
                                        <span class="text-[10px] text-rose-600 block mt-0.5 break-words font-semibold">{{ $g['galat'] }}</span>
                                    @endif
                                </div>
                                <span class="shrink-0 rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">{{ $g['status'] }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="rounded-2xl bg-emerald-50 py-6 text-center text-xs font-bold text-emerald-800">Semua sesi tersambung lancar.</p>
            @endif
        </div>

        <!-- Antrian -->
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6 space-y-4">
            <div class="border-b border-emerald-100 pb-3">
                <h3 class="text-sm font-extrabold text-slate-900">Antrian &amp; Pekerjaan Gagal</h3>
                <p class="text-xs text-slate-500 mt-0.5">Pengiriman WhatsApp berjalan lewat antrian.</p>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-2xl bg-slate-50 p-3.5 border border-slate-100">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Menunggu</p>
                    <p class="text-xl font-extrabold text-slate-900 mt-0.5">{{ $antrian['menunggu'] ?? '—' }}</p>
                </div>
                <div class="rounded-2xl {{ $antrian['gagal_24j'] > 0 ? 'bg-rose-50 border border-rose-200' : 'bg-slate-50 border border-slate-100' }} p-3.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider {{ $antrian['gagal_24j'] > 0 ? 'text-rose-700' : 'text-slate-400' }}">Gagal 24 Jam</p>
                    <p class="text-xl font-extrabold {{ $antrian['gagal_24j'] > 0 ? 'text-rose-900' : 'text-slate-900' }} mt-0.5">{{ $antrian['gagal_24j'] }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-3.5 border border-slate-100">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Gagal Total</p>
                    <p class="text-xl font-extrabold text-slate-900 mt-0.5">{{ $antrian['gagal_total'] }}</p>
                </div>
            </div>

            @if($antrian['redis_galat'])
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-3">
                    <p class="text-xs font-bold text-rose-800">Redis tidak terbaca — antrian mungkin tidak jalan</p>
                    <p class="text-[10px] text-rose-700 mt-0.5 break-words">{{ $antrian['redis_galat'] }}</p>
                </div>
            @endif

            @if($antrian['gagal_terbaru']->isNotEmpty())
                <ul class="divide-y divide-slate-100">
                    @foreach($antrian['gagal_terbaru'] as $j)
                        <li class="py-2.5">
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="text-xs font-bold text-slate-900 truncate">{{ class_basename($j['job']) }}</span>
                                <span class="shrink-0 text-[10px] text-slate-400 font-mono">{{ \Illuminate\Support\Carbon::parse($j['waktu'])->diffForHumans() }}</span>
                            </div>
                            <p class="text-[10px] text-rose-600 mt-0.5 break-words">{{ $j['pesan'] }}</p>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="rounded-2xl bg-emerald-50 py-6 text-center text-xs font-bold text-emerald-800">Tidak ada pekerjaan gagal.</p>
            @endif
        </div>
    </div>

    <!-- Pertumbuhan & skala -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
            <div class="flex items-baseline justify-between">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Pendaftar per Pekan</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Delapan pekan terakhir.</p>
                </div>
                <span class="text-xs font-bold text-emerald-700">
                    {{ $pertumbuhan['guru_memakai_7h'] }} guru membuka sesi absensi pekan ini
                </span>
            </div>

            <div class="mt-5 flex items-end justify-between gap-2 h-32">
                @foreach($pertumbuhan['mingguan'] as $pekan)
                    <div class="flex flex-1 flex-col items-center justify-end gap-1.5 h-full">
                        <span class="text-[10px] font-bold text-slate-600">{{ $pekan['jumlah'] }}</span>
                        <div class="w-full rounded-t-lg bg-emerald-600"
                             style="height: {{ max(4, round($pekan['jumlah'] / $pertumbuhan['puncak'] * 100)) }}%"></div>
                        <span class="text-[9px] text-slate-400 whitespace-nowrap">{{ $pekan['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
            <h3 class="text-sm font-extrabold text-slate-900">Skala Platform</h3>
            <p class="text-xs text-slate-500 mt-0.5">Konteks bagi angka di atas.</p>

            <dl class="mt-4 space-y-3">
                @foreach([
                    'Kelas aktif' => $skala['kelas'],
                    'Siswa aktif' => $skala['siswa'],
                    'Sesi absensi / 30 hari' => $skala['sesi_30h'],
                ] as $label => $nilai)
                    <div class="flex items-baseline justify-between border-b border-slate-100 pb-2.5 last:border-0">
                        <dt class="text-xs font-semibold text-slate-500">{{ $label }}</dt>
                        <dd class="text-base font-extrabold text-slate-900">{{ number_format($nilai) }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</div>
@endsection
