@extends('layouts.app')

@section('title', 'Input Absensi Manual - ' . $class->name)

@section('content')
@php
    // Huruf yang sama dengan Attendance::KODE dan seluruh rekap.
    $pilihan = [
        'hadir' => 'H',
        'terlambat' => 'T',
        'sakit' => 'S',
        'izin' => 'I',
        'alfa' => 'A',
    ];
@endphp

<div class="space-y-5 pb-12">

    <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.attendance.index', $class) }}" class="hover:text-slate-600">Absensi</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Input Manual</span>
            </nav>
            <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Input Absensi Manual / Susulan</h1>
            <p class="mt-1 text-sm text-slate-500">Isi presensi langsung tanpa magic link — untuk tanggal yang sudah lewat.</p>
        </div>

        <a href="{{ route('classes.attendance.index', $class) }}" class="btn-secondary btn-secondary--sm shrink-0">Kembali ke Daftar Absensi</a>
    </div>

    @include('partials.class-nav', ['classroom' => $class])

    @include('partials.flash')

    <form method="POST" action="{{ route('classes.attendance.manual.store', $class) }}" class="space-y-4">
        @csrf

        <section class="blok">
            <div class="blok__kepala"><h2 class="blok__judul">Keterangan sesi</h2></div>
            <div class="blok__isi grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="form-group">
                    <label for="session_date" class="form-label form-label--required">Tanggal absensi</label>
                    <input id="session_date" type="date" name="session_date" required value="{{ date('Y-m-d') }}" class="form-input">
                    <p class="form-hint">Boleh tanggal lampau.</p>
                </div>

                <div class="form-group">
                    <label for="title" class="form-label">Judul sesi <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input id="title" type="text" name="title" value="{{ old('title') }}" placeholder="cth: Absensi Susulan Juli" class="form-input">
                </div>

                {{--
                    Mapel & materi hanya muncul pada kelas ajar.

                    Keduanya bekal jurnal mengajar. Materi disediakan sejak
                    sekarang meski laporan jurnalnya menyusul, karena jurnal
                    tidak bisa diisi mundur — tidak ada yang mengingat materi
                    tanggal 12 Agustus pada bulan berikutnya.
                --}}
                @if ($class->kelasAjar())
                    @php $mapelPilihan = $class->mapelDiampu(); @endphp
                    <div class="form-group">
                        <label for="mapel" class="form-label">Mata pelajaran</label>
                        @if (count($mapelPilihan) > 1)
                            <select id="mapel" name="mapel" class="form-select">
                                @foreach ($mapelPilihan as $m)
                                    <option value="{{ $m }}" @selected(old('mapel') === $m)>{{ $m }}</option>
                                @endforeach
                            </select>
                        @else
                            <input id="mapel" type="text" name="mapel" value="{{ old('mapel', $mapelPilihan[0] ?? '') }}" placeholder="cth: Matematika" class="form-input">
                        @endif
                        <p class="form-hint">Atur daftar mapel di halaman Ubah Kelas.</p>
                    </div>

                    <div class="form-group">
                        <label for="materi" class="form-label">Materi hari ini <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input id="materi" type="text" name="materi" value="{{ old('materi') }}" placeholder="cth: Sistem bilangan biner" class="form-input">
                        <p class="form-hint">Dipakai untuk jurnal mengajar.</p>
                    </div>
                @endif
            </div>
        </section>

        <section class="blok">
            <div class="blok__kepala">
                <h2 class="blok__judul">Roster siswa</h2>
                <span class="eyebrow">Bawaan: hadir</span>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" class="w-10 text-right">No</th>
                            <th scope="col">Nama Siswa</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col">Catatan / Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $idx => $st)
                            <tr>
                                <td class="text-right font-mono text-xs text-slate-400">{{ $idx + 1 }}</td>
                                <td>
                                    {{ $st->name }}
                                    <span class="block font-mono text-[10px] font-normal text-slate-400">{{ $st->nis ?: '—' }}</span>
                                </td>
                                <td>
                                    {{-- Lima tombol berdempet, satu ketuk, tanpa warna
                                         berbeda-beda. Kepala kolomnya satu; huruf H T S I A
                                         inilah yang dipakai di seluruh rekap dan cetakan. --}}
                                    <fieldset class="flex justify-center">
                                        <legend class="sr-only">Status kehadiran {{ $st->name }}</legend>
                                        <div class="inline-flex overflow-hidden rounded border border-slate-200">
                                            @foreach ($pilihan as $nilai => $huruf)
                                                <label class="cursor-pointer border-r border-slate-200 last:border-r-0" title="{{ Str::title($nilai) }}">
                                                    <input type="radio" class="peer sr-only" name="attendance[{{ $st->id }}]" value="{{ $nilai }}" @checked($nilai === 'hadir')>
                                                    <span class="block px-2.5 py-1 font-mono text-xs text-slate-500 transition-colors peer-hover:bg-slate-50 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500 peer-checked:bg-slate-900 peer-checked:text-white">{{ $huruf }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                </td>
                                <td>
                                    <label for="note-{{ $st->id }}" class="sr-only">Catatan untuk {{ $st->name }}</label>
                                    <input id="note-{{ $st->id }}" type="text" name="notes[{{ $st->id }}]" placeholder="cth: surat dokter" class="form-input form-input--sm">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('classes.attendance.index', $class) }}" class="btn-secondary">Batal</a>
            <button type="submit" class="btn-primary">Simpan Absensi Manual Tanggal Ini</button>
        </div>
    </form>
</div>
@endsection
