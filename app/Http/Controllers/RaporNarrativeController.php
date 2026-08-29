<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Services\RaporNarrativeGeneratorService;
use App\Support\PeriodeLaporan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class RaporNarrativeController extends Controller
{
    public function __construct(
        protected RaporNarrativeGeneratorService $generator
    ) {}

    /**
     * Halaman Utama Generator Narasi Rapor Kelas.
     */
    public function index(Request $request, Classroom $class): View
    {
        $semester = (int) $request->input('semester', 1);
        $narratives = $this->generator->generateForClassroom($class, $semester);

        return view('reports.narasi_rapor', [
            'classroom' => $class,
            'semester' => $semester,
            'narratives' => $narratives,
        ]);
    }

    /**
     * API Endpoint: Regenerate narasi 1 orang siswa secara instan.
     */
    public function generateStudent(Request $request, Classroom $class, Student $student): JsonResponse
    {
        abort_unless($student->class_id === $class->id, 403);

        $semester = (int) $request->input('semester', 1);
        $narrative = $this->generator->generateForStudent(
            $student,
            collect(),
            ['persen' => 100, 'alfa' => 0, 'izin' => 0, 'sakit' => 0],
            null,
            null,
            $semester
        );

        return response()->json([
            'success' => true,
            'data' => $narrative,
        ]);
    }

    /**
     * Unduh Rekap Narasi Rapor Seluruh Siswa sebagai PDF resmi.
     */
    public function downloadPdf(Request $request, Classroom $class): Response
    {
        $semester = (int) $request->input('semester', 1);
        $narratives = $this->generator->generateForClassroom($class, $semester);

        $pdf = Pdf::loadView('reports.pdf.narasi_rapor', [
            'classroom' => $class,
            'semester' => $semester,
            'narratives' => $narratives,
            'guru' => $request->user(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Narasi-Rapor-{$class->name}-Semester-{$semester}.pdf");
    }
}
