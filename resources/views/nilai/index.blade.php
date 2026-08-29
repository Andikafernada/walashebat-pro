@extends('layouts.app')
@section('title', 'Nilai Harian — ' . $classroom->name)
@section('content')

@include('partials.class-nav', ['classroom' => $classroom])

<div class="space-y-6 pb-12">

    {{-- HEADER & ACTIONS --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">Nilai Harian &amp; Asesmen</h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-600 font-medium">
                {{ $classroom->name }} &middot; {{ $periode['label'] }}
                @if ($mapelDipilih) &middot; <span class="font-bold text-emerald-800">{{ $mapelDipilih }}</span> @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('classes.nilai.rekap', $classroom) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all">
                <span>📊</span>
                <span>Leger Nilai (PTS/PAS)</span>
            </a>
            <a href="{{ route('classes.nilai.create', $classroom) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm shadow-emerald-200 transition-all">
                <span>+</span>
                <span>Penilaian Baru</span>
            </a>
        </div>
    </div>

    @include('partials.flash')

    {{-- FILTER JENIS NILAI --}}
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ request()->fullUrlWithQuery(['jenis' => null]) }}"
           class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $jenisDipilih === null ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-emerald-200 text-slate-700 hover:bg-emerald-50' }}">
            Semua Jenis
        </a>
        @foreach (\App\Models\Assessment::jenisTersedia() as $nilai => $label)
            <a href="{{ request()->fullUrlWithQuery(['jenis' => $nilai]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $jenisDipilih === $nilai ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-emerald-200 text-slate-700 hover:bg-emerald-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if (count($mapelDiampu) > 1)
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ request()->fullUrlWithQuery(['mapel' => null]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $mapelDipilih === null ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-emerald-200 text-slate-700 hover:bg-emerald-50' }}">
                Semua Mapel
            </a>
            @foreach ($mapelDiampu as $m)
                <a href="{{ request()->fullUrlWithQuery(['mapel' => $m]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ $mapelDipilih === $m ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-emerald-200 text-slate-700 hover:bg-emerald-50' }}">
                    {{ $m }}
                </a>
            @endforeach
        </div>
    @endif

    @if ($penilaian->isEmpty())
        <div class="rounded-3xl border border-emerald-200 bg-white p-10 text-center space-y-3 shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl mx-auto border border-emerald-200">📝</div>
            <p class="text-sm font-bold text-slate-900">Belum Ada Penilaian Tercatat</p>
            <p class="text-xs text-slate-500 max-w-sm mx-auto font-medium">
                Satu penilaian mewakili satu Capaian Pembelajaran (CP) atau Ulangan Harian.
            </p>
            <a href="{{ route('classes.nilai.create', $classroom) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-all">
                + Buat Penilaian Pertama
            </a>
        </div>
    @else
        {{-- DESKTOP VIEW: TABEL LEBAR --}}
        <div class="hidden md:block overflow-x-auto rounded-3xl border border-emerald-200 bg-white shadow-xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-emerald-50/70 border-b border-emerald-100 text-emerald-950 font-bold">
                        <th class="px-4 py-3 font-extrabold">Tanggal</th>
                        @if (count($mapelDiampu) > 1)
                            <th class="px-4 py-3 font-extrabold">Mapel</th>
                        @endif
                        <th class="px-4 py-3 font-extrabold">Jenis</th>
                        <th class="px-4 py-3 font-extrabold">Capaian Pembelajaran (CP)</th>
                        <th class="px-4 py-3 font-extrabold text-center">Rata-Rata</th>
                        <th class="px-4 py-3 font-extrabold text-center">Belum Dinilai</th>
                        <th class="px-4 py-3 text-right font-extrabold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-100/60">
                    @foreach ($penilaian as $p)
                        @php $rata = $p->rataRata(); $belum = $p->belumDinilai(); @endphp
                        <tr class="hover:bg-emerald-50/40 transition-colors">
                            <td class="px-4 py-3.5 whitespace-nowrap font-bold text-slate-900">
                                {{ $p->assessment_date->translatedFormat('d M Y') }}
                            </td>
                            @if (count($mapelDiampu) > 1)
                                <td class="px-4 py-3.5 whitespace-nowrap font-medium text-slate-700">{{ $p->mapel ?: '—' }}</td>
                            @endif
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-[10.5px] font-extrabold {{ $p->harian() ? 'bg-emerald-50 text-emerald-950 border border-emerald-200' : 'bg-emerald-100 text-emerald-950 border border-emerald-300' }}">
                                    {{ $p->harian() ? 'Harian' : strtoupper($p->jenis).' · Sem '.$p->semester }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-slate-800 font-medium max-w-xs truncate">
                                {{ $p->capaian_pembelajaran ?: ($p->mapel ?: '—') }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if ($rata === null)
                                    <span class="text-slate-400 font-bold">—</span>
                                @else
                                    <span class="font-extrabold tabular-nums {{ $rata < 75 ? 'text-slate-900 underline' : 'text-emerald-800' }}">{{ $rata }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center tabular-nums font-bold text-slate-700">
                                {{ $belum > 0 ? $belum . ' siswa' : '✓ Lengkap' }}
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('classes.nilai.edit', [$classroom, $p]) }}"
                                   class="inline-flex items-center px-3 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-900 font-bold border border-emerald-200 transition-colors">
                                    Isi / Ubah Nilai →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- MOBILE VIEW: DAFTAR KARTU --}}
        <div class="md:hidden space-y-3">
            @foreach ($penilaian as $p)
                @php $rata = $p->rataRata(); $belum = $p->belumDinilai(); @endphp
                <div class="bg-white rounded-2xl border border-emerald-200 p-4 shadow-xs space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-950 border border-emerald-200">
                                {{ $p->harian() ? 'Harian' : strtoupper($p->jenis) }}
                            </span>
                            <h3 class="text-sm font-bold text-slate-900 mt-1.5">{{ $p->capaian_pembelajaran ?: ($p->mapel ?: 'Penilaian') }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $p->assessment_date->translatedFormat('l, d F Y') }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-[10px] text-slate-500 font-semibold block">Rata-rata:</span>
                            <span class="text-base font-extrabold text-emerald-800">{{ $rata ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-emerald-100 flex items-center justify-between">
                        <span class="text-xs text-slate-600 font-medium">
                            {{ $belum > 0 ? $belum . ' siswa belum dinilai' : '✓ Semua dinilai' }}
                        </span>
                        <a href="{{ route('classes.nilai.edit', [$classroom, $p]) }}"
                           class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors">
                            Isi Nilai →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="text-[11px] text-slate-500 font-medium">
            Rata-rata hanya menghitung siswa yang sudah dinilai (tidak mengasumsikan nol untuk yang belum diuji).
        </p>
    @endif
</div>
@endsection
