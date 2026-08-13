@extends('layouts.student')
@section('title', 'Refleksi Baru')

@section('content')
<div class="p-6 lg:p-8 space-y-6">
    <div class="card flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Refleksi Baru</h1>
            <p class="text-slate-500 mt-1">Luangkan waktu sejenak untuk merefleksikan dirimu</p>
        </div>
        <a href="{{ route('student.portfolio') }}" class="btn-secondary btn-secondary--sm">Batal</a>
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

    <div class="card">
        <form action="{{ route('student.reflection.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="form-label">Dimensi (opsional)</label>
                <select name="character_dimension_id" class="form-input">
                    <option value="">- Umum, tidak terkait dimensi tertentu -</option>
                    @foreach ($dimensions as $dim)
                        <option value="{{ $dim->id }}" @selected(old('character_dimension_id', $dimension?->id) == $dim->id)>{{ $dim->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="reflection_date" value="{{ old('reflection_date', today()->format('Y-m-d')) }}" required max="{{ today()->format('Y-m-d') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Periode</label>
                    <select name="period" required class="form-input">
                        <option value="daily" selected>Harian</option>
                        <option value="weekly">Mingguan</option>
                        <option value="monthly">Bulanan</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="form-label">Penilaian Diri (1-5)</label>
                <select name="self_rating" class="form-input">
                    <option value="">- Tidak dinilai -</option>
                    @for ($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="form-label">Apa yang berjalan baik?</label>
                <textarea name="what_went_well" rows="3" class="form-input"></textarea>
            </div>
            <div>
                <label class="form-label">Apa yang perlu diperbaiki?</label>
                <textarea name="what_to_improve" rows="3" class="form-input"></textarea>
            </div>
            <div>
                <label class="form-label">Rencana Aksi</label>
                <textarea name="action_plan" rows="3" class="form-input"></textarea>
            </div>

            {{-- Tiga isian di atas seluruhnya penilaian diri sendiri. Pertanyaan
                 ini memaksa siswa melihat dirinya dari luar. Boleh dikosongkan:
                 anak yang merasa tidak punya teman dekat tidak boleh terkunci
                 gara-gara satu pertanyaan yang menyakitkan untuk dijawab. --}}
            <div>
                <label for="kesan_teman" class="form-label">Menurut temanmu, kamu itu seperti apa? <span class="font-normal text-slate-500 lowercase">(boleh dikosongkan)</span></label>
                <textarea id="kesan_teman" name="kesan_teman" rows="3" maxlength="1000" placeholder="cth: Kata Rina aku asyik dan suka bantu, tapi kadang suka memotong pembicaraan." class="form-input">{{ old('kesan_teman') }}</textarea>
            </div>

            <div class="flex gap-3 justify-end pt-2">
                <a href="{{ route('student.portfolio') }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary">Simpan Refleksi</button>
            </div>
        </form>
    </div>
</div>
@endsection
