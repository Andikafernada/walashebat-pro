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

<div class="space-y-5 pb-12">

    <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <nav class="eyebrow flex items-center gap-1.5" aria-label="Remah roti">
                <a href="{{ route('classes.index') }}" class="hover:text-slate-600">Kelas</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-500">{{ $classroom->name }}</span>
            </nav>
            <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Ringkasan Kelas {{ $classroom->name }}</h1>
            {{-- Pada kelas ajar pemiliknya adalah guru mapel, bukan wali
                 kelasnya. Sebutannya ikut jenis kelas; mapel yang diampu
                 disebut agar guru yang mengampu beberapa kelas tahu ia
                 sedang membuka yang mana. --}}
            <p class="mt-1 text-sm text-slate-500">
                {{-- Titik duanya bagian dari pernyataannya, bukan hiasan:
                     "Guru Mapel: <nama>" menyebut peran orang itu. Tanpa itu
                     kalimatnya menggantung dan testnya pun mengunci bentuk ini. --}}
                {{ $classroom->sebutanPeran() }}:
                <span class="font-medium text-slate-700">{{ $classroom->owner->name ?? 'belum ditentukan' }}</span>
                &middot; TA {{ $classroom->academic_year ?? '2026/2027' }}
                &middot; {{ $classroom->kelasAjar() && $classroom->mapelDiampu()
                        ? implode(' · ', $classroom->mapelDiampu())
                        : ($classroom->major ?? 'Umum') }}
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <a href="{{ route('classes.attendance.index', $classroom) }}" class="btn-primary btn-primary--sm">Kelola Absensi</a>
            <a href="{{ route('classes.edit', $classroom) }}" class="btn-secondary btn-secondary--sm">Pengaturan</a>
        </div>
    </div>

    @include('partials.class-nav', ['classroom' => $classroom])

    @include('partials.flash')

    {{--
        Dua angka pokok kelas ini.

        Dulu keduanya duduk di dalam spanduk bergradien indigo-ke-hitam
        setinggi 150px dengan lingkaran buram di pojoknya, inisial kelas dalam
        kotak buram, dan nama kelas dicetak sekali lagi setelah baru saja
        tertulis sebagai judul halaman di atasnya.
    --}}
    <dl class="deret-angka">
        <div>
            <dt class="stat-label">Total Siswa</dt>
            <dd class="stat-value">{{ $totalStudents }}</dd>
        </div>
        <div>
            <dt class="stat-label">Hadir Hari Ini</dt>
            <dd class="stat-value">{{ $todayAttendance['percentage'] ?? 0 }}%</dd>
        </div>
        <div>
            <dt class="stat-label">Perlu Perhatian</dt>
            <dd class="stat-value {{ $atRiskStudents->count() > 0 ? 'text-rose-700' : '' }}">{{ $atRiskStudents->count() }}</dd>
            <p class="stat-sub">{{ $pakaiPoin ? 'ketidakhadiran / poin disiplin' : 'ketidakhadiran di mapel ini' }}</p>
        </div>
    </dl>

    @if($atRiskStudents->count() > 0)
        <section class="blok">
            <div class="blok__kepala">
                <h2 class="blok__judul">Siswa berisiko</h2>
                <span class="kode kode--alfa">{{ $atRiskStudents->count() }}</span>
            </div>
            <div class="blok__daftar">
                @foreach($atRiskStudents->take(6) as $st)
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $st->name }}</p>
                            <p class="truncate text-xs text-rose-700">{{ $st->ews_alasan }}</p>
                        </div>
                        <a href="{{ route('classes.students.show', [$classroom, $st]) }}" class="btn-secondary btn-secondary--sm shrink-0">Detail</a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <section class="blok lg:col-span-2">
            <div class="blok__kepala">
                <h2 class="blok__judul">Tren kehadiran kelas</h2>
                {{-- Lencana "Tren Positif" dulu berdiri di sini sebagai teks
                     tetap: tidak ada satu pun perhitungan naik/turun di
                     belakangnya, dan ia tetap mengaku positif bahkan pada kelas
                     yang tepat di bawahnya tertulis "Belum cukup data". --}}
                <span class="eyebrow">7 sesi terakhir</span>
            </div>

            @php
                $recentSessions = $classroom->attendanceSessions()
                    ->where('status', 'submitted')
                    ->orderByDesc('session_date')
                    ->take(7)
                    ->get()
                    ->reverse();
            @endphp

            @if($recentSessions->isNotEmpty())
                {{-- Batangnya tipis dan sewarna, tanpa sudut membulat: yang
                     dibandingkan tingginya, dan hiasan apa pun di ujung batang
                     hanya menambah ketebalan yang ikut terbaca sebagai nilai. --}}
                <div class="flex items-end justify-between gap-1.5 px-4 pb-3 pt-5">
                    @foreach($recentSessions as $sess)
                        @php
                            $total = $sess->attendances->count();
                            $hadir = $sess->attendances->where('status', 'hadir')->count();
                            $pct = $total > 0 ? round(($hadir / $total) * 100) : 100;
                        @endphp
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <span class="angka text-[10px] font-medium text-slate-600">{{ $pct }}%</span>
                            <div class="relative w-full bg-slate-100" style="height: 88px;">
                                <div class="absolute bottom-0 w-full bg-indigo-600" style="height: {{ $pct }}%;"></div>
                            </div>
                            <span class="font-mono text-[9px] text-slate-400">{{ $sess->session_date->format('d/m') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="p-4 text-sm text-slate-500">Belum cukup data sesi absensi untuk grafik tren.</p>
            @endif
        </section>

        <div class="space-y-4">
            <a href="{{ route('classes.students.index', $classroom) }}" class="card block transition-colors hover:border-slate-400">
                <p class="stat-label">Siswa Terdaftar</p>
                <p class="stat-value">{{ $totalStudents }}</p>
                <p class="mt-1.5 text-xs font-medium text-indigo-700">Lihat Daftar Siswa &rarr;</p>
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
            <a href="{{ route($pintasan['rute'], $classroom) }}" class="card block transition-colors hover:border-slate-400">
                <p class="stat-label">{{ $pintasan['kepala'] }}</p>
                <p class="mt-1.5 text-lg font-semibold tracking-tight text-slate-900">{{ $pintasan['judul'] }}</p>
                <p class="mt-1.5 text-xs font-medium text-indigo-700">{!! $pintasan['ajakan'] !!} &rarr;</p>
            </a>
        </div>
    </div>

</div>
@endsection
