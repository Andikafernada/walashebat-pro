@extends('layouts.app')
@section('title', 'Kas per Siswa — ' . $classroom->name)
@section('content')

@include('partials.class-nav', ['classroom' => $classroom])

<div class="space-y-6 pb-12">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">Rekap Iuran Kas per Siswa</h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-600 font-medium">
                {{ $classroom->name }} &middot; {{ $periode['label'] }}
            </p>
        </div>
        <a href="{{ route('classes.cashbook.index', $classroom) }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all">
            ← Kembali ke Buku Kas
        </a>
    </div>

    @include('partials.flash')

    {{-- 3 KPI STATS --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <div class="rounded-3xl border border-emerald-200 bg-white p-4 sm:p-5 shadow-xs space-y-1">
            <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider text-[10.5px]">Sudah Setor Kas</span>
            <p class="text-2xl font-extrabold text-slate-900">{{ $sudah }} <span class="text-xs font-semibold text-slate-500">siswa</span></p>
        </div>
        <div class="rounded-3xl border border-emerald-200 bg-white p-4 sm:p-5 shadow-xs space-y-1">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider text-[10.5px]">Belum Setor Kas</span>
            <p class="text-2xl font-extrabold text-slate-900">{{ $belum }} <span class="text-xs font-semibold text-slate-500">siswa</span></p>
        </div>
        <div class="rounded-3xl border border-emerald-200 bg-white p-4 sm:p-5 shadow-xs space-y-1">
            <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider text-[10.5px]">Total Terkumpul</span>
            <p class="text-2xl font-extrabold text-slate-900">Rp {{ number_format($total, 0, ',', '.') }}</p>
        </div>
    </div>

    @if ($tanpaNama > 0)
        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-slate-900 font-medium">
            Ada <strong>Rp {{ number_format($tanpaNama, 0, ',', '.') }}</strong> pemasukan kas periode ini yang dicatat tanpa nama siswa spesifik. Buka Buku Kas untuk melihat rinciannya.
        </div>
    @endif

    @if ($baris->isEmpty())
        <div class="rounded-3xl border border-emerald-200 bg-white p-10 text-center space-y-2 shadow-xs">
            <p class="text-sm font-bold text-slate-900">Belum Ada Siswa Aktif di Kelas Ini</p>
        </div>
    @else
    <form method="POST" action="{{ route('classes.cashbook.setoran-massal', $classroom) }}"
          x-data="{ terpilih: [], mengirim: false }" @submit="mengirim = true" class="space-y-4">
        @csrf

        {{-- FORM INPUT SETORAN MASSAL --}}
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50/60 p-4 sm:p-5 space-y-4 shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-lg">⚡</span>
                <h2 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">Input Setoran Kas Sekaligus (Massal)</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-900 uppercase tracking-wider mb-1">Nominal per Siswa (Rp) *</label>
                    <input type="number" name="amount" min="1" required value="{{ old('amount', 5000) }}" placeholder="5000"
                           class="w-full px-3.5 py-2 text-xs rounded-xl border border-emerald-200 bg-white text-slate-900 font-bold focus:outline-none focus:border-emerald-600">
                    @error('amount')<p class="mt-1 text-[11px] font-bold text-slate-900">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-900 uppercase tracking-wider mb-1">Keterangan / Minggu *</label>
                    <input type="text" name="description" required maxlength="191"
                           value="{{ old('description', 'Kas '.now()->translatedFormat('F Y')) }}"
                           class="w-full px-3.5 py-2 text-xs rounded-xl border border-emerald-200 bg-white text-slate-900 font-semibold focus:outline-none focus:border-emerald-600">
                    @error('description')<p class="mt-1 text-[11px] font-bold text-slate-900">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-900 uppercase tracking-wider mb-1">Tanggal Transaksi *</label>
                    <input type="date" name="transaction_date" required value="{{ old('transaction_date', date('Y-m-d')) }}"
                           class="w-full px-3.5 py-2 text-xs rounded-xl border border-emerald-200 bg-white text-slate-900 font-semibold focus:outline-none focus:border-emerald-600">
                    @error('transaction_date')<p class="mt-1 text-[11px] font-bold text-slate-900">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-emerald-200">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="terpilih = @js($baris->where('jumlah', 0)->pluck('siswa.id')->values())"
                            class="px-3 py-1.5 rounded-xl border border-emerald-200 bg-white text-xs font-bold text-slate-800 hover:bg-emerald-100 transition-colors shadow-2xs">
                        Centang Semua yang Belum ({{ $belum }})
                    </button>
                    <button type="button" @click="terpilih = []"
                            class="px-3 py-1.5 rounded-xl border border-emerald-200 bg-white text-xs font-bold text-slate-500 hover:bg-emerald-50 transition-colors">
                        Kosongkan
                    </button>
                </div>

                <button type="submit" :disabled="mengirim || terpilih.length === 0"
                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold shadow-sm shadow-emerald-200 transition-all">
                    <span x-show="!mengirim">Simpan Setoran (<span x-text="terpilih.length"></span> Siswa)</span>
                    <span x-show="mengirim" x-cloak>Menyimpan Data...</span>
                </button>
            </div>

            @error('students')<p class="text-[11px] font-bold text-slate-900">{{ $message }}</p>@enderror
        </div>

        {{-- TABEL SISWA DAN STATUS IURAN --}}
        <div class="overflow-x-auto rounded-3xl border border-emerald-200 bg-white shadow-xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-emerald-50/70 border-b border-emerald-100 text-emerald-950 font-bold">
                        <th class="px-4 py-3 w-10 text-center">Pilih</th>
                        <th class="px-4 py-3 font-extrabold">Nama Siswa</th>
                        <th class="px-4 py-3 font-extrabold text-center">Status</th>
                        <th class="px-4 py-3 font-extrabold text-right">Total Disetor</th>
                        <th class="px-4 py-3 font-extrabold text-center">Frekuensi</th>
                        <th class="px-4 py-3 font-extrabold">Terakhir Bayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-100/60">
                    @foreach ($baris as $b)
                        <tr class="{{ $b['jumlah'] === 0 ? 'bg-white' : 'hover:bg-emerald-50/40' }} transition-colors">
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" name="students[]" value="{{ $b['siswa']->id }}"
                                       x-model.number="terpilih"
                                       class="h-4 w-4 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500 accent-emerald-600 cursor-pointer">
                            </td>
                            <td class="px-4 py-3 font-bold text-slate-900">
                                {{ $b['siswa']->name }}
                                <span class="block text-[10px] font-normal text-slate-500">{{ $b['siswa']->nis }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($b['jumlah'] > 0)
                                    <span class="inline-flex items-center rounded-lg bg-emerald-100 text-emerald-950 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-extrabold">Sudah Setor</span>
                                @else
                                    <span class="inline-flex items-center rounded-lg bg-emerald-50 text-slate-700 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-bold">Belum</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-extrabold {{ $b['jumlah'] > 0 ? 'text-slate-900' : 'text-slate-400' }}">
                                {{ $b['jumlah'] > 0 ? 'Rp '.number_format($b['jumlah'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-slate-700">
                                {{ $b['kali'] > 0 ? $b['kali'].'×' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-600 font-medium">
                                {{ $b['terakhir']?->translatedFormat('d M Y') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>

    <p class="text-[11px] text-slate-500 font-medium">
        "Belum" berarti belum ada setoran tercatat atas nama siswa bersangkutan pada periode bulan ini.
    </p>
    @endif

</div>
@endsection
