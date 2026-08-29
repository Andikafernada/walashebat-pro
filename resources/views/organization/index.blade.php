@extends('layouts.app')

@section('title', 'Struktur Organisasi - ' . $classroom->name)

@section('content')
@php
    $students = $classroom->students()->orderBy('name')->get();
    $availableRoles = $roles ?? config('walikelas.student_roles');
@endphp

<div class="space-y-6 pb-12">
    {{-- HEADER BAR --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-slate-600">{{ $classroom->name }}</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Struktur Organisasi</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                Struktur Organisasi Kelas {{ $classroom->name }}
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-600 font-medium">Penunjukan pengurus kelas: Ketua Kelas, Wakil, Bendahara, dan Seksi Absensi penanggung jawab WhatsApp.</p>
        </div>
    </div>

    {{-- NAVIGASI KELAS --}}
    @include('partials.class-nav', ['classroom' => $classroom])

    @include('partials.flash')

    {{-- DUAL-COLUMN LAYOUT: DAFTAR PENGURUS (2/3) + FORM PENUNJUKAN (1/3) --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- LEFT COLUMN: DAFTAR PENGURUS --}}
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-3xl border border-emerald-200 bg-white p-5 sm:p-6 shadow-xs space-y-4">

                <div class="flex items-center justify-between border-b border-emerald-100 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Pengurus Kelas Aktif</h3>
                        <p class="text-xs text-slate-500 font-medium">Daftar siswa yang memegang amanah jabatan di kelas</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-950 border border-emerald-200">
                        {{ $structures->count() }} Jabatan
                    </span>
                </div>

                @if ($structures->isNotEmpty())
                    <div class="divide-y divide-emerald-100/70">
                        @foreach ($structures as $st)
                            <div class="flex items-center justify-between gap-4 py-3.5 hover:bg-emerald-50/40 px-3 rounded-2xl transition-colors">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-950 font-extrabold text-sm flex items-center justify-center shrink-0 border border-emerald-200">
                                        {{ Str::upper(Str::substr($st->roleLabel(), 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="font-bold text-sm text-slate-900">{{ $st->roleLabel() }}</h4>
                                            @if($st->role === 'seksi_absensi')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-950 border border-emerald-300 text-[10px] font-extrabold">
                                                    📲 Petugas Absensi WA
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs font-bold text-emerald-800 truncate mt-0.5">
                                            {{ $st->student->name ?? 'Belum Ditunjuk' }}
                                        </p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('classes.organization.destroy', [$classroom, $st]) }}"
                                      onsubmit="return confirm('Hapus penunjukan {{ $st->roleLabel() }}?')" class="shrink-0">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-800 text-xs font-bold transition-colors">
                                        Copot Jabatan
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-10 text-center space-y-2">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-2xl mx-auto border border-emerald-200">🏛️</div>
                        <p class="text-sm font-bold text-slate-900">Belum Ada Pengurus Kelas Ditunjuk</p>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto font-medium">Tunjuk siswa untuk mengemban posisi pengurus kelas pada formulir di sebelah kanan.</p>
                    </div>
                @endif

            </div>
        </div>

        {{-- RIGHT COLUMN: FORM PENUNJUKAN JABATAN --}}
        <div class="space-y-4">
            <div class="rounded-3xl border border-emerald-200 bg-white p-5 sm:p-6 shadow-xs space-y-4">

                <div class="border-b border-emerald-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Tunjuk Pengurus Baru</h3>
                    <p class="text-xs text-slate-500 font-medium">Pilih siswa &amp; tetapkan jabatan organisasi</p>
                </div>

                <form method="POST" action="{{ route('classes.organization.store', $classroom) }}" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <div>
                        <label for="role" class="block text-xs font-bold text-slate-900 mb-1.5">Jabatan Organisasi</label>
                        <select id="role" name="role" required
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-emerald-200 bg-white text-slate-900 font-bold focus:outline-none focus:border-emerald-600">
                            @foreach ($availableRoles as $roleKey => $roleName)
                                <option value="{{ $roleKey }}">{{ $roleName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="student_id" class="block text-xs font-bold text-slate-900 mb-1.5">Siswa Terpilih</label>
                        <select id="student_id" name="student_id" required
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-emerald-200 bg-white text-slate-900 font-bold focus:outline-none focus:border-emerald-600">
                            <option value="">-- Pilih Siswa --</option>
                            @foreach ($students as $st)
                                <option value="{{ $st->id }}" @selected(old('student_id') == $st->id)>{{ $st->name }} (NIS: {{ $st->nis ?: '-' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" :disabled="loading"
                            class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs sm:text-sm shadow-sm shadow-emerald-200 transition-all flex items-center justify-center gap-1.5">
                        <template x-if="!loading">
                            <span>+ Simpan Jabatan Siswa</span>
                        </template>
                        <template x-if="loading">
                            <span>Menyimpan Jabatan...</span>
                        </template>
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection
