<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\StudentExcuse;
use App\Support\PeriodeLaporan;
use Illuminate\Http\Request;

class StudentExcuseController extends Controller
{
    public function index(Request $request, Classroom $class)
    {
        $periode = PeriodeLaporan::resolve($request);

        // Query kabar izin/sakit dari orang tua untuk kelas ini
        $query = StudentExcuse::where('class_id', $class->id)
            ->whereBetween('tanggal', [$periode['awal']->format('Y-m-d'), $periode['akhir']->format('Y-m-d')])
            ->with('student')
            ->latest('tanggal')
            ->latest('created_at');

        // Filter Jenis
        if ($request->filled('jenis') && in_array($request->jenis, ['sakit', 'izin'])) {
            $query->where('jenis', $request->jenis);
        }

        $excuses = $query->paginate(20)->withQueryString();

        // Statistik ringkas bulan ini
        $stats = [
            'total' => StudentExcuse::where('class_id', $class->id)->whereBetween('tanggal', [$periode['awal']->format('Y-m-d'), $periode['akhir']->format('Y-m-d')])->count(),
            'sakit' => StudentExcuse::where('class_id', $class->id)->whereBetween('tanggal', [$periode['awal']->format('Y-m-d'), $periode['akhir']->format('Y-m-d')])->where('jenis', 'sakit')->count(),
            'izin' => StudentExcuse::where('class_id', $class->id)->whereBetween('tanggal', [$periode['awal']->format('Y-m-d'), $periode['akhir']->format('Y-m-d')])->where('jenis', 'izin')->count(),
        ];

        return view('excuses.index', [
            'classroom' => $class,
            'excuses' => $excuses,
            'stats' => $stats,
            'periode' => $periode,
            'activeJenis' => $request->jenis,
        ]);
    }
}
