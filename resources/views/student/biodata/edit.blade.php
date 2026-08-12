@extends('layouts.student')
@section('title', 'Edit Biodata')

@section('content')
<div class="p-6 lg:p-8 space-y-6">
    <!-- Header -->
    <div class="glass-card flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Edit Biodata</h1>
            <p class="text-slate-500 mt-1">Perbarui data pribadi dan kontak orang tua</p>
        </div>
        <a href="{{ route('student.biodata') }}" class="btn btn-secondary">Batal</a>
    </div>

    @if ($errors->any())
        <div class="glass-card border border-red-200 bg-red-50">
            <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('student.biodata.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Data Pribadi -->
        <div class="glass-card space-y-4">
            <h2 class="font-bold text-slate-900">Data Pribadi</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $student->tempat_lahir) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $student->tanggal_lahir?->format('Y-m-d')) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Agama</label>
                    <select name="agama" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">- Pilih -</option>
                        @foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'] as $agama)
                            <option value="{{ $agama }}" @selected(old('agama', $student->agama) === $agama)>{{ $agama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Golongan Darah</label>
                    <select name="golongan_darah" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">- Pilih -</option>
                        @foreach (['A', 'B', 'AB', 'O', 'Tidak Tahu'] as $gol)
                            <option value="{{ $gol }}" @selected(old('golongan_darah', $student->golongan_darah) === $gol)>{{ $gol }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Anak Ke-</label>
                    <input type="number" min="1" name="anak_ke" value="{{ old('anak_ke', $student->anak_ke) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Jumlah Saudara</label>
                    <input type="number" min="0" name="jumlah_saudara" value="{{ old('jumlah_saudara', $student->jumlah_saudara) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Tinggi Badan (cm)</label>
                    <input type="number" name="tinggi_badan_cm" value="{{ old('tinggi_badan_cm', $student->tinggi_badan_cm) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Berat Badan (kg)</label>
                    <input type="number" name="berat_badan_kg" value="{{ old('berat_badan_kg', $student->berat_badan_kg) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Hobi</label>
                    <input type="text" name="hobi" value="{{ old('hobi', $student->hobi) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Cita-cita</label>
                    <input type="text" name="cita_cita" value="{{ old('cita_cita', $student->cita_cita) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Asal Sekolah</label>
                    <input type="text" name="asal_sekolah" value="{{ old('asal_sekolah', $student->asal_sekolah) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
        </div>

        <!-- Alamat -->
        <div class="glass-card space-y-4">
            <h2 class="font-bold text-slate-900">Alamat</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">Alamat Lengkap</label>
                    {{-- `address`, BUKAN `alamat`: kolom itu tidak pernah ada di
                         tabel students. Akibatnya isian ini tampil kosong walau
                         alamatnya sudah tercatat, dan yang diketik siswa dibuang
                         diam-diam oleh mass-assignment — halaman tetap menjawab
                         "Biodata berhasil diperbarui". --}}
                    <textarea name="address" rows="2" class="w-full px-3 py-2 border rounded-lg">{{ old('address', $student->address) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">RT/RW</label>
                    <input type="text" name="rt_rw" value="{{ old('rt_rw', $student->rt_rw) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Kelurahan</label>
                    <input type="text" name="kelurahan" value="{{ old('kelurahan', $student->kelurahan) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Kecamatan</label>
                    <input type="text" name="kecamatan" value="{{ old('kecamatan', $student->kecamatan) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Jarak Rumah (km)</label>
                    <input type="number" step="0.1" name="jarak_rumah_km" value="{{ old('jarak_rumah_km', $student->jarak_rumah_km) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Moda Transportasi</label>
                    <input type="text" name="moda_transportasi" value="{{ old('moda_transportasi', $student->moda_transportasi) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
        </div>

        <!-- Orang Tua -->
        <div class="glass-card space-y-4">
            <h2 class="font-bold text-slate-900">Data Orang Tua</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Nama Ayah</label>
                    <input type="text" name="nama_ayah" value="{{ old('nama_ayah', $student->nama_ayah) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Pekerjaan Ayah</label>
                    <input type="text" name="pekerjaan_ayah" value="{{ old('pekerjaan_ayah', $student->pekerjaan_ayah) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Nama Ibu</label>
                    <input type="text" name="nama_ibu" value="{{ old('nama_ibu', $student->nama_ibu) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Pekerjaan Ibu</label>
                    <input type="text" name="pekerjaan_ibu" value="{{ old('pekerjaan_ibu', $student->pekerjaan_ibu) }}" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">Alamat Orang Tua</label>
                    <textarea name="alamat_ortu" rows="2" class="w-full px-3 py-2 border rounded-lg">{{ old('alamat_ortu', $student->alamat_ortu) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">No. WhatsApp Orang Tua</label>
                    <input type="text" name="parent_phone" value="{{ old('parent_phone', $student->parent_phone) }}" placeholder="8123456789" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('student.biodata') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
