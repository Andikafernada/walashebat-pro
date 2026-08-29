@extends('layouts.student')
@section('title', 'Refleksi Baru')

@section('content')
<div class="p-6 lg:p-8 space-y-6 max-w-4xl mx-auto pb-12">
    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Refleksi Baru</h1>
            <p class="text-xs text-slate-500 mt-0.5">Luangkan waktu sejenak untuk merefleksikan dirimu</p>
        </div>
        <a href="{{ route('student.portfolio') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Batal</a>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-900">
            <ul class="list-disc list-inside space-y-1 font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
        <form action="{{ route('student.reflection.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Dimensi (opsional)</label>
                <select name="character_dimension_id" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">- Umum, tidak terkait dimensi tertentu -</option>
                    @foreach ($dimensions as $dim)
                        <option value="{{ $dim->id }}" @selected(old('character_dimension_id', $dimension?->id) == $dim->id)>{{ $dim->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal</label>
                    <input type="date" name="reflection_date" value="{{ old('reflection_date', today()->format('Y-m-d')) }}" required max="{{ today()->format('Y-m-d') }}" 
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Periode</label>
                    <select name="period" required class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <option value="daily" selected>Harian</option>
                        <option value="weekly">Mingguan</option>
                        <option value="monthly">Bulanan</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Penilaian Diri (1-5)</label>
                <select name="self_rating" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">- Tidak dinilai -</option>
                    @for ($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}">{{ $i }} Bintang</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Apa yang berjalan baik?</label>
                <textarea name="what_went_well" rows="3" placeholder="Hal-hal positif yang terjadi..." class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Apa yang perlu diperbaiki?</label>
                <textarea name="what_to_improve" rows="3" placeholder="Hal-hal yang perlu ditingkatkan..." class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Rencana Aksi</label>
                <textarea name="action_plan" rows="3" placeholder="Langkah konkret untuk perbaikan ke depan..." class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"></textarea>
            </div>

            <div>
                <label for="kesan_teman" class="block text-xs font-semibold text-slate-700 mb-1">Menurut temanmu, kamu itu seperti apa? <span class="font-normal text-slate-400 lowercase">(opsional)</span></label>
                <textarea id="kesan_teman" name="kesan_teman" rows="3" maxlength="1000" placeholder="cth: Kata Rina aku asyik dan suka bantu, tapi kadang suka memotong pembicaraan." 
                          class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('kesan_teman') }}</textarea>
            </div>

            <div class="flex gap-3 justify-end pt-3 border-t border-slate-100">
                <a href="{{ route('student.portfolio') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Batal</a>
                <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors shadow-xs">Simpan Refleksi</button>
            </div>
        </form>
    </div>
</div>
@endsection
