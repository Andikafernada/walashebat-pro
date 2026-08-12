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
        <div class="alert alert--danger mb-5">
            <div class="min-w-0">
                <p class="alert__title">Otomasi WhatsApp berhenti</p>
                <p class="alert__body mt-0.5 leading-snug">
                    Masa gratis Anda sudah berakhir. Magic link absensi, rekap ke grup orang tua,
                    dan balasan otomatis tidak lagi dikirim. Seluruh fitur lain tetap bisa dipakai
                    seperti biasa, dan data Anda utuh.
                </p>
                <a href="{{ route('subscription.index') }}" class="btn-danger btn-danger--sm mt-2.5">Perpanjang langganan</a>
            </div>
        </div>
    @else
        <div class="alert alert--warning mb-5 sm:items-center">
            <span class="kode kode--izin shrink-0">{{ $sisaHari }}</span>
            <p class="alert__body leading-snug">
                Otomasi WhatsApp aktif <strong class="font-semibold">{{ $sisaHari }} hari lagi</strong>
                (sampai {{ $penggunaAktif->subscription_ends_at->translatedFormat('d F Y') }}).
                <a href="{{ route('subscription.index') }}" class="font-semibold underline underline-offset-2">Perpanjang sekarang</a>
            </p>
        </div>
    @endif
@endif
