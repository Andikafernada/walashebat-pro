@extends('layouts.app')

@section('title', 'Daftar Guru')

{{--
    Panel Operator menjawab BERAPA; halaman ini menjawab SIAPA.

    Bentuknya sengaja dangkal: satu daftar, satu kotak cari, satu saringan.
    Tidak ada sunting dan tidak ada hapus — yang dibutuhkan operator saat guru
    menelepon adalah menemukan orangnya, dan tindakan atas akun sudah punya
    tempatnya sendiri di halaman langganan.

    Gaya mengikuti admin/dashboard.blade.php: utility Tailwind langsung di
    markup, tanpa @push('styles') — tidak ada layout yang merender @stack.
--}}

@section('content')
<div class="space-y-6 pb-12">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Daftar Guru 📇</h1>
            <p class="text-xs text-slate-500 mt-0.5">Siapa saja yang mendaftar, di sekolah mana, dan sedang di segmen apa.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">‹ Panel Operator</a>
    </div>

    @include('partials.flash')

    {{--
        Saringan segmen berperan ganda sebagai ringkasan: angkanya terlihat
        tanpa harus diklik. Definisinya sama persis dengan Panel Operator, jadi
        kedua halaman tidak mungkin menyebut angka yang berbeda.
    --}}
    <div class="flex flex-wrap gap-2">
        @php
            $labelSegmen = [
                'masa_gratis' => 'Masa gratis',
                'gratis_habis' => 'Gratis habis',
                'berbayar' => 'Berbayar',
                'berbayar_lewat_tempo' => 'Lewat tempo',
            ];
        @endphp

        <a href="{{ route('admin.teachers.index', ['cari' => $cari]) }}"
           class="rounded-xl border px-3 py-1.5 text-xs font-bold transition-colors {{ $segmen === null ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
            Semua
        </a>
        @foreach ($labelSegmen as $kunci => $label)
            <a href="{{ route('admin.teachers.index', ['segmen' => $kunci, 'cari' => $cari]) }}"
               class="rounded-xl border px-3 py-1.5 text-xs font-bold transition-colors {{ $segmen === $kunci ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                {{ $label }}
                <span class="{{ $segmen === $kunci ? 'text-indigo-100' : 'text-slate-400' }}">{{ $jumlahSegmen[$kunci] }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.teachers.index') }}" class="flex gap-2">
        @if ($segmen)
            <input type="hidden" name="segmen" value="{{ $segmen }}">
        @endif
        <input type="search" name="cari" value="{{ $cari }}" placeholder="Cari nama, email, sekolah, atau nomor WhatsApp"
               class="h-10 flex-1 rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none">
        <button type="submit" class="h-10 rounded-xl bg-slate-900 px-5 text-xs font-bold text-white hover:bg-slate-800">Cari</button>
        @if ($cari !== '')
            <a href="{{ route('admin.teachers.index', ['segmen' => $segmen]) }}" class="h-10 inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-500 hover:bg-slate-50">Bersihkan</a>
        @endif
    </form>

    @if ($guru->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <p class="text-sm font-bold text-slate-700">
                {{ $cari !== '' ? 'Tidak ada guru yang cocok dengan pencarian itu' : 'Belum ada guru di segmen ini' }}
            </p>
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-bold">Guru</th>
                        <th class="px-4 py-3 font-bold">Sekolah</th>
                        <th class="px-4 py-3 font-bold text-center">Kelas</th>
                        <th class="px-4 py-3 font-bold">Daftar</th>
                        <th class="px-4 py-3 font-bold">Langganan</th>
                        <th class="px-4 py-3 font-bold text-center">WhatsApp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($guru as $g)
                        @php
                            $aktif = $g->subscription_ends_at && $g->subscription_ends_at->isFuture();
                            $pro = $g->subscription_tier === \App\Models\User::TIER_PRO;
                        @endphp
                        <tr class="{{ $g->is_active ? '' : 'bg-slate-50/60' }} hover:bg-slate-50">
                            <td class="px-4 py-3">
                                {{-- Tautannya di nama, bukan seluruh baris: baris yang bisa
                                     diklik menyembunyikan tujuannya dari status bar peramban
                                     dan tidak bisa dijangkau lewat Tab. --}}
                                <a href="{{ route('admin.teachers.show', $g) }}" class="font-semibold text-indigo-700 hover:underline">{{ $g->name }}</a>
                                @unless ($g->is_active)
                                    <span class="ml-1 rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">nonaktif</span>
                                @endunless
                                <span class="block text-[11px] text-slate-400">{{ $g->email }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $g->school_name ?: '—' }}</td>
                            <td class="px-4 py-3 text-center font-semibold text-slate-700">{{ $g->classes_count }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                {{ $g->created_at->translatedFormat('d M Y') }}
                                <span class="block text-[11px] text-slate-400">{{ $g->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="rounded px-1.5 py-0.5 text-[10px] font-bold {{ $pro ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $pro ? 'PRO' : 'Gratis' }}
                                </span>
                                <span class="block text-[11px] {{ $aktif ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $g->subscription_ends_at
                                        ? ($aktif ? 'sampai '.$g->subscription_ends_at->translatedFormat('d M Y') : 'habis '.$g->subscription_ends_at->translatedFormat('d M Y'))
                                        : 'belum berjalan' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($g->wa_session_status === 'connected')
                                    <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700">tersambung</span>
                                @else
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500">{{ $g->wa_session_status ?: 'belum' }}</span>
                                @endif
                                @if ($g->whatsapp_number)
                                    <span class="block text-[11px] text-slate-400">{{ $g->whatsapp_number }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>{{ $guru->links() }}</div>
    @endif

</div>
@endsection
