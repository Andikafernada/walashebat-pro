<?php

namespace App\Exports;

use App\Models\CharacterRecord;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CharacterPortfolioExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly Classroom $class,
        private readonly Student $student,
        private readonly Collection $records
    ) {}

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Dimensi',
            'Tipe',
            'Judul',
            'Deskripsi',
            'Konteks',
            'Skor',
        ];
    }

    public function map($record): array
    {
        return [
            $record['no'],
            $record['date']->format('d/m/Y'),
            $record['dimension_name'],
            $this->typeLabel($record['type']),
            $record['title'],
            $record['description'] ?? '-',
            $record['context'] ? ucfirst(str_replace('_', ' ', $record['context'])) : '-',
            $record['score_display'],
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
            'A:A' => [
                'alignment' => ['horizontal' => 'center'],
            ],
            'D:D' => [
                'alignment' => ['horizontal' => 'center'],
            ],
            'H:H' => [
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    private function typeLabel(string $type): string
    {
        return match($type) {
            'positive' => 'Positif',
            'negative' => 'Negatif',
            'observation' => 'Observasi',
            'achievement' => 'Prestasi',
            default => ucfirst($type),
        };
    }
}
