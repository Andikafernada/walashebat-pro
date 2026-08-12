@extends('layouts.app')
@section('title', 'Kas per Siswa — ' . $classroom->name)
@section('content')

@include('partials.class-nav')

<div class="space-y-6 pb-12">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Kas per Siswa</h1>
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
            <p class="mt-1 text-2xl font-semibold text-emerald-800">{{ $sudah }} <span class="text-sm font-bold text-emerald-600">siswa</span></p>
        </div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50/60 p-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Belum setor</p>
            <p class="mt-1 text-2xl font-semibold text-rose-800">{{ $belum }} <span class="text-sm font-bold text-rose-600">siswa</span></p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Terkumpul</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">Rp {{ number_format($total, 0, ',', '.') }}</p>
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
    {{--
        Halaman ini SEKALIGUS tempat mengisinya.

        Formulir kas satuan menuntut lima isian untuk satu anak; menagih kas
        bulanan di kelas 40 siswa berarti mengulanginya 40 kali. Beban sebesar
        itu tidak berakhir sebagai pekerjaan yang dikerjakan dengan patuh — ia
        berakhir sebagai kolom "Siswa" yang dilewati, lalu halaman ini kosong
        meski uangnya masuk. Di sinilah wali kelas sudah melihat siapa yang
        belum, jadi di sinilah pula ia harus bisa mencentangnya.
    --}}
    {{--
        `mengirim` menutup tombol begitu formulir dikirim. Di sinyal lambat,
        tombol yang masih hidup akan ditekan dua kali — dan setoran 40 anak
        tercatat dua kali. Ini penjagaan untuk kekeliruan, bukan untuk request
        yang disusun sendiri; yang terakhir butuh unique index di basis data.
    --}}
    <form method="POST" action="{{ route('classes.cashbook.setoran-massal', $classroom) }}"
          x-data="{ terpilih: [], mengirim: false }" @submit="mengirim = true">
        @csrf

        <div class="rounded-2xl border-2 border-emerald-200 bg-emerald-50/40 p-4 mb-4 space-y-3">
            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Nominal per siswa *</label>
                    <input type="number" name="amount" min="1" required value="{{ old('amount') }}" placeholder="20000"
                           class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none">
                    @error('amount')<p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Keterangan *</label>
                    <input type="text" name="description" required maxlength="191"
                           value="{{ old('description', 'Kas '.now()->translatedFormat('F Y')) }}"
                           class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none">
                    @error('description')<p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Tanggal *</label>
                    <input type="date" name="transaction_date" required value="{{ old('transaction_date', date('Y-m-d')) }}"
                           class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none">
                    @error('transaction_date')<p class="mt-1 text-[11px] font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="terpilih = @js($baris->where('jumlah', 0)->pluck('siswa.id')->values())"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50">
                        Centang semua yang belum ({{ $belum }})
                    </button>
                    <button type="button" @click="terpilih = []"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-500 hover:bg-slate-50">
                        Bersihkan
                    </button>
                </div>

                <button type="submit" :disabled="mengirim || terpilih.length === 0"
                        class="h-9 rounded-xl bg-emerald-600 px-5 text-xs font-bold text-white hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <span x-show="!mengirim">💾 Catat setoran <span x-text="terpilih.length"></span> siswa</span>
                    <span x-show="mengirim" x-cloak>Menyimpan…</span>
                </button>
            </div>

            @error('students')<p class="text-[11px] font-semibold text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-3 py-2.5 font-bold w-10"></th>
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
                            <td class="px-3 py-2.5">
                                <input type="checkbox" name="students[]" value="{{ $b['siswa']->id }}"
                                       x-model.number="terpilih"
                                       class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            </td>
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
    </form>

        <p class="mt-3 text-[11px] text-slate-400">
            &ldquo;Belum&rdquo; berarti tidak ada setoran atas namanya pada periode ini.
            Pengeluaran tidak ikut dihitung, sekalipun bertanda siswa.
            Setoran yang nominalnya berbeda-beda tetap lewat formulir satuan di
            <a href="{{ route('classes.cashbook.index', $classroom) }}" class="font-semibold text-slate-500 underline">Buku Kas</a>.
        </p>
    @endif

</div>
@endsection
