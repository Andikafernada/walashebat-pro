@extends('layouts.app')
@section('title', 'Analisis Kehadiran — ' . $classroom->name)
@section('content')

@include('partials.class-nav')

<div class="space-y-6 pb-12">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Analisis Kehadiran</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                {{ $classroom->name }} &middot; {{ $periode['label'] }} &middot; {{ $jumlahPertemuan }} pertemuan
                @if ($mapelDipilih) &middot; <span class="font-semibold text-indigo-600">{{ $mapelDipilih }}</span> @endif
            </p>
        </div>
        <button type="button" onclick="window.print()"
                class="h-9 rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-700 hover:bg-slate-50 print:hidden">
            🖨️ Cetak
        </button>
    </div>

    @if (count($mapelDiampu) > 1)
        <div class="flex flex-wrap gap-2 print:hidden">
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

    @if ($jumlahPertemuan === 0)
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
            <p class="text-sm font-bold text-slate-700">Belum ada pertemuan pada periode ini</p>
            <p class="mt-1 text-xs text-slate-500">Analisis muncul setelah ada presensi yang terisi.</p>
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Kehadiran kelas</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $persenKelas }}%</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Di bawah {{ $ambangPerhatian }}%</p>
                <p class="mt-1 text-2xl font-semibold text-rose-700">
                    {{ $rekap->filter(fn ($r) => $r['total'] > 0 && $r['persen'] < $ambangPerhatian)->count() }}
                </p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Pertemuan</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $jumlahPertemuan }}</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-3 py-2.5 font-bold">Siswa</th>
                        <th class="px-3 py-2.5 font-bold text-center">Hadir</th>
                        <th class="px-3 py-2.5 font-bold text-center">Terlambat</th>
                        <th class="px-3 py-2.5 font-bold text-center">Sakit</th>
                        <th class="px-3 py-2.5 font-bold text-center">Izin</th>
                        <th class="px-3 py-2.5 font-bold text-center">Alfa</th>
                        <th class="px-3 py-2.5 font-bold text-right">Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rekap as $r)
                        @php
                            $belumAda = $r['total'] === 0;
                            $rendah = ! $belumAda && $r['persen'] < $ambangPerhatian;
                        @endphp
                        <tr class="{{ $rendah ? 'bg-rose-50/50' : '' }} hover:bg-slate-50/60">
                            <td class="px-3 py-2 font-semibold text-slate-800">{{ $r['siswa']->name }}</td>
                            <td class="px-3 py-2 text-center tabular-nums">{{ $r['jumlah']['hadir'] }}</td>
                            <td class="px-3 py-2 text-center tabular-nums text-amber-700">{{ $r['jumlah']['terlambat'] }}</td>
                            <td class="px-3 py-2 text-center tabular-nums text-blue-700">{{ $r['jumlah']['sakit'] }}</td>
                            <td class="px-3 py-2 text-center tabular-nums text-purple-700">{{ $r['jumlah']['izin'] }}</td>
                            <td class="px-3 py-2 text-center tabular-nums font-bold {{ $r['jumlah']['alfa'] > 0 ? 'text-rose-700' : 'text-slate-400' }}">{{ $r['jumlah']['alfa'] }}</td>
                            <td class="px-3 py-2 text-right">
                                @if ($belumAda)
                                    {{-- 0% di sini berarti "belum pernah tercatat", bukan
                                         "tidak pernah hadir". Menyebutnya rendah adalah
                                         tuduhan yang keliru. --}}
                                    <span class="text-slate-400">belum ada data</span>
                                @else
                                    <span class="font-semibold tabular-nums {{ $rendah ? 'text-rose-700' : 'text-slate-800' }}">{{ $r['persen'] }}%</span>
                                    <span class="block text-[10px] text-slate-400">{{ $r['total'] }} isian</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-[11px] text-slate-500">
            Diurutkan dari kehadiran terendah. Baris merah berada di bawah {{ $ambangPerhatian }}% —
            ambang yang banyak dipakai sekolah sebagai syarat mengikuti ujian.
            Penyebutnya adalah jumlah isian untuk siswa itu, bukan jumlah pertemuan kelas,
            supaya siswa yang baru pindah masuk tidak terlihat seperti sering bolos.
        </p>
    @endif
</div>
@endsection
