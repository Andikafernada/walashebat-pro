@extends('layouts.app')
@section('title', isset($student) ? 'Edit Siswa' : 'Tambah Siswa')
@section('content')
    @if(isset($student))
        @include('partials.class-nav')
    @endif

    <div class="max-w-3xl pb-12">
        <div class="bg-white rounded-2xl border border-emerald-200 shadow-xs p-6">
            <form method="POST"
                  action="{{ isset($student) ? route('classes.students.update', [$classroom, $student]) : route('classes.students.store', $classroom) }}"
                  enctype="multipart/form-data"
                  class="space-y-6"
                  x-data="{
                      loading: false,
                      bagian: 'identitas',
                      periksa($form) {
                          const buruk = $form.querySelector(':invalid');

                          if (! buruk) {
                              this.loading = true;
                              return;
                          }

                          this.bagian = buruk.closest('[data-bagian]')?.dataset.bagian ?? this.bagian;
                          this.$nextTick(() => { buruk.focus(); buruk.reportValidity(); });
                      },
                  }"
                  @submit="periksa($el)">

                @if(isset($student))
                    @method('PUT')
                @endif
                @csrf
                @include('students._form')

                <!-- Actions -->
                <div class="flex items-center gap-3 border-t border-slate-100 pt-6">
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-700 transition-colors flex-1 sm:flex-none sm:justify-start"
                            :disabled="loading"
                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
                        <span x-show="!loading" class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ isset($student) ? 'Simpan Perubahan' : 'Tambah Siswa' }}
                        </span>
                        <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                            <span class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            Menyimpan...
                        </span>
                    </button>
                    <a href="{{ route('classes.students.index', $classroom) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
