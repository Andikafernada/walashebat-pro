@extends('layouts.app')
@section('title', 'Jurnal Mengajar — ' . $classroom->name)
@section('content')

@include('partials.class-nav')

<div class="space-y-6 pb-12">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-black tracking-tight text-slate-900">Jurnal Mengajar</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                {{ $classroom->name }} &middot; {{ $periode['label'] }}
                @if ($mapelDipilih) &middot; <span class="font-semibold text-indigo-600">{{ $mapelDipilih }}</span> @endif
            </p>
        </div>
        <button type="button" onclick="window.print()"
                class="h-9 rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-700 hover:bg-slate-50 print:hidden">
            🖨️ Cetak
        </button>
    </div>

    @include('partials.flash')

    {{-- Penyaring mapel hanya berguna bila memang mengampu lebih dari satu. --}}
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

    @if ($sesi->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
            <p class="text-sm font-bold text-slate-700">Belum ada pertemuan tercatat</p>
            <p class="mt-1 text-xs text-slate-500">
                Jurnal terisi sendiri setiap kali Anda mengisi presensi. Mulai dari menu
                <a href="{{ route('classes.attendance.manual.create', $classroom) }}" class="font-semibold text-indigo-600 hover:underline">Absensi Manual</a>.
            </p>
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-3 py-2.5 font-bold">Tanggal</th>
                        @if (count($mapelDiampu) > 1 || $sesi->contains(fn ($s) => filled($s->mapel)))
                            <th class="px-3 py-2.5 font-bold">Mapel</th>
                        @endif
                        <th class="px-3 py-2.5 font-bold">Materi</th>
                        <th class="px-3 py-2.5 font-bold text-center">Hadir</th>
                        <th class="px-3 py-2.5 font-bold">Tidak hadir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($sesi as $s)
                        @php
                            // "Hadir" mencakup terlambat: siswanya tetap masuk kelas.
                            $hadir = $s->jumlah_hadir + $s->jumlah_terlambat;
                            $absen = $s->jumlah_sakit + $s->jumlah_izin + $s->jumlah_alfa;
                        @endphp
                        <tr class="align-top hover:bg-slate-50/60">
                            <td class="px-3 py-2.5 whitespace-nowrap">
                                <span class="font-bold text-slate-800">{{ $s->session_date->translatedFormat('d M Y') }}</span>
                                <span class="block text-[10px] text-slate-400">{{ $s->session_date->translatedFormat('l') }}</span>
                            </td>

                            @if (count($mapelDiampu) > 1 || $sesi->contains(fn ($x) => filled($x->mapel)))
                                <td class="px-3 py-2.5 whitespace-nowrap text-slate-700">{{ $s->mapel ?: '—' }}</td>
                            @endif

                            <td class="px-3 py-2.5 min-w-[220px]">
                                {{--
                                    Materi bisa dilengkapi setelah jam mengajar.
                                    Guru kerap mengisi presensi cepat-cepat di kelas
                                    lalu menuliskan materinya belakangan.
                                --}}
                                <form method="POST" action="{{ route('classes.jurnal.materi', [$classroom, $s]) }}"
                                      class="flex items-center gap-1.5">
                                    @csrf @method('PATCH')
                                    <input type="text" name="materi" value="{{ $s->materi }}"
                                           placeholder="Tulis materi…"
                                           class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs focus:border-indigo-500 focus:outline-none print:border-0 print:px-0">
                                    <button type="submit"
                                            class="shrink-0 rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600 hover:bg-indigo-100 hover:text-indigo-700 print:hidden">
                                        Simpan
                                    </button>
                                </form>
                            </td>

                            <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                <span class="font-bold text-emerald-700">{{ $hadir }}</span>
                                @if ($s->jumlah_terlambat > 0)
                                    <span class="block text-[10px] text-amber-600">{{ $s->jumlah_terlambat }} terlambat</span>
                                @endif
                            </td>

                            <td class="px-3 py-2.5">
                                @if ($absen === 0)
                                    <span class="text-slate-400">—</span>
                                @else
                                    <span class="font-bold text-rose-700">{{ $absen }}</span>
                                    {{-- Namanya disebut: jurnal yang hanya menyebut angka
                                         tidak menjawab saat guru ditanya siapa saja. --}}
                                    <span class="block text-[10px] text-slate-500 leading-snug">
                                        @foreach ($s->attendances as $a)
                                            {{ $a->student->name ?? '—' }} ({{ strtoupper(substr($a->status, 0, 1)) }}){{ ! $loop->last ? ', ' : '' }}
                                        @endforeach
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-[11px] text-slate-500">
            {{ $sesi->count() }} pertemuan pada periode ini. Jurnal tersusun otomatis dari presensi — tidak ada data yang perlu diisi dua kali.
        </p>
    @endif
</div>
@endsection
