<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\CharacterRecord;
use App\Models\CharacterReflection;
use App\Models\CharacterDimension;
use App\Models\CharacterBadge;
use App\Models\StudentBadge;
use App\Models\Violation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Collection;

class DashboardController extends Controller
{
    /**
     * Student dashboard.
     */
    public function index()
    {
        $student = Auth::guard('student')->user();
        $class = $student->classroom;

        // Recent records for dashboard
        $records = CharacterRecord::where('student_id', $student->id)
            ->with('dimension')
            ->latest('record_date')
            ->take(5)
            ->get();

        // Stats
        $attendanceStats = $this->getAttendanceStats($student);
        $violationStats = $this->getViolationStats($student);
        $portfolioStats = $this->getPortfolioStats($student);

        return view('student.dashboard', [
            'student' => $student,
            'class' => $class,
            'records' => $records,
            'attendanceStats' => $attendanceStats,
            'violationStats' => $violationStats,
            'portfolioStats' => $portfolioStats,
        ]);
    }

    private function getAttendanceStats($student): array
    {
        $attendances = $student->attendances()->with('session')->get();
        $total = $attendances->count();
        $present = $attendances->where('status', 'hadir')->count();
        $sick = $attendances->where('status', 'sakit')->count();
        $permission = $attendances->where('status', 'izin')->count();
        $alpha = $attendances->where('status', 'alfa')->count();

        return [
            'total' => $total,
            'present' => $present,
            'sick' => $sick,
            'permission' => $permission,
            'alpha' => $alpha,
            'rate' => $total > 0 ? round($present / $total * 100) : 100,
        ];
    }

    private function getViolationStats($student): array
    {
        $violations = $student->violations;
        $total = $violations->count();
        $totalPoints = $violations->sum('points');

        return [
            'total' => $total,
            'points' => $totalPoints,
        ];
    }

    private function getPortfolioStats($student): array
    {
        $records = $student->characterRecords;
        $reflections = $student->characterReflections;
        $badges = $student->studentBadges;
        $earnedBadges = $badges->where('is_earned', true)->count();

        return [
            'records' => $records->count(),
            'reflections' => $reflections->count(),
            'badges' => $earnedBadges,
            'total_badges' => $badges->count(),
        ];
    }
}
