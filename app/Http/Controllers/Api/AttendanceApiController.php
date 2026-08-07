<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Endpoint yang dikonsumsi sistem CBT / ExamBrowser untuk memvalidasi
 * kehadiran siswa sebelum mengizinkan mereka masuk ujian.
 */
class AttendanceApiController extends Controller
{
    /** Rekap kehadiran hari ini untuk sebuah kelas: student_id => status. */
    public function today(Classroom $class): JsonResponse
    {
        $map = Attendance::query()
            ->whereHas('session', fn ($q) => $q->where('class_id', $class->id)
                ->whereDate('session_date', today()))
            ->get(['student_id', 'status'])
            ->mapWithKeys(fn ($a) => [$a->student_id => $a->status]);

        return response()->json([
            'class_id' => $class->id,
            'date' => today()->toDateString(),
            'attendance' => $map,
        ]);
    }

    /**
     * Verifikasi seorang siswa berdasarkan NIS untuk gate ExamBrowser.
     * Mengembalikan apakah siswa hadir hari ini (eligible ujian).
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate(['nis' => ['required', 'string']]);

        /*
         * Penyaring user_id ditulis EKSPLISIT, tidak menyandarkan diri pada
         * global scope semata.
         *
         * TenantScope memang sudah menyaring query ini selama ada user login,
         * dan itu sudah diuji. Tapi keamanan lintas sekolah tidak boleh
         * bergantung pada satu lapis yang tak terlihat di tempat ini: bila
         * suatu saat endpoint ini dipanggil dari konteks tanpa auth (job,
         * command, atau token mesin), scope-nya diam-diam berhenti menyaring
         * dan NIS dari sekolah mana pun bisa ditelusuri.
         */
        $student = Student::where('nis', $request->nis)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $student) {
            return response()->json(['found' => false], 404);
        }

        $todayStatus = Attendance::query()
            ->where('student_id', $student->id)
            ->whereHas('session', fn ($q) => $q->whereDate('session_date', today()))
            ->latest('id')->value('status');

        return response()->json([
            'found' => true,
            'student' => $student->only(['id', 'nis', 'name']),
            'class_id' => $student->class_id,
            'status_today' => $todayStatus,          // hadir|sakit|izin|alfa|null
            'exam_eligible' => $todayStatus === 'hadir',
        ]);
    }
}
