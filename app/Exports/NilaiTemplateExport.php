<?php

namespace App\Exports;

use App\Models\Assessment;
use App\Models\Classroom;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NilaiTemplateExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Classroom $classroom,
        protected Assessment $assessment
    ) {}

    public function collection()
    {
        $existingScores = $this->assessment->scores()->pluck('nilai', 'student_id');

        return $this->classroom->students()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($student, $index) use ($existingScores) {
                return [
                    'no' => $index + 1,
                    'student_id' => $student->id,
                    'nis' => $student->nis ?: '-',
                    'nama' => $student->name,
                    'jenis_kelamin' => $student->gender == 'L' ? 'L' : 'P',
                    'nilai' => $existingScores->get($student->id, ''),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'No',
            'ID Siswa (Jangan Diubah)',
            'NIS',
            'Nama Lengkap Siswa',
            'L/P',
            'Nilai (0-100)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'], // Emerald 600
                ],
            ],
        ];
    }
}
