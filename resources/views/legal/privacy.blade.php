@extends('layouts.guest')

@section('title', 'Kebijakan Privasi - WaliKelas Pro')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-3xl border border-emerald-150 shadow-sm p-6 sm:p-10 space-y-6">
        
        <div class="border-b border-emerald-100 pb-6 flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white font-black text-xl shadow-xs">
                🛡️
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Kebijakan Privasi (Privacy Policy)</h1>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Terakhir diperbarui: 1 September 2026 • Berlaku untuk Aplikasi & Layanan WaliKelas Pro</p>
            </div>
        </div>

        <div class="prose prose-slate max-w-none text-xs sm:text-sm leading-relaxed text-slate-700 space-y-5">
            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>1. Pendahuluan & Komitmen Kami</span>
                </h2>
                <p>
                    <strong>WaliKelas Pro</strong> ("kami", "aplikasi", atau "layanan") berkomitmen tinggi untuk melindungi privasi dan keamanan data seluruh pengguna kami yang mencakup Pendidik, Guru, Wali Kelas, Orang Tua/Wali Murid, dan Peserta Didik di Indonesia. Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, menyimpan, dan melindungi informasi pribadi Anda saat menggunakan aplikasi mobile dan situs web <code>walas.my.id</code>.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>2. Informasi yang Kami Kumpulkan</span>
                </h2>
                <p>Untuk mendukung kelancaran administrasi sekolah dan manajemen kelas, kami mengumpulkan jenis informasi berikut:</p>
                <ul class="list-disc pl-5 space-y-1.5 text-slate-600">
                    <li><strong>Informasi Akun Pendidik:</strong> Nama lengkap, gelar, alamat email resmi, nomor telepon/WhatsApp, dan nama instansi sekolah.</li>
                    <li><strong>Data Administrasi Peserta Didik:</strong> Nama siswa, Nomor Induk Siswa (NIS/NISN), jenis kelamin, data kontak orang tua/wali murid untuk keperluan koordinasi absensi.</li>
                    <li><strong>Data Aktivitas Pembelajaran:</strong> Rekapitulasi kehadiran/presensi harian, nilai asesmen, catatan capaian karakter P5, jurnal mengajar, dan buku kas kelas.</li>
                    <li><strong>Data Formulir Izin/Sakit:</strong> Keterangan izin, tanggal, dan bukti lampiran surat keterangan dokter/orang tua yang diunggah secara sukarela melalui formulir publik.</li>
                </ul>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>3. Penggunaan & Pemrosesan Data</span>
                </h2>
                <p>Informasi yang dikumpulkan hanya digunakan semata-mata untuk tujuan:</p>
                <ul class="list-disc pl-5 space-y-1.5 text-slate-600">
                    <li>Memfasilitasi pencatatan dan pelaporan presensi peserta didik secara akurat dan real-time.</li>
                    <li>Mengirimkan ringkasan rekapitulasi kehadiran harian kepada grup koordinasi orang tua/wali murid melalui integrasi WhatsApp resmi.</li>
                    <li>Menghasilkan format dokumen cetak resmi (seperti Kartu Siswa QR, Leger Nilai, Jurnal Pembelajaran AI, dan Laporan Rekap).</li>
                    <li>Memberikan notifikasi penting terkait pengumuman kelas dan administrasi iuran kas/SPP.</li>
                </ul>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>4. Keamanan & Perlindungan Data</span>
                </h2>
                <p>
                    Kami menerapkan standar keamanan teknis berlapis, meliputi transmisi data terenkripsi HTTPS (SSL/TLS 256-bit), penyimpanan sandi ter-hashing kuat, isolasi data per sekolah, dan pembatasan hak akses. Kami <strong>tidak pernah menjual, menyewakan, atau memperjualbelikan data pribadi pengguna kepada pihak ketiga atau pengiklan mana pun</strong>.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>5. Integrasi WhatsApp & Layanan Pihak Ketiga</span>
                </h2>
                <p>
                    Fitur bot dan pengiriman pesan otomatis memanfaatkan API gateway WhatsApp resmi yang ditautkan oleh masing-masing wali kelas. Kami hanya memproses pesan yang terkait langsung dengan kata kunci absensi, konfirmasi sakit/izin, dan rekap kelas tanpa mengakses obrolan pribadi pengguna di luar grup yang didaftarkan.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>6. Hak Pengguna & Penghapusan Data</span>
                </h2>
                <p>
                    Pengguna memiliki hak penuh untuk mengakses, memperbarui, mencadangkan (ekspor Excel/PDF), atau meminta penghapusan permanen akun dan seluruh data kelas terkait kapan saja melalui menu Pengaturan Profil atau dengan menghubungi tim pengembang kami.
                </p>
            </section>

            <section class="space-y-2">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span>7. Hubungi Kami</span>
                </h2>
                <p>
                    Apabila Anda memiliki pertanyaan, saran, atau permohonan terkait Kebijakan Privasi ini, silakan hubungi tim pengembang kami melalui:
                </p>
                <div class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-4 text-xs space-y-1">
                    <p><strong>Pengembang:</strong> Andika Fernanda (WaliKelas Pro Team)</p>
                    <p><strong>Email Kontak:</strong> forfirebaseme@gmail.com / support@walas.my.id</p>
                    <p><strong>Situs Resmi:</strong> <a href="https://walas.my.id" class="text-emerald-700 font-bold hover:underline">https://walas.my.id</a></p>
                </div>
            </section>
        </div>

        <div class="pt-6 border-t border-emerald-100 flex items-center justify-between">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                ← Kembali ke Beranda
            </a>
            <a href="{{ route('terms') }}" class="text-xs font-bold text-emerald-700 hover:underline">
                Syarat & Ketentuan Layanan →
            </a>
        </div>

    </div>
</div>
@endsection
