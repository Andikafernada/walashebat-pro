<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jurnal Refleksi Karakter Siswa - {{ $class->name }}</title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-900 py-8 px-4 sm:px-6">

<div class="max-w-xl mx-auto space-y-6 pb-12">

    <!-- Header Banner -->
    <div class="rounded-2xl border border-slate-900 bg-slate-900 p-6 text-white shadow-xs">
        <div class="space-y-1.5">
            <span class="inline-block rounded-full bg-emerald-500/20 px-3 py-1 text-[10px] font-extrabold tracking-wider uppercase text-emerald-400 border border-emerald-500/30">
                Jurnal Refleksi Mandiri (P5)
            </span>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Profil Pelajar Pancasila</h1>
            <p class="text-xs text-slate-300">Kelas {{ $class->name }} &middot; Tuliskan evaluasi perkembangan sikap Anda.</p>
        </div>
    </div>

    @if (session('success_public'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-900">
            <p>{{ session('success_public') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-900" role="alert">
            <p class="font-bold">Refleksi belum bisa dikirim:</p>
            <ul class="mt-1 space-y-1 list-disc list-inside">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Card -->
    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
        <form method="POST" action="{{ route('public.reflection.store', $class) }}" class="space-y-4" x-data="{ rating: {{ (int) old('self_rating', 5) }} }">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Nama Siswa <span class="text-rose-500">*</span></label>
                <select name="student_id" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">-- Pilih Nama Anda --</option>
                    @foreach ($students as $st)
                        <option value="{{ $st->id }}" @selected(old('student_id') == $st->id)>{{ $st->name }} (NIS: {{ $st->nis ?: '-' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Dimensi Karakter <span class="text-rose-500">*</span></label>
                @if ($dimensions->isEmpty())
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        <p>
                            Daftar dimensi karakter belum disiapkan wali kelas, sehingga refleksi belum bisa dikirim.
                            Mohon hubungi wali kelas Anda.
                        </p>
                    </div>
                @else
                    <select name="character_dimension_id" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <option value="">-- Pilih Dimensi Karakter --</option>
                        @foreach ($dimensions as $dim)
                            <option value="{{ $dim->id }}" @selected(old('character_dimension_id') == $dim->id)>{{ $dim->name }} ({{ $dim->code }})</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Penilaian Diri Sendiri (1 - 5 Bintang) <span class="text-rose-500">*</span></label>
                <div class="flex items-center gap-2 py-1">
                    <template x-for="i in 5">
                        <button type="button" @click="rating = i" class="text-2xl transition-transform hover:scale-110" :class="i <= rating ? 'text-amber-400' : 'text-slate-200'">
                            ★
                        </button>
                    </template>
                    <input type="hidden" name="self_rating" :value="rating">
                    <span class="text-xs font-bold text-slate-700 ml-2" x-text="rating + ' / 5 Bintang'"></span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">1. Hal Baik yang Sudah Dilakukan <span class="text-rose-500">*</span></label>
                <textarea name="what_went_well" rows="2" required placeholder="cth: Saya selalu hadir tepat waktu dan membantu menyapu kelas..." 
                          class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('what_went_well') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">2. Hal yang Masih Perlu Ditingkatkan <span class="text-rose-500">*</span></label>
                <textarea name="what_to_improve" rows="2" required placeholder="cth: Saya masih suka mengobrol saat pelajaran berlangsung..." 
                          class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('what_to_improve') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">3. Rencana Aksi Perubahan Sikap <span class="text-rose-500">*</span></label>
                <textarea name="action_plan" rows="2" required placeholder="cth: Saya akan fokus mendengarkan penjelasan guru dan duduk di barisan depan..." 
                          class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('action_plan') }}</textarea>
            </div>

            <div class="rounded-2xl border border-sky-200 bg-sky-50/60 p-4 space-y-1.5">
                <label for="kesan_teman" class="block text-xs font-bold uppercase tracking-wider text-sky-900">
                    Menurut Temanmu, Kamu Itu Seperti Apa? <span class="text-rose-500">*</span>
                </label>
                <p class="text-[11px] text-sky-800 leading-relaxed">
                    Coba tanya 1–2 teman dekatmu, lalu tulis jawaban mereka apa adanya — yang enak didengar maupun yang tidak.
                    Kalau belum sempat bertanya, tulis perkiraanmu sendiri.
                </p>
                <textarea id="kesan_teman" name="kesan_teman" rows="3" maxlength="1000" minlength="10" required
                          placeholder="cth: Kata Rina aku orangnya asyik dan suka bantu, tapi kadang suka memotong pembicaraan orang."
                          class="block w-full rounded-xl border border-sky-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('kesan_teman') }}</textarea>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 space-y-1.5">
                <label for="pesan_ortu" class="block text-xs font-bold text-amber-900 uppercase tracking-wider">
                    Pesan untuk Orang Tua <span class="text-rose-500">*</span>
                </label>
                <p class="text-[11px] text-amber-800 leading-relaxed">
                    Tulis pesan, harapan, atau permintaan maaf untuk Ayah dan Ibu. Pesan ini akan dibaca wali kelas dan disampaikan kepada orang tuamu.
                </p>
                <textarea id="pesan_ortu" name="pesan_ortu" rows="3" maxlength="1000" minlength="10" required
                          placeholder="cth: Terima kasih Ayah Ibu sudah sabar. Aku janji akan lebih rajin belajar dan membantu di rumah."
                          class="block w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('pesan_ortu') }}</textarea>
            </div>

            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-3 text-xs font-bold text-white hover:bg-emerald-700 transition-colors shadow-xs">
                Kirim Refleksi Karakter ke Wali Kelas
            </button>
        </form>
    </div>

</div>

</body>
</html>
