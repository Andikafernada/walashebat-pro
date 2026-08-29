<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassroomRequest;
use App\Models\Classroom;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Violation;
use App\Support\Contracts\WhatsAppSessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function index(): View
    {
        $jenis = in_array(request('jenis'), [Classroom::JENIS_PERWALIAN, Classroom::JENIS_AJAR], true)
            ? request('jenis')
            : null;

        $jumlahPerJenis = Classroom::selectRaw('jenis, COUNT(*) as total')
            ->groupBy('jenis')
            ->pluck('total', 'jenis');

        $classes = Classroom::withCount('students')
            ->when($jenis, fn ($q) => $q->where('jenis', $jenis))
            ->when(! $jenis, fn ($q) => $q->orderByRaw(
                'CASE WHEN jenis = ? THEN 0 ELSE 1 END',
                [Classroom::JENIS_PERWALIAN],
            ))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        if ($classes->isNotEmpty()) {
            $classIds = $classes->pluck('id');
            $today = now()->toDateString();

            // 1 Single Batch Query for Today's Sessions across all classes
            $sessions = AttendanceSession::whereIn('class_id', $classIds)
                ->whereDate('session_date', $today)
                ->with(['attendances' => fn ($q) => $q->select('id', 'attendance_session_id', 'status')])
                ->get()
                ->groupBy('class_id');

            // 1 Single Batch Query for Violations count
            $violations = Violation::whereIn('class_id', $classIds)
                ->selectRaw('class_id, COUNT(*) as total')
                ->groupBy('class_id')
                ->pluck('total', 'class_id');

            foreach ($classes as $c) {
                $sesiHariIni = $sessions->get($c->id, collect());
                $c->today_session_count = $sesiHariIni->count();

                if ($sesiHariIni->isNotEmpty()) {
                    $hadir = $sesiHariIni->flatMap->attendances
                        ->whereIn('status', ['hadir', 'terlambat'])
                        ->count();
                    $totalCapacity = $c->students_count * $sesiHariIni->count();

                    $c->recent_attendance_text = $hadir.'/'.$totalCapacity.' Hadir';
                    $c->recent_attendance_pct = $totalCapacity > 0
                        ? round(($hadir / $totalCapacity) * 100)
                        : 0;
                    $c->has_today_session = true;
                } else {
                    $c->recent_attendance_text = 'Belum Absen';
                    $c->recent_attendance_pct = 0;
                    $c->has_today_session = false;
                }

                $c->violation_count = $c->kelasAjar()
                    ? 0
                    : (int) ($violations[$c->id] ?? 0);
            }
        }

        return view('classes.index', [
            'classes' => $classes,
            'jenisDipilih' => $jenis,
            'jumlahPerwalian' => (int) ($jumlahPerJenis[Classroom::JENIS_PERWALIAN] ?? 0),
            'jumlahAjar' => (int) ($jumlahPerJenis[Classroom::JENIS_AJAR] ?? 0),
        ]);
    }

    public function create(WhatsAppSessionManager $manager): View
    {
        return view('classes.create', [
            'grupLabels' => $this->labelGrup($manager),
        ]);
    }

    private function labelGrup(WhatsAppSessionManager $manager): array
    {
        $user = auth()->user();

        return $user && $user->whatsappConnected() ? $manager->groupLabels($user) : [];
    }

    public function store(ClassroomRequest $request): RedirectResponse
    {
        $classroom = Classroom::create($request->validated());

        return $this->serahkanKeWhatsApp($request, $classroom, 'Kelas berhasil dibuat.')
            ?? redirect()->route('classes.show', $classroom)
                ->with('success', 'Kelas berhasil dibuat.');
    }

    private function serahkanKeWhatsApp(
        ClassroomRequest $request,
        Classroom $class,
        string $sukses
    ): ?RedirectResponse {
        if (! $class->auto_attendance || $request->user()->whatsappConnected()) {
            return null;
        }

        return redirect()->route('whatsapp.index')
            ->with('success', $sukses)
            ->with('warning', "Absensi otomatis {$class->name} baru berjalan setelah WhatsApp Anda tersambung. "
                .'Sambungkan sekarang — cukup sekali, dan berlaku untuk semua kelas.');
    }

    public function show(Classroom $class): View
    {
        $class->load(['students' => fn ($q) => $q->orderBy('name')]);

        $totalStudents = $class->students->count();
        $activeClasses = Classroom::where('is_active', true)->count();
        $today = now()->toDateString();

        $todaySession = AttendanceSession::where('class_id', $class->id)
            ->whereDate('session_date', $today)
            ->with('attendances')
            ->first();

        $todayAttendance = null;
        if ($todaySession) {
            $attendances = $todaySession->attendances;
            $hadirCount = $attendances->whereIn('status', ['hadir', 'terlambat'])->count();
            $todayAttendance = [
                'total' => $attendances->count(),
                'hadir' => $hadirCount,
                'alfa' => $attendances->where('status', 'alfa')->count(),
                'percentage' => $totalStudents > 0 ? round(($hadirCount / $totalStudents) * 100) : 0,
            ];
        }

        return view('classes.show', [
            'classroom' => $class,
            'totalStudents' => $totalStudents,
            'activeClasses' => $activeClasses,
            'todaySession' => $todaySession,
            'todayAttendance' => $todayAttendance,
        ]);
    }

    public function edit(Classroom $class, WhatsAppSessionManager $manager): View
    {
        return view('classes.edit', [
            'classroom' => $class,
            'grupLabels' => $this->labelGrup($manager),
        ]);
    }

    public function update(ClassroomRequest $request, Classroom $class): RedirectResponse
    {
        $class->update($request->validated());

        return $this->serahkanKeWhatsApp($request, $class, 'Kelas berhasil diperbarui.')
            ?? redirect()->route('classes.show', $class)
                ->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Classroom $class): RedirectResponse
    {
        $class->delete();

        return redirect()->route('classes.index')
            ->with('success', 'Kelas berhasil dihapus.');
    }
}
