@extends('layouts.app')

@section('title', 'Panel Operator')

{{--
    Halaman ini untuk operator aplikasi (pemilik SaaS), bukan kepala sekolah.
    Urutannya sengaja: yang menuntut tindakan hari ini di atas, angka konteks
    di bawah. Seluruh gaya memakai utility Tailwind langsung di markup — tidak
    ada @push('styles') karena tidak ada layout yang merender @stack('styles'),
    dan @apply di dalam <style> runtime tidak berarti apa-apa.
--}}

@section('content')
<div class="space-y-6 pb-12">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Panel Operator 🛰️</h1>
            <p class="text-xs text-slate-500 mt-0.5">Keadaan langganan, gateway WhatsApp, dan antrian di seluruh tenant.</p>
        </div>
        <span class="text-sm font-medium text-slate-700">{{ now()->translatedFormat('l, d F Y · H:i') }}</span>
    </div>

    @include('partials.flash')

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">Wali Kelas Terdaftar</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($ringkas['guru_aktif']) }}</p>
            <p class="mt-1 text-[11px] font-semibold {{ $ringkas['guru_baru_30h'] > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                +{{ $ringkas['guru_baru_30h'] }} dalam 30 hari
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">Otomasi WA Aktif</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">
                {{ number_format($ringkas['otomasi_aktif']) }}<span class="text-lg font-semibold text-slate-400">/{{ $ringkas['guru_aktif'] }}</span>
            </p>
            <p class="mt-1 text-[11px] font-semibold text-slate-400">masa gratis + berbayar</p>
        </div>

        {{-- Kartu ini satu-satunya yang bisa langsung dikerjakan, jadi ia
             berubah warna dan menjadi tautan begitu ada yang menunggu. --}}
        <a href="{{ route('admin.subscriptions.index') }}"
           class="rounded-2xl border p-5 shadow-sm transition-shadow hover:shadow-md {{ $ringkas['pembayaran_pending'] > 0 ? 'border-amber-300 bg-amber-50' : 'border-slate-200/80 bg-white' }}">
            <p class="text-xs font-semibold {{ $ringkas['pembayaran_pending'] > 0 ? 'text-amber-700' : 'text-slate-500' }}">Bukti Menunggu Verifikasi</p>
            <p class="mt-1 text-3xl font-bold {{ $ringkas['pembayaran_pending'] > 0 ? 'text-amber-900' : 'text-slate-900' }}">
                {{ number_format($ringkas['pembayaran_pending']) }}
            </p>
            <p class="mt-1 text-[11px] font-semibold {{ $ringkas['pembayaran_pending'] > 0 ? 'text-amber-700' : 'text-slate-400' }}">
                @if($ringkas['pembayaran_pending'] > 0)
                    tertua menunggu {{ $ringkas['pending_terlama_jam'] }} jam →
                @else
                    tidak ada antrean verifikasi
                @endif
            </p>
        </a>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">Gateway WA Tersambung</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">
                {{ number_format($ringkas['wa_tersambung']) }}<span class="text-lg font-semibold text-slate-400">/{{ $ringkas['guru_aktif'] }}</span>
            </p>
            <p class="mt-1 text-[11px] font-semibold {{ $gateway['kirim_gagal_7h'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                {{ $gateway['kirim_gagal_7h'] }} pengiriman gagal / 7 hari
            </p>
        </div>
    </div>

    <!-- Segmen langganan -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900">Segmen Langganan</h3>
        <p class="text-xs text-slate-500">Masa gratis {{ \App\Models\User::BULAN_MASA_GRATIS }} bulan; setelah habis hanya otomasi WhatsApp yang berhenti.</p>

        {{--
            Kelas warna ditulis utuh, bukan dirangkai dari variabel
            ("border-{$warna}-200"). Pemindai Tailwind membaca teks sumber dan
            tidak pernah menjalankan Blade, jadi kelas yang baru terbentuk saat
            runtime tidak ikut masuk ke CSS hasil build — kartunya tampil tanpa
            warna sama sekali, dan tidak ada galat yang menjelaskan kenapa.
        --}}
        <div class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach([
                ['Masa Gratis Berjalan', $segmen['masa_gratis'], 'border-sky-200 bg-sky-50', 'text-sky-900', 'text-sky-800', 'text-sky-700/80', 'belum pernah membayar'],
                ['Berbayar Aktif', $segmen['berbayar'], 'border-emerald-200 bg-emerald-50', 'text-emerald-900', 'text-emerald-800', 'text-emerald-700/80', 'PRO, masa aktif berjalan'],
                ['Masa Gratis Habis', $segmen['gratis_habis'], 'border-amber-200 bg-amber-50', 'text-amber-900', 'text-amber-800', 'text-amber-700/80', 'calon konversi pertama'],
                ['Berbayar Lewat Tempo', $segmen['berbayar_lewat_tempo'], 'border-rose-200 bg-rose-50', 'text-rose-900', 'text-rose-800', 'text-rose-700/80', 'pernah bayar, tidak diperpanjang'],
            ] as [$label, $jumlah, $kotak, $angka, $judul, $catatanWarna, $catatan])
                <div class="rounded-xl border p-4 {{ $kotak }}">
                    <p class="text-2xl font-bold {{ $angka }}">{{ number_format($jumlah) }}</p>
                    <p class="text-[11px] font-bold uppercase tracking-wide mt-0.5 {{ $judul }}">{{ $label }}</p>
                    <p class="text-[10px] mt-1 {{ $catatanWarna }}">{{ $catatan }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Perlu ditagih -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Masa Aktif Habis / Segera Habis ({{ $perluDitagih->count() }})</h3>
                <p class="text-xs text-slate-500">14 hari ke depan dan 30 hari ke belakang, terurut paling mendesak.</p>
            </div>
        </div>

        @if($perluDitagih->isNotEmpty())
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th scope="col" class="py-3 px-3">Wali Kelas</th>
                            <th scope="col" class="py-3 px-3">Riwayat</th>
                            <th scope="col" class="py-3 px-3">Berakhir</th>
                            <th scope="col" class="py-3 px-3">Status</th>
                            <th scope="col" class="py-3 px-3 text-center">Hubungi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($perluDitagih as $g)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-3">
                                    <span class="font-bold text-slate-900 block">{{ $g['nama'] }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ $g['sekolah'] }}</span>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="inline-block rounded-md px-2 py-0.5 text-[10px] font-bold {{ $g['pernah_bayar'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $g['pernah_bayar'] ? 'PERNAH BAYAR' : 'MASA GRATIS' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 font-semibold text-slate-700">
                                    {{ $g['berakhir']->translatedFormat('d M Y') }}
                                </td>
                                <td class="py-3 px-3">
                                    @if($g['sisa_hari'] < 0)
                                        <span class="font-bold text-rose-600">habis {{ abs($g['sisa_hari']) }} hari lalu</span>
                                    @elseif($g['sisa_hari'] <= 3)
                                        <span class="font-bold text-amber-600">sisa {{ $g['sisa_hari'] }} hari</span>
                                    @else
                                        <span class="font-semibold text-slate-500">sisa {{ $g['sisa_hari'] }} hari</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center">
                                    @if($g['whatsapp'])
                                        {{-- wa.me menuntut nomor tanpa tanda plus; nomornya sudah
                                             dinormalkan ke format 62… oleh mutator di User. --}}
                                        <a href="https://wa.me/{{ ltrim($g['whatsapp'], '+') }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1 font-bold text-emerald-600 hover:underline">
                                            💬 {{ $g['whatsapp'] }}
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
            <p class="rounded-xl bg-slate-50 py-6 text-center text-xs text-slate-500">Tidak ada masa aktif yang jatuh tempo dalam rentang ini.</p>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Gateway WhatsApp -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900">Kesehatan Gateway WhatsApp</h3>
                <p class="text-xs text-slate-500">Sesi per wali kelas — sumber keluhan "absensi saya tidak terkirim".</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach($gateway['per_status'] as $status => $jumlah)
                    <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ match($status) {
                        'connected' => 'bg-emerald-100 text-emerald-800',
                        'pairing'   => 'bg-amber-100 text-amber-800',
                        default     => 'bg-slate-200 text-slate-700',
                    } }}">
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
                                    <span class="text-[10px] text-slate-400">{{ $g['whatsapp'] ?: 'tanpa nomor' }}</span>
                                    @if($g['galat'])
                                        <span class="text-[10px] text-rose-600 block mt-0.5 break-words">{{ $g['galat'] }}</span>
                                    @endif
                                </div>
                                <span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">{{ $g['status'] }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="rounded-xl bg-emerald-50 py-6 text-center text-xs font-semibold text-emerald-700">Semua sesi tersambung.</p>
            @endif
        </div>

        <!-- Antrian -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900">Antrian &amp; Pekerjaan Gagal</h3>
                <p class="text-xs text-slate-500">Pengiriman WhatsApp berjalan lewat antrian; kegagalannya diam-diam.</p>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Menunggu</p>
                    <p class="text-xl font-bold text-slate-900">{{ $antrian['menunggu'] ?? '—' }}</p>
                </div>
                <div class="rounded-xl {{ $antrian['gagal_24j'] > 0 ? 'bg-rose-50' : 'bg-slate-50' }} p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide {{ $antrian['gagal_24j'] > 0 ? 'text-rose-700' : 'text-slate-500' }}">Gagal 24 jam</p>
                    <p class="text-xl font-bold {{ $antrian['gagal_24j'] > 0 ? 'text-rose-900' : 'text-slate-900' }}">{{ $antrian['gagal_24j'] }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Gagal total</p>
                    <p class="text-xl font-bold text-slate-900">{{ $antrian['gagal_total'] }}</p>
                </div>
            </div>

            @if($antrian['redis_galat'])
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-3">
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
                                <span class="shrink-0 text-[10px] text-slate-400">{{ \Illuminate\Support\Carbon::parse($j['waktu'])->diffForHumans() }}</span>
                            </div>
                            <p class="text-[10px] text-rose-600 mt-0.5 break-words">{{ $j['pesan'] }}</p>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="rounded-xl bg-emerald-50 py-6 text-center text-xs font-semibold text-emerald-700">Tidak ada pekerjaan gagal.</p>
            @endif
        </div>
    </div>

    <!-- Pertumbuhan & skala -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <div class="flex items-baseline justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Pendaftar per Pekan</h3>
                    <p class="text-xs text-slate-500">Delapan pekan terakhir.</p>
                </div>
                <span class="text-[11px] font-bold text-indigo-600">
                    {{ $pertumbuhan['guru_memakai_7h'] }} guru membuka sesi absensi pekan ini
                </span>
            </div>

            <div class="mt-5 flex items-end justify-between gap-2 h-32">
                @foreach($pertumbuhan['mingguan'] as $pekan)
                    <div class="flex flex-1 flex-col items-center justify-end gap-1.5 h-full">
                        <span class="text-[10px] font-bold text-slate-600">{{ $pekan['jumlah'] }}</span>
                        {{-- Tinggi dihitung inline: kelas Tailwind dinamis tidak
                             ikut ter-build karena pemindai hanya membaca teks
                             sumber, bukan nilai runtime. --}}
                        <div class="w-full rounded-t-md bg-indigo-500/80"
                             style="height: {{ max(2, round($pekan['jumlah'] / $pertumbuhan['puncak'] * 100)) }}%"></div>
                        <span class="text-[9px] text-slate-400 whitespace-nowrap">{{ $pekan['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900">Skala Platform</h3>
            <p class="text-xs text-slate-500">Konteks bagi angka di atas.</p>

            <dl class="mt-4 space-y-3">
                @foreach([
                    'Kelas aktif' => $skala['kelas'],
                    'Siswa aktif' => $skala['siswa'],
                    'Sesi absensi / 30 hari' => $skala['sesi_30h'],
                ] as $label => $nilai)
                    <div class="flex items-baseline justify-between border-b border-slate-100 pb-2 last:border-0">
                        <dt class="text-xs text-slate-500">{{ $label }}</dt>
                        <dd class="text-lg font-bold text-slate-900">{{ number_format($nilai) }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</div>
@endsection
