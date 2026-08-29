@extends('layouts.app')
@section('title', 'Arsip Siswa — ' . $classroom->name)
@section('content')

@include('partials.class-nav', ['classroom' => $classroom])

<div class="space-y-6 pb-12">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-slate-600">{{ $classroom->name }}</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.students.index', $classroom) }}" class="hover:text-slate-600">Siswa</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Arsip</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">Arsip Siswa Terhapus</h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-600 font-medium">{{ $students->total() }} siswa berada dalam arsip sementara</p>
        </div>
        <a href="{{ route('classes.students.index', $classroom) }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all">
            ← Kembali ke Daftar Siswa
        </a>
    </div>

    @include('partials.flash')

    @if ($students->isEmpty())
        <div class="rounded-3xl border border-emerald-200 bg-white p-12 text-center space-y-3 shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl mx-auto border border-emerald-200">👥</div>
            <p class="text-base font-bold text-slate-900">Arsip Siswa Kosong</p>
            <p class="text-xs text-slate-500 max-w-sm mx-auto font-medium">
                Siswa yang dihapus akan masuk ke arsip sementara di sini dan dapat dipulihkan kapan saja.
            </p>
        </div>
    @else
        {{-- DESKTOP VIEW --}}
        <div class="hidden md:block overflow-x-auto rounded-3xl border border-emerald-200 bg-white shadow-xs">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-emerald-50/70 border-b border-emerald-100 text-emerald-950 font-bold">
                        <th class="px-4 py-3 font-extrabold">NIS</th>
                        <th class="px-4 py-3 font-extrabold">Nama Lengkap Siswa</th>
                        <th class="px-4 py-3 font-extrabold">Waktu Dihapus</th>
                        <th class="px-4 py-3 text-right font-extrabold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-100/60">
                    @foreach ($students as $s)
                        <tr class="hover:bg-emerald-50/40 transition-colors">
                            <td class="px-4 py-3.5 font-mono text-xs font-bold text-slate-700">
                                {{ $s->nis ?? '—' }}
                            </td>
                            <td class="px-4 py-3.5 font-bold text-slate-900">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-950 font-extrabold text-xs flex items-center justify-center">
                                        {{ Str::upper(Str::substr($s->name, 0, 1)) }}
                                    </div>
                                    <span>{{ $s->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-slate-500 font-medium">
                                {{ $s->deleted_at?->translatedFormat('d M Y, H:i') }} ({{ $s->deleted_at?->diffForHumans() }})
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <form method="POST" action="{{ route('classes.students.restore', [$classroom, $s->id]) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 hover:bg-emerald-200 transition-colors shadow-2xs">
                                        ✓ Pulihkan Siswa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- MOBILE VIEW --}}
        <div class="md:hidden space-y-3">
            @foreach ($students as $s)
                <div class="bg-white rounded-2xl border border-emerald-200 p-4 shadow-xs flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900 truncate">{{ $s->name }}</h3>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">NIS: {{ $s->nis ?: '—' }} &middot; {{ $s->deleted_at?->diffForHumans() }}</p>
                    </div>
                    <form method="POST" action="{{ route('classes.students.restore', [$classroom, $s->id]) }}" class="shrink-0">
                        @csrf @method('PATCH')
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-colors">
                            Pulihkan
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        @if($students->hasPages())
            <div class="mt-6">{{ $students->links() }}</div>
        @endif
    @endif

</div>
@endsection
