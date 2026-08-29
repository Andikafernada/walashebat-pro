@extends('layouts.app')
@section('title', isset($classroom) ? 'Edit Kelas — ' . $classroom->name : 'Buat Kelas Baru — ' . config('app.name'))
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
                <span class="text-slate-500">{{ isset($classroom) ? 'Edit' : 'Buat Baru' }}</span>
            </nav>
            <h1 class="mt-1 text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                {{ isset($classroom) ? 'Pengaturan Kelas ' . $classroom->name : 'Buat Kelas Baru' }}
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-600 font-medium">
                {{ isset($classroom) ? 'Perbarui identitas, jadwal absensi otomatis, dan kontak kelas.' : 'Tambahkan kelas perwalian atau kelas mapel baru untuk mulai mengelola siswa.' }}
            </p>
        </div>
        <a href="{{ route('classes.index') }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all">
            ← Kembali ke Daftar Kelas
        </a>
    </div>

    @include('partials.flash')

    <div class="max-w-2xl">
        <div class="rounded-3xl border border-emerald-200 bg-white p-6 sm:p-8 shadow-xs">
            <form method="POST"
                  action="{{ isset($classroom) ? route('classes.update', $classroom) : route('classes.store') }}"
                  class="space-y-6"
                  x-data="{ loading: false }"
                  @submit="loading = true">

                @if(isset($classroom))
                    @method('PUT')
                @endif
                @csrf

                @include('classes._form')

                <!-- Actions -->
                <div class="flex items-center gap-3 border-t border-emerald-100 pt-6">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm shadow-sm shadow-emerald-200 transition-all"
                            :disabled="loading"
                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
                        <span x-show="!loading" class="inline-flex items-center gap-1.5">
                            <span>✓</span>
                            <span>{{ isset($classroom) ? 'Simpan Perubahan' : 'Simpan Kelas Baru' }}</span>
                        </span>
                        <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                            <span class="spinner"></span>
                            <span>Menyimpan...</span>
                        </span>
                    </button>
                    <a href="{{ route('classes.index') }}"
                       class="px-4 py-2.5 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 font-bold text-xs sm:text-sm transition-colors">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
