@php $jenisAwal = old('jenis', $classroom->jenis ?? \App\Models\Classroom::JENIS_PERWALIAN); @endphp
{{-- x-data di pembungkus TERLUAR, bukan di kelompok "Jenis kelas" saja:
     bagian WhatsApp di bawah perlu membaca pilihan yang sama untuk
     menyembunyikan dirinya. --}}
<div class="grid gap-5" x-data="{ jenis: '{{ $jenisAwal }}' }">
    <!-- Name -->
    <div class="form-group">
        <label for="name" class="form-label form-label--required">Nama kelas</label>
        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $classroom->name ?? '') }}"
            required
            placeholder="cth: XII RPL 1"
            class="form-input"
            aria-describedby="name-hint"
        >
        <p id="name-hint" class="form-hint">Nama kelas sesuai dengan penamaan resmi di sekolah.</p>
        @error('name')
            <p class="form-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    {{--
        Jenis kelas menentukan seluruh menu yang muncul.

        Kebanyakan wali kelas di Indonesia juga guru mapel: satu kelas
        diwalikan, beberapa kelas lain hanya diajar mapelnya. Kelas ajar tidak
        boleh menawarkan buku kas, struktur organisasi, laporan administrasi,
        maupun pembagian biodata ke grup orang tua — semuanya pekerjaan wali
        kelas kelas tersebut, yang orangnya berbeda.
    --}}
    <div class="form-group">
        <label class="form-label form-label--required">Jenis kelas</label>
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="flex items-start gap-2.5 rounded-xl border p-3 cursor-pointer transition-colors"
                   :class="jenis === 'perwalian' ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200 hover:bg-slate-50'">
                <input type="radio" name="jenis" value="perwalian" x-model="jenis" class="mt-0.5">
                <span class="text-xs">
                    <span class="block font-bold text-slate-800">Kelas Perwalian</span>
                    <span class="block text-slate-500 mt-0.5">Kelas yang Anda walikan. Semua modul aktif: absensi otomatis, buku kas, struktur organisasi, laporan administrasi.</span>
                </span>
            </label>

            <label class="flex items-start gap-2.5 rounded-xl border p-3 cursor-pointer transition-colors"
                   :class="jenis === 'ajar' ? 'border-emerald-400 bg-emerald-50' : 'border-slate-200 hover:bg-slate-50'">
                <input type="radio" name="jenis" value="ajar" x-model="jenis" class="mt-0.5">
                <span class="text-xs">
                    <span class="block font-bold text-slate-800">Kelas Ajar (Guru Mapel)</span>
                    <span class="block text-slate-500 mt-0.5">Kelas yang Anda ajar mapelnya saja. Hanya absensi, jurnal, rekap, dan nilai. Cukup impor NIS &amp; nama siswa.</span>
                </span>
            </label>
        </div>
        @error('jenis')
            <p class="form-error" role="alert">{{ $message }}</p>
        @enderror

        {{-- Mapel hanya relevan pada kelas ajar. --}}
        <div x-show="jenis === 'ajar'" x-cloak class="mt-4 grid gap-4 sm:grid-cols-2">
            {{-- isset(), bukan ??: mapelDiampu() adalah pemanggilan method,
                 dan ?? tidak melindungi itu dari objek yang belum ada saat
                 formulir dipakai untuk MEMBUAT kelas. --}}
            @php $mapelLama = old('mapel', isset($classroom) ? $classroom->mapelDiampu() : []); @endphp
            @for ($i = 0; $i < 2; $i++)
                <div class="form-group">
                    <label class="form-label">Mapel yang diampu {{ $i + 1 }}{{ $i === 0 ? '' : ' (opsional)' }}</label>
                    <input type="text" name="mapel[]" value="{{ $mapelLama[$i] ?? '' }}"
                           placeholder="cth: Matematika" class="form-input">
                </div>
            @endfor
            <p class="sm:col-span-2 form-hint">Dipakai saat mengisi presensi, supaya tiap pertemuan jelas mapelnya. Boleh diisi satu saja.</p>
        </div>
    </div>

    <!-- Major & Academic Year -->
    <div class="grid gap-5 sm:grid-cols-2">
        <div class="form-group">
            <label for="major" class="form-label">Jurusan</label>
            <input
                id="major"
                name="major"
                type="text"
                value="{{ old('major', $classroom->major ?? '') }}"
                placeholder="cth: RPL, TKJ, IPA"
                class="form-input"
            >
            @error('major')
                <p class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="academic_year" class="form-label">Tahun ajaran</label>
            <input
                id="academic_year"
                name="academic_year"
                type="text"
                value="{{ old('academic_year', $classroom->academic_year ?? '') }}"
                placeholder="cth: 2025/2026"
                class="form-input"
            >
            @error('academic_year')
                <p class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{--
        Seluruh bagian ini milik kelas PERWALIAN saja.

        Absensi otomatis mengirim magic link + PIN ke Seksi Absensi lewat grup
        atau nomor kelas. Pada kelas ajar, grup itu milik wali kelas LAIN —
        menyalakannya berarti menaruh tautan absensi beserta PIN di percakapan
        yang bukan milik pengirim.

        Dua kolom di atasnya (grup orang tua & nomor cadangan penerima) hanya
        melayani pengiriman itu, jadi ikut disembunyikan — menyisakan kolom
        "penerima magic link" pada kelas yang tidak punya magic link hanya
        membingungkan.

        Ini penjagaan TAMPILAN. Penjagaan sesungguhnya ada di model (memaksa
        auto_attendance mati) dan di penjadwal (melewati kelas ajar), sehingga
        jalur yang melewati formulir pun tetap tertutup.
    --}}
    <div x-show="jenis !== 'ajar'" x-cloak class="grid gap-5">

    <!-- Homeroom WA -->
    <div class="form-group">
{{-- Pemilih grup WhatsApp orang tua membaca $classroom->parent_group_wa, jadi
     hanya masuk akal pada kelas yang SUDAH ada. Pada halaman buat kelas belum
     ada apa pun untuk dibaca. --}}
