<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\CashBook;
use App\Models\CharacterRecord;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Violation;
use App\Models\ViolationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    /**
     * Analytics dashboard untuk wali kelas
     */
    public function index(Request $request): View
    {
        $classrooms = Classroom::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Default to first class if none selected
        $selectedClassId = $request->get('class_id') ?? $classrooms->first()?->id;

        if (!$selectedClassId) {
            return view('analytics.index', [
                'classrooms' => $classrooms,
                'selectedClass' => null,
                'selectedClassId' => null,
                'monthlyAttendance' => [],
                'violationsByCategory' => [],
                'characterTrend' => [],
                'heatmapData' => [],
                'summaryStats' => [],
            ]);
        }

        $selectedClass = Classroom::findOrFail($selectedClassId);

        return view('analytics.index', [
            'classrooms' => $classrooms,
            'selectedClass' => $selectedClass,
            'selectedClassId' => $selectedClassId,
            'monthlyAttendance' => $this->getMonthlyAttendance($selectedClassId),
            'violationsByCategory' => $this->getViolationsByCategory($selectedClassId),
            'characterTrend' => $this->getCharacterTrend($selectedClassId),
            'heatmapData' => $this->getHeatmapData($selectedClassId),
            'summaryStats' => $this->getSummaryStats($selectedClassId),
        ]);
    }

    /**
     * Grafik kehadiran bulanan (12 bulan terakhir)
     */
    private function getMonthlyAttendance(int $classId): array
    {
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $attendance = Attendance::whereHas('session', function ($q) use ($classId, $startOfMonth, $endOfMonth) {
                $q->where('class_id', $classId)
                    ->whereBetween('session_date', [$startOfMonth, $endOfMonth])
                    ->where('status', '!=', 'cancelled');
            })->get(['status']);

            $total = $attendance->count();
            $present = $attendance->whereIn('status', Attendance::STATUS_MASUK)->count();
            $sick = $attendance->where('status', 'sakit')->count();
            $permission = $attendance->where('status', 'izin')->count();
            $alpha = $attendance->where('status', 'alfa')->count();
            $late = $attendance->where('status', 'terlambat')->count();

            $months->push([
                'month' => $date->format('M Y'),
                'month_short' => $date->format('M'),
                'year' => $date->format('Y'),
                'total' => $total,
                'present' => $present,
                'sick' => $sick,
                'permission' => $permission,
                'alpha' => $alpha,
                'late' => $late,
                'rate' => $total > 0 ? round($present / $total * 100, 1) : 0,
            ]);
        }

        return $months->toArray();
    }

    /**
     * Pelanggaran per kategori
     */
    private function getViolationsByCategory(int $classId): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return ViolationType::withCount([
            'violations' => fn ($q) => $q
                ->where('class_id', $classId)
                ->whereBetween('occurred_on', [$startOfMonth, $endOfMonth])
        ])->get()->map(function ($type) {
            return [
                'name' => $type->name,
                /*
                 * mb_*, bukan strlen/substr.
                 *
                 * Nama jenis pelanggaran lazim diawali emoji ("🏆 Prestasi
                 * Akademik"), dan pemotongan per BYTE bisa jatuh di tengah satu
                 * karakter. Hasilnya bukan sekadar label jelek: potongan itu
                 * bukan UTF-8 yang sah, json_encode mengembalikan false, dan
                 * baris `const violationsData = {!! json_encode(...) !!}` di
                 * halaman analitik berubah menjadi `const violationsData = ;` —
                 * satu galat sintaks JavaScript yang mematikan SELURUH grafik
                 * di halaman itu, tanpa jejak apa pun selain konsol peramban.
                 */
                'short_name' => mb_strlen($type->name) > 15
                    ? mb_substr($type->name, 0, 12).'...'
                    : $type->name,
                'count' => $type->violations_count,
                'points' => $type->points,
            ];
        })->filter(fn ($item) => $item['count'] > 0)
            ->sortByDesc('count')
            ->values()
            ->toArray();
    }

    /**
     * Trend poin karakter per siswa (6 bulan terakhir)
     */
    private function getCharacterTrend(int $classId): array
    {
        $students = Student::where('class_id', $classId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'discipline_points']);

        $monthlyTrend = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyTrend->push([
                'month' => $date->format('M'),
                'date' => $date->format('Y-m'),
            ]);
        }

        return [
            'students' => $students->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'current_points' => $s->discipline_points,
            ])->toArray(),
            'monthly_trend' => $monthlyTrend->toArray(),
        ];
    }

    /**
     * Heatmap kehadiran per hari (4 minggu terakhir)
     */
    private function getHeatmapData(int $classId): array
    {
        $weeks = collect();
        for ($week = 3; $week >= 0; $week--) {
            $weekData = collect();
            for ($day = 1; $day <= 7; $day++) {
                $date = now()->subWeeks($week)->startOfWeek()->addDays($day - 1);

                // Skip future dates
                if ($date->isFuture()) {
                    $weekData->push([
                        'day' => $day,
                        'date' => $date->format('Y-m-d'),
                        'formatted' => $date->format('d/m'),
                        'total' => 0,
                        'present' => 0,
                        'rate' => null,
                        'is_today' => $date->isToday(),
                        'is_future' => true,
                    ]);
                    continue;
                }

                $attendance = Attendance::whereHas('session', function ($q) use ($classId, $date) {
                    $q->where('class_id', $classId)
                        ->whereDate('session_date', $date)
                        ->where('status', '!=', 'cancelled');
                })->get(['status']);

                $total = $attendance->count();
                $present = $attendance->whereIn('status', Attendance::STATUS_MASUK)->count();

                $weekData->push([
                    'day' => $day,
                    'date' => $date->format('Y-m-d'),
                    'formatted' => $date->format('d/m'),
                    'total' => $total,
                    'present' => $present,
                    'rate' => $total > 0 ? round($present / $total * 100, 1) : null,
                    'is_today' => $date->isToday(),
                    'is_future' => false,
                ]);
            }
            $weeks->push($weekData);
        }

        return [
            'weeks' => $weeks->toArray(),
            'day_names' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
        ];
    }

    /**
     * Statistik ringkasan
     */
    private function getSummaryStats(int $classId): array
    {
        $studentCount = Student::where('class_id', $classId)->where('is_active', true)->count();

        // Kehadiran bulan ini
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $attendance = Attendance::whereHas('session', function ($q) use ($classId, $startOfMonth, $endOfMonth) {
            $q->where('class_id', $classId)
                ->whereBetween('session_date', [$startOfMonth, $endOfMonth])
                ->where('status', '!=', 'cancelled');
        })->get(['status']);

        $total = $attendance->count();
        $present = $attendance->whereIn('status', Attendance::STATUS_MASUK)->count();
        $attendanceRate = $total > 0 ? round($present / $total * 100, 1) : 0;

        // Pelanggaran bulan ini
        $violationsCount = Violation::where('class_id', $classId)
            ->whereBetween('occurred_on', [$startOfMonth, $endOfMonth])
            ->count();

        // Rata-rata poin karakter
        $avgDisciplinePoints = Student::where('class_id', $classId)
            ->where('is_active', true)
            ->avg('discipline_points') ?? 0;

        // Siswa dengan poin rendah (< 75)
        $lowPointsStudents = Student::where('class_id', $classId)
            ->where('is_active', true)
            ->where('discipline_points', '<', 75)
            ->count();

        /*
         * Saldo kas = pemasukan DIKURANGI pengeluaran.
         *
         * Kolom `amount` menyimpan besaran positif dan arahnya ada di kolom
         * `type`, persis seperti yang dipakai CashBookController::currentBalance.
         * Menjumlahkan seluruh amount berarti pengeluaran ikut MENAMBAH saldo:
         * kas dengan pemasukan 500rb dan pengeluaran 300rb tampil 800rb di
         * halaman analitik, padahal isinya 200rb.
         */
        $masuk = (int) CashBook::where('class_id', $classId)->where('type', 'in')->sum('amount');
        $keluar = (int) CashBook::where('class_id', $classId)->where('type', 'out')->sum('amount');
        $cashBalance = $masuk - $keluar;

        // Siswa alfa >= 3 kali sebulan
        $alfaCount = Attendance::whereHas('session', function ($q) use ($classId, $startOfMonth, $endOfMonth) {
            $q->where('class_id', $classId)
                ->whereBetween('session_date', [$startOfMonth, $endOfMonth]);
        })
            ->where('status', 'alfa')
            ->selectRaw('student_id, COUNT(*) as count')
            ->groupBy('student_id')
            ->having('count', '>=', 3)
            ->count();

        return [
            'student_count' => $studentCount,
            'attendance_rate' => $attendanceRate,
            'total_attendance' => $total,
            'violations_count' => $violationsCount,
            'avg_discipline_points' => round($avgDisciplinePoints, 1),
            'low_points_students' => $lowPointsStudents,
            'cash_balance' => $cashBalance,
            'repeat_alpha_students' => $alfaCount,
        ];
    }

    /**
     * Export analytics as PDF (placeholder for future)
     */
    public function export(Request $request)
    {
        // Future: implement PDF export
        return back()->with('error', 'Fitur export PDF akan segera hadir.');
    }
}
