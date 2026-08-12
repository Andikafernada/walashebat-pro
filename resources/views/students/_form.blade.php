@php
    $s = $student ?? null;

    $nilai = fn (string $kunci, $bawaan = '') => old($kunci, $s?->{$kunci} ?? $bawaan);

    $agamaPilihan = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'];
    $golonganPilihan = ['A', 'B', 'AB', 'O', 'Tidak Tahu'];

    /*
     * Kelas ajar: hanya NIS dan nama.
     *
     * Guru mapel tidak berhak atas biodata, alamat, data orang tua, maupun
     * nomor HP siswa di kelas yang wali kelasnya orang lain. Kolom yang
     * terlihat mengundang untuk diisi — dan begitu terisi, lahir salinan kedua
     * biodata yang menyimpang dari milik wali kelas aslinya tanpa ada yang tahu
     * mana yang benar. Menyembunyikannya di sini sekaligus membuat formulir
     * guru mapel selesai dalam dua isian, bukan empat puluh.
     */
    $ajar = ($classroom ?? null)?->kelasAjar() ?? false;
@endphp

{{--
    Formulir dibagi menjadi beberapa bagian yang bisa dilipat. Empat puluh
    kolom dalam satu kolom panjang membuat guru berhenti mengisi di tengah
    jalan; yang wajib ada di atas dan langsung terbuka, sisanya menyusul.
--}}
<div class="space-y-4">

    {{-- ============ IDENTITAS ============ --}}
    <section class="rounded-2xl border border-slate-200">
        <button type="button" @click="bagian = bagian === 'identitas' ? '' : 'identitas'"
                class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left">
            <span>
                <span class="block text-sm font-semibold text-slate-900">Identitas siswa</span>
                <span class="block text-xs text-slate-500">Nama, NIS, jenis kelamin, kontak</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                 :class="bagian === 'identitas' ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="bagian === 'identitas'" data-bagian="identitas" class="border-t border-slate-100 p-4">
            <div class="grid gap-5">
                <div class="form-group">
                    <label for="name" class="form-label form-label--required">Nama lengkap</label>
                    <input id="name" name="name" type="text" required autocomplete="name"
                           value="{{ $nilai('name') }}" placeholder="cth: Ahmad Fauzi"
                           class="form-input @error('name') form-input--error @enderror">
                    @error('name') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="nis" class="form-label">NIS</label>
                        <input id="nis" name="nis" type="text" autocomplete="off"
                               value="{{ $nilai('nis') }}" placeholder="cth: 2024001"
                               class="form-input @error('nis') form-input--error @enderror">
                        <p class="form-hint">Dipakai untuk mencocokkan data saat impor Excel.</p>
                        @error('nis') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    @unless ($ajar)
                    <div class="form-group">
                        <label for="nisn" class="form-label">NISN</label>
                        <input id="nisn" name="nisn" type="text" autocomplete="off"
                               value="{{ $nilai('nisn') }}" placeholder="cth: 0012345678"
                               class="form-input">
                        @error('nisn') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                    @endunless
                </div>

                @unless ($ajar)
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="gender" class="form-label">Jenis kelamin</label>
                        @php $g = $nilai('gender'); @endphp
                        <select id="gender" name="gender" class="form-select">
                            <option value="">— pilih —</option>
                            <option value="L" @selected($g === 'L')>Laki-laki</option>
                            <option value="P" @selected($g === 'P')>Perempuan</option>
                        </select>
                        @error('gender') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">HP siswa</label>
                        <input id="phone" name="phone" type="tel" autocomplete="tel"
                               value="{{ $nilai('phone') }}" placeholder="cth: 081234567890"
                               class="form-input">
                        @error('phone') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="parent_phone" class="form-label">HP orang tua / wali</label>
                    <input id="parent_phone" name="parent_phone" type="tel" autocomplete="tel"
                           value="{{ $nilai('parent_phone') }}" placeholder="cth: 081298765432"
                           class="form-input">
                    <p class="form-hint">Nomor ini yang menerima notifikasi absensi via WhatsApp.</p>
                    @error('parent_phone') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="foto" class="form-label">Foto siswa</label>
                    <div class="flex items-center gap-4">
                        @if ($s?->photoUrl())
                            <img src="{{ $s->photoUrl() }}" alt="Foto {{ $s->name }}"
                                 class="h-16 w-16 rounded-2xl object-cover">
                        @endif
                        <input id="foto" name="foto" type="file" accept="image/*" class="form-input">
                    </div>
                    <p class="form-hint">JPG, PNG, atau WebP. Maksimal 2 MB.</p>
                    @error('foto') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                </div>
                @endunless

                {{-- Tetap ada untuk kelas ajar: ini bukan biodata, melainkan penanda
                     apakah siswa masih ikut kelas. Tanpa ini guru mapel tidak punya
                     cara mengeluarkan siswa yang pindah dari daftar absensinya. --}}
                <div class="form-group">
                    <label class="flex items-start gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox mt-0.5"
                               @checked(old('is_active', $s?->is_active ?? true))>
                        <span>
                            <span class="block text-sm font-medium text-slate-800">Siswa aktif</span>
                            <span class="block text-xs text-slate-500">Nonaktifkan bila siswa pindah atau berhenti.</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </section>

    @unless ($ajar)
    {{-- ============ DATA PRIBADI ============ --}}
    <section class="rounded-2xl border border-slate-200">
        <button type="button" @click="bagian = bagian === 'pribadi' ? '' : 'pribadi'"
                class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left">
            <span>
                <span class="block text-sm font-semibold text-slate-900">Data pribadi</span>
                <span class="block text-xs text-slate-500">Tempat &amp; tanggal lahir, agama, fisik</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                 :class="bagian === 'pribadi' ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="bagian === 'pribadi'" data-bagian="pribadi" x-cloak class="border-t border-slate-100 p-4">
            <div class="grid gap-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="tempat_lahir" class="form-label">Tempat lahir</label>
                        <input id="tempat_lahir" name="tempat_lahir" type="text"
                               value="{{ $nilai('tempat_lahir') }}" placeholder="cth: Bandung" class="form-input">
                        @error('tempat_lahir') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="tanggal_lahir" class="form-label">Tanggal lahir</label>
                        <input id="tanggal_lahir" name="tanggal_lahir" type="date"
                               value="{{ old('tanggal_lahir', $s?->tanggal_lahir?->format('Y-m-d')) }}"
                               class="form-input">
                        @error('tanggal_lahir') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="agama" class="form-label">Agama</label>
                        @php $a = $nilai('agama'); @endphp
                        <select id="agama" name="agama" class="form-select">
                            <option value="">— pilih —</option>
                            @foreach ($agamaPilihan as $opsi)
                                <option value="{{ $opsi }}" @selected($a === $opsi)>{{ $opsi }}</option>
                            @endforeach
                        </select>
                        @error('agama') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="golongan_darah" class="form-label">Golongan darah</label>
                        @php $gd = $nilai('golongan_darah'); @endphp
                        <select id="golongan_darah" name="golongan_darah" class="form-select">
                            <option value="">— pilih —</option>
                            @foreach ($golonganPilihan as $opsi)
                                <option value="{{ $opsi }}" @selected($gd === $opsi)>{{ $opsi }}</option>
                            @endforeach
                        </select>
                        @error('golongan_darah') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-4">
                    <div class="form-group">
                        <label for="anak_ke" class="form-label">Anak ke</label>
                        <input id="anak_ke" name="anak_ke" type="number" min="1" max="20"
                               value="{{ $nilai('anak_ke') }}" class="form-input">
                        @error('anak_ke') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="jumlah_saudara" class="form-label">Jumlah saudara</label>
                        <input id="jumlah_saudara" name="jumlah_saudara" type="number" min="0" max="20"
                               value="{{ $nilai('jumlah_saudara') }}" class="form-input">
                        @error('jumlah_saudara') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="tinggi_badan_cm" class="form-label">Tinggi (cm)</label>
                        <input id="tinggi_badan_cm" name="tinggi_badan_cm" type="number" min="50" max="250"
                               value="{{ $nilai('tinggi_badan_cm') }}" class="form-input">
                        @error('tinggi_badan_cm') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="berat_badan_kg" class="form-label">Berat (kg)</label>
                        <input id="berat_badan_kg" name="berat_badan_kg" type="number" min="10" max="200"
                               value="{{ $nilai('berat_badan_kg') }}" class="form-input">
                        @error('berat_badan_kg') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="hobi" class="form-label">Hobi</label>
                        <input id="hobi" name="hobi" type="text" value="{{ $nilai('hobi') }}"
                               placeholder="cth: Futsal" class="form-input">
                        @error('hobi') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="cita_cita" class="form-label">Cita-cita</label>
                        <input id="cita_cita" name="cita_cita" type="text" value="{{ $nilai('cita_cita') }}"
                               placeholder="cth: Teknisi jaringan" class="form-input">
                        @error('cita_cita') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ DOMISILI ============ --}}
    <section class="rounded-2xl border border-slate-200">
        <button type="button" @click="bagian = bagian === 'domisili' ? '' : 'domisili'"
                class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left">
            <span>
                <span class="block text-sm font-semibold text-slate-900">Tempat tinggal</span>
                <span class="block text-xs text-slate-500">Alamat, kelurahan, jarak, transportasi</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                 :class="bagian === 'domisili' ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="bagian === 'domisili'" data-bagian="domisili" x-cloak class="border-t border-slate-100 p-4">
            <div class="grid gap-5">
                <div class="form-group">
                    <label for="address" class="form-label">Alamat</label>
                    <textarea id="address" name="address" rows="2" class="form-textarea"
                              placeholder="Jalan, nomor rumah, patokan">{{ $nilai('address') }}</textarea>
                    @error('address') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div class="form-group">
                        <label for="rt_rw" class="form-label">RT / RW</label>
                        <input id="rt_rw" name="rt_rw" type="text" value="{{ $nilai('rt_rw') }}"
                               placeholder="cth: 003/007" class="form-input">
                        @error('rt_rw') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="kelurahan" class="form-label">Kelurahan / desa</label>
                        <input id="kelurahan" name="kelurahan" type="text" value="{{ $nilai('kelurahan') }}"
                               class="form-input">
                        @error('kelurahan') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="kecamatan" class="form-label">Kecamatan</label>
                        <input id="kecamatan" name="kecamatan" type="text" value="{{ $nilai('kecamatan') }}"
                               class="form-input">
                        @error('kecamatan') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="jarak_rumah_km" class="form-label">Jarak ke sekolah (km)</label>
                        <input id="jarak_rumah_km" name="jarak_rumah_km" type="number" step="0.1" min="0"
                               value="{{ $nilai('jarak_rumah_km') }}" class="form-input">
                        @error('jarak_rumah_km') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="moda_transportasi" class="form-label">Transportasi</label>
                        <input id="moda_transportasi" name="moda_transportasi" type="text"
                               value="{{ $nilai('moda_transportasi') }}"
                               placeholder="cth: Sepeda motor, angkot, jalan kaki" class="form-input">
                        @error('moda_transportasi') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ ORANG TUA ============ --}}
    <section class="rounded-2xl border border-slate-200">
        <button type="button" @click="bagian = bagian === 'ortu' ? '' : 'ortu'"
                class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left">
            <span>
                <span class="block text-sm font-semibold text-slate-900">Orang tua / wali</span>
                <span class="block text-xs text-slate-500">Nama, pekerjaan, alamat, bantuan sosial</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                 :class="bagian === 'ortu' ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="bagian === 'ortu'" data-bagian="ortu" x-cloak class="border-t border-slate-100 p-4">
            <div class="grid gap-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="nama_ayah" class="form-label">Nama ayah</label>
                        <input id="nama_ayah" name="nama_ayah" type="text" value="{{ $nilai('nama_ayah') }}" class="form-input">
                        @error('nama_ayah') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label for="pekerjaan_ayah" class="form-label">Pekerjaan ayah</label>
                        <input id="pekerjaan_ayah" name="pekerjaan_ayah" type="text" value="{{ $nilai('pekerjaan_ayah') }}" class="form-input">
                        @error('pekerjaan_ayah') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="nama_ibu" class="form-label">Nama ibu</label>
                        <input id="nama_ibu" name="nama_ibu" type="text" value="{{ $nilai('nama_ibu') }}" class="form-input">
                        @error('nama_ibu') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label for="pekerjaan_ibu" class="form-label">Pekerjaan ibu</label>
                        <input id="pekerjaan_ibu" name="pekerjaan_ibu" type="text" value="{{ $nilai('pekerjaan_ibu') }}" class="form-input">
                        @error('pekerjaan_ibu') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="nama_wali" class="form-label">Nama wali</label>
                        <input id="nama_wali" name="nama_wali" type="text" value="{{ $nilai('nama_wali') }}" class="form-input">
                        <p class="form-hint">Isi hanya bila siswa tidak tinggal dengan orang tua.</p>
                        @error('nama_wali') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label for="pekerjaan_wali" class="form-label">Pekerjaan wali</label>
                        <input id="pekerjaan_wali" name="pekerjaan_wali" type="text" value="{{ $nilai('pekerjaan_wali') }}" class="form-input">
                        @error('pekerjaan_wali') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="alamat_ortu" class="form-label">Alamat orang tua</label>
                    <textarea id="alamat_ortu" name="alamat_ortu" rows="2" class="form-textarea"
                              placeholder="Kosongkan bila sama dengan alamat siswa">{{ $nilai('alamat_ortu') }}</textarea>
                    @error('alamat_ortu') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3">
                        <input type="hidden" name="penerima_kip" value="0">
                        <input type="checkbox" name="penerima_kip" value="1" class="form-checkbox mt-0.5"
                               @checked(old('penerima_kip', $s?->penerima_kip ?? false))>
                        <span>
                            <span class="block text-sm font-medium text-slate-800">Penerima KIP</span>
                            <span class="block text-xs text-slate-500">Kartu Indonesia Pintar</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3">
                        <input type="hidden" name="penerima_pkh" value="0">
                        <input type="checkbox" name="penerima_pkh" value="1" class="form-checkbox mt-0.5"
                               @checked(old('penerima_pkh', $s?->penerima_pkh ?? false))>
                        <span>
                            <span class="block text-sm font-medium text-slate-800">Penerima PKH</span>
                            <span class="block text-xs text-slate-500">Program Keluarga Harapan</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ RIWAYAT SEKOLAH ============ --}}
    <section class="rounded-2xl border border-slate-200">
        <button type="button" @click="bagian = bagian === 'sekolah' ? '' : 'sekolah'"
                class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left">
            <span>
                <span class="block text-sm font-semibold text-slate-900">Riwayat sekolah</span>
                <span class="block text-xs text-slate-500">Asal sekolah, tahun masuk, kompetensi keahlian</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                 :class="bagian === 'sekolah' ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="bagian === 'sekolah'" data-bagian="sekolah" x-cloak class="border-t border-slate-100 p-4">
            <div class="grid gap-5">
                <div class="form-group">
                    <label for="asal_sekolah" class="form-label">Asal sekolah</label>
                    <input id="asal_sekolah" name="asal_sekolah" type="text" value="{{ $nilai('asal_sekolah') }}"
                           placeholder="cth: SMPN 3 Bandung" class="form-input">
                    @error('asal_sekolah') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="form-group">
                        <label for="tahun_masuk" class="form-label">Tahun masuk</label>
                        <input id="tahun_masuk" name="tahun_masuk" type="number" min="2000" max="2100"
                               value="{{ $nilai('tahun_masuk') }}" placeholder="cth: 2024" class="form-input">
                        @error('tahun_masuk') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="kompetensi_keahlian" class="form-label">Kompetensi keahlian</label>
                        <input id="kompetensi_keahlian" name="kompetensi_keahlian" type="text"
                               value="{{ $nilai('kompetensi_keahlian') }}"
                               placeholder="cth: Teknik Komputer dan Jaringan" class="form-input">
                        @error('kompetensi_keahlian') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endunless
</div>
