@extends('layouts.guest')

@section('title', 'Syarat & Ketentuan Layanan - WaliKelas Pro')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-3xl border border-emerald-150 shadow-sm p-6 sm:p-10 space-y-6">
        
        <div class="border-b border-emerald-100 pb-6 flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white font-black text-xl shadow-xs">
                📜
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Syarat & Ketentuan Layanan (Terms of Service)</h1>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Terakhir diperbarui: 1 September 2026 • WaliKelas Pro Platform</p>
            </div>
        </div>

        <div class="prose prose-slate max-w-none text-xs sm:text-sm leading-relaxed text-slate-700 space-y-5">
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>1. Ketentuan Penggunaan</span>
                </h2>
                <p>
                    Dengan mendaftar dan menggunakan aplikasi <strong>WaliKelas Pro</strong>, Anda menyatakan bahwa Anda adalah pendidik, wali kelas, pengurus sekolah, atau orang tua yang berwenang, dan menyetujui untuk menggunakan platform ini secara sah sesuai norma etika pendidikan dan regulasi perlindungan data yang berlaku di Republik Indonesia.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>2. Akun & Tanggung Jawab Keamanan</span>
                </h2>
                <p>
                    Pengguna bertanggung jawab penuh atas kerahasiaan kata sandi akun, token sesi WhatsApp, dan PIN presensi yang dibagikan kepada peserta didik. Setiap aktivitas yang terjadi di bawah akun Anda menjadi tanggung jawab pemilik akun.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>3. Layanan Berlangganan & Pembayaran</span>
                </h2>
                <p>
                    Fitur dasar dapat digunakan secara gratis. Fitur premium (seperti otomatisasi bot WhatsApp tanpa batas, modul jurnal AI, deteksi EWS, dan cetak kartu QR massal) memerlukan langganan paket PRO aktif yang dibayarkan melalui saluran pembayaran resmi yang tersedia di aplikasi.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>4. Batasan Tanggung Jawab</span>
                </h2>
                <p>
                    WaliKelas Pro berupaya maksimal menjaga ketersediaan sistem 24/7 (SLA 99.9%). Namun kami tidak bertanggung jawab atas gangguan konektivitas jaringan pihak ketiga (seperti pemadaman server WhatsApp Meta atau gangguan ISP lokal) yang berada di luar kendali kami.
                </p>
            </section>
        </div>

        <div class="pt-6 border-t border-emerald-100 flex items-center justify-between">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                ← Kembali ke Beranda
            </a>
            <a href="{{ route('privacy') }}" class="text-xs font-bold text-emerald-700 hover:underline">
                Kebijakan Privasi →
            </a>
        </div>

    </div>
</div>
@endsection
