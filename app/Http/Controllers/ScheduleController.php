<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Classroom $class): View
    {
        $schedules = $class->schedules()
            ->orderBy('day_of_week')->orderBy('start_time')->get()
            ->groupBy('day_of_week');

        return view('schedules.index', ['classroom' => $class, 'schedules' => $schedules]);
    }

    public function store(Request $request, Classroom $class): RedirectResponse
    {
        $data = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'subject' => ['required', 'string', 'max:191'],
            'teacher_name' => ['nullable', 'string', 'max:191'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($request) {
                    $start = $request->input('start_time');
                    if ($start && $value) {
                        $startMinutes = (int) substr($start, 0, 2) * 60 + (int) substr($start, 3, 2);
                        $endMinutes = (int) substr($value, 0, 2) * 60 + (int) substr($value, 3, 2);

                        // End time must be after start time
                        if ($endMinutes <= $startMinutes) {
                            $fail('Waktu selesai harus setelah waktu mulai.');
                        }

                        // Reasonable duration check (max 6 hours per session)
                        if (($endMinutes - $startMinutes) > 360) {
                            $fail('Durasi pelajaran maksimal 6 jam.');
                        }
                    }
                },
            ],
        ]);

        $class->schedules()->create($data + ['user_id' => $class->user_id]);

        return back()->with('success', 'Jadwal ditambahkan.');
    }

    public function destroy(Classroom $class, Schedule $schedule): RedirectResponse
    {
        abort_unless($schedule->class_id === $class->id, 403);
        $schedule->delete();

        return back()->with('success', 'Jadwal dihapus.');
    }
}
