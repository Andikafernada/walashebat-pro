@extends('layouts.app')

@section('title', 'Poin Kerajinan - ' . ($class->name ?? ''))

@section('content')
@php
    $subAktif = $periode['sub_periode'] ?? '1_penuh';
    $paramSertifikat = ['sub_periode' => $subAktif, 'tahun' => $periode['tahun'], 'mode' => 'semester'];
@endphp

<div class="space-y-5 pb-12">

    <div class="border-b border-slate-100 pb-4">
        <nav class="text-xs font-semibold uppercase tracking-wider text-slate-400 flex items-center gap-1.5" aria-label="Remah roti">
            <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('classes.show', $class) }}" class="hover:text-slate-600">{{ $class->name }}</a>
            <span aria-hidden="true">/</span>
            <span class="text-slate-500">Poin Kerajinan</span>
        </nav>
        <h1 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Poin Kerajinan &amp; Siswa Terajin</h1>
        <p class="mt-1 text-xs text-slate-500">
            Dihitung otomatis:
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">+{{ \App\Support\PoinKerajinan::NILAI['hadir'] }}</span> tiap hadir,
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">{{ \App\Support\PoinKerajinan::NILAI['alfa'] }}</span> tiap alfa,
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">{{ \App\Support\PoinKerajinan::NILAI['izin'] }}</span> tiap izin/sakit,
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800">+2</span> pengurus kelas.
        </p>
    </div>

    @include('partials.class-nav', ['classroom' => $class])

    <form method="GET" class="flex flex-wrap items-end gap-3 bg-white rounded-2xl border border-emerald-200 shadow-xs p-4">
        <input type="hidden" name="mode" value="semester">

        <div>
            <label for="sub_periode" class="block text-xs font-semibold text-slate-700 mb-1">Periode</label>
            <select id="sub_periode" name="sub_periode" class="block rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 w-52">
                <option value="1_penuh" @selected($subAktif==='1_penuh')>Semester 1 (Penuh)</option>
                <option value="1_pts" @selected($subAktif==='1_pts')>Tengah Semester 1 (PTS)</option>
                <option value="1_pas" @selected($subAktif==='1_pas')>Akhir Semester 1 (PAS)</option>
                <option value="2_penuh" @selected($subAktif==='2_penuh')>Semester 2 (Penuh)</option>
                <option value="2_pts" @selected($subAktif==='2_pts')>Tengah Semester 2 (PTS)</option>
                <option value="2_pas" @selected($subAktif==='2_pas')>Akhir Semester 2 (PAS)</option>
            </select>
        </div>

        <div>
            <label for="tahun" class="block text-xs font-semibold text-slate-700 mb-1">Tahun Ajaran (Awal)</label>
            <input id="tahun" type="number" name="tahun" value="{{ $periode['tahun'] }}" min="2000" max="2100" 
                   class="block rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 w-28">
        </div>

        <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Tampilkan</button>
    </form>

    @if ($peringkat->isEmpty())
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-8 text-center">
            <p class="text-sm font-bold text-slate-900">Belum Ada Data Kehadiran</p>
            <p class="mt-1 text-xs text-slate-500">Peringkat kerajinan disusun dari absensi pada {{ $periode['label'] }}, dan periode itu belum punya satu pun sesi terisi.</p>
        </div>
    @else
        @php $juara = $peringkat->first(); @endphp

        <section class="bg-white rounded-2xl border border-emerald-200 shadow-xs overflow-hidden">
            <div class="flex items-center justify-between px-4 pt-4 pb-3 border-b border-emerald-100">
                <h2 class="text-sm font-extrabold text-slate-900">Peringkat Kerajinan</h2>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $periode['label'] }}</span>
            </div>

            <div class="flex flex-col gap-3 border-b border-l-4 border-slate-100 border-l-emerald-600 bg-emerald-50/30 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white font-extrabold text-sm shrink-0">1</span>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-800">Siswa Terajin</p>
                        <p class="text-sm font-bold tracking-tight text-slate-900">{{ $juara->name }}</p>
                        <p class="font-mono text-xs text-slate-500">{{ $juara->poin }} poin kerajinan</p>
                    </div>
                </div>
                <a href="{{ route('classes.kerajinan.sertifikat', array_merge([$class, 'student_id' => $juara->student_id], $paramSertifikat)) }}"
                   class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition-colors shrink-0">Cetak Sertifikat</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th scope="col" class="py-2.5 px-3 w-10 text-right">#</th>
                            <th scope="col" class="py-2.5 px-3">Nama</th>
                            <th scope="col" class="py-2.5 px-3 text-right">Poin</th>
                            <th scope="col" class="py-2.5 px-3 text-right">Sertifikat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($peringkat as $i => $b)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-2.5 px-3 text-right font-mono text-xs text-slate-400">{{ $i + 1 }}</td>
                                <td class="py-2.5 px-3 font-semibold text-slate-900">{{ $b->name }}</td>
                                <td class="py-2.5 px-3 font-mono text-right font-bold {{ $b->poin < 0 ? 'text-rose-700' : 'text-slate-900' }}">{{ $b->poin }}</td>
                                <td class="py-2.5 px-3 text-right">
                                    <a href="{{ route('classes.kerajinan.sertifikat', array_merge([$class, 'student_id' => $b->student_id], $paramSertifikat)) }}"
                                       class="text-xs font-bold text-emerald-700 hover:underline">Cetak</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
