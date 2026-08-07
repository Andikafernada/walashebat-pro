@extends('layouts.app')
@section('title', 'Nilai Harian — ' . $classroom->name)
@section('content')

@include('partials.class-nav')

<div class="space-y-6 pb-12">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-black tracking-tight text-slate-900">Nilai Harian</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                {{ $classroom->name }} &middot; {{ $periode['label'] }}
                @if ($mapelDipilih) &middot; <span class="font-semibold text-indigo-600">{{ $mapelDipilih }}</span> @endif
            </p>
        </div>
        <a href="{{ route('classes.nilai.create', $classroom) }}"
           class="inline-flex h-9 items-center rounded-xl bg-indigo-600 px-4 text-xs font-bold text-white hover:bg-indigo-700">
            + Penilaian Baru
        </a>
    </div>

    @include('partials.flash')

    @if (count($mapelDiampu) > 1)
        <div class="flex flex-wrap gap-2">
            <a href="{{ request()->fullUrlWithQuery(['mapel' => null]) }}"
               class="rounded-xl border px-3 py-1.5 text-xs font-bold {{ $mapelDipilih === null ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                Semua mapel
            </a>
            @foreach ($mapelDiampu as $m)
                <a href="{{ request()->fullUrlWithQuery(['mapel' => $m]) }}"
                   class="rounded-xl border px-3 py-1.5 text-xs font-bold {{ $mapelDipilih === $m ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $m }}
                </a>
            @endforeach
        </div>
    @endif

    @if ($penilaian->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
            <p class="text-sm font-bold text-slate-700">Belum ada penilaian</p>
            <p class="mt-1 text-xs text-slate-500">
                Satu penilaian mewakili satu Capaian Pembelajaran. Buka sekali, isi nilai sekelas, simpan.
            </p>
            <a href="{{ route('classes.nilai.create', $classroom) }}"
               class="mt-4 inline-flex h-9 items-center rounded-xl bg-indigo-600 px-4 text-xs font-bold text-white hover:bg-indigo-700">
                Buat Penilaian Pertama
            </a>
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-3 py-2.5 font-bold">Tanggal</th>
                        @if (count($mapelDiampu) > 1)
                            <th class="px-3 py-2.5 font-bold">Mapel</th>
                        @endif
                        <th class="px-3 py-2.5 font-bold">Capaian Pembelajaran</th>
                        <th class="px-3 py-2.5 font-bold text-center">Rata-rata</th>
                        <th class="px-3 py-2.5 font-bold text-center">Belum dinilai</th>
                        <th class="px-3 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($penilaian as $p)
                        @php $rata = $p->rataRata(); $belum = $p->belumDinilai(); @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-3 py-2.5 whitespace-nowrap font-semibold text-slate-800">
                                {{ $p->assessment_date->translatedFormat('d M Y') }}
                            </td>
                            @if (count($mapelDiampu) > 1)
                                <td class="px-3 py-2.5 whitespace-nowrap text-slate-600">{{ $p->mapel ?: '—' }}</td>
                            @endif
                            <td class="px-3 py-2.5 text-slate-700">{{ $p->capaian_pembelajaran }}</td>
                            <td class="px-3 py-2.5 text-center">
                                @if ($rata === null)
                                    {{-- Rata-rata dari nol isian bukan 0, melainkan tidak ada. --}}
                                    <span class="text-slate-400">—</span>
                                @else
                                    <span class="font-black tabular-nums {{ $rata < 75 ? 'text-rose-700' : 'text-slate-800' }}">{{ $rata }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center tabular-nums {{ $belum > 0 ? 'font-bold text-amber-700' : 'text-slate-400' }}">
                                {{ $belum > 0 ? $belum : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                <a href="{{ route('classes.nilai.edit', [$classroom, $p]) }}"
                                   class="font-bold text-indigo-600 hover:underline">Isi / Ubah</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-[11px] text-slate-500">
            Rata-rata hanya menghitung siswa yang sudah dinilai. Yang belum diisi tidak dianggap nol —
            kalau dianggap nol, rata-rata kelas anjlok oleh siswa yang sebenarnya belum diuji.
        </p>
    @endif
</div>
@endsection
