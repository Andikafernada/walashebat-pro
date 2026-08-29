@extends('layouts.app')

@section('title', 'Ringkasan Kelas - ' . $classroom->name)

@section('content')
@php
    $pakaiPoin = ! $classroom->kelasAjar();
    $totalStudents = $totalStudents ?? $classroom->students()->count();
    $activeStudentsCount = $classroom->students()->where('is_active', true)->count();
    $totalSessionsCount = $classroom->attendanceSessions()->count();
    $overallAttendance = method_exists($classroom, 'persentaseKehadiran') ? ($classroom->persentaseKehadiran() ?? 100) : 100;

    $atRiskStudents = $classroom->students()
        ->where('is_active', true)
        ->get()
        ->map(function ($s) use ($classroom, $pakaiPoin) {
            $absen = \App\Models\Attendance::where('student_id', $s->id)
                ->whereIn('status', ['alfa', 'izin', 'sakit'])
                ->whereHas('session', fn ($q) => $q
                    ->where('class_id', $classroom->id)
                    ->where('session_date', '>=', now()->subDays(30)))
                ->count();

            $poin = $s->discipline_points ?? 100;

            $alasan = [];
            if ($absen >= 3) {
                $alasan[] = $absen.'x tidak hadir / 30 hari';
            }
            if ($pakaiPoin && $poin <= 70) {
                $alasan[] = 'poin kedisiplinan '.$poin;
            }

            $s->ews_alasan = implode(' · ', $alasan);

            return $s;
        })
        ->filter(fn ($s) => $s->ews_alasan !== '');
@endphp

