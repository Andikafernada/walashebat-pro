<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Services\EarlyWarningSystemService;
use App\Support\PeriodeLaporan;
use Illuminate\Http\Request;

class EarlyWarningSystemController extends Controller
{
    public function __construct(
        protected EarlyWarningSystemService $ewsService
    ) {}

    /**
     * Menampilkan dashboard EWS untuk sebuah kelas.
     */
    public function index(Request $request, Classroom $class)
    {
        $periode = PeriodeLaporan::resolve($request);

        // Ambil hasil analisis seluruh siswa
        $analyzedStudents = $this->ewsService->analyzeClassroom($class, $request);

        // Filter jika pengguna memilih tab tertentu
        $filterLevel = $request->query('level');
        if ($filterLevel && in_array($filterLevel, ['critical', 'warning', 'attention', 'safe'])) {
            $filteredStudents = $analyzedStudents->where('risk_level', $filterLevel)->values();
        } else {
            $filteredStudents = $analyzedStudents;
        }

        // Rekap KPI Risiko
        $kpi = [
            'total' => $analyzedStudents->count(),
            'critical' => $analyzedStudents->where('risk_level', 'critical')->count(),
            'warning' => $analyzedStudents->where('risk_level', 'warning')->count(),
            'attention' => $analyzedStudents->where('risk_level', 'attention')->count(),
            'safe' => $analyzedStudents->where('risk_level', 'safe')->count(),
        ];

        return view('ews.index', [
            'classroom' => $class,
            'students' => $filteredStudents,
            'allStudents' => $analyzedStudents,
            'kpi' => $kpi,
            'periode' => $periode,
            'activeLevel' => $filterLevel,
        ]);
    }

    /**
     * Endpoint API / AJAX untuk mendapatkan analisis instan satu siswa.
     */
    public function analyze(Request $request, Classroom $class, Student $student)
    {
        $periode = PeriodeLaporan::resolve($request);

        $analysis = $this->ewsService->evaluateStudent($student, $class, $periode);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $analysis,
            ]);
        }

        return redirect()->route('classes.ews.index', $class)
            ->with('success', 'Analisis EWS untuk ' . $student->name . ' berhasil diperbarui.');
    }
}
