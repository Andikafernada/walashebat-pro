@extends('layouts.app')
@section('title', ($assessment ? 'Ubah' : 'Penilaian Baru') . ' — ' . $classroom->name)
@section('content')

@include('partials.class-nav')

<div class="space-y-6 pb-12">

    <div>
        <h1 class="text-xl font-black tracking-tight text-slate-900">
            {{ $assessment ? 'Isi / Ubah Nilai' : 'Penilaian Baru' }}
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">{{ $classroom->name }} &middot; {{ $students->count() }} siswa</p>
    </div>

    @include('partials.flash')

    <form method="POST"
          action="{{ $assessment ? route('classes.nilai.update', [$classroom, $assessment]) : route('classes.nilai.store', $classroom) }}"
          class="space-y-6">
        @csrf
        @if ($assessment) @method('PATCH') @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-5 grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Capaian Pembelajaran *</label>
                <input type="text" name="capaian_pembelajaran" required
                       value="{{ old('capaian_pembelajaran', $assessment->capaian_pembelajaran ?? '') }}"
                       placeholder="cth: Memahami sistem bilangan biner dan konversinya"
                       class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                @error('capaian_pembelajaran')
                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal *</label>
                <input type="date" name="assessment_date" required
                       value="{{ old('assessment_date', optional($assessment?->assessment_date)->toDateString() ?? date('Y-m-d')) }}"
                       class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
            </div>

            @if (count($mapelDiampu) > 0)
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Mata Pelajaran</label>
                    @if (count($mapelDiampu) > 1)
                        <select name="mapel" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                            @foreach ($mapelDiampu as $m)
                                <option value="{{ $m }}" @selected(old('mapel', $assessment->mapel ?? '') === $m)>{{ $m }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="mapel" readonly
                               value="{{ old('mapel', $assessment->mapel ?? $mapelDiampu[0]) }}"
                               class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs text-slate-600">
                    @endif
                </div>
            @endif
        </div>

        @if ($students->isEmpty())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-xs text-amber-900">
                Belum ada siswa aktif di kelas ini. Impor daftar siswa (cukup NIS &amp; nama) sebelum menilai.
            </div>
        @else
            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-3 py-2.5 font-bold w-10">#</th>
                            <th class="px-3 py-2.5 font-bold">Nama</th>
                            <th class="px-3 py-2.5 font-bold w-28">Nilai (0–100)</th>
                            <th class="px-3 py-2.5 font-bold">Catatan (opsional)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($students as $i => $st)
                            @php $tersimpan = $nilaiTersimpan[$st->id] ?? null; @endphp
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-3 py-2 text-slate-400 tabular-nums">{{ $i + 1 }}</td>
                                <td class="px-3 py-2">
                                    <span class="font-semibold text-slate-800">{{ $st->name }}</span>
                                    @if ($st->nis)
                                        <span class="block text-[10px] text-slate-400 font-mono">{{ $st->nis }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    {{-- Dibiarkan kosong berarti BELUM dinilai, bukan nol. --}}
                                    <input type="number" name="nilai[{{ $st->id }}]" min="0" max="100" inputmode="numeric"
                                           value="{{ old('nilai.' . $st->id, $tersimpan->nilai ?? '') }}"
                                           placeholder="—"
                                           class="h-9 w-24 rounded-lg border border-slate-200 bg-white px-2 text-center text-xs tabular-nums focus:border-indigo-500 focus:outline-none">
                                    @error('nilai.' . $st->id)
                                        <p class="mt-1 text-[10px] font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" name="catatan[{{ $st->id }}]" maxlength="200"
                                           value="{{ old('catatan.' . $st->id, $tersimpan->catatan ?? '') }}"
                                           placeholder="cth: remedial, belum tuntas"
                                           class="h-9 w-full rounded-lg border border-slate-200 bg-white px-2 text-xs focus:border-indigo-500 focus:outline-none">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-[11px] text-slate-500">
                Kolom nilai yang dibiarkan kosong berarti <strong>belum dinilai</strong> — berbeda dari nilai 0,
                dan tidak ikut menurunkan rata-rata. Berguna saat ada siswa yang sakit ketika ulangan berlangsung.
            </p>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="h-10 rounded-xl bg-indigo-600 px-5 text-xs font-bold text-white hover:bg-indigo-700">
                {{ $assessment ? 'Simpan Perubahan' : 'Simpan Penilaian' }}
            </button>
            <a href="{{ route('classes.nilai.index', $classroom) }}" class="h-10 inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-600 hover:bg-slate-50">
                Batal
            </a>

            @if ($assessment)
                <span class="flex-1"></span>
                <button type="submit" form="hapus-penilaian"
                        onclick="return confirm('Hapus penilaian ini beserta seluruh nilainya? Tindakan ini tidak bisa dibatalkan.')"
                        class="h-10 rounded-xl border border-rose-200 bg-white px-4 text-xs font-bold text-rose-600 hover:bg-rose-50">
                    Hapus Penilaian
                </button>
            @endif
        </div>
    </form>

    @if ($assessment)
        <form id="hapus-penilaian" method="POST" action="{{ route('classes.nilai.destroy', [$classroom, $assessment]) }}" class="hidden">
            @csrf @method('DELETE')
        </form>
    @endif
</div>
@endsection
