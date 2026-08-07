@extends('layouts.app')

@section('title', 'Input Absensi Manual - ' . $class->name)

@section('content')
<div class="space-y-6 pb-12">
    <!-- Header Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('classes.index') }}" class="hover:text-indigo-600 transition-colors">Kelas</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('classes.attendance.index', $class) }}" class="hover:text-indigo-600 transition-colors">Absensi</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">Input Absensi Manual (Susulan)</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Input Absensi Manual / Susulan
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Isi data presensi siswa secara langsung tanpa membuat magic link (untuk bulan-bulan kemarin).</p>
        </div>

        <a href="{{ route('classes.attendance.index', $class) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50">
            &larr; Kembali ke Daftar Absensi
        </a>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $class])

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- Form Card -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-6">
        <form method="POST" action="{{ route('classes.attendance.manual.store', $class) }}" class="space-y-6">
            @csrf

            <!-- Date & Title Header -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 bg-slate-50 p-4 rounded-xl border border-slate-100">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Tanggal Absensi *</label>
                    <input type="date" name="session_date" required value="{{ date('Y-m-d') }}" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                    <p class="text-[10px] text-slate-400 mt-1">Pilih tanggal lampau (misal: bulan Juli, Juni, dll).</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Judul Sesi (opsional)</label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="cth: Absensi Susulan Juli" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                </div>

                {{--
                    Mapel & materi hanya muncul pada kelas ajar.

                    Keduanya bekal jurnal mengajar. Materi disediakan sejak
                    sekarang meski laporan jurnalnya menyusul, karena jurnal
                    tidak bisa diisi mundur — tidak ada yang mengingat materi
                    tanggal 12 Agustus pada bulan berikutnya.
                --}}
                @if ($class->kelasAjar())
                    @php $mapelPilihan = $class->mapelDiampu(); @endphp
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Mata Pelajaran</label>
                        @if (count($mapelPilihan) > 1)
                            <select name="mapel" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                                @foreach ($mapelPilihan as $m)
                                    <option value="{{ $m }}" @selected(old('mapel') === $m)>{{ $m }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" name="mapel" value="{{ old('mapel', $mapelPilihan[0] ?? '') }}"
                                   placeholder="cth: Matematika"
                                   class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                        @endif
                        <p class="text-[10px] text-slate-400 mt-1">Atur daftar mapel di halaman Ubah Kelas.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Materi Hari Ini (opsional)</label>
                        <input type="text" name="materi" value="{{ old('materi') }}"
                               placeholder="cth: Sistem bilangan biner"
                               class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-800 focus:border-indigo-500 focus:outline-none">
                        <p class="text-[10px] text-slate-400 mt-1">Dipakai untuk jurnal mengajar nanti.</p>
                    </div>
                @endif
            </div>

            <!-- Student Roster Table -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Roster Siswa Kelas ({{ $students->count() }} Siswa)</h3>
                    <span class="text-[11px] text-slate-400">Default: Hadir 🟢</span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                            <tr>
                                <th scope="col" class="py-3 px-3 w-12 text-center">No</th>
                                <th scope="col" class="py-3 px-3">Nama Siswa</th>
                                <th scope="col" class="py-3 px-3 text-center">Status Kehadiran</th>
                                <th scope="col" class="py-3 px-3">Catatan / Alasan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($students as $idx => $st)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-3 px-3 text-center font-bold text-slate-400">{{ $idx + 1 }}</td>
                                    <td class="py-3 px-3 font-bold text-slate-900">
                                        {{ $st->name }}
                                        <span class="block text-[10px] text-slate-400 font-normal">NIS: {{ $st->nis ?: '-' }}</span>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <div class="inline-flex items-center gap-1.5 bg-slate-50 p-1.5 rounded-xl border border-slate-200">
                                            <label class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold cursor-pointer hover:bg-emerald-100 transition-colors">
                                                <input type="radio" name="attendance[{{ $st->id }}]" value="hadir" checked class="text-emerald-600 focus:ring-emerald-500">
                                                <span class="text-emerald-800">Hadir</span>
                                            </label>
                                            <label class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold cursor-pointer hover:bg-amber-100 transition-colors">
                                                <input type="radio" name="attendance[{{ $st->id }}]" value="terlambat" class="text-amber-600 focus:ring-amber-500">
                                                <span class="text-amber-800">Terlambat</span>
                                            </label>
                                            <label class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold cursor-pointer hover:bg-blue-100 transition-colors">
                                                <input type="radio" name="attendance[{{ $st->id }}]" value="sakit" class="text-blue-600 focus:ring-blue-500">
                                                <span class="text-blue-800">Sakit</span>
                                            </label>
                                            <label class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold cursor-pointer hover:bg-purple-100 transition-colors">
                                                <input type="radio" name="attendance[{{ $st->id }}]" value="izin" class="text-purple-600 focus:ring-purple-500">
                                                <span class="text-purple-800">Izin</span>
                                            </label>
                                            <label class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold cursor-pointer hover:bg-rose-100 transition-colors">
                                                <input type="radio" name="attendance[{{ $st->id }}]" value="alfa" class="text-rose-600 focus:ring-rose-500">
                                                <span class="text-rose-800">Alfa</span>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="text" name="notes[{{ $st->id }}]" placeholder="cth: Surat dokter..." class="h-8 w-full rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-xs text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('classes.attendance.index', $class) }}" class="h-10 rounded-xl border border-slate-200 bg-slate-100 px-4 text-xs font-bold text-slate-700 flex items-center">
                    Batal
                </a>
                <button type="submit" class="h-10 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-6 text-xs font-bold transition-all shadow-md shadow-emerald-500/20 flex items-center gap-2">
                    ✓ Simpan Absensi Manual Tanggal Ini
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
