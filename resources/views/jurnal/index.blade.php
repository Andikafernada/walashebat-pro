@extends('layouts.app')

@section('title', 'Jurnal Mengajar - ' . $classroom->name)

@section('content')
<div class="space-y-6 pb-12">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 font-semibold" aria-label="Breadcrumb">
                <a href="{{ route('classes.index') }}" class="hover:text-emerald-700">Daftar Kelas</a>
                <span>/</span>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-emerald-700">{{ $classroom->name }}</a>
                <span>/</span>
                <span class="text-slate-900">Jurnal Mengajar</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Jurnal Mengajar Guru (Kurikulum Merdeka)</h1>
            <p class="text-xs sm:text-sm text-slate-600">Dokumentasi materi, TP/CP, dan catatan refleksi pembelajaran kelas {{ $classroom->name }}.</p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            @if($journals->count() > 0)
                <a href="{{ route('classes.jurnal.pdf', $classroom) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-emerald-300 text-emerald-950 hover:bg-emerald-50 text-xs font-bold shadow-xs transition-colors">
                    <span>📄</span>
                    <span>Cetak Rekap PDF</span>
                </a>
            @endif

            <a href="{{ route('classes.jurnal.create', $classroom) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold shadow-md shadow-emerald-600/20 transition-all hover:scale-105">
                <span>🤖</span>
                <span>+ Buat Jurnal (AI OpenCode)</span>
            </a>
        </div>
    </div>

    @include('partials.class-nav', ['classroom' => $classroom])
    @include('partials.flash')

    {{-- Journal Entries Table --}}
    <div class="bg-white rounded-3xl border border-emerald-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-emerald-50/80 border-b border-emerald-100 text-slate-700 font-extrabold uppercase tracking-wider">
                        <th class="py-3.5 px-4 w-16 text-center">Ptm</th>
                        <th class="py-3.5 px-4 w-28">Tanggal</th>
                        <th class="py-3.5 px-4 w-36">Mata Pelajaran</th>
                        <th class="py-3.5 px-4">Topik &amp; Tujuan Pembelajaran (TP)</th>
                        <th class="py-3.5 px-4 w-40">Dimensi P5</th>
                        <th class="py-3.5 px-4 w-16 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-100 text-slate-800">
                    @forelse($journals as $j)
                        <tr class="hover:bg-emerald-50/40 transition-colors">
                            <td class="py-3.5 px-4 text-center font-bold font-mono text-emerald-900 bg-emerald-50/50">
                                #{{ $j->meeting_number }}
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-600">
                                {{ $j->session_date->translatedFormat('d M Y') }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ $j->subject }}
                            </td>
                            <td class="py-3.5 px-4 space-y-1">
                                <p class="font-black text-slate-900 text-sm">{{ $j->topic }}</p>
                                @if($j->learning_objective)
                                    <p class="text-[11px] text-slate-600 line-clamp-2 leading-relaxed whitespace-pre-line">{{ $j->learning_objective }}</p>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($j->p5_dimension)
                                    <span class="inline-block px-2 py-0.5 rounded-lg bg-teal-50 border border-teal-200 text-teal-900 font-semibold text-[10px]">
                                        {{ $j->p5_dimension }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <form method="POST" action="{{ route('classes.jurnal.destroy', [$classroom, $j]) }}" onsubmit="return confirm('Hapus jurnal pertemuan ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-400 hover:text-rose-600 transition-colors font-bold text-sm" title="Hapus Jurnal">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <p class="text-3xl">📖</p>
                                <p class="text-sm font-bold text-slate-700 mt-2">Belum ada jurnal mengajar</p>
                                <p class="text-xs text-slate-400 mt-0.5">Gunakan AI OpenCode untuk membuat administrasi jurnal Anda dalam hitungan detik.</p>
                                <a href="{{ route('classes.jurnal.create', $classroom) }}"
                                   class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition-colors">
                                    <span>✨</span>
                                    <span>Buat Jurnal Pertemuan 1 Sekarang</span>
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($journals->hasPages())
            <div class="p-4 border-t border-emerald-100">
                {{ $journals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
