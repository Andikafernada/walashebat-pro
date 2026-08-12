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
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Leger Nilai</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                {{ $classroom->name }}
                @if ($classroom->academic_year) &middot; T.A. {{ $classroom->academic_year }} @endif
                &middot; <span class="font-semibold text-indigo-600">{{ $labelJenis }} — Semester {{ $semester }}</span>
            </p>
        </div>

        <a href="{{ route('classes.nilai.create', [$classroom, 'jenis' => $jenis, 'semester' => $semester]) }}"
           class="inline-flex h-9 items-center rounded-xl bg-indigo-600 px-4 text-xs font-bold text-white hover:bg-indigo-700">
            + Isi {{ strtoupper($jenis) }} Semester {{ $semester }}
        </a>
    </div>

    @include('partials.flash')

    {{-- Empat kombinasi yang mungkin ditanyakan wali kelas: PTS/PAS × semester
         1/2. Ditampilkan sebagai tautan, bukan formulir, supaya tiap kombinasi
         punya URL sendiri yang bisa disimpan dan dibagikan. --}}
    <div class="flex flex-wrap gap-2">
        @foreach ([\App\Models\Assessment::JENIS_PTS, \App\Models\Assessment::JENIS_PAS] as $j)
            @foreach ([1, 2] as $s)
                @php $aktif = $jenis === $j && $semester === $s; @endphp
                <a href="{{ route('classes.nilai.rekap', [$classroom, 'jenis' => $j, 'semester' => $s]) }}"
                   class="rounded-xl border px-3 py-1.5 text-xs font-bold {{ $aktif ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ strtoupper($j) }} &middot; Sem {{ $s }}
                </a>
            @endforeach
        @endforeach
    </div>

    @if (! $adaPenilaian)
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
            <p class="text-sm font-bold text-slate-700">Belum ada nilai {{ strtoupper($jenis) }} semester {{ $semester }}</p>
            <p class="mt-1 text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                Satu penilaian mewakili satu mata pelajaran. Buat satu penilaian per mapel,
                isi nilai sekelas, lalu tabel ini akan tersusun sendiri.
            </p>
            <a href="{{ route('classes.nilai.create', [$classroom, 'jenis' => $jenis, 'semester' => $semester]) }}"
               class="mt-4 inline-flex h-9 items-center rounded-xl bg-indigo-600 px-4 text-xs font-bold text-white hover:bg-indigo-700">
                + Isi nilai sekarang
            </a>
        </div>
    @elseif ($students->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
            <p class="text-sm font-bold text-slate-700">Belum ada siswa aktif di kelas ini</p>
        </div>
    @else
        {{-- Tabel bisa jadi sangat lebar bila mapelnya banyak, sedangkan wali
             kelas kerap membukanya dari ponsel. Gulirnya dikurung di sini
             supaya halamannya sendiri tidak ikut bergeser ke samping, dan
             kolom nama dibekukan agar barisnya tetap bisa dikenali saat
             digulir. --}}
        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-left">
                            <th class="sticky left-0 z-10 bg-slate-50 px-3 py-2.5 font-bold text-slate-700 whitespace-nowrap">Nama Siswa</th>
                            @foreach ($mapel as $m)
                                <th class="px-3 py-2.5 font-bold text-slate-700 text-center whitespace-nowrap">{{ $m }}</th>
                            @endforeach
                            <th class="px-3 py-2.5 font-bold text-slate-700 text-center whitespace-nowrap bg-indigo-50">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($students as $s)
                            @php
                                $baris = collect($rekap[$s->id] ?? [])->filter();
                                $rata = $baris->isEmpty() ? null : round($baris->avg('nilai'), 1);
                            @endphp
                            <tr class="hover:bg-slate-50/60">
                                <td class="sticky left-0 z-10 bg-white px-3 py-2 font-semibold text-slate-800 whitespace-nowrap">
                                    {{ $s->name }}
                                    <span class="block text-[10px] font-normal text-slate-400">{{ $s->nis }}</span>
                                </td>

                                @foreach ($mapel as $m)
                                    @php $sel = $rekap[$s->id][$m] ?? null; @endphp
                                    <td class="px-3 py-2 text-center">
                                        @if ($sel === null)
                                            {{-- Kosong berarti BELUM dinilai, bukan nol. Dibedakan
                                                 supaya wali kelas bisa melihat siapa yang nilainya
                                                 masih ditunggu dari guru mapel. --}}
                                            <span class="text-slate-300">—</span>
                                        @else
                                            <span class="font-bold {{ $sel['nilai'] < 75 ? 'text-rose-600' : 'text-slate-800' }}">
                                                {{ $sel['nilai'] }}
                                            </span>
                                            @if ($sel['jumlah'] > 1)
                                                {{-- Rata-rata dari beberapa penilaian untuk mapel,
                                                     jenis, dan semester yang sama — hampir selalu
                                                     berarti satu mapel tidak sengaja dinilai dua
                                                     kali. Ditandai, bukan didiamkan. --}}
                                                <span class="ml-0.5 text-[10px] font-bold text-amber-600"
                                                      title="Rata-rata dari {{ $sel['jumlah'] }} penilaian">
                                                    ({{ $sel['jumlah'] }})
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach

                                <td class="px-3 py-2 text-center bg-indigo-50/40 font-semibold text-indigo-800">
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
