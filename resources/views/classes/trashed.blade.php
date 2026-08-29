@extends('layouts.app')
@section('title', 'Arsip Kelas — ' . config('app.name'))
@section('content')

<div class="space-y-6 pb-12">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600">Dashboard</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Arsip</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">Arsip Kelas Terhapus</h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-600 font-medium">{{ $classes->total() }} kelas tersimpan di arsip sementara</p>
        </div>
        <a href="{{ route('classes.index') }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all">
            ← Kembali ke Semua Kelas
        </a>
    </div>

    @include('partials.flash')

    @if ($classes->isEmpty())
        <div class="rounded-3xl border border-emerald-200 bg-white p-12 text-center space-y-3 shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl mx-auto border border-emerald-200">📁</div>
            <p class="text-base font-bold text-slate-900">Arsip Masih Kosong</p>
            <p class="text-xs text-slate-500 max-w-sm mx-auto font-medium">
                Kelas yang Anda hapus akan masuk ke arsip sementara di sini. Anda dapat memulihkannya kapan saja atau menghapusnya secara permanen.
            </p>
        </div>
    @else
        {{-- DESKTOP VIEW --}}
        <div class="hidden md:block overflow-x-auto rounded-3xl border border-emerald-200 bg-white shadow-xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-emerald-50/70 border-b border-emerald-100 text-emerald-950 font-bold">
                        <th class="px-4 py-3 font-extrabold">Nama Kelas</th>
                        <th class="px-4 py-3 font-extrabold">Jurusan</th>
                        <th class="px-4 py-3 font-extrabold">Waktu Dihapus</th>
                        <th class="px-4 py-3 text-right font-extrabold">Aksi Pemulihan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-100/60">
                    @foreach ($classes as $class)
                        <tr class="hover:bg-emerald-50/40 transition-colors">
                            <td class="px-4 py-3.5 font-bold text-slate-900">
                                {{ $class->name }}
                            </td>
                            <td class="px-4 py-3.5 text-slate-600 font-medium">
                                {{ $class->major ?? '—' }}
                            </td>
                            <td class="px-4 py-3.5 text-slate-500 font-medium">
                                {{ $class->deleted_at?->translatedFormat('d M Y, H:i') }} ({{ $class->deleted_at?->diffForHumans() }})
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('classes.restore', $class->id) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 hover:bg-emerald-200 transition-colors shadow-2xs">
                                            ✓ Pulihkan Kelas
                                        </button>
                                    </form>
                                    <form method="POST"
                                          action="{{ route('classes.force-delete', $class->id) }}"
                                          class="inline"
                                          onsubmit="return confirm('Hapus PERMANEN kelas {{ $class->name }}? Seluruh data siswa, absensi, dan kas kelas akan hilang dan TIDAK BISA dikembalikan.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-white border border-slate-300 hover:bg-slate-100 text-slate-900 font-bold transition-colors">
                                            Hapus Permanen
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- MOBILE VIEW --}}
        <div class="md:hidden space-y-3">
            @foreach ($classes as $class)
                <div class="bg-white rounded-2xl border border-emerald-200 p-4 shadow-xs space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">{{ $class->name }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $class->major ?? 'Umum' }} &middot; Dihapus {{ $class->deleted_at?->diffForHumans() }}</p>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-emerald-100 flex items-center justify-end gap-2">
                        <form method="POST" action="{{ route('classes.restore', $class->id) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors">
                                Pulihkan
                            </button>
                        </form>
                        <form method="POST"
                              action="{{ route('classes.force-delete', $class->id) }}"
                              onsubmit="return confirm('Hapus PERMANEN kelas {{ $class->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-white border border-slate-300 text-slate-900 font-bold text-xs">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        @if($classes->hasPages())
            <div class="mt-6">{{ $classes->links() }}</div>
        @endif
    @endif

</div>
@endsection
