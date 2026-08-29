@extends('layouts.app')

@section('title', 'WhatsApp Gateway')

@push('styles')
<style>
/* Fullscreen QR Modal for PWA */
.qr-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,0.95);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
}
.qr-modal-overlay.active {
    opacity: 1;
    pointer-events: auto;
}
.qr-modal-close {
    position: absolute;
    top: max(20px, env(safe-area-inset-top));
    right: 20px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(255,255,255,0.15);
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endpush

@section('content')
<script src="/vendor/qrcode.min.js?v=1.4.4"></script>

<!-- Fullscreen QR Modal for PWA -->
<div class="qr-modal-overlay" id="qrModal" x-show="qr" x-cloak>
    <button class="qr-modal-close" onclick="closeQrModal()" aria-label="Tutup">✕</button>
    <div class="text-center text-white space-y-4 max-w-xs mx-auto">
        <p class="text-xs uppercase tracking-wider opacity-70 font-semibold">Scan dengan WhatsApp</p>
        <div class="bg-white p-4 rounded-2xl inline-block shadow-2xl">
            <canvas id="wa-qr-canvas-fullscreen"></canvas>
        </div>
        <p class="text-sm opacity-80">Arahkan kamera HP ke kode QR di atas</p>
        <p class="text-xs opacity-50">Halaman akan terhubung otomatis setelah dipindai</p>
    </div>
</div>

<!-- Pairing Code Modal for HP-only users -->
<div class="qr-modal-overlay" id="pairingModal" x-show="pairingCode" x-cloak>
    <button class="qr-modal-close" onclick="closePairingModal()" aria-label="Tutup">✕</button>
    <div class="text-center text-white space-y-6 max-w-xs mx-auto">
        <div>
            <p class="text-xs uppercase tracking-wider opacity-70 font-semibold">Masukkan kode ini di WhatsApp</p>
            <h2 class="text-2xl font-bold mt-2 tracking-widest" x-text="pairingCode"></h2>
        </div>
        <div class="bg-white/10 backdrop-blur rounded-2xl p-4 text-left">
            <p class="text-xs font-semibold mb-2">Langkah:</p>
            <ol class="text-xs space-y-1 opacity-90">
                <li>1. Buka WhatsApp di HP ini</li>
                <li>2. <strong>⋮ Setelan</strong> → <strong>Perangkat Tertaut</strong></li>
                <li>3. Ketuk <strong>Tautkan Perangkat</strong></li>
                <li>4. Pilih <strong>"Tautkan dengan nomor telepon"</strong></li>
                <li>5. Masukkan kode di atas</li>
            </ol>
        </div>
        <p class="text-xs opacity-50">Kode berlaku beberapa menit</p>
    </div>
</div>

<div class="space-y-4 lg:space-y-6 pb-12 lg:pb-6"
     x-data="waSession({
         connected: {{ auth()->user()->whatsappConnected() ? 'true' : 'false' }},
         statusUrl: '{{ route('whatsapp.status') }}'
     })">

    <!-- Page Header -->
    <div class="page-header">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-lg lg:text-xl font-semibold tracking-tight text-slate-900">
                    WhatsApp Gateway
                </h1>
                <p class="mt-1 text-xs lg:text-sm text-slate-500">
                    Tautkan nomor untuk mengirim pesan &amp; balasan otomatis
                </p>
            </div>
            <!-- WhatsApp Icon -->
            <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-2xl bg-emerald-100 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 lg:w-7 lg:h-7 text-emerald-600" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- STATUS CARD - Mobile Optimized -->
    <div class="bg-white rounded-2xl lg:rounded-lg border border-slate-200 overflow-hidden shadow-xs">
        <!-- Status Header -->
        <div class="flex items-center justify-between p-4 lg:p-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-xl flex items-center justify-center"
                     :class="status === 'connected' ? 'bg-emerald-100' : (status === 'pairing' ? 'bg-amber-100' : 'bg-rose-100')">
                    <span class="text-lg lg:text-xl" x-text="status === 'connected' ? '✓' : (status === 'pairing' ? '⏳' : '⚠')"></span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900" x-text="label()"></p>
                    <p class="text-xs text-slate-500">{{ auth()->user()->whatsapp_number ?: 'Nomor belum diatur' }}</p>
                </div>
            </div>
            <span class="hidden lg:inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                  :class="status === 'connected' ? 'bg-emerald-100 text-emerald-800' : (status === 'pairing' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')"
                  x-text="status === 'connected' ? 'Tersambung' : (status === 'pairing' ? 'Menunggu...' : 'Terputus')">
            </span>
        </div>

        @if(!auth()->user()->whatsappConnected())
            <!-- Not Connected State -->
            <div class="p-4 lg:p-5 space-y-4">
                <!-- Desktop Required Notice -->
                <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="text-xs">
                            <p class="font-semibold text-indigo-900">Pilih metode penautan:</p>
                            <p class="text-indigo-700 mt-1">
                                <strong>QR Code</strong> - Butuh komputer/laptop untuk buka halaman ini.<br>
                                <strong>Kode 8 Digit</strong> - Bisa dari HP yang sama (buka di tab baru).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Two Methods -->
                <div class="grid gap-3">
                    <!-- QR Code Method -->
                    <form method="POST" action="{{ route('whatsapp.pair') }}" class="contents">
                        @csrf
                        <input type="hidden" name="metode" value="qr">
                        <button type="submit" class="h-auto p-4 rounded-xl border-2 border-emerald-200 bg-white hover:bg-emerald-50 transition-colors text-left">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Pindai QR Code</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Butuh komputer/laptop untuk buka halaman ini</p>
                                </div>
                            </div>
                        </button>
                    </form>

                    <!-- Pairing Code Method -->
                    <form method="POST" action="{{ route('whatsapp.pair') }}" class="contents">
                        @csrf
                        <input type="hidden" name="metode" value="kode">
                        <button type="submit" class="h-auto p-4 rounded-xl border-2 border-slate-200 bg-white hover:bg-slate-50 transition-colors text-left">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Kode 8 Digit</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Bisa dari HP yang sama - buka di tab baru</p>
                                </div>
                            </div>
                        </button>
                    </form>
                </div>

                <!-- Instructions based on method -->
                <div class="bg-amber-50 rounded-xl p-3 text-xs text-amber-900">
                    <p class="font-semibold mb-2">Tips:</p>
                    <ul class="space-y-1">
                        <li>• <strong>QR Code</strong>: Buka halaman ini di komputer, pindai dengan HP</li>
                        <li>• <strong>Kode 8 Digit</strong>: Buka di tab baru di HP yang sama, masukkan kode di WhatsApp</li>
                        <li>• Tidak punya komputer? <strong>Pinjam HP teman 5 detik</strong> untuk scan QR</li>
                    </ul>
                </div>
            </div>
        @else
            <!-- Connected State -->
            <div class="p-4 lg:p-5 bg-emerald-50/50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-500 text-white text-xs">✓</span>
                        <span class="text-sm font-semibold text-emerald-800">WhatsApp Tersambung</span>
                    </div>
                    <form method="POST" action="{{ route('whatsapp.disconnect') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Putuskan koneksi WhatsApp?')"
                                class="text-xs font-medium text-slate-500 hover:text-rose-600 transition-colors">
                            Putus
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <!-- QR CODE CARD - Improved for Mobile -->
    <div class="bg-white rounded-2xl lg:rounded-lg border-2 border-emerald-500 overflow-hidden shadow-sm"
         x-show="status !== 'connected' && qr" x-cloak>
        <div class="p-4 lg:p-5 text-center border-b border-emerald-100 bg-emerald-50/50">
            <div class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                QR Aktif
            </div>
            <h3 class="text-sm lg:text-base font-semibold text-slate-900">Pindai dengan WhatsApp</h3>
        </div>

        <!-- QR Display -->
        <div class="p-4 lg:p-6 flex flex-col items-center">
            <!-- Tap to expand on mobile -->
            <button onclick="openQrModal()" class="block lg:hidden mb-3">
                <div class="p-3 bg-white border-2 border-slate-900 rounded-xl shadow-md">
                    <canvas id="wa-qr-canvas-dynamic" class="max-w-[200px]"></canvas>
                </div>
                <p class="mt-2 text-xs text-slate-500 flex items-center gap-1.5 justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    Ketuk untuk tampilan penuh
                </p>
            </button>
            <!-- Normal display on desktop -->
            <div class="hidden lg:block p-4 bg-white border-2 border-slate-900 rounded-xl shadow-lg">
                <canvas id="wa-qr-canvas-dynamic"></canvas>
            </div>

            <!-- Expand hint for mobile -->
            <p class="lg:hidden mt-3 text-xs text-slate-500 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                Ketuk QR untuk tampilan penuh
            </p>

            <!-- Loading indicator -->
            <p class="hidden lg:block mt-4 text-xs text-slate-500 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Menunggu pemindaian...
            </p>
        </div>
    </div>

    <!-- AUTOREPLY GROUPS (When Connected) -->
    @if(auth()->user()->whatsappConnected())
        <div class="bg-white rounded-2xl lg:rounded-lg border border-slate-200 overflow-hidden shadow-xs"
             x-data="autoreply({
                grupUrl: '{{ route('whatsapp.groups') }}',
                terpilih: {{ json_encode($autoreply['groups'] ?? []) }},
                label: {{ json_encode($grupLabels ?? []) }},
             })"
             x-init="awal()">

            <div class="p-4 lg:p-5 border-b border-slate-100">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Pilih &amp; Kelola Grup WhatsApp yang Dibalas Otomatis</h3>
                            <p class="text-xs text-slate-500"><span x-text="terpilih.length">0</span> Grup Terkoneksi</p>
                        </div>
                    </div>
                    <template x-if="mode === 'ringkas'">
                        <button type="button" @click="bukaPemilih()"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors">
                            Ubah
                        </button>
                    </template>
                </div>

                @php
                    $gatewayTerjangkau = blank($autoreply['error'] ?? null);
                @endphp

                @unless ($gatewayTerjangkau)
                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-900">
                        <p class="font-bold">⚠️ Status: Tidak diketahui (Gateway tidak terjangkau)</p>
                        <p class="mt-1 text-[11px]">Pengaturan Anda tidak hilang, coba muat ulang dalam 45 detik.</p>
                    </div>
                @endunless
            </div>

            <form method="POST" action="{{ route('whatsapp.autoreply') }}" class="p-4 lg:p-5 space-y-4">
                @csrf
                <input type="hidden" name="enabled" value="0">

                <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 cursor-pointer hover:bg-slate-100 transition-colors">
                    <input type="checkbox" name="enabled" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                           @checked($autoreply['enabled'] ?? false)>
                    <div class="text-xs">
                        <span class="font-semibold text-slate-800">Balasan Otomatis</span>
                        <span class="block text-slate-500 mt-0.5">Membalas pesan izin/sakit dari grup orang tua</span>
                    </div>
                </label>

                <!-- Groups List (Ringkasan) -->
                <template x-if="mode === 'ringkas'">
                    <div class="space-y-2">
                        <template x-for="id in terpilih" :key="id">
                            <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/50 border border-emerald-100">
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-slate-900 truncate" x-text="namaGrup(id)"></p>
                                    <p class="text-[10px] text-slate-500" x-text="ketGrup(id)"></p>
                                </div>
                                <span class="w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center text-white text-[10px] shrink-0 ml-2">✓</span>
                            </div>
                        </template>
                        <p x-show="terpilih.length === 0" class="text-xs text-slate-500 text-center py-4">
                            Belum ada grup dipilih. Ketuk "Ubah" untuk memilih.
                        </p>
                    </div>
                </template>

                <!-- Groups Selection (Pemilih) -->
                <template x-if="mode === 'pilih'">
                    <div class="space-y-3">
                        <div class="flex gap-2">
                            <input type="text" x-model="cari" placeholder="Cari nama grup WhatsApp..."
                                   class="flex-1 h-10 rounded-lg border border-slate-200 bg-white px-3 text-xs focus:border-indigo-500 focus:outline-none">
                            <button type="button" @click="muat(true)" :disabled="memuat"
                                    class="h-10 px-3 rounded-lg border border-slate-200 bg-white text-xs font-medium hover:bg-slate-50 disabled:opacity-50">
                                <span x-show="!memuat">↻</span>
                                <span x-show="memuat" class="animate-spin">⟳</span>
                            </button>
                        </div>

                        <div class="max-h-64 overflow-y-auto space-y-1.5">
                            <template x-for="g in tersaring" :key="g.id">
                                <label class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-indigo-50/50 cursor-pointer transition-colors"
                                       :class="terpilih.includes(g.id) ? 'bg-indigo-50/50' : ''">
                                    <input type="checkbox" name="groups[]" :value="g.id" x-model="terpilih"
                                           class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-medium text-slate-900 truncate" x-text="g.subject"></p>
                                        <p class="text-[10px] text-slate-500" x-text="g.peserta + ' anggota'"></p>
                                    </div>
                                    <button type="button" @click.stop.prevent="periksa(g.id)" class="px-2 py-1 text-[10px] font-bold rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors">
                                        Cek status
                                    </button>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="w-full lg:w-auto px-6 h-10 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors shadow-sm">
                        Simpan
                    </button>
                </div>
            </form>
    @endif


    {{--
        PENGINGAT IURAN BULANAN (SPP) — per kelas, bukan per guru.

        Ditaruh di halaman ini, bukan di Buku Kas, karena targetnya grup
        WhatsApp orang tua: seekor dengan seluruh pengaturan WA lain. Kelas
        ajar tidak muncul di sini (dikecualikan di controller) — iuran adalah
        urusan wali kelasnya.

        Isinya teks bebas dan TIDAK menyebut siapa yang belum membayar.
        Menyebut nama anak yang menunggak di grup yang dibaca seluruh orang
        tua adalah keputusan yang jauh lebih besar daripada teknisnya.
    --}}
    @if ($kelasWali->isNotEmpty())
        @foreach ($kelasWali as $kelas)
            <div class="card space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-200 pb-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded bg-emerald-100 text-emerald-700 shrink-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Pengingat Iuran Bulanan — {{ $kelas->name }}</h3>
                        <p class="text-xs text-slate-500">Terkirim otomatis ke grup WhatsApp orang tua kelas ini</p>
                    </div>
                </div>

                @if (! $kelas->parent_group_wa)
                    <p class="rounded border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        Grup WhatsApp orang tua kelas <strong>{{ $kelas->name }}</strong> belum diatur di halaman Kelas, jadi pengingat belum bisa dikirim.
                    </p>
                @else
                    <form method="POST" action="{{ route('classes.cashbook.pengingat', $kelas) }}" class="space-y-3">
                        @csrf

                        <label class="flex items-center gap-2.5 rounded border border-slate-200 bg-slate-50 p-3">
                            <input type="hidden" name="spp_pengingat_aktif" value="0">
                            <input type="checkbox" name="spp_pengingat_aktif" value="1"
                                   @checked($kelas->spp_pengingat_aktif)
                                   class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-semibold text-slate-800">Kirim pengingat tiap bulan</span>
                        </label>

                        <div>
                            <label for="spp_pengingat_tanggal_{{ $kelas->id }}" class="block eyebrow mb-1">Tanggal kirim</label>
                            <input id="spp_pengingat_tanggal_{{ $kelas->id }}" type="number" name="spp_pengingat_tanggal" min="1" max="31"
                                   value="{{ old('spp_pengingat_tanggal', $kelas->spp_pengingat_tanggal) }}"
                                   class="h-9 w-24 rounded border border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-800 focus:border-emerald-500 focus:outline-none">
                            <span class="text-[11px] text-slate-500">tiap bulan, pukul 07.00</span>
                            <p class="mt-1 text-[11px] text-slate-400">Bila bulannya lebih pendek, dikirim pada hari terakhir bulan itu.</p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label for="spp_pengingat_teks_{{ $kelas->id }}" class="block eyebrow mb-1">Isi pesan</label>
                                <span class="text-[11px] font-semibold text-emerald-700">Tag: {nama_kelas}, {bulan}, {tahun}, {wali_kelas}</span>
                            </div>
                            {{--
                                Pesan bawaan ditampilkan APA ADANYA di kotaknya,
                                bukan disembunyikan di balik placeholder "kosongkan
                                untuk bawaan" — guru berhak melihat persis apa yang
                                akan dikirim sebelum menyalakannya.
                            --}}
                            <textarea id="spp_pengingat_teks_{{ $kelas->id }}" name="spp_pengingat_teks" rows="7" maxlength="1000"
                                      class="form-textarea text-xs font-mono">{{ old('spp_pengingat_teks', $kelas->spp_pengingat_teks ?: \App\Console\Commands\KirimPengingatSpp::TEKS_BAWAAN) }}</textarea>
                        </div>

                        @if ($kelas->spp_pengingat_terkirim_pada)
                            <p class="text-[11px] text-slate-500">
                                Terakhir terkirim {{ $kelas->spp_pengingat_terkirim_pada->translatedFormat('d F Y') }}.
                            </p>
                        @endif

                        <button type="submit" class="h-9 w-full rounded bg-emerald-600 text-xs font-semibold text-white transition-colors hover:bg-emerald-700">
                            Simpan pengaturan pengingat
                        </button>
                    </form>
                @endif
            </div>
        @endforeach
    @endif

    <!-- CUSTOM KEYWORDS & TEMPLATE FORM -->
    <div class="card space-y-6">
        <div class="border-b border-slate-200 pb-3">
            <h3 class="text-base font-semibold text-slate-900">Klasifikasi Kata Kunci &amp; Templat Balasan Guru</h3>
            <p class="mt-1 text-sm text-slate-500">Tentukan sendiri kata kunci yang dijadikan patokan balasan otomatis. Pesan di luar kata kunci ini <strong>TIDAK AKAN DIBALAS</strong>.</p>
        </div>

        <form method="POST" action="{{ route('whatsapp.template.save') }}" class="space-y-6 text-xs">
            @csrf

            <!-- KEYWORDS CONFIGURATION -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-indigo-50/50 p-4 rounded border border-indigo-100">
                {{--
                    Kolom ini TAMBAHAN, bukan daftar utama.

                    Dulu kolomnya diisi otomatis dengan daftar contoh, dan
                    apa pun yang tersimpan MENGGANTIKAN daftar bawaan di
                    gateway. Akibatnya guru yang cuma membuka halaman ini lalu
                    menekan Simpan diam-diam mematikan puluhan kata bawaan —
                    "muntah", "takziyah", "ke luar kota", dan seluruh kata
                    Sunda berhenti dikenali tanpa ada yang tahu sebabnya.

                    Sekarang bawaan selalu berlaku dan isian di sini menambah,
                    jadi kolomnya sengaja DIBIARKAN KOSONG.
                --}}
                <div class="md:col-span-2 flex items-start gap-2 text-[11px] text-indigo-900">
                    <span>ℹ </span>
                    <p>Kata umum seperti <span class="font-mono">izin, sakit, demam, acara, takziyah</span> — termasuk kata Sunda seperti <span class="font-mono">gering, muriang, teu tiasa</span> — <strong>sudah dikenali otomatis</strong>. Kolom di bawah hanya untuk menambah kata khas daerah atau kebiasaan grup Anda. Boleh dikosongkan.</p>
                </div>

                <div class="space-y-1.5">
                    <label class="block font-semibold text-indigo-950 uppercase tracking-wider text-[11px]">1. Tambahan Kata Kunci IZIN <span class="text-indigo-600 font-normal lowercase">(pisahkan koma, boleh kosong)</span></label>
                    <input type="text" name="wa_permission_keywords"
                           value="{{ auth()->user()->wa_permission_keywords }}"
                           placeholder="mis. wangsul, ngalayad..."
                           class="w-full rounded border border-indigo-200 bg-white p-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none font-mono">
                    <p class="text-[10px] text-slate-500">Ditambahkan ke kata bawaan, bukan menggantikannya.</p>
                </div>

                <div class="space-y-1.5">
                    <label class="block font-semibold text-indigo-950 uppercase tracking-wider text-[11px]">2. Tambahan Kata Kunci SAKIT <span class="text-indigo-600 font-normal lowercase">(pisahkan koma, boleh kosong)</span></label>
                    <input type="text" name="wa_sick_keywords"
                           value="{{ auth()->user()->wa_sick_keywords }}"
                           placeholder="mis. meriang, nyeri huntu..."
                           class="w-full rounded border border-indigo-200 bg-white p-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none font-mono">
                    <p class="text-[10px] text-slate-500">Ditambahkan ke kata bawaan, bukan menggantikannya.</p>
                </div>
            </div>

            {{--
                Dua kolom ini dulu satu kolom yang DITULIS ke basis data lalu
                tidak pernah dibaca siapa pun — gateway bahkan tidak mengenal
                kata "template". Guru menyunting balasannya, menekan Simpan,
                melihat "berhasil disimpan", dan tidak ada yang berubah.

                Teks bawaannya juga menjanjikan "telah tercatat dalam sistem
                absensi", padahal balasan otomatis memang tidak menyentuh
                absensi sama sekali. Janji itu ikut dihapus.
            --}}
            <div class="space-y-2 border-t border-slate-200 pt-4">
                <div class="flex items-start justify-between gap-3">
                    <label class="block font-semibold text-slate-800 uppercase tracking-wider">Variasi Balasan Anda Sendiri</label>
                    <span class="text-[11px] font-semibold text-indigo-600 shrink-0">Tag: {nama}</span>
                </div>
                @php
                    $kelasBerlink = $kelasWali->filter(fn ($k) => filled($k->parent_group_wa));
                @endphp

                <div class="flex items-start gap-2 text-[11px] text-slate-600 bg-slate-50 rounded p-2.5">
                    <span> </span>
                    <p>Balasan sudah berganti-ganti otomatis dari 8 kalimat bawaan, jadi kolom ini <strong>boleh dikosongkan</strong>. Isi bila ingin memakai kalimat Anda sendiri — <strong>satu kalimat per baris</strong>, boleh lebih dari satu untuk tetap bergilir. Begitu diisi, balasan <strong>hanya memakai kalimat Anda</strong>, kalimat bawaan tidak lagi ikut dipilih. Tulis <span class="font-mono">{nama}</span> untuk menyebut nama anaknya.
                        @if($kelasBerlink->isNotEmpty())
                            Tautan formulir izin/sakit kelasnya <strong>selalu ditambahkan otomatis</strong> di baris terakhir balasan — tidak perlu, dan tidak perlu ditulis ulang di sini.
                        @else
                            Tautan formulir izin/sakit akan otomatis ditambahkan begitu grup WhatsApp orang tua sudah dipilih untuk kelasnya (halaman Kelas → Bagikan Link Izin/Sakit).
                        @endif
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider">Untuk kabar IZIN</label>
                        <textarea name="wa_permission_template" rows="4"
                                  placeholder="Nuhun infona Bu, mugia urusan {nama} lancar.&#10;Baik Pak, izin {nama} sudah saya terima."
                                  class="form-textarea text-xs">{{ old('wa_permission_template', auth()->user()->wa_permission_template) }}</textarea>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-semibold text-slate-700 uppercase tracking-wider">Untuk kabar SAKIT</label>
                        <textarea name="wa_sick_template" rows="4"
                                  placeholder="Mugia {nama} enggal damang nya Bu.&#10;Semoga {nama} lekas sehat, salam dari saya."
                                  class="form-textarea text-xs">{{ old('wa_sick_template', auth()->user()->wa_sick_template) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- TEMPLATE 2: MAGIC LINK ABSENSI BROADCAST -->
            <div class="space-y-2 border-t border-slate-200 pt-4">
                <div class="flex items-center justify-between">
                    <label class="block font-semibold text-slate-800 uppercase tracking-wider">Format Broadcast Magic Link Presensi Kelas</label>
                    <span class="text-[11px] font-semibold text-indigo-600">Tag: {nama_kelas}, {magic_link}, {pin}, {jam_berlaku}</span>
                </div>
                <textarea name="wa_magic_link_template" rows="5"
                          placeholder="Ketikkan templat pesan magic link..."
                          class="form-textarea text-xs">{{ auth()->user()->wa_magic_link_template ?: "*Wali Kelas Hebat - Absensi Kelas {nama_kelas}*\n\nPetugas absensi, silakan isi kehadiran hari ini melalui tautan berikut:\n{magic_link}\n\nPIN Harian: *{pin}*\nBerlaku s/d: {jam_berlaku} WIB\n\nJangan bagikan PIN ini ke siapa pun." }}</textarea>
            </div>

            <div class="flex items-center justify-end border-t border-slate-200 pt-4">
                <button type="submit" class="btn-primary">Simpan Kata Kunci &amp; Templat WhatsApp</button>
            </div>
        </form>
    </div>

</div>

<script>
    function waSession(cfg) {
        return {
            status: cfg.connected ? 'connected' : 'disconnected',
            qr: @json(session('wa_qr')),
            pairingCode: @json(session('wa_pairing_code')),
            timer: null,
            lastQrRendered: null,
            init() {
                if (this.qr) this.renderQr(this.qr);
                if (this.pairingCode) openPairingModal();

                // Polling hanya berguna selama menunggu guru memindai QR/pairing.
                // Kalau sesi SUDAH tersambung saat halaman dibuka, tidak ada
                // yang perlu ditunggu.
                if (this.status === 'connected') return;

                this.poll();
                this.timer = setInterval(() => this.poll(), 2500);
            },
            label() {
                if (this.status === 'connected') return 'Tersambung ✓';
                if (this.status === 'pairing') return 'Menunggu Pemindaian QR';
                return 'Belum Tersambung';
            },
            renderQr(qrStr) {
                if (!qrStr || this.lastQrRendered === qrStr) return;
                this.lastQrRendered = qrStr;
                this.$nextTick(() => {
                    this._drawQr('wa-qr-canvas-dynamic', qrStr, 260);
                    this._drawQr('wa-qr-canvas-fullscreen', qrStr, 320);
                });
            },
            _drawQr(canvasId, qrStr, maxSize) {
                const canvas = document.getElementById(canvasId);
                if (!canvas || typeof qrcode !== 'function') return;
                const typeNumber = 0;
                const errorCorrectionLevel = 'M';
                const qr = qrcode(typeNumber, errorCorrectionLevel);
                qr.addData(qrStr);
                qr.make();
                const cellSize = Math.floor(maxSize / qr.getModuleCount());
                const qrSize = cellSize * qr.getModuleCount();
                canvas.width = qrSize;
                canvas.height = qrSize;
                canvas.style.width = qrSize + 'px';
                canvas.style.height = qrSize + 'px';
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, qrSize, qrSize);
                ctx.fillStyle = '#0f172a';
                for (let row = 0; row < qr.getModuleCount(); row++) {
                    for (let col = 0; col < qr.getModuleCount(); col++) {
                        if (qr.isDark(row, col)) {
                            ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize);
                        }
                    }
                }
            },
            async poll() {
                try {
                    const res = await fetch(cfg.statusUrl, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (data.status) {
                        this.status = data.status;
                        if (data.qr) {
                            this.qr = data.qr;
                            this.renderQr(data.qr);
                        } else {
                            this.qr = null;
                        }
                        // QR baru dipindai atau pairing code terbit
                        if (data.status === 'connected') {
                            clearInterval(this.timer);
                            this.timer = null;
                            window.location.reload();
                        }
                        if (data.pairing_code && !this.pairingCode) {
                            this.pairingCode = data.pairing_code;
                            openPairingModal();
                        }
                    }
                } catch (e) {}
            }
        }
    }

    function openQrModal() {
        const modal = document.getElementById('qrModal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function openPairingModal() {
        const modal = document.getElementById('pairingModal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closePairingModal() {
        const modal = document.getElementById('pairingModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    function closeQrModal() {
        const modal = document.getElementById('qrModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeQrModal();
            closePairingModal();
        }
    });

    function autoreply(cfg) {
        return {
            grup: [],
            memuat: false,
            galat: '',
            catatan: '',
            dariCache: false,
            memeriksa: '',
            hasilCek: null,
            cari: '',
            terpilih: cfg.terpilih || [],

            // Nama grup yang sudah diingat server, dipakai agar pilihan
            // tersimpan bisa ditampilkan tanpa memindai. Berkunci JID.
            label: cfg.label || {},

            /*
             * 'ringkas' = tampilkan pilihan tersimpan, jangan memindai.
             * 'pilih'   = daftar grup lengkap untuk dipilih, perlu memindai.
             *
             * Guru yang sudah menyimpan pilihannya mulai dari 'ringkas'; yang
             * belum punya pilihan tidak akan bisa memilih apa pun tanpa daftar,
             * jadi baginya pemindaian memang tak terhindarkan.
             */
            mode: (cfg.terpilih || []).length > 0 ? 'ringkas' : 'pilih',
            sudahMuat: false,

            awal() {
                if (this.mode === 'pilih') this.muat();
            },

            /*
             * Daftar hasil pindai didahulukan karena paling baru; peta label
             * dari server menjadi cadangannya saat belum ada pemindaian sama
             * sekali. Kalau keduanya tidak tahu, JID mentah tetap ditampilkan —
             * lebih jujur daripada kosong.
             */
            infoGrup(id) {
                return this.grup.find((g) => g.id === id) || this.label[id] || null;
            },

            namaGrup(id) {
                const g = this.infoGrup(id);
                return g ? g.subject : id;
            },

            ketGrup(id) {
                const g = this.infoGrup(id);
                return g
                    ? g.peserta + ' Anggota'
                    : 'Nama grup belum tersimpan — tekan “Ubah pilihan grup” untuk memuatnya';
            },

            /*
             * Satu-satunya jalan dari 'ringkas' ke pemindaian, dan hanya sekali
             * per kunjungan: setelah daftarnya ada, menutup lalu membuka lagi
             * tidak menghubungi WhatsApp untuk kedua kalinya. Tombol
             * "Refresh Grup" tetap tersedia bila guru memang menambah grup baru.
             */
            async bukaPemilih() {
                this.mode = 'pilih';
                if (! this.sudahMuat) await this.muat();
            },

            get tersaring() {
                const kata = this.cari.trim().toLowerCase();
                if (!kata) return this.grup;

                return this.grup.filter((g) =>
                    g.subject.toLowerCase().includes(kata) || this.terpilih.includes(g.id)
                );
            },
            /*
             * Memeriksa apakah balasan otomatis akan benar-benar jalan untuk
             * satu grup. Tidak mengirim pesan apa pun — berbeda dari uji kirim.
             */
            async periksa(idGrup) {
                this.memeriksa = idGrup;
                this.hasilCek = null;
                try {
                    const res = await fetch('{{ route('whatsapp.autoreply.check') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ group_id: idGrup }),
                    });
                    const data = await res.json();

                    const nama = this.namaGrup(idGrup);

                    if (!data.ok) {
                        this.hasilCek = { grup: nama, siap: false, pesan: data.pesan || 'Pemeriksaan gagal.', syarat: null };
                        return;
                    }

                    this.hasilCek = {
                        grup: nama,
                        siap: data.siap,
                        syarat: data.syarat,
                        jam: data.jam,
                        kuota: data.kuota_harian,
                        terpakai: data.terpakai_hari_ini,
                        pesan: null,
                    };
                } catch (e) {
                    console.error('[WA] periksa() gagal:', e);
                    this.hasilCek = { grup: idGrup, siap: false, pesan: e.message || 'Tidak bisa联系的server.', syarat: null };
                } finally {
                    this.memeriksa = '';
                }
            },

            async muat(paksaSegar = false) {
                this.memuat = true;
                this.galat = '';
                try {
                    const url = cfg.grupUrl + (paksaSegar ? '?refresh=1' : '');
                    const res = await fetch(url, { headers: { Accept: 'application/json' } });
                    const data = await res.json();

                    // data.ok membedakan "pengambilan gagal" dari "memang tidak
                    // punya grup" — keduanya sama-sama berupa daftar kosong.
                    if (!data.ok) {
                        this.galat = data.warning || 'Gateway tidak merespons dengan benar.';
                        this.grup = [];
                        return;
                    }

                    // ok=true tapi ada warning berarti muat ulang gagal dan yang
                    // tampil adalah data terakhir — daftarnya tetap dipakai.
                    this.catatan = data.warning || '';
                    this.dariCache = data.cached === true;
                    this.grup = (data.groups || []).sort((a, b) => a.peserta - b.peserta);
                    this.sudahMuat = true;
                } catch (e) {
                    console.error('Gagal memuat grup:', e);
                    this.galat = 'Tidak bisa menghubungi server. Periksa koneksi lalu coba lagi.';
                    this.grup = [];
                } finally {
                    this.memuat = false;
                }
            }
        }
    }
</script>
@endsection
