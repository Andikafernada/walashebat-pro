@extends('layouts.app')

@section('title', 'Struktur Organisasi - ' . $classroom->name)

@section('content')
@php
    $students = $classroom->students()->orderBy('name')->get();
    $availableRoles = $roles ?? config('walikelas.student_roles');
@endphp

<div class="space-y-6 pb-12">
    <!-- Header Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('classes.index') }}" class="hover:text-indigo-600 transition-colors">Kelas</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('classes.show', $classroom) }}" class="hover:text-indigo-600 transition-colors">{{ $classroom->name }}</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">Struktur Organisasi</span>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                Struktur Organisasi Kelas {{ $classroom->name }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Penunjukan jabatan pengurus kelas, Ketua Kelas, Bendahara, dan Seksi Absensi penanggung jawab WhatsApp.</p>
        </div>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $classroom])

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- Main Two-Column Layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <!-- LEFT COLUMN: Organization Structure List (2/3 width) -->
        <div class="space-y-4 lg:col-span-2">
            <div class="card space-y-4">

                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Pengurus Kelas Aktif</h3>
                        <p class="text-xs text-slate-500">Daftar siswa yang memegang jabatan pengurus di kelas ini</p>
                    </div>
                    <span class="text-xs font-semibold text-slate-400">{{ $structures->count() }} Jabatan</span>
                </div>

                @if ($structures->isNotEmpty())
                    <div class="divide-y divide-slate-200">
                        @foreach ($structures as $st)
                            <div class="flex items-center justify-between gap-4 py-3 hover:bg-slate-50 px-2 rounded transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded text-indigo-700 font-semibold text-xs border border-indigo-200/60">
                                        {{ substr($st->roleLabel(), 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-semibold text-sm text-slate-900">{{ $st->roleLabel() }}</h4>
                                            @if($st->role === 'seksi_absensi')
                                                <span class="inline-flex items-center gap-1 rounded-sm bg-indigo-50 px-2.5 py-0.5 text-[10px] font-semibold text-indigo-700 border border-indigo-200">
 Penerima Magic Link WA
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs font-semibold text-indigo-600 mt-0.5">
                                            {{ $st->student->name ?? 'Belum Ditunjuk' }}
                                        </p>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('classes.organization.destroy', [$classroom, $st]) }}"
                                      onsubmit="return confirm('Hapus penunjukan {{ $st->roleLabel() }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="h-8 rounded-lg border border-rose-200 bg-rose-50 px-2.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 transition-colors flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Copot Jabatan
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="my-10 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-indigo-50 text-indigo-500 mb-3">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-800">Belum Ada Pengurus Kelas</p>
                        <p class="mt-1 text-xs text-slate-500">Tunjuk siswa untuk mengemban posisi pengurus kelas di formulir sebelah kanan.</p>
                    </div>
                @endif

            </div>
        </div>

        <!-- RIGHT COLUMN: Add Form (1/3 width) -->
        <div class="space-y-4">
            <div class="card space-y-4">

                <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
                    <div class="stat-icon">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Tunjuk Pengurus Kelas</h3>
                        <p class="text-xs text-slate-500">Pilih Siswa &amp; Tetapkan Jabatan</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('classes.organization.store', $classroom) }}" class="space-y-4" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <div>
                        <label for="role" class="form-label">Jabatan Organisasi</label>
                        <select id="role" name="role" required class="form-input form-input--sm">
                            @foreach ($availableRoles as $roleKey => $roleName)
                                <option value="{{ $roleKey }}">{{ $roleName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="student_id" class="form-label">Siswa Terpilih</label>
                        <select id="student_id" name="student_id" required class="form-input form-input--sm">
                            {{-- Pilihan kosong: tanpa ini siswa pertama pada daftar
                                 tersorot otomatis, dan jabatan bisa terpasang pada
                                 siswa yang tidak pernah dipilih. --}}
                            <option value="">-- Pilih Siswa --</option>
                            @foreach ($students as $st)
                                <option value="{{ $st->id }}" @selected(old('student_id') == $st->id)>{{ $st->name }} (NIS: {{ $st->nis ?: '-' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" :disabled="loading"
                            class="h-10 w-full rounded bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition-colors flex items-center justify-center gap-2">
                        <template x-if="!loading">
                            <span class="flex items-center gap-1.5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Simpan Jabatan
                            </span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center gap-1.5">
                                <span class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                Menyimpan...
                            </span>
                        </template>
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection
