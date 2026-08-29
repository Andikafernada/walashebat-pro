<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form Biodata Mandiri Siswa - {{ $class->name }}</title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-900 py-8 px-4 sm:px-6">

<div class="max-w-2xl mx-auto space-y-6 pb-12">

    <!-- Header Banner -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900 p-6 text-white shadow-xs">
        <div class="space-y-1.5">
            <span class="inline-block rounded-full bg-emerald-500/20 px-3 py-1 text-[10px] font-extrabold tracking-wider uppercase text-emerald-400 border border-emerald-500/30">
                Form Biodata Mandiri Siswa &amp; Orang Tua
            </span>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Kelas {{ $class->name }}</h1>
            <p class="text-xs text-slate-300">Lengkapi data diri Anda dengan lengkap, teliti, dan benar.</p>
        </div>
    </div>

    @if (session('success_public'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-900">
            <p>{{ session('success_public') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-900" role="alert">
            <p class="font-bold">Isian belum bisa disimpan:</p>
            <ul class="mt-1 space-y-1 list-disc list-inside">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6 space-y-6"
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

                this.mengirim = true;
            }
         }">

        <!-- Step Indicator -->
        <div class="grid grid-cols-4 gap-1 border-b border-slate-100 pb-4 text-xs text-center font-bold">
            <button type="button" @click="step = 1" :class="step === 1 ? 'text-emerald-800 border-b-2 border-emerald-600 pb-1 font-extrabold' : 'text-slate-400'">
                1. Data Diri
            </button>
            <button type="button" @click="step = 2" :class="step === 2 ? 'text-emerald-800 border-b-2 border-emerald-600 pb-1 font-extrabold' : 'text-slate-400'">
                2. Orang Tua &amp; Wali
            </button>
            <button type="button" @click="step = 3" :class="step === 3 ? 'text-emerald-800 border-b-2 border-emerald-600 pb-1 font-extrabold' : 'text-slate-400'">
                3. Alamat
            </button>
            <button type="button" @click="step = 4" :class="step === 4 ? 'text-emerald-800 border-b-2 border-emerald-600 pb-1 font-extrabold' : 'text-slate-400'">
                4. Fisik &amp; KIP
            </button>
        </div>

        <form method="POST" action="{{ route('public.biodata.store', $class) }}" class="space-y-5"
              novalidate @submit="periksa($event)">
            @csrf

            <!-- STEP 1: DATA DIRI SISWA -->
            <div x-show="step === 1" data-langkah="1" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Nama Siswa (jika sudah diimpor)</label>
                    <select name="student_id" x-model="siswaTerpilih" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <option value="">— Siswa Baru / Tambah Diri —</option>
                        @foreach ($students as $st)
                            <option value="{{ $st->id }}" @selected(old('student_id') == $st->id)>{{ $st->name }} (NIS: {{ $st->nis ?: '-' }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[10px] text-slate-400">
                        Sudah pernah mengisi? <strong>Pilih nama Anda di sini</strong>, jangan mengetik ulang —
                        agar biodata yang sudah ada tidak terduplikat. Isian yang dikosongkan tidak akan menghapus data lama.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Nama Lengkap Siswa <span x-show="! siswaTerpilih" class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="cth: Ahmad Fauzi"
                           :required="! siswaTerpilih"
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <p class="mt-1 text-[10px] text-slate-400" x-show="siswaTerpilih" x-cloak>
                        Boleh dikosongkan — nama Anda sudah dipilih dari daftar di atas.
                        Isi hanya bila ejaan namanya perlu diperbaiki.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">NIS</label>
                        <input type="text" name="nis" value="{{ old('nis') }}" placeholder="cth: 22231001" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">NISN</label>
                        <input type="text" name="nisn" value="{{ old('nisn') }}" placeholder="cth: 0051234567" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
                        <select name="gender" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="L" @selected(old('gender') === 'L')>Laki-laki</option>
                            <option value="P" @selected(old('gender') === 'P')>Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Agama</label>
                        <select name="agama" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
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
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir') }}" placeholder="cth: Bandung" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor HP Siswa</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="cth: 081234567890" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Masuk</label>
                        <input type="text" name="tahun_masuk" value="{{ old('tahun_masuk') }}" placeholder="cth: 2024" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <button type="button" @click="step = 2" class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">
                    Lanjut ke Data Orang Tua &amp; Wali &rarr;
                </button>
            </div>

            <!-- STEP 2: ORANG TUA & WALI -->
            <div x-show="step === 2" data-langkah="2" x-cloak class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp Orang Tua <span class="text-rose-500">*</span></label>
                    <input type="tel" name="parent_phone" value="{{ old('parent_phone') }}" placeholder="cth: 081987654321" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <p class="text-[10px] text-slate-400 mt-1">Wajib diisi untuk menerima notifikasi presensi harian dari wali kelas.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Ayah</label>
                        <input type="text" name="nama_ayah" value="{{ old('nama_ayah') }}" placeholder="Nama lengkap ayah" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pekerjaan Ayah</label>
                        <input type="text" name="pekerjaan_ayah" value="{{ old('pekerjaan_ayah') }}" placeholder="cth: Karyawan Swasta" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Ibu</label>
                        <input type="text" name="nama_ibu" value="{{ old('nama_ibu') }}" placeholder="Nama lengkap ibu" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pekerjaan Ibu</label>
                        <input type="text" name="pekerjaan_ibu" value="{{ old('pekerjaan_ibu') }}" placeholder="cth: Ibu Rumah Tangga" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Wali (opsional)</label>
                        <input type="text" name="nama_wali" value="{{ old('nama_wali') }}" placeholder="Jika ada" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pekerjaan Wali</label>
                        <input type="text" name="pekerjaan_wali" value="{{ old('pekerjaan_wali') }}" placeholder="Pekerjaan wali" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Anak Ke-</label>
                        <input type="number" name="anak_ke" value="{{ old('anak_ke') }}" placeholder="cth: 1" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Saudara</label>
                        <input type="number" name="jumlah_saudara" value="{{ old('jumlah_saudara') }}" placeholder="cth: 2" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="step = 1" class="w-1/3 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        &larr; Kembali
                    </button>
                    <button type="button" @click="step = 3" class="w-2/3 inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">
                        Lanjut ke Alamat Rumah &rarr;
                    </button>
                </div>
            </div>

            <!-- STEP 3: ALAMAT & DOMISILI -->
            <div x-show="step === 3" data-langkah="3" x-cloak class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Rumah Lengkap</label>
                    <textarea name="address" rows="2" placeholder="Jalan, No. Rumah, Kampung..." class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('address') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Orang Tua / Wali <span class="text-slate-400 font-normal lowercase">(kosongkan bila sama dengan alamat rumah)</span></label>
                    <textarea name="alamat_ortu" rows="2" placeholder="Isi bila orang tua tinggal di alamat berbeda" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('alamat_ortu') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">RT / RW</label>
                        <input type="text" name="rt_rw" value="{{ old('rt_rw') }}" placeholder="cth: RT 02 / RW 05" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kelurahan / Desa</label>
                        <input type="text" name="kelurahan" value="{{ old('kelurahan') }}" placeholder="Kelurahan" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kecamatan</label>
                        <input type="text" name="kecamatan" value="{{ old('kecamatan') }}" placeholder="Kecamatan" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jarak ke Sekolah (km)</label>
                        <input type="number" step="0.1" name="jarak_rumah_km" value="{{ old('jarak_rumah_km') }}" placeholder="cth: 3.5" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Moda Transportasi</label>
                    <select name="moda_transportasi" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <option value="Jalan Kaki" @selected(old('moda_transportasi') === 'Jalan Kaki')>Jalan Kaki</option>
                        <option value="Sepeda Motor" @selected(old('moda_transportasi') === 'Sepeda Motor')>Sepeda Motor</option>
                        <option value="Jemputan Sekolah" @selected(old('moda_transportasi') === 'Jemputan Sekolah')>Jemputan Sekolah</option>
                        <option value="Angkutan Umum" @selected(old('moda_transportasi') === 'Angkutan Umum')>Angkutan Umum</option>
                        <option value="Mobil Pribadi" @selected(old('moda_transportasi') === 'Mobil Pribadi')>Mobil Pribadi</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="step = 2" class="w-1/3 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        &larr; Kembali
                    </button>
                    <button type="button" @click="step = 4" class="w-2/3 inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">
                        Lanjut ke Fisik &amp; KIP/PKH &rarr;
                    </button>
                </div>
            </div>

            <!-- STEP 4: FISIK, MINAT & BANTUAN -->
            <div x-show="step === 4" data-langkah="4" x-cloak class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tinggi Badan (cm)</label>
                        <input type="number" name="tinggi_badan_cm" value="{{ old('tinggi_badan_cm') }}" placeholder="cth: 165" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Berat Badan (kg)</label>
                        <input type="number" name="berat_badan_kg" value="{{ old('berat_badan_kg') }}" placeholder="cth: 55" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Golongan Darah</label>
                        <select name="golongan_darah" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="Tidak Tahu" @selected(old('golongan_darah') === 'Tidak Tahu')>Tidak Tahu</option>
                            <option value="A" @selected(old('golongan_darah') === 'A')>A</option>
                            <option value="B" @selected(old('golongan_darah') === 'B')>B</option>
                            <option value="AB" @selected(old('golongan_darah') === 'AB')>AB</option>
                            <option value="O" @selected(old('golongan_darah') === 'O')>O</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Sekolah Asal</label>
                        <input type="text" name="asal_sekolah" value="{{ old('asal_sekolah') }}" placeholder="cth: SMPN 1 Bandung" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Hobi</label>
                        <input type="text" name="hobi" value="{{ old('hobi') }}" placeholder="cth: Membaca, Olahraga" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cita-Cita</label>
                        <input type="text" name="cita_cita" value="{{ old('cita_cita') }}" placeholder="cth: Insinyur, Dokter" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 space-y-2">
                    <span class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Status Bantuan Sosial:</span>
                    <div class="flex items-center gap-6">
                        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                            <input type="checkbox" name="penerima_kip" value="1" @checked(old('penerima_kip')) class="rounded accent-emerald-600">
                            Penerima KIP / PIP
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                            <input type="checkbox" name="penerima_pkh" value="1" @checked(old('penerima_pkh')) class="rounded accent-emerald-600">
                            Penerima PKH / KKS
                        </label>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="step = 3" class="w-1/3 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        &larr; Kembali
                    </button>
                    <button type="submit" :disabled="mengirim"
                            class="w-2/3 inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors shadow-xs">
                        <span x-show="! mengirim">Simpan Biodata Mandiri</span>
                        <span x-show="mengirim" x-cloak>Menyimpan…</span>
                    </button>
                </div>
            </div>

        </form>
    </div>

</div>

</body>
</html>
