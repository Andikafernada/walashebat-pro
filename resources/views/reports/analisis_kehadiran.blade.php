@extends('layouts.app')
@section('title', 'Analisis Kehadiran — ' . $classroom->name)
@section('content')

@include('partials.class-nav')

<div class="space-y-6 pb-12">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Analisis Kehadiran</h1>
            <p class="mt-1 text-xs text-slate-500">
                {{ $classroom->name }} &middot; {{ $periode['label'] }} &middot; {{ $jumlahPertemuan }} pertemuan
                @if ($mapelDipilih) &middot; <span class="font-bold text-emerald-700">{{ $mapelDipilih }}</span> @endif
            </p>
        </div>
        <button type="button" onclick="window.print()"
                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 print:hidden transition-colors">
                Cetak
        </button>
    </div>

    @if (count($mapelDiampu) > 1)
        <div class="flex flex-wrap gap-2 print:hidden">
            <a href="{{ request()->fullUrlWithQuery(['mapel' => null]) }}"
               class="rounded-xl border px-3.5 py-1.5 text-xs font-bold transition-colors {{ $mapelDipilih === null ? 'border-emerald-500 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                Semua Mapel
            </a>
            @foreach ($mapelDiampu as $m)
                <a href="{{ request()->fullUrlWithQuery(['mapel' => $m]) }}"
                   class="rounded-xl border px-3.5 py-1.5 text-xs font-bold transition-colors {{ $mapelDipilih === $m ? 'border-emerald-500 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $m }}
                </a>
            @endforeach
        </div>
    @endif

    @if ($jumlahPertemuan === 0)
        <div class="rounded-2xl border border-emerald-200 bg-white p-8 text-center shadow-xs">
            <p class="text-sm font-bold text-slate-700">Belum Ada Pertemuan Pada Periode Ini</p>
            <p class="mt-1 text-xs text-slate-500">Analisis muncul setelah ada presensi yang terisi.</p>
        </div>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Kehadiran Kelas</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $persenKelas }}%</p>
            </div>
            <div class="bg-white rounded-2xl border border-rose-200 shadow-xs p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-500">Di bawah {{ $ambangPerhatian }}%</p>
                <p class="mt-1 text-2xl font-extrabold text-rose-700">
                    {{ $rekap->filter(fn ($r) => $r['total'] > 0 && $r['persen'] < $ambangPerhatian)->count() }}
                </p>
            </div>
            <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pertemuan</p>
                <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $jumlahPertemuan }}</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-emerald-200 bg-white shadow-xs">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-2.5 px-3 font-bold">Siswa</th>
                        <th class="py-2.5 px-3 font-bold text-center">Hadir</th>
                        <th class="py-2.5 px-3 font-bold text-center">Terlambat</th>
                        <th class="py-2.5 px-3 font-bold text-center">Sakit</th>
                        <th class="py-2.5 px-3 font-bold text-center">Izin</th>
                        <th class="py-2.5 px-3 font-bold text-center">Alfa</th>
                        <th class="py-2.5 px-3 font-bold text-right">Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rekap as $r)
                        @php
                            $belumAda = $r['total'] === 0;
                            $rendah = ! $belumAda && $r['persen'] < $ambangPerhatian;
                        @endphp
                        <tr class="{{ $rendah ? 'bg-rose-50/50' : '' }} hover:bg-slate-50/50 transition-colors">
                            <td class="py-2.5 px-3 font-bold text-slate-900">{{ $r['siswa']->name }}</td>
                            <td class="py-2.5 px-3 text-center tabular-nums font-semibold">{{ $r['jumlah']['hadir'] }}</td>
                            <td class="py-2.5 px-3 text-center tabular-nums font-semibold text-amber-700">{{ $r['jumlah']['terlambat'] }}</td>
                            <td class="py-2.5 px-3 text-center tabular-nums font-semibold text-blue-700">{{ $r['jumlah']['sakit'] }}</td>
                            <td class="py-2.5 px-3 text-center tabular-nums font-semibold text-purple-700">{{ $r['jumlah']['izin'] }}</td>
                            <td class="py-2.5 px-3 text-center tabular-nums font-bold {{ $r['jumlah']['alfa'] > 0 ? 'text-rose-700' : 'text-slate-400' }}">{{ $r['jumlah']['alfa'] }}</td>
                            <td class="py-2.5 px-3 text-right">
                                @if ($belumAda)
                                    <span class="text-slate-400 text-[11px]">belum ada data</span>
                                @else
                                    <span class="font-bold tabular-nums {{ $rendah ? 'text-rose-700' : 'text-slate-800' }}">{{ $r['persen'] }}%</span>
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
