@extends('layouts.app')
@section('title', 'Kas per Siswa — ' . $classroom->name)
@section('content')

@include('partials.class-nav')

<div class="space-y-6 pb-12">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-black tracking-tight text-slate-900">Kas per Siswa</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                {{ $classroom->name }} &middot; {{ $periode['label'] }}
            </p>
        </div>
        <a href="{{ route('classes.cashbook.index', $classroom) }}"
           class="inline-flex h-9 items-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-600 hover:bg-slate-50">
            ← Buku Kas
        </a>
    </div>

    @include('partials.flash')

    {{-- Tiga angka yang paling sering ditanyakan, sebelum daftarnya. --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Sudah setor</p>
            <p class="mt-1 text-2xl font-black text-emerald-800">{{ $sudah }} <span class="text-sm font-bold text-emerald-600">siswa</span></p>
        </div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50/60 p-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Belum setor</p>
            <p class="mt-1 text-2xl font-black text-rose-800">{{ $belum }} <span class="text-sm font-bold text-rose-600">siswa</span></p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Terkumpul</p>
            <p class="mt-1 text-2xl font-black text-slate-900">Rp {{ number_format($total, 0, ',', '.') }}</p>
        </div>
    </div>

    @if ($tanpaNama > 0)
        {{-- Tanpa baris ini, jumlah di halaman ini tidak akan pernah cocok
             dengan saldo di buku besar, dan selisihnya terlihat seperti uang
             yang hilang. --}}
        <p class="rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-xs text-amber-900">
            Ada <strong>Rp {{ number_format($tanpaNama, 0, ',', '.') }}</strong> pemasukan pada periode ini yang
            dicatat <strong>tanpa nama siswa</strong>, jadi tidak ikut terhitung di tabel bawah.
            Buka Buku Kas untuk melihat rinciannya.
        </p>
    @endif

    @if ($baris->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
            <p class="text-sm font-bold text-slate-700">Belum ada siswa aktif di kelas ini</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-3 py-2.5 font-bold">Nama Siswa</th>
                        <th class="px-3 py-2.5 font-bold text-center">Status</th>
                        <th class="px-3 py-2.5 font-bold text-right">Jumlah</th>
                        <th class="px-3 py-2.5 font-bold text-center">Setoran</th>
                        <th class="px-3 py-2.5 font-bold">Terakhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($baris as $b)
                        <tr class="{{ $b['jumlah'] === 0 ? 'bg-rose-50/40' : '' }} hover:bg-slate-50/60">
                            <td class="px-3 py-2.5 font-semibold text-slate-800">
                                {{ $b['siswa']->name }}
                                <span class="block text-[10px] font-normal text-slate-400">{{ $b['siswa']->nis }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                @if ($b['jumlah'] > 0)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold text-emerald-800">Sudah</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-[10px] font-bold text-rose-800">Belum</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right font-bold {{ $b['jumlah'] > 0 ? 'text-slate-900' : 'text-slate-300' }}">
                                {{ $b['jumlah'] > 0 ? 'Rp '.number_format($b['jumlah'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-center text-slate-600">
                                {{ $b['kali'] > 0 ? $b['kali'].'×' : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-slate-600">
                                {{ $b['terakhir']?->translatedFormat('d M Y') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-[11px] text-slate-400">
            &ldquo;Belum&rdquo; berarti tidak ada setoran atas namanya pada periode ini.
            Pengeluaran tidak ikut dihitung, sekalipun bertanda siswa.
        </p>
    @endif

</div>
@endsection
