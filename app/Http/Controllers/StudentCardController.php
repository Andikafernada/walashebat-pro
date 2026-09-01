<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StudentCardController extends Controller
{
    /**
     * Tampilkan pratinjau kartu pelajar & QR presensi dengan kontrol kustomisasi.
     */
    public function index(Request $request, Classroom $class): View
    {
        $students = $class->students()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $mode = $request->input('mode', 'pelajar');
        $theme = $request->input('theme', 'emerald');
        $schoolName = $request->input('school_name', auth()->user()->school_name ?: 'WaliKelas Pro');

        return view('students.cards', [
            'classroom' => $class,
            'students' => $students,
            'user' => auth()->user(),
            'mode' => $mode,
            'theme' => $theme,
            'schoolName' => $schoolName,
        ]);
    }

    /**
     * Unduh PDF Lembar Kartu Pelajar / Kartu Ujian A4 Siap Cetak.
     */
    public function exportPdf(Request $request, Classroom $class): Response
    {
        $students = $class->students()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $mode = $request->input('mode', 'pelajar');
        $theme = $request->input('theme', 'emerald');
        $schoolName = $request->input('school_name', auth()->user()->school_name ?: 'WaliKelas Pro');

        $colorMap = [
            'emerald' => ['primary' => '#047857', 'border' => '#059669', 'accent' => '#064e3b', 'light' => '#a7f3d0'],
            'navy'    => ['primary' => '#1e3a8a', 'border' => '#2563eb', 'accent' => '#172554', 'light' => '#bfdbfe'],
            'maroon'  => ['primary' => '#991b1b', 'border' => '#dc2626', 'accent' => '#450a0a', 'light' => '#fecaca'],
            'gold'    => ['primary' => '#854d0e', 'border' => '#d97706', 'accent' => '#451a03', 'light' => '#fde68a'],
            'purple'  => ['primary' => '#6b21a8', 'border' => '#9333ea', 'accent' => '#3b0764', 'light' => '#e9d5ff'],
        ];

        $colors = $colorMap[$theme] ?? $colorMap['emerald'];

        $pdf = Pdf::loadView('students.cards_pdf', [
            'classroom' => $class,
            'students' => $students,
            'user' => auth()->user(),
            'mode' => $mode,
            'theme' => $theme,
            'colors' => $colors,
            'schoolName' => $schoolName,
        ])
        ->setPaper('a4', 'portrait')
        ->setOption('isHtml5ParserEnabled', true)
        ->setOption('isRemoteEnabled', true);

        $prefix = $mode === 'ujian' ? 'Kartu_Ujian_' : 'Kartu_Pelajar_QR_';
        $filename = $prefix . str_replace(' ', '_', $class->name) . '.pdf';

        return $pdf->download($filename);
    }
}
