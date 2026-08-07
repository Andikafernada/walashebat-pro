{{--
    Pemberitahuan masa otomasi WhatsApp.

    Ditempatkan di layout, bukan di halaman tertentu, supaya wali kelas melihat
    peringatannya sebelum masa habis — bukan baru menyadarinya pada pagi hari
    ketika absensi tidak terkirim dan kelas sudah dimulai.

    Sengaja diam saat masa masih panjang: spanduk yang selalu tampil akan
    berhenti dibaca, lalu tidak berguna justru di minggu terakhir.
--}}
@php
    $penggunaAktif = auth()->user();
    $sisaHari = $penggunaAktif?->sisaHariOtomasi() ?? 0;
    $otomasiAktif = $penggunaAktif?->otomasiWhatsAppAktif() ?? false;
    // Admin tidak berlangganan; menampilkan hitung mundur padanya hanya bising.
    $perluDitampilkan = $penggunaAktif && ! $penggunaAktif->isAdmin()
        && (! $otomasiAktif || $sisaHari <= 14);
@endphp

@if ($perluDitampilkan)
    @if (! $otomasiAktif)
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4">
            <div class="flex items-start gap-3">
                <svg class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-rose-900">Otomasi WhatsApp berhenti</p>
                    <p class="mt-0.5 text-xs text-rose-800 leading-snug">
                        Masa gratis Anda sudah berakhir. Magic link absensi, rekap ke grup orang tua,
                        dan balasan otomatis tidak lagi dikirim. Seluruh fitur lain tetap bisa dipakai
                        seperti biasa, dan data Anda utuh.
                    </p>
                    <a href="{{ route('subscription.index') }}" class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-700 transition">
                        Perpanjang langganan
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3.5">
            <div class="flex items-center gap-3">
                <svg class="h-4.5 w-4.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-xs text-amber-900 leading-snug">
                    Otomasi WhatsApp aktif <strong>{{ $sisaHari }} hari lagi</strong>
                    (sampai {{ $penggunaAktif->subscription_ends_at->translatedFormat('d F Y') }}).
                    <a href="{{ route('subscription.index') }}" class="font-bold underline underline-offset-2">Perpanjang sekarang</a>
                </p>
            </div>
        </div>
    @endif
@endif
