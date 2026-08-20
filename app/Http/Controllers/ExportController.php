<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceReportExport;
use App\Exports\CashBookReportExport;
use App\Exports\CharacterPortfolioExport;
use App\Exports\ViolationsReportExport;
use App\Models\CashBook;
use App\Models\CharacterRecord;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Violation;
use App\Support\PeriodeLaporan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    /**
     * Export attendance report as Excel.
     */
    public function attendanceExcel(Request $request, Classroom $class): Response
    {
        $periode = PeriodeLaporan::resolve($request);

        // Get attendance data
        $attendanceData = $this->prepareAttendanceData($class, $periode);
        $dateRange = $this->getDateRange($class, $periode);

        $export = new AttendanceReportExport($class, $dateRange, $attendanceData);

        $filename = sprintf(
            'Rekap-Absensi-%s-%s.xlsx',
            str($class->name)->slug(),
            $periode['slug']
        );

        return Excel::download($export, $filename);
    }

    /**
     * Export attendance report as PDF.
     */
    public function attendancePdf(Request $request, Classroom $class): Response
    {
        $periode = PeriodeLaporan::resolve($request);
        $dateRange = $this->getDateRange($class, $periode);
        $attendanceData = $this->prepareAttendanceData($class, $periode);

        $pdf = Pdf::loadView('exports.attendance-pdf', [
            'class' => $class,
            'periode' => $periode,
            'dateRange' => $dateRange,
            'attendanceData' => $attendanceData,
            'user' => $request->user(),
        ])->setPaper('a4', 'landscape');

        $filename = sprintf(
            'Rekap-Absensi-%s-%s.pdf',
            str($class->name)->slug(),
            $periode['slug']
        );

        return $pdf->download($filename);
    }

    /**
     * Export violations report as Excel.
     */
    public function violationsExcel(Request $request, Classroom $class): Response
    {
        $periode = PeriodeLaporan::resolve($request);
        $studentId = $request->input('student_id');

        $violationsData = $this->prepareViolationsData($class, $periode, $studentId);

        $export = new ViolationsReportExport($class, $violationsData, []);

        $filename = $studentId
            ? sprintf('Laporan-Pelanggaran-Siswa-%s.xlsx', $class->name)
            : sprintf('Laporan-Pelanggaran-%s-%s.xlsx', str($class->name)->slug(), $periode['slug']);

        return Excel::download($export, $filename);
    }

    /**
     * Export violations report as PDF.
     */
    public function violationsPdf(Request $request, Classroom $class): Response
    {
        $periode = PeriodeLaporan::resolve($request);
        $studentId = $request->input('student_id');

        $student = $studentId ? Student::find($studentId) : null;
        $violations = Violation::where('class_id', $class->id)
            ->whereBetween('occurred_on', [$periode['awal'], $periode['akhir']])
            ->when($studentId, fn($q) => $q->where('student_id', $studentId))
            ->with(['student', 'type'])
            ->orderBy('occurred_on', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.violations-pdf', [
            'class' => $class,
            'periode' => $periode,
            'violations' => $violations,
            'student' => $student,
            'user' => $request->user(),
        ])->setPaper('a4', 'portrait');

        $filename = $student
            ? sprintf('Laporan-Pelanggaran-%s.pdf', str($student->name)->slug())
            : sprintf('Laporan-Pelanggaran-%s-%s.pdf', str($class->name)->slug(), $periode['slug']);

        return $pdf->download($filename);
    }

    /**
     * Export cash book report as Excel.
     */
    public function cashBookExcel(Request $request, Classroom $class): Response
    {
        $transactions = $this->prepareCashBookData($class);

        $export = new CashBookReportExport($class, $transactions);

        $filename = sprintf('Buku-Kas-%s.xlsx', str($class->name)->slug());

        return Excel::download($export, $filename);
    }

    /**
     * Export cash book report as PDF.
     */
    public function cashBookPdf(Request $request, Classroom $class): Response
    {
        $transactions = $this->prepareCashBookData($class);

        $pdf = Pdf::loadView('exports.cashbook-pdf', [
            'class' => $class,
            'transactions' => $transactions,
            'user' => $request->user(),
            /*
             * 'in'/'out', BUKAN 'income'/'expense'. Enum kolomnya memang begitu
             * (migrasi create_cash_books_table), sehingga pembanding lama tidak
             * pernah cocok: kedua total selalu Rp 0 dan setiap setoran masuk
             * ikut DIKURANGKAN dari saldo berjalan di bawah.
             */
            'totalIncome' => $transactions->where('type', 'in')->sum('amount'),
            'totalExpense' => $transactions->where('type', 'out')->sum('amount'),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf('Buku-Kas-%s.pdf', str($class->name)->slug());

        return $pdf->download($filename);
    }

    /**
     * Export character portfolio as Excel.
     */
    public function characterPortfolioExcel(Request $request, Classroom $class, Student $student): Response
    {
        // $class dan $student adalah dua binding independen; tanpa ini, guru
        // dengan 2+ kelas bisa menukar id siswa di URL dan mendapat dokumen
        // resmi berkop kelas A berisi data karakter siswa kelas lainnya.
        abort_unless($student->class_id === $class->id, 404);

        $records = $this->prepareCharacterRecordsData($student);

        $export = new CharacterPortfolioExport($class, $student, $records);

        $filename = sprintf('Portofolio-Karakter-%s.xlsx', str($student->name)->slug());

        return Excel::download($export, $filename);
    }

    /**
     * Export character portfolio as PDF.
     */
    public function characterPortfolioPdf(Request $request, Classroom $class, Student $student): Response
    {
        abort_unless($student->class_id === $class->id, 404);

        $records = $this->prepareCharacterRecordsData($student);

        $pdf = Pdf::loadView('exports.character-portfolio-pdf', [
            'class' => $class,
            'student' => $student,
            'records' => $records,
            'user' => $request->user(),
            'dimensionScores' => $this->calculateDimensionScores($student),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf('Portofolio-Karakter-%s.pdf', str($student->name)->slug());

        return $pdf->download($filename);
    }

    /**
     * Prepare attendance data for export.
     */
    private function prepareAttendanceData(Classroom $class, array $periode): array
    {
        $students = $class->students()->orderBy('name')->get();
        $sessions = $class->attendanceSessions()
            ->whereBetween('session_date', [$periode['awal'], $periode['akhir']])
            ->where('status', '!=', 'cancelled')
            ->with('attendances')
            ->get();

        $dateRange = $this->getDateRange($class, $periode);
        $result = [];

        foreach ($students as $index => $student) {
            $row = [
                'no' => $index + 1,
                'name' => $student->name,
                'nis' => $student->nis,
                'attendance' => [],
                'hadir' => 0,
                'sakit' => 0,
                'izin' => 0,
                'alfa' => 0,
                'terlambat' => 0,
                'total' => 0,
            ];

            foreach ($dateRange as $date) {
                $dateKey = $date->format('Y-m-d');
                $session = $sessions->first(fn($s) => $s->session_date->format('Y-m-d') === $dateKey);
                $att = $session?->attendances->first(fn($a) => $a->student_id === $student->id);

                $status = $att?->status ?? '-';
                $row['attendance'][$dateKey] = $status;

                /*
                 * 'alfa', BUKAN 'alpha' — itu nilai enum yang sesungguhnya
                 * tersimpan. Pembanding lama tidak pernah cocok, jadi setiap
                 * alfa hilang dari rekap ekspor sekaligus dari kolom Total,
                 * padahal PDF administrasi menghitungnya dengan benar.
                 *
                 * 'terlambat' dulu tidak ditangani sama sekali: siswa yang
                 * datang terlambat lenyap dari keempat kolom.
                 */
                if ($status === 'hadir') $row['hadir']++;
                elseif ($status === 'terlambat') $row['terlambat']++;
                elseif ($status === 'sakit') $row['sakit']++;
                elseif ($status === 'izin') $row['izin']++;
                elseif ($status === 'alfa') $row['alfa']++;
            }

            $row['total'] = $row['hadir'] + $row['terlambat'] + $row['sakit'] + $row['izin'] + $row['alfa'];
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Get date range for attendance.
     */
    private function getDateRange(Classroom $class, array $periode): array
    {
        $dates = [];
        $current = $periode['awal']->copy();

        while ($current->lte($periode['akhir'])) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Prepare violations data for export.
     */
    private function prepareViolationsData(Classroom $class, array $periode, ?int $studentId = null): array
    {
        $query = Violation::where('class_id', $class->id)
            ->whereBetween('occurred_on', [$periode['awal'], $periode['akhir']])
            ->with(['student', 'type']);

        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        $violations = $query->orderBy('occurred_on', 'desc')->get();
        $result = [];

        foreach ($violations as $index => $violation) {
            $result[] = [
                'no' => $index + 1,
                'student_name' => $violation->student->name,
                'nis' => $violation->student->nis,
                'violation_type' => $violation->type->name ?? 'Lainnya',
                'points' => $violation->points,
                'date' => $violation->occurred_on,
                'note' => $violation->note,
                'total_points' => Violation::where('student_id', $violation->student_id)
                    ->whereBetween('occurred_on', [$periode['awal'], $periode['akhir']])
                    ->sum('points'),
            ];
        }

        return $result;
    }

    /**
     * Prepare cash book data for export.
     */
    private function prepareCashBookData(Classroom $class): \Illuminate\Support\Collection
    {
        $transactions = CashBook::where('class_id', $class->id)
            ->orderBy('transaction_date')
            ->get();

        $result = collect();
        $no = 1;
        $runningBalance = 0;

        foreach ($transactions as $transaction) {
            $amount = $transaction->amount;
            if ($transaction->type === 'in') {
                $runningBalance += $amount;
            } else {
                $runningBalance -= $amount;
            }

            $result->push([
                'no' => $no++,
                'date' => $transaction->transaction_date,
                'type' => $transaction->type,
                'description' => $transaction->description,
                'amount' => $amount,
                'amount_formatted' => 'Rp ' . number_format($amount, 0, ',', '.'),
                'balance_after' => $runningBalance,
                'balance_formatted' => 'Rp ' . number_format($runningBalance, 0, ',', '.'),
            ]);
        }

        return $result;
    }

    /**
     * Prepare character records data for export.
     */
    private function prepareCharacterRecordsData(Student $student): \Illuminate\Support\Collection
    {
        $records = CharacterRecord::where('student_id', $student->id)
            ->with('dimension')
            ->orderBy('record_date', 'desc')
            ->get();

        $result = collect();
        $no = 1;

        foreach ($records as $record) {
            $result->push([
                'no' => $no++,
                'date' => $record->record_date,
                'dimension_name' => $record->dimension->name ?? '-',
                'type' => $record->type,
                'title' => $record->title,
                'description' => $record->description,
                'context' => $record->context,
                'score_display' => $record->getScoreDisplay(),
            ]);
        }

        return $result;
    }

    /**
     * Calculate dimension scores for a student.
     */
    private function calculateDimensionScores(Student $student): array
    {
        $month = now()->month;
        if ($month >= 7) {
            $startDate = now()->month(7)->startOfMonth();
            $endDate = now()->month(12)->endOfMonth();
        } else {
            $startDate = now()->month(1)->startOfMonth();
            $endDate = now()->month(6)->endOfMonth();
        }

        /*
         * Disaring ke wali kelas si siswa.
         *
         * CharacterDimension tidak memakai BelongsToTenant — halaman publik dan
         * guard 'student' membacanya tanpa Auth, jadi tidak ada penyaring
         * otomatis. Tanpa penyaring tegas di sini, rapor karakter yang diekspor
         * memuat daftar dimensi SELURUH sekolah yang memakai aplikasi ini,
         * bukan hanya milik sekolah si siswa.
         */
        $dimensions = \App\Models\CharacterDimension::forOwner($student->user_id)
            ->active()
            ->get();

        $scores = [];

        foreach ($dimensions as $dimension) {
            $scores[$dimension->id] = [
                'name' => $dimension->name,
                'score' => CharacterRecord::where('student_id', $student->id)
                    ->where('character_dimension_id', $dimension->id)
                    ->whereBetween('record_date', [$startDate, $endDate])
                    ->sum('score'),
                'color' => $dimension->color,
            ];
        }

        return $scores;
    }
}
