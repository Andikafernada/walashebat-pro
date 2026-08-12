@extends('layouts.app')

@section('title', 'Portofolio Karakter - ' . ($class->name ?? ''))

@section('content')
<div class="space-y-6 pb-12" x-data="{ showShareModal: false }">
    <!-- Header Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('classes.index') }}" class="hover:text-indigo-600 transition-colors">Kelas</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">{{ $class->name }}</span>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">Jurnal &amp; Portofolio Karakter</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Jurnal &amp; Portofolio Karakter Siswa
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Observasi 6 Dimensi Profil Pelajar Pancasila untuk Kelas {{ $class->name }}</p>
        </div>

        <div>
            <button type="button" @click="showShareModal = true"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-purple-200 bg-purple-50 px-3.5 py-2 text-xs font-bold text-purple-700 shadow-xs hover:bg-purple-100 transition-all">
                <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                📲 Bagikan Form Refleksi Siswa
            </button>
        </div>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $class])

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- Dimensi Profil Pelajar Pancasila Grid -->
    <div>
        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">6 Dimensi Profil Pelajar Pancasila</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($dimensions as $dimension)
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm hover:shadow-md transition-all space-y-3" 
                     style="border-top: 3.5px solid {{ $dimension->color }};">
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl font-bold text-white shadow-xs" 
                                 style="background-color: {{ $dimension->color }};">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $dimension->icon }}"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-slate-900">{{ $dimension->name }}</h4>
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
                        <div class="rounded-xl bg-emerald-50 p-2 border border-emerald-100">
                            <span class="block text-base font-black text-emerald-700">{{ $dimensionStats[$dimension->id]['positive'] ?? 0 }}</span>
                            <span class="text-[10px] font-semibold text-emerald-600">Catatan Positif</span>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-2 border border-slate-100">
                            <span class="block text-base font-black text-slate-700">{{ $dimensionStats[$dimension->id]['total_records'] ?? 0 }}</span>
                            <span class="text-[10px] font-semibold text-slate-500">Total Catatan</span>
                        </div>
                        <div class="rounded-xl bg-rose-50 p-2 border border-rose-100">
                            <span class="block text-base font-black text-rose-700">{{ $dimensionStats[$dimension->id]['negative'] ?? 0 }}</span>
                            <span class="text-[10px] font-semibold text-rose-600">Catatan Evaluasi</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between rounded-xl border border-sky-100 bg-sky-50 px-3 py-2">
                        <span class="text-[10px] font-semibold text-sky-700">Refleksi dikirim siswa</span>
                        <span class="text-base font-black text-sky-700">{{ $dimensionStats[$dimension->id]['refleksi'] ?? 0 }}</span>
                    </div>

                </div>
            @endforeach
        </div>
    </div>

    <!-- Students Character List -->
    <div class="space-y-3 pt-4 border-t border-slate-200/60">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Daftar Portofolio Siswa Kelas</h3>
            <span class="text-xs font-semibold text-slate-500">{{ $students->count() }} Siswa</span>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($students as $student)
                <a href="{{ route('classes.character-portfolio.student', [$class, $student]) }}" 
                   class="group rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs hover:border-indigo-300 hover:shadow-md transition-all space-y-3 block">
                    
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white font-black text-sm shadow-md shadow-indigo-500/20">
                            {{ substr($student->name, 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-bold text-sm text-slate-900 truncate group-hover:text-indigo-600 transition-colors">{{ $student->name }}</h4>
                            <p class="text-xs text-slate-500">NIS: {{ $student->nis ?? '-' }}</p>
                        </div>
                    </div>

                    @php
                        $earnedBadges = $student->studentBadges->where('is_earned', true)->take(3);
                    @endphp
                    <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                        <span class="text-[11px] text-slate-400">Badge Karakter:</span>
                        <span class="font-bold text-indigo-600">{{ $earnedBadges->count() }} Terperoleh</span>
                    </div>

                    {{-- Yang paling ingin diketahui wali kelas setelah menyebar
                         tautan: siapa yang belum menyetor. --}}
                    @php $jumlahRefleksi = $refleksiPerSiswa[$student->id] ?? 0; @endphp
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-[11px] text-slate-400">Refleksi mandiri:</span>
                        @if ($jumlahRefleksi)
                            <span class="font-bold text-sky-700">{{ $jumlahRefleksi }} dikirim</span>
                        @else
                            <span class="font-bold text-amber-700">Belum mengisi</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Share Modal -->
    <div x-show="showShareModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4" @click.away="showShareModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Bagikan Form Refleksi Karakter Siswa</h3>
                <button type="button" @click="showShareModal = false" class="text-slate-400 hover:text-slate-600 font-bold">×</button>
            </div>

            <p class="text-xs text-slate-600">
                Kirimkan tautan di bawah ini ke WhatsApp siswa agar mereka dapat mengisi Refleksi Karakter P5 (Rating Diri &amp; Rencana Perubahan Sikap) langsung dari HP:
            </p>

            <div class="space-y-2">
                <input type="text" readonly value="{{ route('public.reflection.show', $class->tokenPublik()) }}"
                       class="h-10 w-full rounded-xl border border-purple-200 bg-purple-50/50 px-3 text-xs font-mono text-purple-900 focus:outline-none select-all">
                <button type="button" onclick="navigator.clipboard.writeText('{{ route('public.reflection.show', $class->tokenPublik()) }}'); alert('Tautan Refleksi disalin!');"
                        class="h-10 w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs transition-all flex items-center justify-center gap-1.5">
                    📋 Salin Tautan Ke Clipboard
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
