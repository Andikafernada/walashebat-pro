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
        <div class="flex flex-wrap gap-2">
            {{-- Leger adalah bentuk yang dipakai saat menyusun rapor; daftar di
                 bawah ini bentuk untuk mengisi sehari-hari. Keduanya perlu, dan
                 yang satu tidak menggantikan yang lain. --}}
            <a href="{{ route('classes.nilai.rekap', $classroom) }}"
               class="inline-flex h-9 items-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-xs font-bold text-indigo-700 hover:bg-indigo-100">
                📋 Leger Nilai (PTS/PAS)
            </a>
            <a href="{{ route('classes.nilai.create', $classroom) }}"
               class="inline-flex h-9 items-center rounded-xl bg-indigo-600 px-4 text-xs font-bold text-white hover:bg-indigo-700">
                + Penilaian Baru
            </a>
        </div>
    </div>

    @include('partials.flash')

    <div class="flex flex-wrap gap-2">
        <a href="{{ request()->fullUrlWithQuery(['jenis' => null]) }}"
           class="rounded-xl border px-3 py-1.5 text-xs font-bold {{ $jenisDipilih === null ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
            Semua jenis
        </a>
        @foreach (\App\Models\Assessment::jenisTersedia() as $nilai => $label)
            <a href="{{ request()->fullUrlWithQuery(['jenis' => $nilai]) }}"
               class="rounded-xl border px-3 py-1.5 text-xs font-bold {{ $jenisDipilih === $nilai ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

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
                        <th class="px-3 py-2.5 font-bold">Jenis</th>
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
                            <td class="px-3 py-2.5 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold
                                      {{ $p->harian() ? 'bg-slate-100 text-slate-700' : 'bg-indigo-100 text-indigo-800' }}">
                                    {{ $p->harian() ? 'Harian' : strtoupper($p->jenis).' · Sem '.$p->semester }}
                                </span>
                            </td>
                            {{-- PTS dan PAS tidak punya Capaian Pembelajaran; kolomnya diisi
                                 mapel supaya barisnya tetap bisa dikenali sekilas. --}}
                            <td class="px-3 py-2.5 text-slate-700">
                                {{ $p->capaian_pembelajaran ?: ($p->mapel ?: '—') }}
                            </td>
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
