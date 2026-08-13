@extends('layouts.app')
@section('title', isset($classroom) ? 'Edit Kelas' : 'Buat Kelas Baru')
@section('content')

    <!-- Page Header -->
    <div class="page-header mb-5">
        <div>
            <p class="text-sm text-slate-500">
                {{ isset($classroom) ? 'Perbarui informasi kelas' : 'Tambah kelas baru untuk mulai mengelola administrasi' }}
            </p>
        </div>
    </div>

    <div class="max-w-2xl">
        <div class="card-elevated">
            <form method="POST"
                  action="{{ isset($classroom) ? route('classes.update', $classroom) : route('classes.store') }}"
                  class="space-y-6"
                  x-data="{ loading: false }"
                  @submit="loading = true">

                @if(isset($classroom))
                    @method('PUT')
                @endif
                @csrf

                {{--
                    Formulirnya dibagi dengan halaman Ubah Kelas.

                    Sebelumnya kedua halaman menyalin kolom yang sama, dan
                    salinannya sudah menyimpang: kolom "Jenis kelas" beserta
                    daftar mapel ditambahkan di _form dan otomatis muncul di
                    halaman Ubah, tetapi halaman Buat tidak berubah sama sekali
                    — fiturnya ada di kode namun tidak bisa dijangkau dari
                    tempat orang pertama kali membuat kelas.
                --}}
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
                            {{ isset($classroom) ? 'Simpan perubahan' : 'Simpan kelas' }}
                        </span>
                        <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                            <span class="spinner spinner--white"></span>
                            Menyimpan...
                        </span>
                    </button>
                    <a href="{{ route('classes.index') }}" class="btn-secondary">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
