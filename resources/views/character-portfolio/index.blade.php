@extends('layouts.app')

@section('title', 'Portofolio Karakter - ' . ($class->name ?? ''))

@section('content')
<div class="space-y-6 pb-12" x-data="{ showShareModal: false }">
    <!-- Header Bar -->
    <div class="page-header">
        <div>
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">{{ $class->name }}</span>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">Jurnal &amp; Portofolio Karakter</span>
            </nav>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">
                Jurnal &amp; Portofolio Karakter Siswa
            </h1>
            <p class="mt-1 text-sm text-slate-500">Observasi 6 Dimensi Profil Pelajar Pancasila untuk Kelas {{ $class->name }}</p>
        </div>

        <div>
            <button type="button" @click="showShareModal = true" class="btn-primary btn-primary--sm">
                Bagikan Form Refleksi Siswa
            </button>
        </div>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $class])

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- Dimensi Profil Pelajar Pancasila Grid -->
    <div>
        <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-3">6 Dimensi Profil Pelajar Pancasila</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($dimensions as $dimension)
                <div class="card transition-colors space-y-3"
                     style="border-top: 3.5px solid {{ $dimension->color }};">

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="stat-icon"
                                 style="background-color: {{ $dimension->color }};">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $dimension->icon }}"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-sm text-slate-900">{{ $dimension->name }}</h4>
                                <span class="text-[10px] font-mono text-slate-400 uppercase tracking-wider">{{ $dimension->code }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Angka pengamatan wali kelas dan angka refleksi siswa
                         dipisah bergaris, karena sumbernya memang dua benda
                         berbeda: yang atas ditulis guru tentang anak, yang bawah
                         ditulis anaknya sendiri. Dulu hanya yang atas dihitung,
                         sehingga halaman ini melaporkan 0 kepada wali kelas yang
                         siswanya sudah mengisi. --}}
                    <div class="grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="rounded bg-emerald-50 p-2 border border-emerald-100">
                            <span class="block text-base font-semibold text-emerald-700">{{ $dimensionStats[$dimension->id]['positive'] ?? 0 }}</span>
                            <span class="text-[10px] font-semibold text-emerald-600">Catatan Positif</span>
                        </div>
                        <div class="rounded bg-slate-50 p-2 border border-slate-200">
                            <span class="block text-base font-semibold text-slate-700">{{ $dimensionStats[$dimension->id]['total_records'] ?? 0 }}</span>
                            <span class="text-[10px] font-semibold text-slate-500">Total Catatan</span>
                        </div>
                        <div class="rounded bg-rose-50 p-2 border border-rose-100">
                            <span class="block text-base font-semibold text-rose-700">{{ $dimensionStats[$dimension->id]['negative'] ?? 0 }}</span>
                            <span class="text-[10px] font-semibold text-rose-600">Catatan Evaluasi</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between rounded border border-sky-100 bg-sky-50 px-3 py-2">
                        <span class="text-[10px] font-semibold text-sky-700">Refleksi dikirim siswa</span>
                        <span class="text-base font-semibold text-sky-700">{{ $dimensionStats[$dimension->id]['refleksi'] ?? 0 }}</span>
                    </div>

                </div>
            @endforeach
        </div>
    </div>

    <!-- Students Character List -->
    <div class="space-y-3 pt-4 border-t border-slate-200/60">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wider">Daftar Portofolio Siswa Kelas</h3>
            <span class="text-xs font-semibold text-slate-500">{{ $students->count() }} Siswa</span>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($students as $student)
                <a href="{{ route('classes.character-portfolio.student', [$class, $student]) }}"
                   class="group card hover:border-indigo-300 transition-colors space-y-3 block">

                    <div class="flex items-center gap-3">
                        <div class="stat-icon">
                            {{ substr($student->name, 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-semibold text-sm text-slate-900 truncate group-hover:text-indigo-600 transition-colors">{{ $student->name }}</h4>
                            <p class="text-xs text-slate-500">NIS: {{ $student->nis ?? '-' }}</p>
                        </div>
                    </div>

                    @php
                        $earnedBadges = $student->studentBadges->where('is_earned', true)->take(3);
                    @endphp
                    <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-200">
                        <span class="text-[11px] text-slate-400">Badge Karakter:</span>
                        <span class="font-semibold text-indigo-600">{{ $earnedBadges->count() }} Terperoleh</span>
                    </div>

                    {{-- Yang paling ingin diketahui wali kelas setelah menyebar
                         tautan: siapa yang belum menyetor. --}}
                    @php $jumlahRefleksi = $refleksiPerSiswa[$student->id] ?? 0; @endphp
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-[11px] text-slate-400">Refleksi mandiri:</span>
                        @if ($jumlahRefleksi)
                            <span class="font-semibold text-sky-700">{{ $jumlahRefleksi }} dikirim</span>
                        @else
                            <span class="font-semibold text-amber-700">Belum mengisi</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Share Modal -->
    <div x-show="showShareModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-lg border border-slate-300 bg-white shadow-xl" @click.away="showShareModal = false">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h2 class="text-sm font-semibold tracking-tight text-slate-900">Bagikan Form Refleksi Karakter Siswa</h2>
                <button type="button" @click="showShareModal = false" class="btn-icon" aria-label="Tutup">&times;</button>
            </div>

            <div class="space-y-3 p-4">
                <p class="text-xs text-slate-600">
                    Kirimkan tautan di bawah ini ke WhatsApp siswa agar mereka dapat mengisi Refleksi Karakter P5 (Rating Diri &amp; Rencana Perubahan Sikap) langsung dari HP:
                </p>

                <input type="text" readonly value="{{ route('public.reflection.show', $class->tokenPublik()) }}"
                       class="form-input select-all font-mono text-xs">

                <button type="button" onclick="navigator.clipboard.writeText('{{ route('public.reflection.show', $class->tokenPublik()) }}'); alert('Tautan Refleksi disalin!');"
                        class="btn-primary w-full">Salin Tautan Ke Clipboard
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
