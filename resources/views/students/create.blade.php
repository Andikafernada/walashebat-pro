@extends('layouts.app')
@section('title', isset($student) ? 'Edit Siswa' : 'Tambah Siswa')
@section('content')
    @if(isset($student))
        @include('partials.class-nav')
    @endif

    <div class="max-w-3xl">
        <div class="card-elevated">
            <form method="POST"
                  action="{{ isset($student) ? route('classes.students.update', [$classroom, $student]) : route('classes.students.store', $classroom) }}"
                  enctype="multipart/form-data"
                  class="space-y-6"
                  x-data="{ loading: false }"
                  @submit="loading = true">

                @if(isset($student))
                    @method('PUT')
                @endif
                @csrf
                @include('students._form')

                <!-- Actions -->
                <div class="flex items-center gap-3 border-t border-slate-100 pt-6">
                    <button type="submit"
                            class="btn-primary flex-1 justify-center sm:flex-none sm:justify-start"
                            :disabled="loading"
                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
                        <span x-show="!loading" class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ isset($student) ? 'Simpan perubahan' : 'Tambah siswa' }}
                        </span>
                        <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                            <span class="spinner spinner--white"></span>
                            Menyimpan...
                        </span>
                    </button>
                    <a href="{{ route('classes.students.index', $classroom) }}" class="btn-secondary">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
