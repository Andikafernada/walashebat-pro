@extends('layouts.app')
@section('title', ($assessment ? 'Ubah' : 'Penilaian Baru') . ' — ' . $classroom->name)
@section('content')

@include('partials.class-nav')

<div class="space-y-6 pb-12">

    <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">
            {{ $assessment ? 'Isi / Ubah Nilai' : 'Penilaian Baru' }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">{{ $classroom->name }} &middot; {{ $students->count() }} siswa</p>
    </div>

    @include('partials.flash')

    <form method="POST"
          action="{{ $assessment ? route('classes.nilai.update', [$classroom, $assessment]) : route('classes.nilai.store', $classroom) }}"
          class="space-y-6">
        @csrf
        @if ($assessment) @method('PATCH') @endif

        <div class="card grid gap-4 sm:grid-cols-3"
             x-data="{ jenis: '{{ old('jenis', $jenisAwal) }}' }">

            <div>
                <label class="form-label">Jenis Penilaian *</label>
                <select name="jenis" x-model="jenis"
                        class="form-input form-input--sm">
                    @foreach (\App\Models\Assessment::jenisTersedia() as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('jenis', $jenisAwal) === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('jenis')
                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">Semester *</label>
                {{-- Semester DISIMPAN, bukan disimpulkan dari tanggal: nilai
                     akhir semester 1 lazim baru dimasukkan pada Januari, yang
                     menurut kalender sudah semester 2. --}}
                <select name="semester"
                        class="form-input form-input--sm">
                    @foreach ([1, 2] as $s)
                        <option value="{{ $s }}" @selected((int) old('semester', $semesterAwal) === $s)>Semester {{ $s }}</option>
                    @endforeach
                </select>
                @error('semester')
                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">Tanggal *</label>
                <input type="date" name="assessment_date" required
                       value="{{ old('assessment_date', optional($assessment?->assessment_date)->toDateString() ?? date('Y-m-d')) }}"
                       class="form-input form-input--sm">
                @error('assessment_date')
                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Capaian Pembelajaran hanya bermakna pada nilai harian. PTS dan
                 PAS menilai satu semester penuh, bukan satu capaian — memaksa
                 isian ini di sana membuat wali kelas mengarang teks yang tidak
                 berarti apa-apa hanya supaya formulirnya mau tersimpan. --}}
            <div class="sm:col-span-2" x-show="jenis === 'harian'" x-cloak>
                <label class="form-label">Capaian Pembelajaran *</label>
                <input type="text" name="capaian_pembelajaran"
                       value="{{ old('capaian_pembelajaran', $assessment->capaian_pembelajaran ?? '') }}"
                       placeholder="cth: Memahami sistem bilangan biner dan konversinya"
                       class="form-input form-input--sm">
                @error('capaian_pembelajaran')
                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">
                    Mata Pelajaran
                    @if (count($mapelDiampu) === 0) <span class="text-rose-600">*</span> @endif
                </label>

                @if (count($mapelDiampu) > 1)
                    <select name="mapel" class="form-input form-input--sm">
                        @foreach ($mapelDiampu as $m)
                            <option value="{{ $m }}" @selected(old('mapel', $assessment->mapel ?? '') === $m)>{{ $m }}</option>
                        @endforeach
                    </select>
                @elseif (count($mapelDiampu) === 1)
                    <input type="text" name="mapel" readonly
                           value="{{ old('mapel', $assessment->mapel ?? $mapelDiampu[0]) }}"
                           class="form-input form-input--sm">
                @else
                    {{-- Kelas perwalian tidak punya daftar mapel: wali kelas
                         merekap nilai dari banyak mapel yang diajar guru lain,
                         jadi kolomnya harus bisa diisi bebas. Tanpa ini seluruh
                         nilai PTS/PAS-nya menumpuk pada satu kolom tanpa nama
                         di rekap. --}}
                    <input type="text" name="mapel" list="daftar-mapel"
                           value="{{ old('mapel', $assessment->mapel ?? '') }}"
                           placeholder="cth: Matematika"
                           class="form-input form-input--sm">
                    <datalist id="daftar-mapel">
                        @foreach ($mapelPernahDipakai ?? [] as $m)
                            <option value="{{ $m }}"></option>
                        @endforeach
                    </datalist>
                @endif

                @error('mapel')
                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        @if ($students->isEmpty())
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-xs text-amber-900">
                Belum ada siswa aktif di kelas ini. Impor daftar siswa (cukup NIS &amp; nama) sebelum menilai.
            </div>
        @else
            <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="px-3 py-2.5 font-semibold w-10">#</th>
                            <th class="px-3 py-2.5 font-semibold">Nama</th>
                            <th class="px-3 py-2.5 font-semibold w-28">Nilai (0–100)</th>
                            <th class="px-3 py-2.5 font-semibold">Catatan (opsional)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $i => $st)
                            @php $tersimpan = $nilaiTersimpan[$st->id] ?? null; @endphp
                            <tr>
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
                                           class="form-input form-input--sm">
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
            <button type="submit" class="h-10 rounded bg-indigo-600 px-5 text-xs font-semibold text-white hover:bg-indigo-700">
                {{ $assessment ? 'Simpan Perubahan' : 'Simpan Penilaian' }}
            </button>
            <a href="{{ route('classes.nilai.index', $classroom) }}" class="h-10 inline-flex items-center rounded border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                Batal
            </a>

            @if ($assessment)
                <span class="flex-1"></span>
                <button type="submit" form="hapus-penilaian"
                        onclick="return confirm('Hapus penilaian ini beserta seluruh nilainya? Tindakan ini tidak bisa dibatalkan.')"
                        class="h-10 rounded border border-rose-200 bg-white px-4 text-xs font-semibold text-rose-600 hover:bg-rose-50">
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
