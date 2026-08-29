@php
    $s = $student ?? null;

    $nilai = fn (string $kunci, $bawaan = '') => old($kunci, $s?->{$kunci} ?? $bawaan);

    $agamaPilihan = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'];
    $golonganPilihan = ['A', 'B', 'AB', 'O', 'Tidak Tahu'];

    $ajar = ($classroom ?? null)?->kelasAjar() ?? false;
@endphp

<div class="space-y-4">

    {{-- ============ IDENTITAS ============ --}}
    <section class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
        <button type="button" @click="bagian = bagian === 'identitas' ? '' : 'identitas'"
                class="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left hover:bg-slate-50/50 transition-colors">
            <span>
                <span class="block text-xs font-bold text-slate-900">Identitas Siswa</span>
                <span class="block text-[11px] text-slate-500">Nama, NIS, jenis kelamin, kontak</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                 :class="bagian === 'identitas' ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="bagian === 'identitas'" data-bagian="identitas" class="border-t border-emerald-100 p-4">
            <div class="grid gap-4">
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input id="name" name="name" type="text" required autocomplete="name"
                           value="{{ $nilai('name') }}" placeholder="cth: Ahmad Fauzi"
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('name') border-rose-400 @enderror">
                    @error('name') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nis" class="block text-xs font-semibold text-slate-700 mb-1">NIS</label>
                        <input id="nis" name="nis" type="text" inputmode="numeric" autocomplete="off"
                               value="{{ $nilai('nis') }}" placeholder="cth: 2024001"
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 @error('nis') border-rose-400 @enderror">
                        <p class="mt-1 text-xs text-slate-400">Dipakai untuk mencocokkan data saat impor Excel.</p>
                        @error('nis') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>

                    @unless ($ajar)
                    <div>
                        <label for="nisn" class="block text-xs font-semibold text-slate-700 mb-1">NISN</label>
                        <input id="nisn" name="nisn" type="text" inputmode="numeric" autocomplete="off"
                               value="{{ $nilai('nisn') }}" placeholder="cth: 0012345678"
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('nisn') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                    @endunless
                </div>

                @unless ($ajar)
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="gender" class="block text-xs font-semibold text-slate-700 mb-1">Jenis Kelamin</label>
                        @php $g = $nilai('gender'); @endphp
                        <select id="gender" name="gender" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">— pilih —</option>
                            <option value="L" @selected($g === 'L')>Laki-laki</option>
                            <option value="P" @selected($g === 'P')>Perempuan</option>
                        </select>
                        @error('gender') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-semibold text-slate-700 mb-1">HP Siswa</label>
                        <input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel"
                               value="{{ $nilai('phone') }}" placeholder="cth: 081234567890"
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('phone') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="parent_phone" class="block text-xs font-semibold text-slate-700 mb-1">HP Orang Tua / Wali</label>
                    <input id="parent_phone" name="parent_phone" type="tel" inputmode="numeric" autocomplete="tel"
                           value="{{ $nilai('parent_phone') }}" placeholder="cth: 081298765432"
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <p class="mt-1 text-xs text-slate-400">Nomor ini yang menerima notifikasi absensi via WhatsApp.</p>
                    @error('parent_phone') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="foto" class="block text-xs font-semibold text-slate-700 mb-1">Foto Siswa</label>
                    <div class="flex items-center gap-4">
                        @if ($s?->photoUrl())
                            <img src="{{ $s->photoUrl() }}" alt="Foto {{ $s->name }}"
                                 class="h-14 w-14 rounded-xl object-cover border border-slate-200">
                        @endif
                        <input id="foto" name="foto" type="file" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                    <p class="mt-1 text-xs text-slate-400">JPG, PNG, atau WebP. Maksimal 2 MB.</p>
                    @error('foto') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                </div>
                @endunless

                <div>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="mt-0.5 accent-emerald-600 rounded"
                               @checked(old('is_active', $s?->is_active ?? true))>
                        <span>
                            <span class="block text-xs font-bold text-slate-900">Siswa Aktif</span>
                            <span class="block text-[11px] text-slate-500">Nonaktifkan bila siswa pindah atau berhenti.</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </section>

    @unless ($ajar)
    {{-- ============ BIODATA LENGKAP ============ --}}
    <section class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
        <button type="button" @click="bagian = bagian === 'pribadi' ? '' : 'pribadi'"
                class="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left hover:bg-slate-50/50 transition-colors">
            <span>
                <span class="block text-xs font-bold text-slate-900">Biodata Lengkap</span>
                <span class="block text-[11px] text-slate-500">Kelahiran, agama, kewarganegaraan, fisik</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                 :class="bagian === 'pribadi' ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="bagian === 'pribadi'" data-bagian="pribadi" x-cloak class="border-t border-emerald-100 p-4">
            <div class="grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="tempat_lahir" class="block text-xs font-semibold text-slate-700 mb-1">Tempat Lahir</label>
                        <input id="tempat_lahir" name="tempat_lahir" type="text" value="{{ $nilai('tempat_lahir') }}" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('tempat_lahir') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="tanggal_lahir" class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Lahir</label>
                        <input id="tanggal_lahir" name="tanggal_lahir" type="date" value="{{ $nilai('tanggal_lahir') }}" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('tanggal_lahir') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="agama" class="block text-xs font-semibold text-slate-700 mb-1">Agama</label>
                        <select id="agama" name="agama" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">— pilih —</option>
                            @foreach ($agamaPilihan as $a)
                                <option value="{{ $a }}" @selected($nilai('agama') === $a)>{{ $a }}</option>
                            @endforeach
                        </select>
                        @error('agama') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="kewarganegaraan" class="block text-xs font-semibold text-slate-700 mb-1">Kewarganegaraan</label>
                        <input id="kewarganegaraan" name="kewarganegaraan" type="text" value="{{ $nilai('kewarganegaraan', 'WNI') }}" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('kewarganegaraan') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="anak_ke" class="block text-xs font-semibold text-slate-700 mb-1">Anak ke-</label>
                        <input id="anak_ke" name="anak_ke" type="number" inputmode="numeric" min="1" max="30" value="{{ $nilai('anak_ke') }}" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('anak_ke') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="jumlah_saudara" class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Saudara</label>
                        <input id="jumlah_saudara" name="jumlah_saudara" type="number" inputmode="numeric" min="0" max="30" value="{{ $nilai('jumlah_saudara') }}" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('jumlah_saudara') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="address" class="block text-xs font-semibold text-slate-700 mb-1">Alamat Siswa</label>
                    <textarea id="address" name="address" rows="2" 
                              class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ $nilai('address') }}</textarea>
                    @error('address') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </section>

    {{-- ============ ORANG TUA ============ --}}
    <section class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
        <button type="button" @click="bagian = bagian === 'ortu' ? '' : 'ortu'"
                class="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left hover:bg-slate-50/50 transition-colors">
            <span>
                <span class="block text-xs font-bold text-slate-900">Data Orang Tua / Wali</span>
                <span class="block text-[11px] text-slate-500">Nama, pekerjaan, bantuan sosial</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                 :class="bagian === 'ortu' ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="bagian === 'ortu'" data-bagian="ortu" x-cloak class="border-t border-emerald-100 p-4">
            <div class="grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nama_ayah" class="block text-xs font-semibold text-slate-700 mb-1">Nama Ayah</label>
                        <input id="nama_ayah" name="nama_ayah" type="text" value="{{ $nilai('nama_ayah') }}" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('nama_ayah') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="pekerjaan_ayah" class="block text-xs font-semibold text-slate-700 mb-1">Pekerjaan Ayah</label>
                        <input id="pekerjaan_ayah" name="pekerjaan_ayah" type="text" value="{{ $nilai('pekerjaan_ayah') }}" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('pekerjaan_ayah') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nama_ibu" class="block text-xs font-semibold text-slate-700 mb-1">Nama Ibu</label>
                        <input id="nama_ibu" name="nama_ibu" type="text" value="{{ $nilai('nama_ibu') }}" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('nama_ibu') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="pekerjaan_ibu" class="block text-xs font-semibold text-slate-700 mb-1">Pekerjaan Ibu</label>
                        <input id="pekerjaan_ibu" name="pekerjaan_ibu" type="text" value="{{ $nilai('pekerjaan_ibu') }}" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('pekerjaan_ibu') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nama_wali" class="block text-xs font-semibold text-slate-700 mb-1">Nama Wali</label>
                        <input id="nama_wali" name="nama_wali" type="text" value="{{ $nilai('nama_wali') }}" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <p class="mt-1 text-xs text-slate-400">Isi hanya bila siswa tidak tinggal dengan orang tua.</p>
                        @error('nama_wali') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="pekerjaan_wali" class="block text-xs font-semibold text-slate-700 mb-1">Pekerjaan Wali</label>
                        <input id="pekerjaan_wali" name="pekerjaan_wali" type="text" value="{{ $nilai('pekerjaan_wali') }}" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('pekerjaan_wali') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-3 bg-slate-50/50 cursor-pointer">
                        <input type="hidden" name="penerima_kip" value="0">
                        <input type="checkbox" name="penerima_kip" value="1" class="mt-0.5 accent-emerald-600 rounded"
                               @checked(old('penerima_kip', $s?->penerima_kip ?? false))>
                        <span>
                            <span class="block text-xs font-bold text-slate-900">Penerima KIP</span>
                            <span class="block text-[11px] text-slate-500">Kartu Indonesia Pintar</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-3 bg-slate-50/50 cursor-pointer">
                        <input type="hidden" name="penerima_pkh" value="0">
                        <input type="checkbox" name="penerima_pkh" value="1" class="mt-0.5 accent-emerald-600 rounded"
                               @checked(old('penerima_pkh', $s?->penerima_pkh ?? false))>
                        <span>
                            <span class="block text-xs font-bold text-slate-900">Penerima PKH</span>
                            <span class="block text-[11px] text-slate-500">Program Keluarga Harapan</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ RIWAYAT SEKOLAH ============ --}}
    <section class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
        <button type="button" @click="bagian = bagian === 'sekolah' ? '' : 'sekolah'"
                class="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left hover:bg-slate-50/50 transition-colors">
            <span>
                <span class="block text-xs font-bold text-slate-900">Riwayat Sekolah</span>
                <span class="block text-[11px] text-slate-500">Asal sekolah, tahun masuk, kompetensi keahlian</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
                 :class="bagian === 'sekolah' ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="bagian === 'sekolah'" data-bagian="sekolah" x-cloak class="border-t border-emerald-100 p-4">
            <div class="grid gap-4">
                <div>
                    <label for="asal_sekolah" class="block text-xs font-semibold text-slate-700 mb-1">Asal Sekolah</label>
                    <input id="asal_sekolah" name="asal_sekolah" type="text" value="{{ $nilai('asal_sekolah') }}"
                           placeholder="cth: SMPN 3 Bandung" 
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @error('asal_sekolah') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="tahun_masuk" class="block text-xs font-semibold text-slate-700 mb-1">Tahun Masuk</label>
                        <input id="tahun_masuk" name="tahun_masuk" type="number" inputmode="numeric" min="2000" max="2100"
                               value="{{ $nilai('tahun_masuk') }}" placeholder="cth: 2024" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('tahun_masuk') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="kompetensi_keahlian" class="block text-xs font-semibold text-slate-700 mb-1">Kompetensi Keahlian</label>
                        <input id="kompetensi_keahlian" name="kompetensi_keahlian" type="text"
                               value="{{ $nilai('kompetensi_keahlian') }}"
                               placeholder="cth: Teknik Komputer dan Jaringan" 
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('kompetensi_keahlian') <p class="mt-1 text-xs text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endunless
</div>
