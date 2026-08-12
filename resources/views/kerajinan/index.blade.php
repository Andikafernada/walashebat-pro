@extends('layouts.app')

@section('title', 'Poin Kerajinan - ' . ($class->name ?? ''))

@section('content')
@php
    $subAktif = $periode['sub_periode'] ?? '1_penuh';
    $paramSertifikat = ['sub_periode' => $subAktif, 'tahun' => $periode['tahun'], 'mode' => 'semester'];
@endphp
<div class="space-y-6 pb-12">

    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('classes.index') }}" class="hover:text-indigo-600 transition-colors">Kelas</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">Poin Kerajinan</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Poin Kerajinan &amp; Siswa Terajin</h1>
            <p class="text-sm text-slate-500 mt-1">
                Otomatis dari kehadiran:
                <span class="font-semibold text-emerald-600">+{{ \App\Support\PoinKerajinan::NILAI['hadir'] }} tiap hadir</span>,
                <span class="font-semibold text-rose-600">{{ \App\Support\PoinKerajinan::NILAI['alfa'] }} tiap alfa</span>.
            </p>
        </div>
    </div>

    <!-- Pemilih periode -->
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4">
        <input type="hidden" name="mode" value="semester">
        <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
            Periode
            <select name="sub_periode" class="rounded-xl border-slate-300 text-sm">
                <option value="1_penuh" @selected($subAktif==='1_penuh')>Semester 1 (Penuh)</option>
                <option value="1_pts" @selected($subAktif==='1_pts')>Tengah Semester 1 (PTS)</option>
                <option value="1_pas" @selected($subAktif==='1_pas')>Akhir Semester 1 (PAS)</option>
                <option value="2_penuh" @selected($subAktif==='2_penuh')>Semester 2 (Penuh)</option>
                <option value="2_pts" @selected($subAktif==='2_pts')>Tengah Semester 2 (PTS)</option>
                <option value="2_pas" @selected($subAktif==='2_pas')>Akhir Semester 2 (PAS)</option>
            </select>
        </label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
            Tahun Ajaran (awal)
            <input type="number" name="tahun" value="{{ $periode['tahun'] }}" min="2000" max="2100" class="w-28 rounded-xl border-slate-300 text-sm">
        </label>
        <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tampilkan</button>
    </form>

    <p class="text-sm font-semibold text-slate-700">{{ $periode['label'] }}</p>

    @if ($peringkat->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
            <p class="text-sm text-slate-500">Belum ada data kehadiran pada periode ini, jadi peringkat belum bisa disusun.</p>
        </div>
    @else
        {{-- Juara --}}
        @php $juara = $peringkat->first(); @endphp
        <div class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-400 text-2xl">🏆</div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">Siswa Terajin</p>
                    <p class="text-lg font-bold text-slate-900">{{ $juara->name }}</p>
                    <p class="text-sm text-slate-500">{{ $juara->poin }} poin kerajinan</p>
                </div>
            </div>
            <a href="{{ route('classes.kerajinan.sertifikat', array_merge([$class, 'student_id' => $juara->student_id], $paramSertifikat)) }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Cetak Sertifikat
            </a>
        </div>

        {{-- Tabel peringkat --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-16">#</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3 text-right">Poin</th>
                        <th class="px-4 py-3 text-right">Sertifikat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($peringkat as $i => $b)
                        <tr class="{{ $i === 0 ? 'bg-amber-50/40' : '' }}">
                            <td class="px-4 py-3 font-semibold text-slate-400">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $b->name }}</td>
                            <td class="px-4 py-3 text-right font-bold {{ $b->poin < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $b->poin }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('classes.kerajinan.sertifikat', array_merge([$class, 'student_id' => $b->student_id], $paramSertifikat)) }}"
                                   class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Cetak</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
