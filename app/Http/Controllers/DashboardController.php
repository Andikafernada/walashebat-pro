<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Violation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $userId = $user->id;
        $todayStr = today()->toDateString();

        $jenis = in_array(request('jenis'), [Classroom::JENIS_PERWALIAN, Classroom::JENIS_AJAR], true)
            ? request('jenis')
            : null;

        $jumlahPerJenis = Classroom::where('is_active', true)
            ->selectRaw('jenis, COUNT(*) as total')
            ->groupBy('jenis')
            ->pluck('total', 'jenis');

        $kelas = Classroom::withCount(['students' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)
            ->when($jenis, fn ($q) => $q->where('jenis', $jenis))
            ->orderBy('name')
            ->get();

        $classIds = $kelas->pluck('id');
        $idPerwalian = $kelas->where('jenis', Classroom::JENIS_PERWALIAN)->pluck('id');

        // CACHED SNAPSHOT: 7-Day Attendance Trend
        $cacheKeyTrend = "dash_trend_{$userId}_" . ($jenis ?? 'all') . "_{$todayStr}";
        $chartTrend = Cache::remember($cacheKeyTrend, 600, function () use ($classIds) {
            $awal = today()->subDays(6);
            $masukIn = implode(',', array_fill(0, count(Attendance::STATUS_MASUK), '?'));

            $rekapHarian = Attendance::query()
                ->join('attendance_sessions', 'attendances.attendance_session_id', '=', 'attendance_sessions.id')
                ->whereIn('attendance_sessions.class_id', $classIds)
                ->whereBetween('attendance_sessions.session_date', [$awal, today()])
                ->where('attendance_sessions.status', '!=', 'cancelled')
                ->groupBy('tanggal')
                ->selectRaw('DATE(attendance_sessions.session_date) as tanggal')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN attendances.status IN ($masukIn) THEN 1 ELSE 0 END) as masuk", Attendance::STATUS_MASUK)
                ->get()
                ->keyBy('tanggal');

            $chart = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = today()->subDays($i);
                $baris = $rekapHarian->get($date->toDateString());
                $total = (int) ($baris->total ?? 0);
                $present = (int) ($baris->masuk ?? 0);

                $chart[] = [
                    'tanggal' => $date->format('d/m'),
                    'persen' => $total > 0 ? (int) round($present / $total * 100) : 100,
                ];
            }
            return $chart;
        });

        // CACHED SNAPSHOT: Siswa Perlu Perhatian (EWS)
        $cacheKeyPerhatian = "dash_ews_{$userId}_" . implode('_', $idPerwalian->toArray()) . "_{$todayStr}";
        $perluPerhatian = Cache::remember($cacheKeyPerhatian, 600, function () use ($idPerwalian) {
            return $this->perluPerhatian($idPerwalian);
        });

        // Live Real-Time Today Status for Classes
        $statusKelas = $this->statusKelasHariIni($kelas);

        $gagalKirim = AttendanceSession::with('classroom:id,name')
            ->whereIn('class_id', $classIds)
            ->where('delivery_status', 'failed')
            ->whereDate('session_date', today())
            ->get();

        $waPerluPerhatian = Classroom::whereIn('id', $classIds)->where('auto_attendance', true)->exists()
            && ! $user->whatsappConnected();

        $kelasUtama = $idPerwalian->count() === 1
            ? $kelas->firstWhere('id', $idPerwalian->first())
            : ($kelas->count() === 1 ? $kelas->first() : null);

        // CACHED SNAPSHOT: Summary Cards
        $cacheKeySummary = "dash_summary_{$userId}_" . ($jenis ?? 'all') . "_{$todayStr}";
        $ringkasan = Cache::remember($cacheKeySummary, 300, function () use ($kelas, $classIds, $idPerwalian) {
            return $this->ringkasanKartu($kelas, $classIds, $idPerwalian);
        });

        $tugasHariIni = $this->tugasHariIni($kelas, $statusKelas, $gagalKirim);

        return view('dashboard', [
            'classes' => $kelas,
            'statusKelas' => $statusKelas,
            'summary' => $ringkasan,
            'perluPerhatian' => $perluPerhatian,
            'chartTrend' => $chartTrend,
            'gagalKirim' => $gagalKirim,
            'waPerluPerhatian' => $waPerluPerhatian,
            'kelasUtama' => $kelasUtama,
            'jenisDipilih' => $jenis,
            'jumlahPerwalian' => (int) ($jumlahPerJenis[Classroom::JENIS_PERWALIAN] ?? 0),
            'jumlahAjar' => (int) ($jumlahPerJenis[Classroom::JENIS_AJAR] ?? 0),
            'tugasHariIni' => $tugasHariIni,
        ]);
    }

    private function tugasHariIni(Collection $classes, Collection $statusKelas, Collection $gagalKirim): array
    {
        $tugas = [];

        foreach ($statusKelas as $st) {
            if ($st['keadaan'] === 'belum') {
                $tugas[] = [
                    'judul' => "Presensi Kelas {$st['kelas']->name}",
                    'rinci' => 'Sesi presensi hari ini belum dibuat',
                    'tautan' => route('classes.attendance.index', $st['kelas']),
                    'aksi' => 'Mulai',
                ];
            } elseif ($st['keadaan'] === 'menunggu') {
                $tugas[] = [
                    'judul' => "Presensi Kelas {$st['kelas']->name} Sedang Berjalan",
                    'rinci' => "{$st['terisi']}/{$st['total']} siswa terabsen",
                    'tautan' => route('classes.attendance.show', [$st['kelas'], $st['sesi']]),
                    'aksi' => 'Lanjutkan',
                ];
            }
        }

        if ($gagalKirim->isNotEmpty()) {
            $tugas[] = [
                'judul' => "{$gagalKirim->count()} Rekap WhatsApp Gagal Terkirim",
                'rinci' => 'Periksa koneksi WhatsApp gateway',
                'tautan' => route('whatsapp.index'),
                'aksi' => 'Periksa',
            ];
        }

        return $tugas;
    }

    private function ringkasanKartu(
        Collection $kelas,
        Collection $classIds,
        Collection $idPerwalian
    ): array {
        $totalStudents = (int) $kelas->sum('students_count');

        $siswaPerwalian = Student::whereIn('class_id', $idPerwalian)->where('is_active', true);
        $totalPerwalian = (clone $siswaPerwalian)->count();

        $siswaLaki = (clone $siswaPerwalian)->where('gender', 'L')->count();
        $siswaPerempuan = (clone $siswaPerwalian)->where('gender', 'P')->count();

        $absensiHariIni = Attendance::whereHas(
            'session',
            fn ($q) => $q->whereIn('class_id', $classIds)
                ->whereDate('session_date', today())
                ->where('status', '!=', 'cancelled'),
        )->get(['status']);

        $terisi = $absensiHariIni->count();
        $masuk = $absensiHariIni->whereIn('status', Attendance::STATUS_MASUK)->count();
        $sakit = $absensiHariIni->where('status', 'sakit')->count();
        $izin = $absensiHariIni->where('status', 'izin')->count();
        $alfa = $absensiHariIni->where('status', 'alfa')->count();

        $realBiodataCount = (int) (clone $siswaPerwalian)
            ->whereNotNull('nama_ayah')
            ->where('nama_ayah', '<>', '')
            ->count();

        $biodataPercent = $totalPerwalian > 0 ? (int) round(($realBiodataCount / $totalPerwalian) * 100) : 0;

        $characterStudentsCount = \App\Models\CharacterReflection::whereIn('student_id', (clone $siswaPerwalian)->pluck('id'))
            ->distinct('student_id')
            ->count('student_id');
        $characterPercent = $totalPerwalian > 0 ? (int) round(($characterStudentsCount / $totalPerwalian) * 100) : 0;

        return [
            'classes' => $kelas->count(),
            'students' => $totalStudents,
            'siswa_laki' => $siswaLaki,
            'siswa_perempuan' => $siswaPerempuan,
            'terisi' => $terisi,
            'masuk' => $masuk,
            'sakit' => $sakit,
            'izin' => $izin,
            'alfa' => $alfa,
            'persen' => $terisi > 0 ? (int) round($masuk / $terisi * 100) : null,
            'biodata_percent' => $biodataPercent,
            'character_percent' => $characterPercent,
            'siswa_perwalian' => $totalPerwalian,
            'violations_today' => Violation::whereIn('class_id', $idPerwalian)->whereDate('occurred_on', today())->count(),
        ];
    }

    private function statusKelasHariIni(Collection $kelas): Collection
    {
        if ($kelas->isEmpty()) {
            return collect();
        }

        $sesi = AttendanceSession::with('classroom:id,name')
            ->whereIn('class_id', $kelas->pluck('id'))
            ->whereDate('session_date', today())
            ->where('status', '!=', 'cancelled')
            ->withCount([
                'attendances as terisi_count',
                'attendances as masuk_count' => fn ($q) => $q->whereIn('status', Attendance::STATUS_MASUK),
                'attendances as alfa_count' => fn ($q) => $q->where('status', 'alfa'),
            ])
            ->orderByDesc('sequence')
            ->get()
            ->groupBy('class_id');

        return $kelas->map(function ($k) use ($sesi) {
            $milik = $sesi->get($k->id);
            $terbaru = $milik?->first();

            $keadaan = match (true) {
                $terbaru === null => 'belum',
                $terbaru->status === 'submitted' => 'selesai',
                $terbaru->isExpired() => 'hangus',
                default => 'menunggu',
            };

            return [
                'kelas' => $k,
                'sesi' => $terbaru,
                'keadaan' => $keadaan,
                'total' => (int) $k->students_count,
                'terisi' => (int) ($terbaru->terisi_count ?? 0),
                'masuk' => (int) ($terbaru->masuk_count ?? 0),
                'alfa' => (int) ($terbaru->alfa_count ?? 0),
                'persen' => ($terbaru?->terisi_count ?? 0) > 0
                    ? (int) round($terbaru->masuk_count / $terbaru->terisi_count * 100)
                    : null,
            ];
        });
    }

    private function perluPerhatian(Collection $idPerwalian): Collection
    {
        if ($idPerwalian->isEmpty()) {
            return collect();
        }

        $sejak = today()->subDays(30);

        $alfa = Attendance::query()
            ->where('status', 'alfa')
            ->whereHas('session', fn ($q) => $q->whereIn('class_id', $idPerwalian)
                ->whereDate('session_date', '>=', $sejak))
            ->selectRaw('student_id, COUNT(*) as jumlah')
            ->groupBy('student_id')
            ->having('jumlah', '>=', 3)
            ->pluck('jumlah', 'student_id');

        $poinRendah = Student::whereIn('class_id', $idPerwalian)
            ->where('is_active', true)
            ->where('discipline_points', '<', 75)
            ->pluck('discipline_points', 'id');

        $idSemua = $alfa->keys()->merge($poinRendah->keys())->unique();

        if ($idSemua->isEmpty()) {
            return collect();
        }

        return Student::with('classroom:id,name')
            ->whereIn('id', $idSemua)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($s) use ($alfa, $poinRendah) {
                $alasan = [];

                if ($alfa->has($s->id)) {
                    $alasan[] = 'Alfa '.$alfa[$s->id].'× (30 hari)';
                }

                if ($poinRendah->has($s->id)) {
                    $alasan[] = 'Poin '.$poinRendah[$s->id];
                }

                return ['siswa' => $s, 'alasan' => $alasan];
            })
            ->take(8);
    }
}
