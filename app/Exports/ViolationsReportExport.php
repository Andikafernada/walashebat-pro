<?php

namespace App\Exports;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\Violation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ViolationsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly Classroom $class,
        private readonly array $violations,
        private readonly array $dateRange
    ) {}

    public function collection(): Collection
    {
        return collect($this->violations);
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Siswa',
            'NIS',
            'Jenis Pelanggaran',
            'Poin',
            'Tanggal',
            'Keterangan',
            'Total Poin Siswa',
        ];
    }

    public function map($row): array
    {
        return [
            $row['no'],
            $row['student_name'],
            $row['nis'],
            $row['violation_type'],
            $row['points'],
            $row['date']->format('d/m/Y'),
            $row['note'] ?? '-',
            $row['total_points'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E5E7EB']],
                'alignment' => ['horizontal' => 'center'],
            ],
            'A:H' => [
                'alignment' => ['vertical' => 'center'],
            ],
            'E:H' => [
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
