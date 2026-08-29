<!DOCTYPE html>
<html lang="id" class="h-full bg-emerald-50/40">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lapor Izin / Sakit - Kelas {{ $class->name }}</title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full font-sans antialiased text-slate-900 py-6 px-4 sm:px-6">

<div class="max-w-xl mx-auto space-y-5">

    {{-- HEADER CARD (3-Color Bright Emerald) --}}
    <div class="rounded-3xl border border-emerald-200 bg-white p-6 shadow-xs relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-28 h-28 rounded-full bg-emerald-100/60 pointer-events-none"></div>
        <div class="relative z-10 space-y-2">
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-950 border border-emerald-200">
                    📋 Form Lapor Izin / Sakit Resmi
                </span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Kelas {{ $class->name }}</h1>
            <p class="text-xs text-slate-600 leading-relaxed">
                Layanan presensi mandiri orang tua/wali murid. Laporan langsung terhubung dengan rekap presensi Wali Kelas.
            </p>
        </div>
    </div>

    {{-- FLASH MESSAGES --}}
    @if (session('success_public'))
        <div class="p-4 rounded-2xl bg-emerald-100 border border-emerald-300 text-emerald-950 text-sm font-bold flex items-start gap-3 shadow-xs">
            <span class="text-xl">✅</span>
            <p class="mt-0.5 leading-snug">{{ session('success_public') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-sm font-semibold space-y-1.5 shadow-xs">
            <div class="flex items-center gap-2 font-bold text-rose-950">
                <span>⚠️</span>
                <p>Laporan Belum Bisa Dikirim:</p>
            </div>
            <ul class="list-disc list-inside text-xs text-rose-800 space-y-1">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- MAIN FORM CARD --}}
    <div class="bg-white rounded-3xl border border-emerald-200/80 p-5 sm:p-7 shadow-xs space-y-6">
        @if (empty($students) || count($students) === 0)
            <div class="p-6 text-center space-y-2">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-900 flex items-center justify-center text-2xl mx-auto border border-emerald-200">⚠️</div>
                <p class="text-sm font-bold text-slate-900">Daftar Siswa Belum Tersedia</p>
                <p class="text-xs text-slate-500">Mohon hubungi wali kelas untuk memperbarui daftar siswa kelas ini.</p>
            </div>
        @else
            <form method="POST" 
                  action="{{ route('public.excuse.store', $class) }}" 
                  enctype="multipart/form-data"
                  class="space-y-5" 
                  x-data="{ 
                      studentId: '{{ old('student_id') }}',
                      jenis: '{{ old('jenis', 'izin') }}',
                      studentsList: {{ json_encode($students) }},
                      get selectedStudent() {
                          return this.studentsList.find(s => String(s.id) === String(this.studentId)) || null;
                      }
                  }">
                @csrf

                {{-- 1. PILIH SISWA --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700">
                        1. Pilih Nama Anak <span class="text-rose-500">*</span>
                    </label>
                    <select name="student_id" 
                            x-model="studentId"
                            required 
                            class="w-full px-4 py-3 rounded-2xl border border-emerald-200 bg-white text-sm font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all shadow-xs">
                        <option value="">-- Pilih Nama Anak --</option>
                        <template x-for="st in studentsList" :key="st.id">
                            <option :value="st.id" x-text="st.name + ' (NIS: ' + st.nis + ')'"></option>
                        </template>
                    </select>
                </div>

                {{-- 2. VERIFIKASI KEAMANAN ORTU --}}
                <div x-show="selectedStudent" x-transition class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-base">🔒</span>
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-emerald-950">
                            Verifikasi Identitas Orang Tua
                        </h3>
                    </div>

                    <template x-if="selectedStudent && selectedStudent.has_phone">
                        <div class="space-y-2">
                            <p class="text-xs text-slate-600">
                                Masukkan 4 digit terakhir nomor HP Orang Tua yang terdaftar di sekolah:
                            </p>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-2.5 rounded-xl bg-white border border-emerald-200 text-xs font-mono font-bold text-slate-500" x-text="selectedStudent.masked_phone"></span>
                                <input type="text" 
                                       name="parent_phone_last4" 
                                       maxlength="4"
                                       pattern="[0-9]{4}"
                                       placeholder="4 Digit (cth: 7890)"
                                       value="{{ old('parent_phone_last4') }}"
                                       class="flex-1 px-4 py-2.5 rounded-xl border border-emerald-300 bg-white text-sm font-mono font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 shadow-xs">
                            </div>
                        </div>
                    </template>

                    <template x-if="selectedStudent && !selectedStudent.has_phone">
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-medium flex items-center gap-2">
                            <span>ℹ️</span>
                            <p>No. HP Orang Tua belum terdaftar di sistem. Anda tetap dapat melanjutkan pengiriman laporan ini.</p>
                        </div>
                    </template>
                </div>

                {{-- 3. TANGGAL KETIDAKHADIRAN --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700">
                        2. Tanggal Tidak Masuk <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" 
                           name="tanggal" 
                           required
                           value="{{ old('tanggal', now()->toDateString()) }}"
                           min="{{ now()->subDays(2)->toDateString() }}"
                           max="{{ now()->addDays(3)->toDateString() }}"
                           class="w-full px-4 py-3 rounded-2xl border border-emerald-200 bg-white text-sm font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all shadow-xs">
                </div>

                {{-- 4. ALASAN (IZIN / SAKIT) --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700">
                        3. Alasan Ketidakhadiran <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center justify-center gap-2.5 p-3.5 rounded-2xl border-2 cursor-pointer transition-all text-center"
                               :class="jenis === 'izin' ? 'border-emerald-600 bg-emerald-100/60 text-emerald-950 font-bold shadow-xs' : 'border-emerald-100 bg-white text-slate-600 font-semibold hover:bg-emerald-50/50'">
                            <input type="radio" name="jenis" value="izin" x-model="jenis" class="sr-only" @checked(old('jenis', 'izin') === 'izin')>
                            <span class="text-lg">🏡</span>
                            <span class="text-sm">Izin Kepentingan</span>
                        </label>
                        <label class="flex items-center justify-center gap-2.5 p-3.5 rounded-2xl border-2 cursor-pointer transition-all text-center"
                               :class="jenis === 'sakit' ? 'border-amber-600 bg-amber-100/60 text-amber-950 font-bold shadow-xs' : 'border-emerald-100 bg-white text-slate-600 font-semibold hover:bg-emerald-50/50'">
                            <input type="radio" name="jenis" value="sakit" x-model="jenis" class="sr-only" @checked(old('jenis') === 'sakit')>
                            <span class="text-lg">💊</span>
                            <span class="text-sm">Sakit</span>
                        </label>
                    </div>
                </div>

                {{-- 5. KETERANGAN --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700">
                        4. Catatan / Keterangan Tambahan
                    </label>
                    <textarea name="keterangan" 
                              rows="3" 
                              maxlength="500"
                              placeholder="cth: Demam sejak semalam, sudah berobat ke puskesmas / Ada acara keluarga di luar kota."
                              class="w-full px-4 py-3 rounded-2xl border border-emerald-200 bg-white text-sm font-medium text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all shadow-xs">{{ old('keterangan') }}</textarea>
                </div>

                {{-- 6. UPLOAD FOTO BUKTI SURAT --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700">
                        5. Unggah Foto Surat Bukti (Opsional / Direkomendasikan)
                    </label>
                    <div class="p-4 rounded-2xl border-2 border-dashed border-emerald-200 bg-emerald-50/30 hover:bg-emerald-50 transition-colors text-center space-y-2">
                        <span class="text-2xl">📸</span>
                        <div class="text-xs text-slate-600">
                            <label for="file-attachment" class="font-bold text-emerald-800 hover:underline cursor-pointer">
                                Klik untuk upload foto
                            </label>
                            <span>foto Surat Dokter / Surat Tangan Orang Tua</span>
                        </div>
                        <input id="file-attachment" 
                               type="file" 
                               name="attachment" 
                               accept="image/*,.pdf" 
                               class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-950 hover:file:bg-emerald-200 cursor-pointer">
                        <p class="text-[11px] text-slate-400">Format: JPG, PNG, WEBP, atau PDF (Maks. 5MB)</p>
                    </div>
                </div>

                {{-- SUBMIT BUTTON --}}
                <button type="submit" 
                        class="w-full py-4 px-6 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-sm shadow-md transition-all flex items-center justify-center gap-2">
                    <span>🔒</span>
                    <span>Kirim Laporan Resmi ke Wali Kelas</span>
                </button>
            </form>
        @endif
    </div>

    {{-- FOOTER CREDITS --}}
    <div class="text-center text-[11px] text-slate-400">
        <p>Aplikasi Administrasi Kelas Resmi &bull; WaliKelas Pro</p>
    </div>

</div>

</body>
</html>