@isset($classroom)
    @include('classes._parent_group')
@endisset

        <label for="homeroom_wa" class="form-label">Nomor cadangan penerima</label>
        <input
            id="homeroom_wa"
            name="homeroom_wa"
            type="tel"
            value="{{ old('homeroom_wa', $classroom->homeroom_wa ?? '') }}"
            placeholder="6281234567890"
            class="form-input"
        >
        <p class="form-hint">
            Dipakai bila Seksi Absensi dan Ketua Kelas belum punya nomor HP — misalnya di
            awal tahun ajaran. <b>Pengirimnya selalu nomor WhatsApp akun Anda</b>, karena
            hanya nomor itu yang tertaut ke gateway.
        </p>
        @error('homeroom_wa')
            <p class="form-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <!-- Auto Attendance -->
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="hidden" name="auto_attendance" value="0">
            <input type="checkbox"
                   name="auto_attendance"
                   value="1"
                   class="form-checkbox mt-0.5"
                   @checked(old('auto_attendance', $classroom->auto_attendance ?? false))>
            <div>
                <span class="font-medium text-slate-900">Absensi otomatis</span>
                <p class="mt-1 text-sm text-slate-500">
                    Sistem mengirim <b>satu</b> magic link per hari dari nomor WhatsApp akun
                    Anda ke petugas Seksi Absensi — berapa pun jumlah mata pelajaran hari itu.
                    Nomor Anda harus sudah tertaut di menu <b>Nomor WhatsApp</b>.
                    Waktunya beberapa menit sebelum jam pelajaran <b>pertama hari itu</b>,
                    mengikuti jadwal sehingga sistem blok tetap terlayani. Hari tanpa jadwal
                    dan tanggal di kalender libur otomatis dilewati.
                </p>
            </div>
        </label>
    </div>
    </div>
</div>
