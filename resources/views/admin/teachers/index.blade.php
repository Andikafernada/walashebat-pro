@extends('layouts.app-operator')

@section('title', 'Daftar Guru')

@section('content')
<div class="space-y-6 pb-12">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">Daftar Guru</h1>
            <p class="mt-0.5 text-xs text-slate-500">Siapa saja yang mendaftar, di sekolah mana, dan sedang di segmen apa.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800">‹ Panel Operator</a>
    </div>

    @include('partials.flash')

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
           class="rounded-xl border px-3.5 py-1.5 text-xs font-bold transition-colors {{ $segmen === null ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
            Semua
        </a>
        @foreach ($labelSegmen as $kunci => $label)
            <a href="{{ route('admin.teachers.index', ['segmen' => $kunci, 'cari' => $cari]) }}"
               class="rounded-xl border px-3.5 py-1.5 text-xs font-bold transition-colors {{ $segmen === $kunci ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                {{ $label }}
                <span class="{{ $segmen === $kunci ? 'text-emerald-100' : 'text-slate-400' }}">{{ $jumlahSegmen[$kunci] }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.teachers.index') }}" class="flex gap-2">
        @if ($segmen)
            <input type="hidden" name="segmen" value="{{ $segmen }}">
        @endif
        <input type="search" name="cari" value="{{ $cari }}" placeholder="Cari nama, email, sekolah, atau nomor WhatsApp"
               class="h-10 flex-1 rounded-xl border border-slate-200 bg-white px-4 text-xs text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        <button type="submit" class="h-10 rounded-xl bg-slate-900 px-5 text-xs font-bold text-white hover:bg-slate-800 transition-colors">Cari</button>
        @if ($cari !== '')
            <a href="{{ route('admin.teachers.index', ['segmen' => $segmen]) }}" class="h-10 inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-500 hover:bg-slate-50 transition-colors">Bersihkan</a>
        @endif
    </form>

    @if ($guru->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <p class="text-xs font-bold text-slate-700">
                {{ $cari !== '' ? 'Tidak ada guru yang cocok dengan pencarian itu' : 'Belum ada guru di segmen ini' }}
            </p>
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-emerald-200 bg-white shadow-xs">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50/60 border-b border-slate-100 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="px-4 py-3 font-bold">Guru</th>
                        <th class="px-4 py-3 font-bold">Sekolah</th>
                        <th class="px-4 py-3 font-bold text-center">Kelas</th>
                        <th class="px-4 py-3 font-bold">Daftar</th>
                        <th class="px-4 py-3 font-bold">Langganan</th>
                        <th class="px-4 py-3 font-bold text-center">WhatsApp</th>
                        <th class="px-4 py-3 font-bold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($guru as $g)
                        @php
                            $aktif = $g->subscription_ends_at && $g->subscription_ends_at->isFuture();
                            $pro = $g->subscription_tier === \App\Models\User::TIER_PRO;
                        @endphp
                        <tr class="{{ $g->is_active ? '' : 'bg-slate-50' }} hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.teachers.show', $g) }}" class="font-bold text-emerald-800 hover:underline">{{ $g->name }}</a>
                                @unless ($g->is_active)
                                    <span class="ml-1 rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">nonaktif</span>
                                @endunless
                                <span class="block text-[11px] text-slate-400 font-mono">{{ $g->email }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $g->school_name ?: '—' }}</td>
                            <td class="px-4 py-3 text-center font-bold text-slate-700 font-mono">{{ $g->classes_count }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                {{ $g->created_at->translatedFormat('d M Y') }}
                                <span class="block text-[11px] text-slate-400">{{ $g->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold {{ $pro ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $pro ? 'PRO' : 'Gratis' }}
                                </span>
                                <span class="block text-[11px] font-semibold {{ $aktif ? 'text-emerald-700' : 'text-rose-600' }}">
                                    {{ $g->subscription_ends_at
                                        ? ($aktif ? 'sampai '.$g->subscription_ends_at->translatedFormat('d M Y') : 'habis '.$g->subscription_ends_at->translatedFormat('d M Y'))
                                        : 'belum berjalan' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($g->wa_session_status === 'connected')
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">tersambung</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">{{ $g->wa_session_status ?: 'belum' }}</span>
                                @endif
                                @if ($g->whatsapp_number)
                                    <span class="block text-[11px] text-slate-400 font-mono">{{ $g->whatsapp_number }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.teachers.show', $g) }}"
                                       class="text-xs text-emerald-700 hover:underline font-bold">Detail</a>
                                    <form method="POST"
                                          action="{{ route('admin.teachers.destroy', $g) }}"
                                          onsubmit="return confirm('Hapus akun {{ $g->name }} beserta seluruh datanya? Tindakan ini tidak bisa dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-xs text-rose-500 hover:text-rose-700 font-semibold">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
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
