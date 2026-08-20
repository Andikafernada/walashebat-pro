<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lapor Izin / Sakit - {{ $class->name }}</title>
    {{-- Sama seperti form biodata & refleksi: tautannya beredar di grup
         WhatsApp yang sewaktu-waktu bisa jadi terbuka. --}}
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-900 py-8 px-4 sm:px-6">

<div class="max-w-xl mx-auto space-y-6">

    <div class="rounded-lg border border-slate-800 bg-slate-900 p-5 text-white">
        <div class="relative z-10 space-y-1">
            <span class="inline-block rounded-full bg-amber-500/30 px-3 py-1 text-[10px] font-semibold tracking-wider uppercase text-amber-200 border border-amber-400/30">
                Lapor Izin / Sakit
            </span>
            <h1 class="text-2xl font-semibold text-white">Kelas {{ $class->name }}</h1>
            <p class="text-xs text-slate-300">Isi tiap kali anak tidak masuk sekolah, supaya tercatat resmi di wali kelas.</p>
        </div>
    </div>

    @if (session('success_public'))
        <div class="alert alert--success">
            <p class="alert__body font-semibold">{{ session('success_public') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert--danger" role="alert">
            <div>
                <p class="alert__title">Laporan belum bisa dikirim:</p>
                <ul class="alert__body mt-1.5 space-y-1">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="card space-y-5">
        @if ($students->isEmpty())
            <div class="alert alert--warning">
                <p class="alert__body">Daftar siswa kelas ini belum tersedia. Mohon hubungi wali kelas.</p>
            </div>
        @else
            <form method="POST" action="{{ route('public.excuse.store', $class) }}" class="space-y-4" x-data="{ jenis: '{{ old('jenis', 'izin') }}' }">
                @csrf

                <div>
                    <label class="form-label">Nama Anak *</label>
                    <select name="student_id" required class="form-input">
                        <option value="">-- Pilih Nama Anak --</option>
                        @foreach ($students as $st)
                            <option value="{{ $st->id }}" @selected(old('student_id') == $st->id)>{{ $st->name }} (NIS: {{ $st->nis ?: '-' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">Tanggal Tidak Masuk *</label>
                    <input type="date" name="tanggal" required
                           value="{{ old('tanggal', now()->toDateString()) }}"
                           min="{{ now()->subDays(2)->toDateString() }}"
                           max="{{ now()->addDays(3)->toDateString() }}"
                           class="form-input">
                </div>

                <div>
                    <label class="form-label">Alasan *</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center justify-center gap-2 rounded border-2 p-3 cursor-pointer transition-colors"
                               :class="jenis === 'izin' ? 'border-indigo-600 bg-indigo-50 text-indigo-900' : 'border-slate-200 text-slate-600'">
                            <input type="radio" name="jenis" value="izin" x-model="jenis" class="sr-only" @checked(old('jenis', 'izin') === 'izin')>
                            <span class="text-sm font-semibold">Izin</span>
                        </label>
                        <label class="flex items-center justify-center gap-2 rounded border-2 p-3 cursor-pointer transition-colors"
                               :class="jenis === 'sakit' ? 'border-amber-600 bg-amber-50 text-amber-900' : 'border-slate-200 text-slate-600'">
                            <input type="radio" name="jenis" value="sakit" x-model="jenis" class="sr-only" @checked(old('jenis') === 'sakit')>
                            <span class="text-sm font-semibold">Sakit</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="form-label">Keterangan (opsional)</label>
                    <textarea name="keterangan" rows="3" maxlength="500"
                              placeholder="cth: Demam sejak semalam, sudah dibawa ke puskesmas."
                              class="form-input">{{ old('keterangan') }}</textarea>
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    Kirim Laporan ke Wali Kelas
                </button>
            </form>
        @endif
    </div>

</div>

</body>
</html>
