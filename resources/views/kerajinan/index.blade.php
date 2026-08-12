@extends('layouts.app')

@section('title', 'Poin Kerajinan - ' . ($class->name ?? ''))

@section('content')
@php
    $subAktif = $periode['sub_periode'] ?? '1_penuh';
    $paramSertifikat = ['sub_periode' => $subAktif, 'tahun' => $periode['tahun'], 'mode' => 'semester'];
@endphp

<div class="space-y-5 pb-12">

    <div class="border-b border-slate-200 pb-4">
        <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
            <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('classes.show', $class) }}" class="hover:text-slate-600">{{ $class->name }}</a>
            <span aria-hidden="true">/</span>
            <span class="text-slate-500">Poin Kerajinan</span>
        </nav>
        <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Poin Kerajinan &amp; Siswa Terajin</h1>
        <p class="mt-1 text-sm text-slate-500">
            Dihitung otomatis dari kehadiran:
            <span class="kode kode--hadir">+{{ \App\Support\PoinKerajinan::NILAI['hadir'] }}</span> tiap hadir,
            <span class="kode kode--alfa">{{ \App\Support\PoinKerajinan::NILAI['alfa'] }}</span> tiap alfa.
            Sakit, izin, dan terlambat tidak menggeser poin.
        </p>
    </div>

    @include('partials.class-nav', ['classroom' => $class])

    <form method="GET" class="flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 bg-white p-3">
        <input type="hidden" name="mode" value="semester">

        <div class="form-group">
            <label for="sub_periode" class="form-label">Periode</label>
            <select id="sub_periode" name="sub_periode" class="form-select form-input--sm w-52">
                <option value="1_penuh" @selected($subAktif==='1_penuh')>Semester 1 (Penuh)</option>
                <option value="1_pts" @selected($subAktif==='1_pts')>Tengah Semester 1 (PTS)</option>
                <option value="1_pas" @selected($subAktif==='1_pas')>Akhir Semester 1 (PAS)</option>
                <option value="2_penuh" @selected($subAktif==='2_penuh')>Semester 2 (Penuh)</option>
                <option value="2_pts" @selected($subAktif==='2_pts')>Tengah Semester 2 (PTS)</option>
                <option value="2_pas" @selected($subAktif==='2_pas')>Akhir Semester 2 (PAS)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tahun" class="form-label">Tahun ajaran (awal)</label>
            <input id="tahun" type="number" name="tahun" value="{{ $periode['tahun'] }}" min="2000" max="2100" class="form-input form-input--sm w-24">
        </div>

        <button type="submit" class="btn-secondary btn-secondary--sm">Tampilkan</button>
    </form>

    @if ($peringkat->isEmpty())
        <div class="empty-state">
            <p class="empty-state__title">Belum ada data kehadiran</p>
            <p class="empty-state__description">Peringkat kerajinan disusun dari absensi pada {{ $periode['label'] }}, dan periode itu belum punya satu pun sesi terisi.</p>
        </div>
    @else
        @php $juara = $peringkat->first(); @endphp

        <section class="blok">
            <div class="blok__kepala">
                <h2 class="blok__judul">Peringkat kerajinan</h2>
                <span class="eyebrow">{{ $periode['label'] }}</span>
            </div>

            {{-- Juara diberi satu baris sendiri di atas tabel, bukan piala emas
                 bergradien: yang perlu dibaca wali kelas adalah namanya dan
                 tombol cetaknya. --}}
            <div class="flex flex-col gap-3 border-b border-l-[3px] border-slate-200 border-l-slate-900 bg-slate-50 p-3.5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="kode kode--aksen shrink-0">1</span>
                    <div>
                        <p class="eyebrow">Siswa terajin</p>
                        <p class="text-sm font-semibold tracking-tight text-slate-900">{{ $juara->name }}</p>
                        <p class="angka text-xs text-slate-500">{{ $juara->poin }} poin kerajinan</p>
                    </div>
                </div>
                <a href="{{ route('classes.kerajinan.sertifikat', array_merge([$class, 'student_id' => $juara->student_id], $paramSertifikat)) }}"
                   class="btn-primary btn-primary--sm shrink-0">Cetak Sertifikat</a>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" class="w-10 text-right">#</th>
                            <th scope="col">Nama</th>
                            <th scope="col" class="text-right">Poin</th>
                            <th scope="col" class="text-right">Sertifikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($peringkat as $i => $b)
                            <tr>
                                <td class="text-right font-mono text-xs text-slate-400">{{ $i + 1 }}</td>
                                <td>{{ $b->name }}</td>
                                <td class="angka text-right font-medium {{ $b->poin < 0 ? 'text-rose-700' : 'text-slate-900' }}">{{ $b->poin }}</td>
                                <td class="text-right">
                                    <a href="{{ route('classes.kerajinan.sertifikat', array_merge([$class, 'student_id' => $b->student_id], $paramSertifikat)) }}"
                                       class="text-xs font-medium text-indigo-700 hover:underline">Cetak</a>
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
