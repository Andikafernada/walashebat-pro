@extends('layouts.app')
@section('title', ($assessment ? 'Ubah' : 'Penilaian Baru') . ' — ' . $classroom->name)
@section('content')

@include('partials.class-nav')

<div class="space-y-6 pb-12">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">
                {{ $assessment ? 'Isi / Ubah Nilai' : 'Penilaian Baru' }}
            </h1>
            <p class="mt-1 text-xs text-slate-500">{{ $classroom->name }} &middot; {{ $students->count() }} siswa</p>
        </div>

        <a href="{{ route('classes.rapor.narasi', $classroom) }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-100 hover:bg-emerald-200 text-emerald-950 border border-emerald-300 text-xs font-bold shadow-2xs transition-all">
            <span>🤖</span>
            <span>AI Narasi Rapor Otomatis</span>
        </a>
    </div>

    @include('partials.flash')

    {{-- KOTAK SINKRONISASI EXCEL DUA ARAH --}}
    @if ($assessment)
        <div class="bg-white rounded-2xl border border-emerald-300 p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-0.5">
                <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-950">
                    <span>📑</span>
                    <span>Sinkronisasi Nilai via Excel (Bi-Directional)</span>
                </div>
                <p class="text-[11px] text-slate-600 font-medium">
                    Unduh format Excel untuk diisi offline, lalu unggah kembali untuk update nilai massal instan tanpa mengetik manual.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('classes.nilai.excel.template', [$classroom, $assessment]) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 text-emerald-950 text-xs font-bold transition-all shadow-2xs">
                    <span>📥</span>
                    <span>Unduh Format Excel</span>
                </a>
                <form method="POST" action="{{ route('classes.nilai.excel.import', [$classroom, $assessment]) }}"
                      enctype="multipart/form-data" class="inline-flex items-center">
                    @csrf
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-2xs">
                        <span>📤</span>
                        <span>Unggah &amp; Sinkron Excel</span>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" onchange="this.form.submit()" class="hidden">
                    </label>
                </form>
            </div>
        </div>
    @endif

    <form method="POST"
          action="{{ $assessment ? route('classes.nilai.update', [$classroom, $assessment]) : route('classes.nilai.store', $classroom) }}"
          class="space-y-6">
        @csrf
        @if ($assessment) @method('PATCH') @endif

        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-5 grid gap-4 sm:grid-cols-3"
             x-data="{ jenis: '{{ old('jenis', $jenisAwal) }}' }">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Penilaian <span class="text-rose-500">*</span></label>
                <select name="jenis" x-model="jenis"
                        class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @foreach (\App\Models\Assessment::jenisTersedia() as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('jenis', $jenisAwal) === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('jenis')
                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal <span class="text-rose-500">*</span></label>
                <input type="date" name="assessment_date"
                       value="{{ old('assessment_date', $assessment?->assessment_date?->format('Y-m-d') ?? date('Y-m-d')) }}"
                       class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                @error('assessment_date')
                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Semester <span class="text-rose-500">*</span></label>
                <select name="semester"
                        class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="1" @selected(old('semester', $assessment->semester ?? $semesterAwal) == 1)>Semester 1 (Ganjil)</option>
                    <option value="2" @selected(old('semester', $assessment->semester ?? $semesterAwal) == 2)>Semester 2 (Genap)</option>
                </select>
                @error('semester')
                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Judul / Materi Pokok <span class="text-rose-500">*</span></label>
                <input type="text" name="title"
                       value="{{ old('title', $assessment->title ?? '') }}"
                       placeholder="cth: TP 1 — Aljabar Linier / Penilaian Tengah Semester"
                       class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                @error('title')
                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2" x-show="jenis === 'harian'" x-cloak>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Capaian Pembelajaran <span class="text-rose-500">*</span></label>
                <input type="text" name="capaian_pembelajaran"
                       value="{{ old('capaian_pembelajaran', $assessment->capaian_pembelajaran ?? '') }}"
                       placeholder="cth: Memahami sistem bilangan biner dan konversinya"
                       class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                @error('capaian_pembelajaran')
                    <p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                    Mata Pelajaran
                    @if (count($mapelDiampu) === 0) <span class="text-rose-500">*</span> @endif
                </label>

                @if (count($mapelDiampu) > 1)
                    <select name="mapel" class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @foreach ($mapelDiampu as $m)
                            <option value="{{ $m }}" @selected(old('mapel', $assessment->mapel ?? '') === $m)>{{ $m }}</option>
                        @endforeach
                    </select>
                @elseif (count($mapelDiampu) === 1)
                    <input type="text" name="mapel" readonly
                           value="{{ old('mapel', $assessment->mapel ?? $mapelDiampu[0]) }}"
                           class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-800">
                @else
                    <input type="text" name="mapel" list="daftar-mapel"
                           value="{{ old('mapel', $assessment->mapel ?? '') }}"
                           placeholder="cth: Matematika"
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
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
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-xs text-amber-900">
                Belum ada siswa aktif di kelas ini. Impor daftar siswa (cukup NIS &amp; nama) sebelum menilai.
            </div>
        @else
            <div class="overflow-x-auto rounded-2xl border border-emerald-200 bg-white shadow-xs">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-2.5 px-3 font-bold w-10 text-right">#</th>
                            <th class="py-2.5 px-3 font-bold">Nama</th>
                            <th class="py-2.5 px-3 font-bold w-28 text-center">Nilai (0–100)</th>
                            <th class="py-2.5 px-3 font-bold">Catatan (opsional)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($students as $i => $st)
                            @php $tersimpan = $nilaiTersimpan[$st->id] ?? null; @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-2 px-3 text-slate-400 font-mono text-right">{{ $i + 1 }}</td>
                                <td class="py-2 px-3">
                                    <span class="font-bold text-slate-900">{{ $st->name }}</span>
                                    @if ($st->nis)
                                        <span class="block text-[10px] text-slate-400 font-mono">{{ $st->nis }}</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <input type="number" name="nilai[{{ $st->id }}]" min="0" max="100" inputmode="numeric"
                                           value="{{ old('nilai.' . $st->id, $tersimpan->nilai ?? '') }}"
                                           placeholder="—"
                                           class="h-9 w-24 rounded-xl border border-slate-200 bg-white px-2 text-center text-xs font-bold tabular-nums focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                    @error('nilai.' . $st->id)
                                        <p class="mt-1 text-[10px] font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="py-2 px-3">
                                    <input type="text" name="catatan[{{ $st->id }}]" maxlength="200"
                                           value="{{ old('catatan.' . $st->id, $tersimpan->catatan ?? '') }}"
                                           placeholder="cth: remedial, belum tuntas"
                                           class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
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
            <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors shadow-2xs">
                {{ $assessment ? 'Simpan Perubahan' : 'Simpan Penilaian' }}
            </button>
            <a href="{{ route('classes.nilai.index', $classroom) }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                Batal
            </a>

            @if ($assessment)
                <span class="flex-1"></span>
                <button type="submit" form="hapus-penilaian"
                        onclick="return confirm('Hapus penilaian ini beserta seluruh nilainya? Tindakan ini tidak bisa dibatalkan.')"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors">
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
