@extends('layouts.app')

@section('title', 'Langganan PRO Wali Kelas')

@section('content')
@php
    // Satu-satunya sumber angka di halaman ini; lihat PaymentProof::HARGA_BULANAN.
    $rpHarga = 'Rp '.number_format($hargaBulanan, 0, ',', '.');
@endphp
<div class="space-y-8 pb-16">

    <!-- HERO BANNER -->
    <div class="relative overflow-hidden rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-slate-800">
        <div class="relative z-10 space-y-3">
            <div class="inline-flex items-center gap-2 rounded-full bg-amber-500/10 px-3 py-1 text-xs font-bold text-amber-400 border border-amber-500/20">
                <span>👑 Paket Akun VIP PRO</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-white">
                Upgrade ke <span class="bg-clip-text text-transparent">Wali Kelas PRO</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-300 max-w-2xl">
                Nikmati akses otomatisasi grup WhatsApp, pengisian biodata mandiri 33 field, rekapitulasi presensi, dan cetak PDF laporan tanpa batas.
            </p>

            {{--
                Status dibaca dari masa aktif yang sesungguhnya. Sebelumnya
                lencana ini menampilkan `subscription_tier ?? 'PRO'` — sehingga
                akun yang kolomnya kosong, termasuk yang sudah kedaluwarsa,
                tetap terlihat berstatus PRO.
            --}}
            @php $otomasiAktif = $user->otomasiWhatsAppAktif(); @endphp
            <div class="pt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="text-slate-400">Status Akun:</span>
                    @if ($otomasiAktif)
                        <span class="font-bold text-emerald-400 uppercase tracking-wider bg-emerald-500/20 px-2 py-0.5 rounded-full border border-emerald-500/30">
                            {{ strtoupper($user->subscription_tier) }}
                        </span>
                    @else
                        <span class="font-bold text-rose-300 uppercase tracking-wider bg-rose-500/20 px-2 py-0.5 rounded-full border border-rose-500/30">
                            Berakhir
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-1.5">
                    <span class="text-slate-400">Otomasi WhatsApp:</span>
                    @if ($otomasiAktif)
                        <span class="font-semibold text-white">
                            aktif {{ $user->sisaHariOtomasi() }} hari lagi
                            ({{ $user->subscription_ends_at->translatedFormat('d M Y') }})
                        </span>
                    @else
                        <span class="font-semibold text-rose-300">nonaktif</span>
                    @endif
                </div>
            </div>

            @if($pendingProof)
                <div class="mt-3 rounded-xl bg-amber-500/20 border border-amber-400/40 p-3 text-xs text-amber-200">
                    <span class="font-bold block text-white">⏳ Konfirmasi Pembayaran Diproses</span>
                    <span>Admin sedang mengonfirmasi bukti transfer DANA Anda. Akun akan otomatis aktif.</span>
                </div>
            @endif
        </div>
    </div>

    <!-- PRICING & DANA PAYMENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- PRO PLAN CARD -->
        <div class="rounded-2xl border-2 border-indigo-600 bg-white p-6 space-y-4 relative flex flex-col justify-between">
            <div class="absolute -top-3 right-6 rounded-full px-3 py-0.5 text-[10px] font-semibold text-white uppercase shadow-sm">
                Spesial PRO
            </div>

            <div class="space-y-3">
                <span class="inline-block rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-bold text-indigo-700 uppercase tracking-wider">Akses Penuh 1 Bulan</span>
                <h3 class="text-2xl font-semibold text-slate-900">PRO Bulanan</h3>
                <div class="flex items-baseline gap-1">
                    <span class="text-4xl font-semibold text-indigo-600">{{ $rpHarga }}</span>
                    <span class="text-xs text-slate-500 font-medium">/ bulan</span>
                </div>
                <p class="text-xs text-slate-500">Harga sangat terjangkau hanya {{ $rpHarga }}/bulan untuk kemudahan pengelolaan kelas.</p>

                <ul class="space-y-2.5 text-xs text-slate-700 pt-3 border-t border-slate-100 font-medium">
                    <li class="flex items-center gap-2 text-emerald-700 font-bold">
                        <span>✓</span> Unlimited Kelas &amp; Siswa Tidak Terbatas
                    </li>
                    <li class="flex items-center gap-2 text-emerald-700 font-bold">
                        <span>✓</span> Otomatis Balas WA Grup Orang Tua (Filter Kata Kunci)
                    </li>
                    <li class="flex items-center gap-2">
                        <span>✓</span> Form Pengisian Biodata Mandiri 33 Field Siswa
                    </li>
                    <li class="flex items-center gap-2">
                        <span>✓</span> Evaluasi &amp; Jurnal Karakter P5 (6 Dimensi)
                    </li>
                    <li class="flex items-center gap-2">
                        <span>✓</span> Cetak Laporan PDF Eksekutif 1-Click
                    </li>
                    <li class="flex items-center gap-2">
                        <span>✓</span> Prioritas Support WhatsApp 24/7
                    </li>
                </ul>
            </div>
        </div>

        <!-- PAYMENT DETAILS & FORM (EXCLUSIVELY DANA) -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Pembayaran Resmi DANA</h3>
                    <p class="text-xs text-slate-500">Transfer {{ $rpHarga }} ke nomor DANA resmi di bawah</p>
                </div>
                <span class="px-3 py-1 rounded-lg bg-sky-50 text-sky-700 font-semibold text-xs border border-sky-200">
                    💙 ONLY DANA
                </span>
            </div>

            <!-- DANA INFO BOX -->
            <div class="rounded-xl p-5 text-white space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-sky-200">Nomor Akun DANA Resmi</span>
                    <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full">Terverifikasi</span>
                </div>

                <div>
                    <span class="text-xs text-sky-100 font-medium block">Nomor HP DANA:</span>
                    <p class="text-2xl font-semibold tracking-wider text-white mt-0.5">0838-1720-3455</p>
                </div>

                <div class="pt-2 border-t border-white/20 flex items-center justify-between text-xs">
                    <span class="text-sky-100">Jumlah Transfer:</span>
                    <strong class="text-amber-300 font-semibold text-sm">{{ $rpHarga }}</strong>
                </div>
            </div>

            <!-- Upload Form -->
            <form method="POST" action="{{ route('subscription.upload') }}" enctype="multipart/form-data" class="space-y-3 text-xs">
                @csrf

                <input type="hidden" name="plan_type" value="monthly">

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Paket Dibatalkan / Dipilih</label>
                    <input type="text" readonly value="PRO 1 Bulan — {{ $rpHarga }}" class="h-9 w-full rounded-xl border border-slate-200 bg-slate-100 px-3 text-xs font-bold text-slate-800">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Akun / Pengirim DANA *</label>
                    <input type="text" name="sender_name" value="{{ old('sender_name') }}" required placeholder="cth: Budi Santoso" class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800 focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Metode Pembayaran</label>
                    <input type="text" readonly name="bank_name" value="DANA (083817203455)" class="h-9 w-full rounded-xl border border-slate-200 bg-slate-100 px-3 text-xs font-bold text-sky-700">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Foto / Tangkapan Layar Bukti DANA *</label>
                    <input type="file" name="proof_image" required accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100">
                </div>

                <button type="submit" class="h-10 w-full rounded-xl hover: hover: text-white font-bold transition-colors">
                    📤 Unggah Bukti Transfer DANA ({{ $rpHarga }})
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
