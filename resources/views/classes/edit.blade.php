@extends('layouts.app')
@section('title', 'Edit Kelas')
@section('content')
    @include('partials.class-nav')

    <div class="max-w-2xl">
        <div class="card-elevated">
            <form method="POST"
                  action="{{ route('classes.update', $classroom) }}"
                  class="space-y-6"
                  x-data="{ loading: false }"
                  @submit="loading = true">

                @method('PUT')
                @csrf
                @include('classes._form')

                <!-- Actions -->
                <div class="flex items-center gap-3 border-t border-slate-200 pt-6">
                    <button type="submit"
                            class="btn-primary flex-1 justify-center sm:flex-none sm:justify-start"
                            :disabled="loading"
                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
                        <span x-show="!loading" class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Simpan perubahan
                        </span>
                        <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                            <span class="spinner spinner--white"></span>
                            Menyimpan...
                        </span>
                    </button>
                    <a href="{{ route('classes.show', $classroom) }}" class="btn-secondary">
                        Batal
                    </a>
                </div>
            </form>

            <!-- Danger Zone -->
            <div class="mt-6 border-t border-rose-100 pt-6">
                <h4 class="text-sm font-semibold text-rose-700 mb-3">Zona berbahaya</h4>
                <p class="text-xs text-slate-500 mb-3">
                    Menghapus kelas akan memindahkan semua data siswa ke arsip. Data tidak bisa dipulihkan setelah dihapus permanen.
                </p>
                <form method="POST"
                      action="{{ route('classes.destroy', $classroom) }}"
                      class="inline"
                      onsubmit="return confirm('Hapus kelas {{ $classroom->name }} beserta seluruh datanya?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger btn-danger--sm">Hapus kelas ini
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
