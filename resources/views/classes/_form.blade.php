@php
    $jenisAwal = old('jenis', isset($classroom) && $classroom->kelasAjar() ? 'ajar' : 'perwalian');
@endphp

<div class="grid gap-5" x-data="{ jenis: '{{ $jenisAwal }}' }">
    <!-- Name -->
    <div>
        <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Kelas <span class="text-rose-500">*</span></label>
        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $classroom->name ?? '') }}"
            required
            placeholder="cth: XII TKJ D"
            class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
            aria-describedby="name-hint"
        >
        <p id="name-hint" class="mt-1 text-xs text-slate-400">Nama kelas sesuai dengan penamaan resmi di sekolah.</p>
        @error('name')
            <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <!-- Jenis Kelas -->
    <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Kelas <span class="text-rose-500">*</span></label>
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="flex items-start gap-2.5 rounded-2xl border p-3.5 cursor-pointer transition-colors"
                   :class="jenis === 'perwalian' ? 'border-emerald-500 bg-emerald-50 text-emerald-950 font-bold' : 'border-slate-200 bg-white hover:bg-emerald-50/50'">
                <input type="radio" name="jenis" value="perwalian" x-model="jenis" class="mt-0.5 accent-emerald-600">
                <span class="text-xs">
                    <span class="block font-bold text-slate-900">Kelas Perwalian</span>
                    <span class="block text-slate-600 mt-0.5 font-medium">Kelas yang Anda walikan. Semua modul aktif: absensi otomatis, buku kas, struktur organisasi, laporan administrasi.</span>
                </span>
            </label>

            <label class="flex items-start gap-2.5 rounded-2xl border p-3.5 cursor-pointer transition-colors"
                   :class="jenis === 'ajar' ? 'border-emerald-500 bg-emerald-50 text-emerald-950 font-bold' : 'border-slate-200 bg-white hover:bg-emerald-50/50'">
                <input type="radio" name="jenis" value="ajar" x-model="jenis" class="mt-0.5 accent-emerald-600">
                <span class="text-xs">
                    <span class="block font-bold text-slate-900">Kelas Ajar (Guru Mapel)</span>
                    <span class="block text-slate-600 mt-0.5 font-medium">Kelas yang Anda ajar mapelnya saja. Hanya absensi, jurnal, rekap, dan nilai. Cukup impor NIS &amp; nama siswa.</span>
                </span>
            </label>
        </div>
        @error('jenis')
            <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
        @enderror

        {{-- Mapel hanya relevan pada kelas ajar --}}
        <div x-show="jenis === 'ajar'" x-cloak class="mt-4 grid gap-4 sm:grid-cols-2">
            @php $mapelLama = old('mapel', isset($classroom) ? $classroom->mapelDiampu() : []); @endphp
            @for ($i = 0; $i < 2; $i++)
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mapel yang diampu {{ $i + 1 }}{{ $i === 0 ? '' : ' (opsional)' }}</label>
                    <input type="text" name="mapel[]" value="{{ $mapelLama[$i] ?? '' }}"
                           placeholder="cth: Komputer Jaringan" 
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
            @endfor
            <p class="sm:col-span-2 mt-1 text-xs text-slate-400">Dipakai saat mengisi presensi, supaya tiap pertemuan jelas mapelnya. Boleh diisi satu saja.</p>
        </div>
    </div>

    <!-- Major & Academic Year -->
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="major" class="block text-xs font-semibold text-slate-700 mb-1">Jurusan / Peminatan</label>
            <input
                id="major"
                name="major"
                type="text"
                value="{{ old('major', $classroom->major ?? '') }}"
                placeholder="cth: TKJ, RPL, IPA, IPS"
                class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
            >
            @error('major')
                <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="academic_year" class="block text-xs font-semibold text-slate-700 mb-1">Tahun Ajaran</label>
            <input
                id="academic_year"
                name="academic_year"
                type="text"
                value="{{ old('academic_year', $classroom->academic_year ?? '') }}"
                placeholder="cth: 2026/2027"
                class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
            >
            @error('academic_year')
                <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Kolom Khusus Perwalian --}}
    <div x-show="jenis !== 'ajar'" x-cloak class="grid gap-5">
        <div>
            @isset($classroom)
                @include('classes._parent_group')
            @endisset

            <label for="homeroom_wa" class="block text-xs font-semibold text-slate-700 mb-1 mt-3">Nomor WhatsApp Petugas Presensi (Cadangan)</label>
            <input
                id="homeroom_wa"
                name="homeroom_wa"
                type="tel"
                value="{{ old('homeroom_wa', $classroom->homeroom_wa ?? '') }}"
                placeholder="081234567890"
                class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
            >
            <p class="mt-1 text-xs text-slate-400">
                Dipakai bila Seksi Absensi dan Ketua Kelas belum punya nomor HP di awal tahun ajaran.
            </p>
            @error('homeroom_wa')
                <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <!-- Auto Attendance -->
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 sm:p-5">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="hidden" name="auto_attendance" value="0">
                <input type="checkbox"
                       name="auto_attendance"
                       value="1"
                       class="mt-0.5 accent-emerald-600 rounded"
                       @checked(old('auto_attendance', $classroom->auto_attendance ?? false))>
                <div>
                    <span class="font-bold text-slate-900 text-xs sm:text-sm">Absensi Otomatis Harian</span>
                    <p class="mt-1 text-xs text-slate-600 font-medium leading-relaxed">
                        Sistem mengirimkan magic link per hari dari WhatsApp akun Anda ke petugas Seksi Absensi sebelum jam pelajaran pertama. Tanggal libur sekolah otomatis dilewati.
                    </p>
                </div>
            </label>
        </div>
    </div>
</div>
