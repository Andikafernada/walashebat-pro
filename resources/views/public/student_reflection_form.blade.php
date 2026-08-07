<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jurnal Refleksi Karakter Siswa - {{ $class->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Alpine sudah ikut di dalam bundel Vite (resources/js/app.js) dan
         dijalankan di sana. Memuatnya sekali lagi dari CDN membuat dua
         salinan Alpine berjalan berbarengan — "Detected multiple instances of
         Alpine running" — dan penangan yang sama terpasang dua kali. --}}
</head>
<body class="h-full font-sans antialiased text-slate-900 bg-gradient-to-br from-slate-100 via-indigo-50/30 to-purple-50/20 py-8 px-4 sm:px-6">

<div class="max-w-xl mx-auto space-y-6">

    <!-- Header Banner -->
    <div class="rounded-2xl bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 p-6 text-white shadow-xl relative overflow-hidden">
        <div class="absolute right-0 top-0 -mr-10 -mt-10 h-40 w-40 rounded-full bg-purple-500/10 blur-2xl"></div>
        <div class="relative z-10 space-y-1">
            <span class="inline-block rounded-full bg-purple-500/30 px-3 py-1 text-[10px] font-bold tracking-wider uppercase text-purple-200 border border-purple-400/30">
                Jurnal Refleksi Mandiri (P5)
            </span>
            <h1 class="text-2xl font-extrabold text-white">Profil Pelajar Pancasila</h1>
            <p class="text-xs text-slate-300">Kelas {{ $class->name }} &middot; Tuliskan evaluasi perkembangan sikap Anda.</p>
        </div>
    </div>

    @if (session('success_public'))
        <div class="rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-xs font-bold text-emerald-900 flex items-center gap-3 shadow-sm">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-500 text-white font-bold shrink-0">✓</div>
            <div>{{ session('success_public') }}</div>
        </div>
    @endif

    {{-- Halaman berdiri sendiri, tanpa blok ini kegagalan validasi tidak terlihat
         sama sekali: halaman hanya termuat ulang seolah tidak terjadi apa-apa. --}}
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-300 bg-rose-50 p-4 shadow-sm" role="alert">
            <p class="text-xs font-bold text-rose-900">Refleksi belum bisa dikirim:</p>
            <ul class="mt-1.5 space-y-1 text-xs text-rose-800">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Card -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-5">
        <form method="POST" action="{{ route('public.reflection.store', $class) }}" class="space-y-4" x-data="{ rating: 5 }">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Nama Siswa *</label>
                <select name="student_id" required class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs font-medium text-slate-800 focus:border-indigo-500 focus:outline-none">
                    <option value="">-- Pilih Nama Anda --</option>
                    @foreach ($students as $st)
                        <option value="{{ $st->id }}">{{ $st->name }} (NIS: {{ $st->nis ?: '-' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Dimensi Karakter *</label>
                @if ($dimensions->isEmpty())
                    {{-- Tanpa blok ini isiannya hanya kotak kosong: siswa tidak bisa
                         mengirim dan tidak tahu mengapa, padahal penyebabnya ada di
                         sisi wali kelas. --}}
                    <p class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        Daftar dimensi karakter belum disiapkan wali kelas, sehingga refleksi belum bisa dikirim.
                        Mohon hubungi wali kelas Anda.
                    </p>
                @else
                    {{-- Pilihan kosong di baris pertama, dan nilai lama dipertahankan.
                         Tanpa keduanya peramban otomatis menyorot dimensi pertama:
                         atribut required jadi tidak berarti, siswa yang melewati isian
                         ini terkirim dengan dimensi yang tidak pernah ia pilih, dan
                         setiap kegagalan validasi mengembalikannya ke pilihan itu lagi. --}}
                    <select name="character_dimension_id" required class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs font-medium text-slate-800 focus:border-indigo-500 focus:outline-none">
                        <option value="">-- Pilih Dimensi Karakter --</option>
                        @foreach ($dimensions as $dim)
                            <option value="{{ $dim->id }}" @selected(old('character_dimension_id') == $dim->id)>{{ $dim->name }} ({{ $dim->code }})</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Penilaian Diri Sendiri (1 - 5 Bintang) *</label>
                <div class="flex items-center gap-2 py-1">
                    <template x-for="i in 5">
                        <button type="button" @click="rating = i" class="text-2xl transition-transform hover:scale-110" :class="i <= rating ? 'text-amber-400' : 'text-slate-300'">
                            ★
                        </button>
                    </template>
                    <input type="hidden" name="self_rating" :value="rating">
                    <span class="text-xs font-bold text-slate-600 ml-2" x-text="rating + ' / 5 Bintang'"></span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">1. Hal Baik yang Sudah Dilakukan *</label>
                <textarea name="what_went_well" rows="2" required placeholder="cth: Saya selalu hadir tepat waktu dan membantu menyapu kelas..." class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">2. Hal yang Masih Perlu Ditingkatkan *</label>
                <textarea name="what_to_improve" rows="2" required placeholder="cth: Saya masih suka mengobrol saat pelajaran berlangsung..." class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">3. Rencana Aksi Perubahan Sikap *</label>
                <textarea name="action_plan" rows="2" required placeholder="cth: Saya akan fokus mendengarkan penjelasan guru dan duduk di barisan depan..." class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">{{ old('action_plan') }}</textarea>
            </div>

            {{-- Ditujukan kepada orang tua, bukan kepada diri sendiri maupun wali
                 kelas seperti tiga isian di atas. Karena itu disimpan dan
                 ditampilkan terpisah. --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3 space-y-1.5">
                <label class="block text-xs font-bold text-amber-900 uppercase tracking-wider">
                    💌 Pesan untuk Orang Tua <span class="text-amber-700 font-normal lowercase">(boleh dikosongkan)</span>
                </label>
                <p class="text-[10px] text-amber-800">
                    Tulis pesan, harapan, atau permintaan maaf untuk Ayah dan Ibu. Pesan ini akan dibaca wali kelas dan disampaikan kepada orang tuamu.
                </p>
                <textarea name="pesan_ortu" rows="3" maxlength="1000"
                          placeholder="cth: Terima kasih Ayah Ibu sudah sabar. Aku janji akan lebih rajin belajar dan membantu di rumah."
                          class="w-full rounded-xl border border-amber-200 bg-white p-2.5 text-xs text-slate-800 focus:border-amber-500 focus:outline-none">{{ old('pesan_ortu') }}</textarea>
            </div>

            <button type="submit" class="h-10 w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs transition-all shadow-md shadow-purple-500/20">
                ✓ Kirim Refleksi Karakter ke Wali Kelas
            </button>
        </form>
    </div>

</div>

</body>
</html>
