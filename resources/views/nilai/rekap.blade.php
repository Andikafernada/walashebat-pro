@extends('layouts.app')
@section('title', 'Leger Nilai — ' . $classroom->name)
@section('content')

@include('partials.class-nav')

@php
    $labelJenis = \App\Models\Assessment::jenisTersedia()[$jenis] ?? $jenis;
@endphp

<div class="space-y-6 pb-12">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Leger Nilai</h1>
            <p class="mt-1 text-xs text-slate-500">
                {{ $classroom->name }}
                @if ($classroom->academic_year) &middot; T.A. {{ $classroom->academic_year }} @endif
                &middot; <span class="font-bold text-emerald-700">{{ $labelJenis }} — Semester {{ $semester }}</span>
            </p>
        </div>

        <a href="{{ route('classes.nilai.create', [$classroom, 'jenis' => $jenis, 'semester' => $semester]) }}"
           class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">
            + Isi {{ strtoupper($jenis) }} Semester {{ $semester }}
        </a>
    </div>

    @include('partials.flash')

    <div class="flex flex-wrap gap-2">
        @foreach ([\App\Models\Assessment::JENIS_PTS, \App\Models\Assessment::JENIS_PAS] as $j)
            @foreach ([1, 2] as $s)
                @php $aktif = $jenis === $j && $semester === $s; @endphp
                <a href="{{ route('classes.nilai.rekap', [$classroom, 'jenis' => $j, 'semester' => $s]) }}"
                   class="rounded-xl border px-3.5 py-1.5 text-xs font-bold transition-colors {{ $aktif ? 'border-emerald-500 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ strtoupper($j) }} &middot; Sem {{ $s }}
                </a>
            @endforeach
        @endforeach
    </div>

    @if (! $adaPenilaian)
        <div class="rounded-2xl border border-emerald-200 bg-white p-8 text-center shadow-xs">
            <p class="text-sm font-bold text-slate-800">Belum ada nilai {{ strtoupper($jenis) }} semester {{ $semester }}</p>
            <p class="mt-1 text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                Satu penilaian mewakili satu mata pelajaran. Buat satu penilaian per mapel,
                isi nilai sekelas, lalu tabel ini akan tersusun sendiri.
            </p>
            <a href="{{ route('classes.nilai.create', [$classroom, 'jenis' => $jenis, 'semester' => $semester]) }}"
               class="mt-4 inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition-colors">
                + Isi nilai sekarang
            </a>
        </div>
    @elseif ($students->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
            <p class="text-sm font-bold text-slate-700">Belum ada siswa aktif di kelas ini</p>
        </div>
    @else
        <div class="rounded-2xl border border-emerald-200 bg-white overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-700 text-[11px] font-bold uppercase tracking-wider">
                            <th class="sticky left-0 z-10 bg-slate-50 px-3 py-2.5 whitespace-nowrap">Nama Siswa</th>
                            @foreach ($mapel as $m)
                                <th class="px-3 py-2.5 text-center whitespace-nowrap">{{ $m }}</th>
                            @endforeach
                            <th class="px-3 py-2.5 text-center whitespace-nowrap bg-emerald-50 text-emerald-900 font-extrabold">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($students as $s)
                            @php
                                $baris = collect($rekap[$s->id] ?? [])->filter();
                                $rata = $baris->isEmpty() ? null : round($baris->avg('nilai'), 1);
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="sticky left-0 z-10 bg-white px-3 py-2 font-bold text-slate-900 whitespace-nowrap">
                                    {{ $s->name }}
                                    <span class="block text-[10px] font-normal text-slate-400 font-mono">{{ $s->nis }}</span>
                                </td>

                                @foreach ($mapel as $m)
                                    @php $sel = $rekap[$s->id][$m] ?? null; @endphp
                                    <td class="px-3 py-2 text-center">
                                        @if ($sel === null)
                                            <span class="text-slate-300">—</span>
                                        @else
                                            <span class="font-bold {{ $sel['nilai'] < 75 ? 'text-rose-600' : 'text-slate-800' }}">
                                                {{ $sel['nilai'] }}
                                            </span>
                                            @if ($sel['jumlah'] > 1)
                                                <span class="ml-0.5 text-[10px] font-bold text-amber-600"
                                                      title="Rata-rata dari {{ $sel['jumlah'] }} penilaian">
                                                    ({{ $sel['jumlah'] }})
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach

                                <td class="px-3 py-2 text-center bg-emerald-50/40 font-extrabold text-emerald-800">
                                    {{ $rata ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-[11px] text-slate-400">
            &mdash; berarti nilainya belum diisi, bukan nol.
            Angka dalam kurung menandai mapel yang punya lebih dari satu penilaian pada semester dan jenis ini.
        </p>
    @endif

</div>
@endsection
