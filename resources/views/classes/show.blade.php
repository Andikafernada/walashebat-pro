@extends('layouts.app')

@section('title', 'Ringkasan Kelas - ' . $classroom->name)

@section('content')
@php
    /*
     * Early Warning System (EWS).
     *
     * Poin kedisiplinan hanya berlaku di kelas perwalian. Angkanya berasal dari
     * buku poin pelanggaran — modul yang sengaja disembunyikan dari guru mapel
     * (lihat partials/class-nav). Menampilkannya di sini membocorkan kembali
     * catatan pembinaan yang bukan urusannya, lengkap dengan angka yang tidak
     * punya cara ia perbaiki.
     */
    $pakaiPoin = ! $classroom->kelasAjar();

    $atRiskStudents = $classroom->students()
        ->where('is_active', true)
        ->get()
        ->map(function ($s) use ($classroom, $pakaiPoin) {
            /*
             * Dihitung khusus KELAS INI. Tanpa penyaringan class_id, yang
             * terhitung adalah seluruh ketidakhadiran siswa itu di kelas guru
             * mana pun: seorang siswa yang sering absen di jam pelajaran orang
             * lain muncul sebagai berisiko di sini, dan guru yang membukanya
             * mencari sebab pada pelajaran yang tidak pernah ia tinggalkan.
             */
            $absen = \App\Models\Attendance::where('student_id', $s->id)
                ->whereIn('status', ['alfa', 'izin', 'sakit'])
                ->whereHas('session', fn ($q) => $q
                    ->where('class_id', $classroom->id)
                    ->where('session_date', '>=', now()->subDays(30)))
                /*
                 * session_date, BUKAN created_at: yang dicari "berapa kali anak
                 * ini tidak hadir dalam 30 hari terakhir", bukan "berapa banyak
                 * baris yang DIKETIK petugas dalam 30 hari terakhir". Absensi
                 * susulan lazim: di kelas 611 ada baris tanggal sesi 25 Juli
                 * yang baru dientri 4 Agustus, sehingga jendela lamanya bergeser
                 * mengikuti kapan orang sempat mengetik.
                 */
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

<div class="space-y-6 pb-12">
    <!-- Header Bar & Subnav -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('classes.index') }}" class="hover:text-indigo-600 transition-colors">Kelas</a>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">{{ $classroom->name }}</span>
                <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-700 font-semibold">Ringkasan</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Ringkasan Kelas {{ $classroom->name }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Tahun Ajaran {{ $classroom->academic_year ?? '2026/2027' }} &middot; {{ $classroom->major ?? 'Umum' }}</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('classes.attendance.index', $classroom) }}" 
               class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition-all hover:bg-indigo-700 active:scale-95">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Kelola Absensi
            </a>
            <a href="{{ route('classes.edit', $classroom) }}" 
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50">
                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Pengaturan
            </a>
        </div>
    </div>

    <!-- Include Class Subnav -->
    @include('partials.class-nav', ['classroom' => $classroom])

    <!-- Flash Messages -->
    @include('partials.flash')

    <!-- Top Hero Banner Card -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-900 via-slate-900 to-indigo-950 p-6 text-white shadow-xl">
        <div class="absolute right-0 top-0 -mr-16 -mt-16 h-64 w-64 rounded-full bg-indigo-500/10 blur-3xl"></div>
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-2xl font-black text-white backdrop-blur-sm border border-white/10 shadow-md">
                    {{ strtoupper(substr($classroom->name, 0, 2)) }}
                </div>
                <div>
                    <span class="text-xs font-semibold tracking-wider text-indigo-300 uppercase block">Profil Kelas</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white">{{ $classroom->name }}</h2>
                    {{-- Pada kelas ajar pemiliknya adalah guru mapel, bukan wali
                         kelasnya. Sebutannya ikut jenis kelas; mapel yang diampu
                         disebut agar guru yang mengampu beberapa kelas tahu ia
                         sedang membuka yang mana. --}}
                    <p class="text-xs text-slate-300 mt-1">
                        {{ $classroom->sebutanPeran() }}:
                        <span class="font-bold text-white">{{ $classroom->owner->name ?? 'Belum Ditentukan' }}</span>
                        @if ($classroom->kelasAjar() && $classroom->mapelDiampu())
                            <span class="text-teal-300">&middot; {{ implode(' · ', $classroom->mapelDiampu()) }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4 border-t sm:border-t-0 border-white/10 pt-4 sm:pt-0">
                <div class="text-center px-4 py-2 rounded-xl bg-white/5 border border-white/10">
                    <span class="block text-2xl font-black text-white">{{ $totalStudents }}</span>
                    <span class="text-[10px] text-indigo-200 uppercase font-semibold">Total Siswa</span>
                </div>
                <div class="text-center px-4 py-2 rounded-xl bg-white/5 border border-white/10">
                    <span class="block text-2xl font-black text-emerald-400">{{ $todayAttendance['percentage'] ?? 0 }}%</span>
                    <span class="text-[10px] text-indigo-200 uppercase font-semibold">Hadir Hari Ini</span>
                </div>
            </div>
        </div>
    </div>

    <!-- EARLY WARNING SYSTEM (EWS) ALERT BANNER -->
    @if($atRiskStudents->count() > 0)
        <div class="rounded-2xl border border-rose-200 bg-gradient-to-r from-rose-50 to-amber-50/50 p-4 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-600 text-white font-bold shadow-md shadow-rose-500/20">
                        ⚠️
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-rose-950">Sistem Deteksi Siswa Berisiko (EWS)</h3>
                        <p class="text-xs text-rose-700/80">{{ $atRiskStudents->count() }} siswa memerlukan perhatian khusus ({{ $pakaiPoin ? 'ketidakhadiran berulang / poin disiplin rendah' : 'ketidakhadiran berulang di mapel ini' }})</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3 pt-1">
                @foreach($atRiskStudents->take(6) as $st)
                    <div class="flex items-center justify-between gap-2 rounded-xl bg-white p-2.5 border border-rose-100 shadow-xs text-xs">
                        <div>
                            <span class="font-bold text-slate-900 block truncate">{{ $st->name }}</span>
                            <span class="text-[10px] text-rose-600 font-semibold">{{ $st->ews_alasan }}</span>
                        </div>
                        <a href="{{ route('classes.students.show', [$classroom, $st]) }}" class="h-7 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 px-2.5 font-bold flex items-center shrink-0">
                            Detail
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- ATTENDANCE TREND CHART & QUICK METRICS -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Attendance Trend Widget -->
        <div class="lg:col-span-2 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Grafik Tren Kehadiran Kelas</h3>
                    <p class="text-xs text-slate-500">Pergerakan persentase kehadiran 7 sesi terakhir</p>
                </div>
                {{-- Lencana "Tren Positif" dulu berdiri di sini sebagai teks
                     tetap: tidak ada satu pun perhitungan naik/turun di
                     belakangnya, dan ia tetap mengaku positif bahkan pada kelas
                     yang tepat di bawahnya tertulis "Belum cukup data". --}}
            </div>

            <!-- SVG Visual Trend Chart -->
            <div class="h-40 w-full pt-4 flex items-end justify-between gap-2 px-2">
                @php
                    $recentSessions = $classroom->attendanceSessions()
                        ->where('status', 'submitted')
                        ->orderByDesc('session_date')
                        ->take(7)
                        ->get()
                        ->reverse();
                @endphp
                @if($recentSessions->isNotEmpty())
                    @foreach($recentSessions as $sess)
                        @php
                            $total = $sess->attendances->count();
                            $hadir = $sess->attendances->where('status', 'hadir')->count();
                            $pct = $total > 0 ? round(($hadir / $total) * 100) : 100;
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-1.5">
                            <span class="text-[10px] font-bold text-indigo-600">{{ $pct }}%</span>
                            <div class="w-full bg-indigo-100 rounded-t-lg relative overflow-hidden" style="height: 90px;">
                                <div class="bg-indigo-600 w-full absolute bottom-0 rounded-t-lg transition-all" style="height: {{ $pct }}%;"></div>
                            </div>
                            <span class="text-[9px] font-mono text-slate-400">{{ $sess->session_date->format('d/m') }}</span>
                        </div>
                    @endforeach
                @else
                    <div class="w-full my-auto text-center text-xs text-slate-400">Belum cukup data sesi absensi untuk grafik tren.</div>
                @endif
            </div>
        </div>

        <!-- 3 Quick Action Links -->
        <div class="space-y-4">
            <!-- Students -->
            <a href="{{ route('classes.students.index', $classroom) }}" 
               class="group rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all block">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Siswa Terdaftar</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 group-hover:scale-110 transition-transform">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                    </div>
                </div>
                <p class="mt-2 text-2xl font-black tracking-tight text-slate-900">{{ $totalStudents }} <span class="text-xs font-semibold text-slate-500">Siswa</span></p>
                <p class="mt-1 text-[11px] font-semibold text-indigo-600 group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">Lihat Daftar Siswa &rarr;</p>
            </a>

            {{--
                Pintasan kedua mengikuti jenis kelas.

                Laporan Administrasi adalah modul perwalian — class-nav sudah
                menyingkirkan tabnya dari kelas ajar, tetapi kartu ini dulu
                membukanya kembali dari halaman ringkasan, sehingga guru mapel
                tetap sampai ke berkas yang menuntut data milik wali kelas.
                Di kelas ajar yang setara gunanya adalah daftar nilai mapelnya.
            --}}
            @php $pintasan = $classroom->kelasAjar()
                ? ['rute' => 'classes.nilai.index', 'kepala' => 'Penilaian Mapel', 'judul' => 'Daftar Nilai', 'ajakan' => 'Kelola Nilai &amp; Capaian']
                : ['rute' => 'classes.reports.full', 'kepala' => 'Laporan Administrasi', 'judul' => 'Rekap Lengkap', 'ajakan' => 'Cetak &amp; Ekspor Laporan'];
            @endphp
            <a href="{{ route($pintasan['rute'], $classroom) }}"
               class="group rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all block">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ $pintasan['kepala'] }}</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 group-hover:scale-110 transition-transform">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>
                <p class="mt-2 text-2xl font-black tracking-tight text-purple-950">{{ $pintasan['judul'] }}</p>
                <p class="mt-1 text-[11px] font-semibold text-purple-600 group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">{!! $pintasan['ajakan'] !!} &rarr;</p>
            </a>
        </div>
    </div>

</div>
@endsection
