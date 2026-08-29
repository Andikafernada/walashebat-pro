<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\TeachingJournal;
use App\Services\OpenCodeJurnalGeneratorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class JurnalMengajarController extends Controller
{
    public function __construct(
        private readonly OpenCodeJurnalGeneratorService $aiGenerator
    ) {}

    /**
     * Daftar seluruh jurnal mengajar kelas ini.
     */
    public function index(Classroom $class): View
    {
        $journals = TeachingJournal::where('class_id', $class->id)
            ->orderByDesc('session_date')
            ->orderByDesc('meeting_number')
            ->paginate(15);

        return view('jurnal.index', [
            'classroom' => $class,
            'journals' => $journals,
        ]);
    }

    /**
     * Formulir pembuatan jurnal mengajar baru.
     */
    public function create(Classroom $class): View
    {
        $mapelList = $class->mapelDiampu();
        $nextMeeting = (int) TeachingJournal::where('class_id', $class->id)->max('meeting_number') + 1;

        return view('jurnal.create', [
            'classroom' => $class,
            'mapelList' => $mapelList,
            'nextMeeting' => $nextMeeting,
        ]);
    }

    /**
     * Endpoint AI Generator OpenCode: Generate Jurnal secara instan (JSON).
     */
    public function generateAi(Request $request, Classroom $class): JsonResponse
    {
        $request->validate([
            'subject' => ['required', 'string', 'max:100'],
            'topic' => ['required', 'string', 'max:200'],
            'meeting_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $meeting = (int) ($request->meeting_number ?: 1);
        $result = $this->aiGenerator->generate(
            subject: $request->subject,
            topic: $request->topic,
            meeting: $meeting,
            className: $class->name
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Simpan jurnal mengajar ke database.
     */
    public function store(Request $request, Classroom $class): RedirectResponse
    {
        $data = $request->validate([
            'session_date' => ['required', 'date'],
            'meeting_number' => ['required', 'integer', 'min:1'],
            'subject' => ['required', 'string', 'max:191'],
            'topic' => ['required', 'string', 'max:191'],
            'learning_objective' => ['nullable', 'string'],
            'activity' => ['nullable', 'string'],
            'reflection' => ['nullable', 'string'],
            'p5_dimension' => ['nullable', 'string', 'max:191'],
            'attendance_summary' => ['nullable', 'string', 'max:191'],
        ]);

        $data['user_id'] = auth()->id();
        $data['class_id'] = $class->id;

        TeachingJournal::create($data);

        return redirect()->route('classes.jurnal.index', $class)
            ->with('success', 'Jurnal mengajar pertemuan ke-' . $data['meeting_number'] . ' berhasil disimpan.');
    }

    /**
     * Hapus jurnal mengajar.
     */
    public function destroy(Classroom $class, TeachingJournal $jurnal): RedirectResponse
    {
        $jurnal->delete();

        return redirect()->route('classes.jurnal.index', $class)
            ->with('success', 'Jurnal mengajar berhasil dihapus.');
    }

    /**
     * Cetak rekap lembar jurnal mengajar kelas ke format PDF resmi.
     */
    public function exportPdf(Classroom $class): Response
    {
        $journals = TeachingJournal::where('class_id', $class->id)
            ->orderBy('meeting_number')
            ->get();

        $pdf = Pdf::loadView('jurnal.pdf', [
            'classroom' => $class,
            'journals' => $journals,
            'user' => auth()->user(),
        ])
        ->setPaper('a4', 'landscape');

        $filename = 'Jurnal_Mengajar_' . str_replace(' ', '_', $class->name) . '.pdf';

        return $pdf->download($filename);
    }
}
