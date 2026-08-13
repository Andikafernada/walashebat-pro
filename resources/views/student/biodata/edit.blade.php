@extends('layouts.student')
@section('title', 'Edit Biodata')

@section('content')
<div class="p-6 lg:p-8 space-y-6">
    <!-- Header -->
    <div class="card flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Edit Biodata</h1>
            <p class="text-slate-500 mt-1">Perbarui data pribadi dan kontak orang tua</p>
        </div>
        <a href="{{ route('student.biodata') }}" class="btn-secondary btn-secondary--sm">Batal</a>
    </div>

    @if ($errors->any())
        <div class="alert alert--danger">
            <div class="alert__body">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('student.biodata.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Data Pribadi -->
        <div class="card space-y-4">
            <h2 class="font-semibold text-slate-900">Data Pribadi</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $student->tempat_lahir) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $student->tanggal_lahir?->format('Y-m-d')) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Agama</label>
                    <select name="agama" class="form-input">
                        <option value="">- Pilih -</option>
                        @foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'] as $agama)
                            <option value="{{ $agama }}" @selected(old('agama', $student->agama) === $agama)>{{ $agama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Golongan Darah</label>
                    <select name="golongan_darah" class="form-input">
                        <option value="">- Pilih -</option>
                        @foreach (['A', 'B', 'AB', 'O', 'Tidak Tahu'] as $gol)
                            <option value="{{ $gol }}" @selected(old('golongan_darah', $student->golongan_darah) === $gol)>{{ $gol }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Anak Ke-</label>
                    <input type="number" min="1" name="anak_ke" value="{{ old('anak_ke', $student->anak_ke) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Jumlah Saudara</label>
                    <input type="number" min="0" name="jumlah_saudara" value="{{ old('jumlah_saudara', $student->jumlah_saudara) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Tinggi Badan (cm)</label>
                    <input type="number" name="tinggi_badan_cm" value="{{ old('tinggi_badan_cm', $student->tinggi_badan_cm) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Berat Badan (kg)</label>
                    <input type="number" name="berat_badan_kg" value="{{ old('berat_badan_kg', $student->berat_badan_kg) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Hobi</label>
                    <input type="text" name="hobi" value="{{ old('hobi', $student->hobi) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Cita-cita</label>
                    <input type="text" name="cita_cita" value="{{ old('cita_cita', $student->cita_cita) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Asal Sekolah</label>
                    <input type="text" name="asal_sekolah" value="{{ old('asal_sekolah', $student->asal_sekolah) }}" class="form-input">
                </div>
            </div>
        </div>

        <!-- Alamat -->
        <div class="card space-y-4">
            <h2 class="font-semibold text-slate-900">Alamat</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="form-label">Alamat Lengkap</label>
                    {{-- `address`, BUKAN `alamat`: kolom itu tidak pernah ada di
                         tabel students. Akibatnya isian ini tampil kosong walau
                         alamatnya sudah tercatat, dan yang diketik siswa dibuang
                         diam-diam oleh mass-assignment — halaman tetap menjawab
                         "Biodata berhasil diperbarui". --}}
                    <textarea name="address" rows="2" class="form-input">{{ old('address', $student->address) }}</textarea>
                </div>
                <div>
                    <label class="form-label">RT/RW</label>
                    <input type="text" name="rt_rw" value="{{ old('rt_rw', $student->rt_rw) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Kelurahan</label>
                    <input type="text" name="kelurahan" value="{{ old('kelurahan', $student->kelurahan) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Kecamatan</label>
                    <input type="text" name="kecamatan" value="{{ old('kecamatan', $student->kecamatan) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Jarak Rumah (km)</label>
                    <input type="number" step="0.1" name="jarak_rumah_km" value="{{ old('jarak_rumah_km', $student->jarak_rumah_km) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Moda Transportasi</label>
                    <input type="text" name="moda_transportasi" value="{{ old('moda_transportasi', $student->moda_transportasi) }}" class="form-input">
                </div>
            </div>
        </div>

        <!-- Orang Tua -->
        <div class="card space-y-4">
            <h2 class="font-semibold text-slate-900">Data Orang Tua</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Nama Ayah</label>
                    <input type="text" name="nama_ayah" value="{{ old('nama_ayah', $student->nama_ayah) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Pekerjaan Ayah</label>
                    <input type="text" name="pekerjaan_ayah" value="{{ old('pekerjaan_ayah', $student->pekerjaan_ayah) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Nama Ibu</label>
                    <input type="text" name="nama_ibu" value="{{ old('nama_ibu', $student->nama_ibu) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Pekerjaan Ibu</label>
                    <input type="text" name="pekerjaan_ibu" value="{{ old('pekerjaan_ibu', $student->pekerjaan_ibu) }}" class="form-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Alamat Orang Tua</label>
                    <textarea name="alamat_ortu" rows="2" class="form-input">{{ old('alamat_ortu', $student->alamat_ortu) }}</textarea>
                </div>
                <div>
                    <label class="form-label">No. WhatsApp Orang Tua</label>
                    <input type="text" name="parent_phone" value="{{ old('parent_phone', $student->parent_phone) }}" placeholder="8123456789" class="form-input">
                </div>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('student.biodata') }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
