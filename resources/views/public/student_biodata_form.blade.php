<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Biodata Mandiri Siswa - {{ $class->name }}</title>
    {{-- Halaman ini memuat nama kelas dan menerima data pribadi siswa. Cukup
         satu guru menempelkan tautannya di laman sekolah atau grup terbuka
         untuk membuatnya dapat dirayapi, dan setelah terindeks isinya muncul
         di hasil pencarian nama anak. robots.txt saja tidak cukup: ia melarang
         perayapan, bukan pengindeksan tautan yang sudah telanjur ditemukan. --}}
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Alpine sudah ikut di dalam bundel Vite (resources/js/app.js) dan
         dijalankan di sana. Memuatnya sekali lagi dari CDN membuat dua
         salinan Alpine berjalan berbarengan — "Detected multiple instances of
         Alpine running" — dan penangan yang sama terpasang dua kali. --}}
</head>
<body class="h-full font-sans antialiased text-slate-900 py-8 px-4 sm:px-6">

<div class="max-w-2xl mx-auto space-y-6">

    <!-- Header Banner -->
    <div class="rounded-lg p-6 text-white shadow-xl relative overflow-hidden">
        <div class="absolute right-0 top-0 -mr-10 -mt-10 h-40 w-40 rounded-full bg-indigo-500/10 blur-2xl"></div>
        <div class="relative z-10 space-y-1">
            <span class="inline-block rounded-full bg-indigo-500/30 px-3 py-1 text-[10px] font-semibold tracking-wider uppercase text-indigo-200 border border-indigo-400/30">
                Form Biodata Mandiri Siswa &amp; Orang Tua
            </span>
            <h1 class="text-2xl font-semibold text-white">Kelas {{ $class->name }}</h1>
            <p class="text-xs text-slate-300">Lengkapi data diri Anda dengan lengkap, teliti, dan benar.</p>
        </div>
    </div>

    @if (session('success_public'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-xs font-semibold text-emerald-900 flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded bg-emerald-500 text-white font-semibold shrink-0">✓</div>
            <div>{{ session('success_public') }}</div>
        </div>
    @endif

    {{-- Halaman ini berdiri sendiri, tidak memakai layouts/public, jadi blok
         galatnya harus di sini. Tanpa ini penolakan validasi tidak terlihat
         sama sekali: halaman hanya termuat ulang seolah tidak terjadi apa-apa. --}}
    @if ($errors->any())
        <div class="rounded-lg border border-rose-300 bg-rose-50 p-4" role="alert">
            <p class="text-xs font-semibold text-rose-900">Isian belum bisa disimpan:</p>
            <ul class="mt-1.5 space-y-1 text-xs text-rose-800">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Container -->
    {{--
        periksa() ada karena isian wajib formulir ini tersebar di langkah yang
        berbeda, sedangkan tombol Simpan hanya muncul di langkah terakhir.
        x-show menyembunyikan langkah lain dengan display:none, dan peramban
        menolak mengirim formulir yang punya isian wajib kosong yang TIDAK BISA
        DISOROT — tanpa pesan apa pun. Yang dialami siswa: sudah mengisi
        semuanya, menekan Simpan, lalu tidak terjadi apa-apa sama sekali.

        Karena itu validasi bawaan peramban dimatikan (novalidate) dan
        dijalankan sendiri di sini: langkah yang bermasalah dibuka dulu, baru
        peringatannya ditampilkan pada isian yang bersangkutan.
    --}}
    <div class="card space-y-6"
         x-data="{
            step: 1,
            siswaTerpilih: '{{ old('student_id') }}',
            mengirim: false,
            periksa($event) {
                const formulir = $event.target;
                const bermasalah = formulir.querySelector(':invalid');

                if (bermasalah) {
                    $event.preventDefault();

                    const langkah = bermasalah.closest('[data-langkah]');
                    if (langkah) {
                        this.step = Number(langkah.dataset.langkah);
                    }

                    this.$nextTick(() => {
                        bermasalah.focus();
                        bermasalah.reportValidity();
                    });

                    return;
                }

                // Kiriman ganda karena tombol diklik berkali-kali melahirkan
                // biodata kembar; sekali tekan sudah cukup.
                this.mengirim = true;
            }
         }">
        
        <!-- Step Indicator -->
        <div class="grid grid-cols-4 gap-1 border-b border-slate-200 pb-4 text-[11px] text-center font-semibold">
            <button type="button" @click="step = 1" :class="step === 1 ? 'text-indigo-600 border-b-2 border-indigo-600 pb-1' : 'text-slate-400'">
                1. Data Diri
            </button>
            <button type="button" @click="step = 2" :class="step === 2 ? 'text-indigo-600 border-b-2 border-indigo-600 pb-1' : 'text-slate-400'">
                2. Orang Tua &amp; Wali
            </button>
            <button type="button" @click="step = 3" :class="step === 3 ? 'text-indigo-600 border-b-2 border-indigo-600 pb-1' : 'text-slate-400'">
                3. Alamat
            </button>
            <button type="button" @click="step = 4" :class="step === 4 ? 'text-indigo-600 border-b-2 border-indigo-600 pb-1' : 'text-slate-400'">
                4. Fisik &amp; KIP
            </button>
        </div>

        <form method="POST" action="{{ route('public.biodata.store', $class) }}" class="space-y-5"
              novalidate @submit="periksa($event)">
            @csrf

            <!-- STEP 1: DATA DIRI SISWA -->
            <div x-show="step === 1" data-langkah="1" class="space-y-4">
                <div>
                    <label class="form-label">Pilih Nama Siswa (jika sudah diimpor)</label>
                    <select name="student_id" x-model="siswaTerpilih" class="form-input form-input--sm">
                        <option value="">— Siswa Baru / Tambah Diri —</option>
                        @foreach ($students as $st)
                            <option value="{{ $st->id }}" @selected(old('student_id') == $st->id)>{{ $st->name }} (NIS: {{ $st->nis ?: '-' }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[10px] text-slate-500">
                        Sudah pernah mengisi? <strong>Pilih nama Anda di sini</strong>, jangan mengetik ulang —
                        agar biodata yang sudah ada tidak terduplikat. Isian yang dikosongkan tidak akan menghapus data lama.
                    </p>
                </div>

                {{-- Wajib hanya bila namanya TIDAK dipilih dari daftar di atas.
                     Daftar itulah jalur yang dianjurkan halaman ini, dan siswa
                     yang menurutinya lalu membiarkan kolom ini kosong dulu
                     tertahan tanpa penjelasan. --}}
                <div>
                    <label class="form-label">
                        Nama Lengkap Siswa <span x-show="! siswaTerpilih">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="cth: Ahmad Fauzi"
                           :required="! siswaTerpilih"
                           class="form-input form-input--sm">
                    <p class="mt-1 text-[10px] text-slate-500" x-show="siswaTerpilih" x-cloak>
                        Boleh dikosongkan — nama Anda sudah dipilih dari daftar di atas.
                        Isi hanya bila ejaan namanya perlu diperbaiki.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">NIS</label>
                        <input type="text" name="nis" value="{{ old('nis') }}" placeholder="cth: 22231001" class="form-input form-input--sm">
                    </div>
                    <div>
                        <label class="form-label">NISN</label>
                        <input type="text" name="nisn" value="{{ old('nisn') }}" placeholder="cth: 0051234567" class="form-input form-input--sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Jenis Kelamin *</label>
                        <select name="gender" required class="form-input form-input--sm">
                            <option value="L" @selected(old('gender') === 'L')>Laki-laki</option>
                            <option value="P" @selected(old('gender') === 'P')>Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Agama</label>
                        <select name="agama" class="form-input form-input--sm">
                            <option value="Islam" @selected(old('agama') === 'Islam')>Islam</option>
                            <option value="Kristen" @selected(old('agama') === 'Kristen')>Kristen</option>
                            <option value="Katolik" @selected(old('agama') === 'Katolik')>Katolik</option>
                            <option value="Hindu" @selected(old('agama') === 'Hindu')>Hindu</option>
                            <option value="Buddha" @selected(old('agama') === 'Buddha')>Buddha</option>
                            <option value="Konghucu" @selected(old('agama') === 'Konghucu')>Konghucu</option>
                            <option value="Lainnya" @selected(old('agama') === 'Lainnya')>Lainnya</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir') }}" placeholder="cth: Bandung" class="form-input form-input--sm">
                    </div>
                    <div>
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" class="form-input form-input--sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nomor HP Siswa</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="cth: 081234567890" class="form-input form-input--sm">
                    </div>
                    <div>
                        <label class="form-label">Tahun Masuk</label>
                        <input type="text" name="tahun_masuk" value="{{ old('tahun_masuk') }}" placeholder="cth: 2024" class="form-input form-input--sm">
                    </div>
                </div>

                <button type="button" @click="step = 2" class="h-10 w-full rounded bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition-colors">
                    Lanjut ke Data Orang Tua &amp; Wali &rarr;
                </button>
            </div>

            <!-- STEP 2: ORANG TUA & WALI -->
            <div x-show="step === 2" data-langkah="2" x-cloak class="space-y-4">
                <div>
                    <label class="form-label">Nomor WhatsApp Orang Tua *</label>
                    <input type="tel" name="parent_phone" value="{{ old('parent_phone') }}" placeholder="cth: 081987654321" required class="form-input form-input--sm">
                    <p class="text-[10px] text-slate-400 mt-1">Wajib diisi untuk menerima notifikasi presensi harian dari wali kelas.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nama Ayah</label>
                        <input type="text" name="nama_ayah" value="{{ old('nama_ayah') }}" placeholder="Nama lengkap ayah" class="form-input form-input--sm">
                    </div>
                    <div>
                        <label class="form-label">Pekerjaan Ayah</label>
                        <input type="text" name="pekerjaan_ayah" value="{{ old('pekerjaan_ayah') }}" placeholder="cth: Karyawan Swasta" class="form-input form-input--sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nama Ibu</label>
                        <input type="text" name="nama_ibu" value="{{ old('nama_ibu') }}" placeholder="Nama lengkap ibu" class="form-input form-input--sm">
                    </div>
                    <div>
                        <label class="form-label">Pekerjaan Ibu</label>
                        <input type="text" name="pekerjaan_ibu" value="{{ old('pekerjaan_ibu') }}" placeholder="cth: Ibu Rumah Tangga" class="form-input form-input--sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nama Wali (opsional)</label>
                        <input type="text" name="nama_wali" value="{{ old('nama_wali') }}" placeholder="Jika ada" class="form-input form-input--sm">
                    </div>
                    <div>
                        <label class="form-label">Pekerjaan Wali</label>
                        <input type="text" name="pekerjaan_wali" value="{{ old('pekerjaan_wali') }}" placeholder="Pekerjaan wali" class="form-input form-input--sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Anak Ke-</label>
                        <input type="number" name="anak_ke" value="{{ old('anak_ke') }}" placeholder="cth: 1" class="form-input form-input--sm">
                    </div>
                    <div>
                        <label class="form-label">Jumlah Saudara</label>
                        <input type="number" name="jumlah_saudara" value="{{ old('jumlah_saudara') }}" placeholder="cth: 2" class="form-input form-input--sm">
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="step = 1" class="h-10 w-1/3 rounded border border-slate-200 bg-slate-100 font-semibold text-xs text-slate-700">
                        &larr; Kembali
                    </button>
                    <button type="button" @click="step = 3" class="h-10 w-2/3 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition-colors">
                        Lanjut ke Alamat Rumah &rarr;
                    </button>
                </div>
            </div>

            <!-- STEP 3: ALAMAT & DOMISILI -->
            <div x-show="step === 3" data-langkah="3" x-cloak class="space-y-4">
                <div>
                    <label class="form-label">Alamat Rumah Lengkap</label>
                    <textarea name="address" rows="2" placeholder="Jalan, No. Rumah, Kampung..." class="form-input form-input--sm">{{ old('address') }}</textarea>
                </div>

                {{-- Dipakai laporan administrasi tetapi dulu tidak ada di formulir
                     mana pun, sehingga tidak seorang pun bisa mengisinya. --}}
                <div>
                    <label class="form-label">Alamat Orang Tua / Wali <span class="text-slate-400 font-normal lowercase">(kosongkan bila sama dengan alamat rumah)</span></label>
                    <textarea name="alamat_ortu" rows="2" placeholder="Isi bila orang tua tinggal di alamat berbeda" class="form-input form-input--sm">{{ old('alamat_ortu') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">RT / RW</label>
                        <input type="text" name="rt_rw" value="{{ old('rt_rw') }}" placeholder="cth: RT 02 / RW 05" class="form-input form-input--sm">
                    </div>
                    <div>
                        <label class="form-label">Kelurahan / Desa</label>
                        <input type="text" name="kelurahan" value="{{ old('kelurahan') }}" placeholder="Kelurahan" class="form-input form-input--sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Kecamatan</label>
                        <input type="text" name="kecamatan" value="{{ old('kecamatan') }}" placeholder="Kecamatan" class="form-input form-input--sm">
                    </div>
                    <div>
                        <label class="form-label">Jarak ke Sekolah (km)</label>
                        <input type="number" step="0.1" name="jarak_rumah_km" value="{{ old('jarak_rumah_km') }}" placeholder="cth: 3.5" class="form-input form-input--sm">
                    </div>
                </div>

                <div>
                    <label class="form-label">Moda Transportasi</label>
                    <select name="moda_transportasi" class="form-input form-input--sm">
                        <option value="Jalan Kaki" @selected(old('moda_transportasi') === 'Jalan Kaki')>Jalan Kaki</option>
                        <option value="Sepeda Motor" @selected(old('moda_transportasi') === 'Sepeda Motor')>Sepeda Motor</option>
                        <option value="Jemputan Sekolah" @selected(old('moda_transportasi') === 'Jemputan Sekolah')>Jemputan Sekolah</option>
                        <option value="Angkutan Umum" @selected(old('moda_transportasi') === 'Angkutan Umum')>Angkutan Umum</option>
                        <option value="Mobil Pribadi" @selected(old('moda_transportasi') === 'Mobil Pribadi')>Mobil Pribadi</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="step = 2" class="h-10 w-1/3 rounded border border-slate-200 bg-slate-100 font-semibold text-xs text-slate-700">
                        &larr; Kembali
                    </button>
                    <button type="button" @click="step = 4" class="h-10 w-2/3 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition-colors">
                        Lanjut ke Fisik &amp; KIP/PKH &rarr;
                    </button>
                </div>
            </div>

            <!-- STEP 4: FISIK, MINAT & BANTUAN -->
            <div x-show="step === 4" data-langkah="4" x-cloak class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Tinggi Badan (cm)</label>
                        <input type="number" name="tinggi_badan_cm" value="{{ old('tinggi_badan_cm') }}" placeholder="cth: 165" class="form-input form-input--sm">
                    </div>
                    <div>
                        <label class="form-label">Berat Badan (kg)</label>
                        <input type="number" name="berat_badan_kg" value="{{ old('berat_badan_kg') }}" placeholder="cth: 55" class="form-input form-input--sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Golongan Darah</label>
                        <select name="golongan_darah" class="form-input form-input--sm">
                            <option value="Tidak Tahu" @selected(old('golongan_darah') === 'Tidak Tahu')>Tidak Tahu</option>
                            <option value="A" @selected(old('golongan_darah') === 'A')>A</option>
                            <option value="B" @selected(old('golongan_darah') === 'B')>B</option>
                            <option value="AB" @selected(old('golongan_darah') === 'AB')>AB</option>
                            <option value="O" @selected(old('golongan_darah') === 'O')>O</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Sekolah Asal</label>
                        <input type="text" name="asal_sekolah" value="{{ old('asal_sekolah') }}" placeholder="cth: SMPN 1 Bandung" class="form-input form-input--sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Hobi</label>
                        <input type="text" name="hobi" value="{{ old('hobi') }}" placeholder="cth: Membaca, Olahraga" class="form-input form-input--sm">
                    </div>
                    <div>
                        <label class="form-label">Cita-Cita</label>
                        <input type="text" name="cita_cita" value="{{ old('cita_cita') }}" placeholder="cth: Insinyur, Dokter" class="form-input form-input--sm">
                    </div>
                </div>

                <div class="rounded border border-slate-200 bg-slate-50 p-3.5 space-y-2">
                    <span class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Status Bantuan Sosial:</span>
                    <div class="flex items-center gap-6">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                            <input type="checkbox" name="penerima_kip" value="1" @checked(old('penerima_kip')) class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Penerima KIP / PIP
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                            <input type="checkbox" name="penerima_pkh" value="1" @checked(old('penerima_pkh')) class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Penerima PKH / KKS
                        </label>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="step = 3" class="h-10 w-1/3 rounded border border-slate-200 bg-slate-100 font-semibold text-xs text-slate-700">
                        &larr; Kembali
                    </button>
                    <button type="submit" :disabled="mengirim"
                            class="h-10 w-2/3 rounded bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-400 disabled:cursor-not-allowed text-white font-semibold text-xs transition-colors">
                        <span x-show="! mengirim">✓ Simpan Biodata Mandiri</span>
                        <span x-show="mengirim" x-cloak>Menyimpan…</span>
                    </button>
                </div>
            </div>

        </form>
    </div>

</div>

</body>
</html>
