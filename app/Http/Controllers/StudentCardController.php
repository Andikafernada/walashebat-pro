<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StudentCardController extends Controller
{
    /**
     * Tampilkan pratinjau kartu pelajar & QR presensi di web.
     */
    public function index(Classroom $class): View
    {
        $students = $class->students()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('students.cards', [
            'classroom' => $class,
            'students' => $students,
            'user' => auth()->user(),
        ]);
    }

    /**
     * Unduh PDF Lembar Kartu Pelajar A4 Siap Cetak (Grid 8 Kartu per Halaman).
     */
    public function exportPdf(Classroom $class): Response
    {
        $students = $class->students()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('students.cards_pdf', [
            'classroom' => $class,
            'students' => $students,
            'user' => auth()->user(),
        ])
        ->setPaper('a4', 'portrait')
        ->setOption('isHtml5ParserEnabled', true)
        ->setOption('isRemoteEnabled', true);

        $filename = 'Kartu_Presensi_QR_' . str_replace(' ', '_', $class->name) . '.pdf';

        return $pdf->download($filename);
    }
}
