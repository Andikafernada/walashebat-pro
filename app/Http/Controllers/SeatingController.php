<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SeatingController extends Controller
{
    public function index(Classroom $class): View
    {
        $seats = $class->seats()->with('student:id,name')->get();
        $students = $class->students()->orderBy('name')->get(['id', 'name']);

        return view('seating.index', [
            'classroom' => $class,
            'seats' => $seats,
            'students' => $students,
        ]);
    }

    /**
     * Simpan denah (grid). Menerima array sel {row, col, student_id, label}.
     * Dipanggil via fetch dari Alpine.js pada halaman denah.
     */
    public function save(Request $request, Classroom $class): JsonResponse
    {
        $data = $request->validate([
            'seats' => ['sometimes', 'array'],
            'seats.*.row_index' => ['required_with:seats', 'integer', 'min:0', 'max:50'],
            'seats.*.col_index' => ['required_with:seats', 'integer', 'min:0', 'max:50'],
            'seats.*.student_id' => ['nullable', Rule::exists('students', 'id')->where('class_id', $class->id)],
            'seats.*.label' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            DB::transaction(function () use ($class, $data) {
                $class->seats()->delete();
                foreach ($data['seats'] as $seat) {
                    $class->seats()->create($seat + ['user_id' => $class->user_id]);
                }
            });
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Gagal menyimpan denah.'], 500);
        }

        return response()->json(['ok' => true, 'count' => count($data['seats'])]);
    }
}
