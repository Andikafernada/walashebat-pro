<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\JsonResponse;

class ClassApiController extends Controller
{
    public function index(): JsonResponse
    {
        // TenantScope memastikan hanya kelas milik pemilik token yang tampil.
        $classes = Classroom::withCount('students')
            ->get(['id', 'name', 'academic_year', 'major']);

        return response()->json(['data' => $classes]);
    }

    public function students(Classroom $class): JsonResponse
    {
        $students = $class->students()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'nis', 'nisn', 'name', 'gender', 'discipline_points']);

        return response()->json([
            'class' => $class->only(['id', 'name', 'major']),
            'data' => $students,
        ]);
    }
}
