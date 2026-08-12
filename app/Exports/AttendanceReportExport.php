<?php

namespace App\Exports;

use App\Models\AttendanceSession;
use App\Models\Classroom;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly Classroom $class,
        private readonly array $dateRange,
        private readonly array $attendanceData
    ) {}

    public function collection(): Collection
    {
        return collect($this->attendanceData);
    }

    public function headings(): array
    {
        $dates = [];
        foreach ($this->dateRange as $date) {
            $dates[] = $date->format('d/m');
        }

        return array_merge(
            ['No', 'Nama Siswa', 'NIS'],
            $dates,
            ['H', 'T', 'S', 'I', 'A', 'Total']
        );
    }

    public function map($row): array
    {
        $attendance = [];
        foreach ($this->dateRange as $date) {
            $dateKey = $date->format('Y-m-d');
            $status = $row['attendance'][$dateKey] ?? '-';
            $attendance[] = $this->statusSymbol($status);
        }

        return array_merge(
            [$row['no'], $row['name'], $row['nis']],
            $attendance,
            [
                $row['hadir'] ?? 0,
                $row['terlambat'] ?? 0,
                $row['sakit'] ?? 0,
                $row['izin'] ?? 0,
                $row['alfa'] ?? 0,
                $row['total'] ?? 0,
            ]
        );
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E5E7EB']],
                'alignment' => ['horizontal' => 'center'],
            ],
            'A:Z' => [
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
            'A:A' => [
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    private function statusSymbol(?string $status): string
    {
        return match($status) {
            'hadir' => 'H',
            'sakit' => 'S',
            'izin' => 'I',
            // 'alfa', bukan 'alpha' — itu nilai enum yang sesungguhnya tersimpan.
            'alfa' => 'A',
            'terlambat' => 'T',
            default => '-',
        };
    }
}
