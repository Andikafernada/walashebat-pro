<?php

namespace App\Http\Controllers;

use App\Models\CharacterBadge;
use App\Models\CharacterDimension;
use App\Models\CharacterRecord;
use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentBadge;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CharacterPortfolioController extends Controller
{
    /**
     * Get semester date range for Indonesian school year (July - June).
     */
    private function getSemesterDateRange(): array
    {
        $month = now()->month;
        $year = now()->year;

        if ($month >= 7) {
            // Semester 1: July Year X - December Year X
            return [
                'start' => Carbon::create($year, 7, 1)->startOfMonth(),
                'end' => Carbon::create($year, 12, 31)->endOfMonth(),
                'semester' => 1,
                'tahun_ajaran' => $year . '/' . ($year + 1),
            ];
        } else {
            // Semester 2: January Year X - June Year X
            return [
                'start' => Carbon::create($year, 1, 1)->startOfMonth(),
                'end' => Carbon::create($year, 6, 30)->endOfMonth(),
                'semester' => 2,
                'tahun_ajaran' => ($year - 1) . '/' . $year,
            ];
        }
    }

    /**
     * Display overview of character portfolio for a class.
     */
    public function index(Classroom $class): View
    {
        $dimensions = CharacterDimension::forOwner($class->user_id)->active()->get();
        $students = $class->students()->with(['characterRecords', 'studentBadges'])->get();

        // Calculate stats per dimension
        $dimensionStats = [];
        foreach ($dimensions as $dimension) {
            $dimensionStats[$dimension->id] = [
                'total_records' => CharacterRecord::where('class_id', $class->id)
                    ->where('character_dimension_id', $dimension->id)
                    ->count(),
                'positive' => CharacterRecord::where('class_id', $class->id)
                    ->where('character_dimension_id', $dimension->id)
                    ->where('type', 'positive')
                    ->count(),
                'negative' => CharacterRecord::where('class_id', $class->id)
                    ->where('character_dimension_id', $dimension->id)
                    ->where('type', 'negative')
                    ->count(),
            ];
        }

        return view('character-portfolio.index', compact('class', 'dimensions', 'students', 'dimensionStats'));
    }

    /**
     * Show student's character portfolio.
     */
    public function showStudent(Classroom $class, Student $student): View
    {
        $dimensions = CharacterDimension::forOwner($class->user_id)->active()->get();
        $records = CharacterRecord::where('student_id', $student->id)
            ->with('dimension')
            ->latest('record_date')
            ->paginate(20);

        $badges = StudentBadge::where('student_id', $student->id)
            ->with('badge')
            ->get();

        $reflections = CharacterReflection::where('student_id', $student->id)
            ->with('dimension')
            ->latest('reflection_date')
            ->take(5)
            ->get();

        // Calculate dimension scores for the semester
        $semester = $this->getSemesterDateRange();
        $startDate = $semester['start'];
        $endDate = $semester['end'];

        $dimensionScores = [];
        foreach ($dimensions as $dimension) {
            $score = CharacterRecord::where('student_id', $student->id)
                ->where('character_dimension_id', $dimension->id)
                ->whereBetween('record_date', [$startDate, $endDate])
                ->sum('score');

            $dimensionScores[$dimension->id] = [
                'score' => $score,
                'records_count' => CharacterRecord::where('student_id', $student->id)
                    ->where('character_dimension_id', $dimension->id)
                    ->whereBetween('record_date', [$startDate, $endDate])
                    ->count(),
            ];
        }

        return view('character-portfolio.student', compact(
            'class', 'student', 'dimensions', 'records', 'badges', 'reflections', 'dimensionScores'
        ));
    }

    /**
     * Show create form for character record.
     */
    public function createRecord(Classroom $class, Student $student): View
    {
        $dimensions = CharacterDimension::forOwner($class->user_id)->active()->get();

        return view('character-portfolio.create', compact('class', 'student', 'dimensions'));
    }

    /**
     * Show character record details.
     */
    public function showRecord(Classroom $class, Student $student, CharacterRecord $record): View
    {
        // Authorization: Pastikan record milik siswa dan kelas ini
        abort_unless($record->student_id === $student->id && $record->class_id === $class->id, 403);

        $record->load(['dimension', 'recorder', 'acknowledger']);

        return view('character-portfolio.show', compact('class', 'student', 'record'));
    }

    /**
     * Store a new character record.
     */
    public function storeRecord(Request $request, Classroom $class): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where('class_id', $class->id)],
            // Disaring ke pemilik kelas: aturan exists polos menerima id dimensi
            // milik sekolah lain, dan catatan karakternya ikut tersimpan ke sana.
            'character_dimension_id' => [
                'required',
                Rule::exists('character_dimensions', 'id')->where('user_id', $class->user_id),
            ],
            'type' => ['required', Rule::in(CharacterRecord::TYPES)],
            'score' => ['required', 'integer', 'min:-10', 'max:10'],
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:1000'],
            'evidence' => ['nullable', 'string', 'max:500'],
            'context' => ['nullable', 'string', 'max:100'],
            'record_date' => ['required', 'date'],
            'notify_parent' => ['nullable', 'boolean'],
        ]);

        $data['class_id'] = $class->id;
        $data['recorded_by'] = auth()->id();

        CharacterRecord::create($data + ['user_id' => auth()->id()]);

        // Update student badges progress
        $this->updateStudentBadgesProgress($data['student_id'], $data['character_dimension_id']);

        return back()->with('success', 'Catatan karakter berhasil disimpan.');
    }

    /**
     * Update a character record.
     */
    public function updateRecord(Request $request, Classroom $class, CharacterRecord $record): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(CharacterRecord::TYPES)],
            'score' => ['required', 'integer', 'min:-10', 'max:10'],
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:1000'],
            'evidence' => ['nullable', 'string', 'max:500'],
            'context' => ['nullable', 'string', 'max:100'],
            'notify_parent' => ['nullable', 'boolean'],
        ]);

        $record->update($data);

        // Update student badges progress
        $this->updateStudentBadgesProgress($record->student_id, $record->character_dimension_id);

        return back()->with('success', 'Catatan karakter berhasil diperbarui.');
    }

    /**
     * Delete a character record.
     */
    public function destroyRecord(Classroom $class, CharacterRecord $record): RedirectResponse
    {
        $studentId = $record->student_id;
        $dimensionId = $record->character_dimension_id;

        $record->delete();

        // Update student badges progress
        $this->updateStudentBadgesProgress($studentId, $dimensionId);

        return back()->with('success', 'Catatan karakter berhasil dihapus.');
    }

    /**
     * Acknowledge a record.
     */
    public function acknowledgeRecord(Classroom $class, CharacterRecord $record): RedirectResponse
    {
        $record->update([
            'is_acknowledged' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => auth()->id(),
        ]);

        return back()->with('success', 'Catatan telah dikonfirmasi.');
    }

    /**
     * Store a student reflection.
     */
    public function storeReflection(Request $request, Classroom $class): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where('class_id', $class->id)],
            'character_dimension_id' => [
                'nullable',
                Rule::exists('character_dimensions', 'id')->where('user_id', $class->user_id),
            ],
            'period' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'reflection_date' => ['required', 'date'],
            'self_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'what_went_well' => ['nullable', 'string', 'max:1000'],
            'what_to_improve' => ['nullable', 'string', 'max:1000'],
            'action_plan' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['class_id'] = $class->id;
        $data['status'] = CharacterReflection::STATUS_SUBMITTED;
        $data['submitted_at'] = now();

        CharacterReflection::create($data + ['user_id' => auth()->id()]);

        return back()->with('success', 'Refleksi berhasil disimpan.');
    }

    /**
     * Add teacher feedback to a reflection.
     */
    public function storeFeedback(Request $request, Classroom $class, CharacterReflection $reflection): RedirectResponse
    {
        $data = $request->validate([
            'teacher_feedback' => ['nullable', 'string', 'max:2000'],
            'teacher_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $reflection->update([
            'teacher_feedback' => $data['teacher_feedback'],
            'teacher_rating' => $data['teacher_rating'],
            'feedback_by' => auth()->id(),
            'feedback_at' => now(),
            'status' => CharacterReflection::STATUS_REVIEWED,
        ]);

        return back()->with('success', 'Feedback berhasil disimpan.');
    }

    /**
     * Get all records for a student in date range (API).
     */
    public function getStudentRecords(Request $request, Student $student): \Illuminate\Http\JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());
        $dimensionId = $request->input('dimension_id');

        $query = CharacterRecord::where('student_id', $student->id)
            ->whereBetween('record_date', [$startDate, $endDate])
            ->with('dimension');

        if ($dimensionId) {
            $query->where('character_dimension_id', $dimensionId);
        }

        $records = $query->get();

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }

    /**
     * Get dimension summary for a student.
     */
    public function getDimensionSummary(Student $student): \Illuminate\Http\JsonResponse
    {
        $dimensions = CharacterDimension::forOwner($student->user_id)->active()->get();
        $semester = $this->getSemesterDateRange();
        $startDate = $semester['start'];
        $endDate = $semester['end'];

        $summary = [];
        foreach ($dimensions as $dimension) {
            $score = CharacterRecord::where('student_id', $student->id)
                ->where('character_dimension_id', $dimension->id)
                ->whereBetween('record_date', [$startDate, $endDate])
                ->sum('score');

            $summary[] = [
                'dimension' => [
                    'id' => $dimension->id,
                    'name' => $dimension->name,
                    'code' => $dimension->code,
                    'color' => $dimension->color,
                    'icon' => $dimension->icon,
                ],
                'score' => $score,
                'record_count' => CharacterRecord::where('student_id', $student->id)
                    ->where('character_dimension_id', $dimension->id)
                    ->whereBetween('record_date', [$startDate, $endDate])
                    ->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Update student badges progress.
     */
    private function updateStudentBadgesProgress(int $studentId, int $dimensionId): void
    {
        $badges = CharacterBadge::where('character_dimension_id', $dimensionId)
            ->where('is_active', true)
            ->get();

        foreach ($badges as $badge) {
            $studentBadge = StudentBadge::firstOrCreate([
                'student_id' => $studentId,
                'character_badge_id' => $badge->id,
            ]);

            // Calculate current progress based on criteria type
            $progress = 0;
            if ($badge->criteria_type === CharacterBadge::CRITERIA_COUNT) {
                $progress = CharacterRecord::where('student_id', $studentId)
                    ->where('character_dimension_id', $dimensionId)
                    ->where('type', 'positive')
                    ->count();
            } elseif ($badge->criteria_type === CharacterBadge::CRITERIA_SCORE) {
                $semester = $this->getSemesterDateRange();
                $progress = CharacterRecord::where('student_id', $studentId)
                    ->where('character_dimension_id', $dimensionId)
                    ->whereBetween('record_date', [$semester['start'], $semester['end']])
                    ->sum('score');
            }

            // Check if criteria is met
            $isEarned = $badge->checkCriteria($progress);

            $studentBadge->update([
                'current_progress' => $progress,
                'is_earned' => $isEarned,
                'earned_at' => $isEarned && !$studentBadge->is_earned ? now() : $studentBadge->earned_at,
            ]);
        }
    }
}