<div class="space-y-5 pb-12">

    {{-- HEADER HALAMAN --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="min-w-0">
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600 transition-colors">Kelas</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500 font-medium">{{ $classroom->name }}</span>
            </nav>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">Ringkasan Kelas {{ $classroom->name }}</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $classroom->kelasAjar() ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
                    {{ $classroom->kelasAjar() ? 'Guru Mapel' : 'Wali Kelas' }}
                </span>
            </div>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500">
                @if ($classroom->kelasAjar())
                    Guru Mapel: <span class="font-bold text-slate-900">{{ $classroom->owner->name ?? 'belum ditentukan' }}</span>
                    &middot; Mapel: <span class="font-semibold text-emerald-800">{{ implode(' · ', $classroom->mapelDiampu() ?: ['Mapel']) }}</span>
                @else
                    Wali Kelas: <span class="font-bold text-slate-900">{{ $classroom->owner->name ?? 'belum ditentukan' }}</span>
                    &middot; Kelas perwalian dengan akses seluruh administrasi kelas.
                @endif
                &middot; TA {{ $classroom->academic_year ?? '2026/2027' }}
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @if(! $classroom->kelasAjar())
                <a href="{{ route('classes.reports.full', $classroom) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all">
                    📊 Laporan Administrasi
                </a>
            @else
                <a href="{{ route('classes.nilai.index', $classroom) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all">
                    📝 Nilai Mapel
                </a>
            @endif
            <a href="{{ route('classes.attendance.index', $classroom) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm shadow-emerald-300 transition-all">
                Kelola Absensi
            </a>
            <a href="{{ route('classes.edit', $classroom) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-emerald-200 hover:bg-emerald-50 text-slate-800 text-xs font-bold shadow-xs transition-all">
                Pengaturan
            </a>
        </div>
    </div>

    {{-- NAVIGASI KELAS (DAILY COMMAND HUB) --}}
    @include('partials.class-nav', ['classroom' => $classroom])

    @include('partials.flash')

    {{-- ⚡ DAILY COMMAND CENTER (Bright Soft Emerald Gradient) --}}
    <div class="bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-700 rounded-3xl p-4 sm:p-5 text-white shadow-lg shadow-emerald-600/15 relative overflow-hidden">
        {{-- Decorative accent --}}
        <div class="absolute -top-12 -right-12 w-48 h-48 bg-white/20 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-white/25 text-white text-sm">⚡</span>
                    <h2 class="text-sm font-bold tracking-tight text-white uppercase text-[11px] sm:text-xs">Pusat Komando Harian</h2>
                </div>
                <span class="text-[11px] text-emerald-100 font-bold">{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                {{-- Aksi 1: Absensi --}}
                @if(isset($todaySession) && $todaySession && $todaySession->status === 'submitted')
                    <a href="{{ route('classes.attendance.index', $classroom) }}"
                       class="flex items-center gap-3 p-3 rounded-2xl bg-white/15 hover:bg-white/25 border border-white/30 backdrop-blur-md transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-white/20 text-white flex items-center justify-center text-xl shrink-0">✅</div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-white group-hover:text-emerald-100 transition-colors">Absensi Selesai</p>
                            <p class="text-[10px] text-emerald-100 truncate font-semibold">{{ $todayAttendance['hadir'] ?? 0 }} hadir ({{ $todayAttendance['percentage'] ?? 0 }}%)</p>
                        </div>
                    </a>
                @elseif(isset($todaySession) && $todaySession)
                    <a href="{{ route('classes.attendance.show', [$classroom, $todaySession]) }}"
                       class="flex items-center gap-3 p-3 rounded-2xl bg-white/15 hover:bg-white/25 border border-white/30 backdrop-blur-md transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-white/20 text-white flex items-center justify-center text-xl shrink-0">⏳</div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-white group-hover:text-emerald-100 transition-colors">Sesi Sedang Berjalan</p>
                            <p class="text-[10px] text-emerald-100 truncate">Lanjutkan absensi &rarr;</p>
                        </div>
                    </a>
                @else
                    <a href="{{ route('classes.attendance.index', $classroom) }}"
                       class="flex items-center gap-3 p-3 rounded-2xl bg-white text-emerald-950 hover:bg-emerald-50 border border-white/50 backdrop-blur-md transition-all group shadow-md">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-950 flex items-center justify-center text-xl shrink-0">📋</div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-extrabold text-slate-900">Mulai Absensi Hari Ini</p>
                            <p class="text-[10px] text-emerald-800 truncate font-semibold">Buat sesi presensi &rarr;</p>
                        </div>
                    </a>
                @endif

                {{-- Aksi 2: Jurnal Mengajar --}}
                <a href="{{ route('classes.jurnal.index', $classroom) }}"
                    class="flex items-center gap-3 p-3 rounded-2xl bg-white/15 hover:bg-white/25 border border-white/20 backdrop-blur-md transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-white/20 text-white flex items-center justify-center text-xl shrink-0">📖</div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-white group-hover:text-emerald-100 transition-colors">Jurnal Mengajar</p>
                        <p class="text-[10px] text-emerald-100 truncate">Catat materi pembelajaran &rarr;</p>
                    </div>
                </a>

                {{-- Aksi 3: Catat Disiplin / Penilaian --}}
                @if($pakaiPoin)
                    <a href="{{ route('classes.violations.index', $classroom) }}"
                       class="flex items-center gap-3 p-3 rounded-2xl bg-white/15 hover:bg-white/25 border border-white/20 backdrop-blur-md transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-white/20 text-white flex items-center justify-center text-xl shrink-0">⚠️</div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-white group-hover:text-emerald-100 transition-colors">Buku Pelanggaran</p>
                            <p class="text-[10px] text-emerald-100 truncate">Catat insiden / sanksi &rarr;</p>
                        </div>
                    </a>
                @else
                    <a href="{{ route('classes.nilai.index', $classroom) }}"
                       class="flex items-center gap-3 p-3 rounded-2xl bg-white/15 hover:bg-white/25 border border-white/20 backdrop-blur-md transition-all group">
                        <div class="w-10 h-10 rounded-xl bg-white/20 text-white flex items-center justify-center text-xl shrink-0">📝</div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-white group-hover:text-emerald-100 transition-colors">Penilaian Siswa</p>
                            <p class="text-[10px] text-emerald-100 truncate">Input nilai harian &rarr;</p>
                        </div>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- STATISTIK KELAS --}}
    <dl class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs space-y-1">
            <dt class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Total Siswa</dt>
            <dd class="text-2xl font-extrabold text-slate-900">{{ $totalStudents }}</dd>
            <p class="text-[11px] text-slate-600 font-semibold truncate">{{ $activeStudentsCount }} siswa aktif</p>
        </div>

        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs space-y-1">
            <dt class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Kehadiran 30 Hari</dt>
            <dd class="text-2xl font-extrabold text-emerald-700">{{ $attendanceStats['overall_percentage'] ?? $overallAttendance }}%</dd>
            <p class="text-[11px] text-slate-600 font-semibold truncate">{{ $attendanceStats['total_sessions'] ?? $totalSessionsCount }} sesi terlaksana</p>
        </div>

        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs space-y-1">
            <dt class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Presensi Hari Ini</dt>
            <dd class="text-2xl font-extrabold text-slate-900">
                @if(isset($todayAttendance['percentage']) && $todayAttendance['percentage'] !== null)
                    {{ $todayAttendance['percentage'] }}%
                @else
                    —
                @endif
            </dd>
            <p class="text-[11px] text-slate-600 font-semibold truncate">
                @if(isset($todayAttendance['hadir']))
                    {{ $todayAttendance['hadir'] }} Hadir &middot; {{ $todayAttendance['alfa'] ?? 0 }} Alfa
                @else
                    Belum ada sesi
                @endif
            </p>
        </div>

        @if($pakaiPoin)
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs space-y-1">
            <dt class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Siswa Butuh Perhatian</dt>
            <dd class="text-2xl font-extrabold {{ $atRiskStudents->count() > 0 ? 'text-slate-900' : 'text-emerald-700' }}">
                {{ $atRiskStudents->count() }}
            </dd>
            <p class="text-[11px] text-slate-600 font-semibold truncate">Ketidakhadiran &amp; Poin</p>
        </div>
        @else
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-4 sm:p-5 shadow-xs space-y-1">
            <dt class="text-xs font-bold text-slate-500 uppercase tracking-wider text-[10.5px]">Peran Anda</dt>
            <dd class="text-lg font-extrabold text-emerald-800 truncate">Guru Mapel</dd>
            <p class="text-[11px] text-slate-600 font-semibold truncate">Pengampu Mapel</p>
        </div>
        @endif
    </dl>

    {{-- EARLY WARNING SYSTEM (EWS) --}}
    @if ($atRiskStudents->isNotEmpty())
        <section class="bg-white rounded-3xl border border-rose-200 p-5 sm:p-6 shadow-xs space-y-3">
            <div class="flex items-center justify-between border-b border-rose-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xl">⚠️</span>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Siswa Butuh Perhatian Khusus</h2>
                        <p class="text-xs text-slate-500 font-medium">Siswa terdeteksi berisiko berdasarkan riwayat kehadiran dan poin pembinaan.</p>
                    </div>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                    {{ $atRiskStudents->count() }} Siswa
                </span>
            </div>

            <div class="divide-y divide-rose-50">
                @foreach ($atRiskStudents as $st)
                    <div class="py-2.5 flex items-center justify-between gap-4">
                        <div>
                            <a href="{{ route('classes.students.show', [$classroom, $st]) }}" class="text-xs font-bold text-slate-900 hover:text-emerald-600 transition-colors">
                                {{ $st->name }}
                            </a>
                            <p class="text-[11px] text-rose-600 font-semibold mt-0.5">{{ $st->ews_alasan }}</p>
                        </div>
                        <a href="{{ route('classes.students.show', [$classroom, $st]) }}"
                           class="px-2.5 py-1 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition-colors">
                            Lihat Profil
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

</div>
@endsection
